<?php
/**
 * Synchronise WooCommerce customers ↔ Zoho Inventory contacts.
 *
 * @package ZohoInventorySync\Sync
 */

namespace ZohoInventorySync\Sync;

use ZohoInventorySync\Api\Zoho_Contacts_API;
use ZohoInventorySync\Includes\Logger;
use ZohoInventorySync\Includes\OAuth_Manager;
use ZohoInventorySync\Includes\Sync_Queue;

defined( 'ABSPATH' ) || exit;

/**
 * Class Customer_Sync
 */
class Customer_Sync {

	/** User meta key for the linked Zoho contact ID. */
	const META_ZOHO_ID       = '_zoho_inv_contact_id';
	const META_ZOHO_SYNCED   = '_zoho_inv_synced_at';

	/** @var Zoho_Contacts_API */
	private Zoho_Contacts_API $api;

	/** @var Sync_Queue */
	private Sync_Queue $queue;

	/** @var Logger */
	private Logger $logger;

	/** @var OAuth_Manager */
	private OAuth_Manager $oauth;

	public function __construct( OAuth_Manager $oauth, Sync_Queue $queue, Logger $logger ) {
		$this->oauth  = $oauth;
		$this->queue  = $queue;
		$this->logger = $logger;
		$this->api    = new Zoho_Contacts_API( $oauth );
	}

	// -------------------------------------------------------------------------
	// WooCommerce hook callbacks
	// -------------------------------------------------------------------------

	/**
	 * Called when WooCommerce creates a new customer.
	 *
	 * @param int $user_id WordPress user ID.
	 */
	public function on_customer_created( int $user_id ): void {
		if ( ! $this->oauth->is_connected() ) {
			return;
		}
		$this->queue->enqueue( 'customer', $user_id, 'create' );
	}

	/**
	 * Called when a customer is updated (WooCommerce or profile_update).
	 *
	 * @param int $user_id WordPress user ID.
	 */
	public function on_customer_updated( int $user_id ): void {
		if ( ! $this->oauth->is_connected() ) {
			return;
		}
		// Only sync WooCommerce customers (users with woocommerce role or orders).
		if ( ! $this->is_woocommerce_customer( $user_id ) ) {
			return;
		}
		$this->queue->enqueue( 'customer', $user_id, 'update' );
	}

	// -------------------------------------------------------------------------
	// Sync methods
	// -------------------------------------------------------------------------

	/**
	 * Sync a single customer to Zoho Inventory.
	 *
	 * @param  int $user_id WordPress user ID.
	 * @return string|false Zoho contact ID on success, false on failure.
	 */
	public function sync_customer( int $user_id ) {
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			$this->logger->error( 'customer', $user_id, 'sync', 'User not found.' );
			return false;
		}

		$data        = $this->map_to_zoho( $user );
		$zoho_id     = get_user_meta( $user_id, self::META_ZOHO_ID, true );
		$action      = 'create';

		if ( $zoho_id ) {
			$response = $this->api->update_contact( $zoho_id, $data );
			$action   = 'update';
		} else {
			// Try to find existing contact by email before creating.
			$existing = $this->api->find_by_email( $user->user_email );
			if ( $existing ) {
				$zoho_id  = $existing['contact_id'];
				$response = $this->api->update_contact( $zoho_id, $data );
				$action   = 'update';
			} else {
				$response = $this->api->create_contact( $data );
			}
		}

		if ( is_wp_error( $response ) ) {
			$this->logger->error( 'customer', $user_id, $action, $response->get_error_message() );
			return false;
		}

		$returned_id = $response['contact']['contact_id'] ?? $zoho_id ?? '';

		if ( $returned_id ) {
			update_user_meta( $user_id, self::META_ZOHO_ID, $returned_id );
			update_user_meta( $user_id, self::META_ZOHO_SYNCED, current_time( 'mysql' ) );
		}

		$this->logger->success( 'customer', $user_id, $action, "Customer synced to Zoho Inventory contact {$returned_id}", $returned_id );
		return $returned_id;
	}

	/**
	 * Batch-sync all WooCommerce customers.
	 *
	 * @param int $batch_size Number of customers per batch.
	 * @param int $offset     Starting offset.
	 */
	public function sync_all_customers( int $batch_size = 50, int $offset = 0 ): void {
		$users = get_users(
			[
				'role__in' => [ 'customer', 'subscriber' ],
				'number'   => $batch_size,
				'offset'   => $offset,
				'fields'   => 'ID',
			]
		);

		foreach ( $users as $user_id ) {
			$this->queue->enqueue( 'customer', (int) $user_id, 'sync' );
		}

		// If there may be more, schedule the next batch.
		if ( count( $users ) === $batch_size ) {
			wp_schedule_single_event(
				time() + 30,
				'zoho_inventory_sync_process_queue'
			);
		}

		$this->logger->info( 'Batch customer sync queued', [ 'count' => count( $users ), 'offset' => $offset ] );
	}

	/**
	 * Sync a guest order's billing info as a Zoho contact.
	 *
	 * Used when an order has no WordPress user ID (guest checkout). Looks up
	 * an existing Zoho contact by billing email; creates one if not found.
	 * The resulting Zoho contact ID is stored in order meta so it is reused
	 * on subsequent syncs without an extra API call.
	 *
	 * @param  \WC_Order $order
	 * @return string Zoho contact ID, or empty string on failure.
	 */
	public function sync_guest_contact( \WC_Order $order ): string {
		// Return cached value if we already resolved this order's guest contact.
		$cached = $order->get_meta( '_zoho_inv_guest_contact_id', true );
		if ( $cached ) {
			return (string) $cached;
		}

		$email = $order->get_billing_email();
		if ( ! $email ) {
			return '';
		}

		// Try to find an existing contact by email first.
		$existing = $this->api->find_by_email( $email );
		if ( $existing ) {
			$zoho_id = $existing['contact_id'];
			$order->update_meta_data( '_zoho_inv_guest_contact_id', $zoho_id );
			$order->save_meta_data();
			return $zoho_id;
		}

		// Build contact data from billing info.
		$data = [
			'contact_name' => trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() )
							  ?: $email,
			'contact_type' => 'customer',
			'email'        => $email,
			'phone'        => $order->get_billing_phone(),
			'contact_persons' => [
				[
					'first_name'         => $order->get_billing_first_name(),
					'last_name'          => $order->get_billing_last_name(),
					'email'              => $email,
					'phone'              => $order->get_billing_phone(),
					'is_primary_contact' => true,
				],
			],
			'billing_address' => [
				'attention' => trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ),
				'address'   => $order->get_billing_address_1(),
				'street2'   => $order->get_billing_address_2(),
				'city'      => $order->get_billing_city(),
				'state'     => $order->get_billing_state(),
				'zip'       => $order->get_billing_postcode(),
				'country'   => $order->get_billing_country(),
				'phone'     => $order->get_billing_phone(),
			],
		];

		$response = $this->api->create_contact( $data );

		if ( is_wp_error( $response ) ) {
			$this->logger->error( 'customer', 0, 'guest_contact', $response->get_error_message() );
			return '';
		}

		$zoho_id = $response['contact']['contact_id'] ?? '';
		if ( $zoho_id ) {
			$order->update_meta_data( '_zoho_inv_guest_contact_id', $zoho_id );
			$order->save_meta_data();
			$this->logger->success( 'customer', 0, 'guest_contact', "Guest contact created in Zoho: {$zoho_id}", $zoho_id );
		}

		return (string) $zoho_id;
	}

	/**
	 * Create a WooCommerce customer from a Zoho Inventory contact.
	 * Used for Zoho → WooCommerce direction.
	 *
	 * @param  array $contact Zoho contact array.
	 * @return int|false      New WordPress user ID or false.
	 */
	public function create_from_zoho( array $contact ) {
		$email = $contact['email'] ?? '';
		if ( empty( $email ) ) {
			return false;
		}

		// Don't create duplicate.
		if ( email_exists( $email ) ) {
			$user = get_user_by( 'email', $email );
			if ( $user ) {
				update_user_meta( $user->ID, self::META_ZOHO_ID, $contact['contact_id'] );
				return $user->ID;
			}
		}

		$name  = explode( ' ', $contact['contact_name'] ?? '', 2 );
		$first = $name[0] ?? '';
		$last  = $name[1] ?? '';

		$user_id = wc_create_new_customer(
			$email,
			wc_create_new_customer_username( $email ),
			wp_generate_password(),
			[
				'first_name' => $first,
				'last_name'  => $last,
			]
		);

		if ( is_wp_error( $user_id ) ) {
			$this->logger->error( 'customer', 0, 'zoho_to_wc', $user_id->get_error_message() );
			return false;
		}

		update_user_meta( $user_id, self::META_ZOHO_ID, $contact['contact_id'] );
		$this->logger->success( 'customer', $user_id, 'zoho_to_wc', 'Customer created from Zoho Inventory contact.', $contact['contact_id'] );
		return $user_id;
	}

	// -------------------------------------------------------------------------
	// Field mapping
	// -------------------------------------------------------------------------

	/**
	 * Map a WP_User + WooCommerce meta to a Zoho Inventory contact array.
	 *
	 * @param  \WP_User $user
	 * @return array
	 */
	private function map_to_zoho( \WP_User $user ): array {
		$customer = new \WC_Customer( $user->ID );

		$billing  = $this->map_address( $customer, 'billing' );
		$shipping = $this->map_address( $customer, 'shipping' );

		$data = [
			'contact_name'  => trim( $user->first_name . ' ' . $user->last_name ) ?: $user->display_name,
			'contact_type'  => 'customer',
			'email'         => $user->user_email,
			'contact_persons' => [
				[
					'first_name'  => $user->first_name,
					'last_name'   => $user->last_name,
					'email'       => $user->user_email,
					'phone'       => $customer->get_billing_phone(),
					'is_primary_contact' => true,
				],
			],
			'billing_address'  => $billing,
			'shipping_address' => $shipping,
		];

		$phone = $customer->get_billing_phone();
		if ( $phone ) {
			$data['phone'] = $phone;
		}

		return $data;
	}

	/**
	 * Build a Zoho-compatible address array from WC_Customer data.
	 *
	 * @param \WC_Customer $customer
	 * @param string       $type 'billing' or 'shipping'.
	 * @return array
	 */
	private function map_address( \WC_Customer $customer, string $type ): array {
		$get = fn( string $field ) => call_user_func( [ $customer, "get_{$type}_{$field}" ] );

		return [
			'attention'  => $get( 'first_name' ) . ' ' . $get( 'last_name' ),
			'address'    => $get( 'address_1' ),
			'street2'    => $get( 'address_2' ),
			'city'       => $get( 'city' ),
			'state'      => $get( 'state' ),
			'zip'        => $get( 'postcode' ),
			'country'    => $get( 'country' ),
			'phone'      => $type === 'billing' ? $get( 'phone' ) : '',
		];
	}

	/**
	 * Check whether a user is a WooCommerce customer.
	 *
	 * @param  int $user_id
	 * @return bool
	 */
	private function is_woocommerce_customer( int $user_id ): bool {
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return false;
		}

		if ( in_array( 'customer', (array) $user->roles, true ) ) {
			return true;
		}

		// Also consider users that have placed orders.
		$order_count = wc_get_customer_order_count( $user_id );
		return $order_count > 0;
	}
}
