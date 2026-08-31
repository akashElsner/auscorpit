<?php
/**
 * Synchronise WooCommerce orders to Zoho Inventory sales orders and invoices.
 *
 * @package ZohoInventorySync\Sync
 */

namespace ZohoInventorySync\Sync;

use ZohoInventorySync\Api\Zoho_Sales_Orders_API;
use ZohoInventorySync\Api\Zoho_Invoices_API;
use ZohoInventorySync\Includes\Logger;
use ZohoInventorySync\Includes\OAuth_Manager;
use ZohoInventorySync\Includes\Sync_Queue;

defined( 'ABSPATH' ) || exit;

/**
 * Class Order_Sync
 */
class Order_Sync {

	/** Order meta keys. */
	const META_ZOHO_SO_ID      = '_zoho_inv_salesorder_id';
	const META_ZOHO_INV_ID     = '_zoho_inv_invoice_id';
	const META_ZOHO_SYNCED     = '_zoho_inv_synced_at';

	/**
	 * Zoho Inventory SO statuses that should trigger invoice creation.
	 * Default trigger: delivered.
	 */
	const INVOICE_STATUSES = [ 'delivered' ];

	/** Order statuses that should trigger void/cancel in Zoho. */
	const VOID_STATUSES = [ 'cancelled' ];

	/** Order statuses to skip entirely (no sync action). */
	const SKIP_STATUSES = [ 'cancelled' ];

	/** @var Zoho_Sales_Orders_API */
	private Zoho_Sales_Orders_API $so_api;

	/** @var Zoho_Invoices_API */
	private Zoho_Invoices_API $inv_api;

	/** @var Sync_Queue */
	private Sync_Queue $queue;

	/** @var Logger */
	private Logger $logger;

	/** @var OAuth_Manager */
	private OAuth_Manager $oauth;

	public function __construct( OAuth_Manager $oauth, Sync_Queue $queue, Logger $logger ) {
		$this->oauth   = $oauth;
		$this->queue   = $queue;
		$this->logger  = $logger;
		$this->so_api  = new Zoho_Sales_Orders_API( $oauth );
		$this->inv_api = new Zoho_Invoices_API( $oauth );
	}

	// -------------------------------------------------------------------------
	// Hook callbacks
	// -------------------------------------------------------------------------

	/**
	 * Called when a new WooCommerce order is created.
	 *
	 * @param int $order_id
	 */
	public function on_order_created( int $order_id ): void {
		if ( ! $this->oauth->is_connected() ) {
			return;
		}

		$order = wc_get_order( $order_id );
		if ( $order && in_array( $order->get_status(), self::VOID_STATUSES, true ) ) {
			return;
		}

		$this->queue->enqueue( 'order', $order_id, 'create' );
	}

	/**
	 * Called by hooks that pass a WC_Order object instead of an ID.
	 *
	 * woocommerce_checkout_order_created and
	 * woocommerce_store_api_checkout_order_processed both pass a WC_Order.
	 *
	 * @param \WC_Order $order
	 */
	public function on_order_object_created( \WC_Order $order ): void {
		$this->on_order_created( $order->get_id() );
	}

	/**
	 * Called when an order status changes.
	 *
	 * @param int    $order_id   WooCommerce order ID.
	 * @param string $old_status Previous status (without "wc-" prefix).
	 * @param string $new_status New status (without "wc-" prefix).
	 */
	public function on_order_status_changed( int $order_id, string $old_status, string $new_status ): void {
		if ( ! $this->oauth->is_connected() ) {
			return;
		}

		$settings = get_option( 'zoho_inventory_sync_settings', [] );

		// Void invoice + sales order when cancelled or refunded.
		if ( in_array( $new_status, self::VOID_STATUSES, true ) ) {
			$this->queue->enqueue( 'order', $order_id, 'void' );
			return;
		}

		// Invoice creation: trigger when the configured Zoho Inventory SO status is reached.
		$trigger_status = $settings['invoice_trigger_status'] ?? 'delivered';
		if ( ! empty( $settings['auto_create_invoice'] ) && $new_status === $trigger_status ) {
			$this->queue->enqueue( 'order', $order_id, 'invoice' );
			return;
		}

		// For all other status changes, re-sync the sales order.
		$this->queue->enqueue( 'order', $order_id, 'update' );
	}

	// -------------------------------------------------------------------------
	// Sync methods
	// -------------------------------------------------------------------------

	/**
	 * Sync a WooCommerce order to a Zoho Inventory Sales Order.
	 *
	 * @param  int $order_id
	 * @return string|false  Zoho sales order ID or false.
	 */
	public function sync_order( int $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			$this->logger->error( 'order', $order_id, 'sync', 'Order not found.' );
			return false;
		}

		// Ensure customer is synced first so we have a Zoho contact ID.
		$zoho_contact_id = $this->ensure_customer_synced( $order );

		$data      = $this->map_order_to_zoho( $order, $zoho_contact_id );
		$zoho_so_id = $order->get_meta( self::META_ZOHO_SO_ID, true );
		$action     = 'create';

		$is_new = false;

		if ( $zoho_so_id ) {
			$response = $this->so_api->update_sales_order( $zoho_so_id, $data );
			$action   = 'update';
		} else {
			// Check by WC order number to avoid duplicates.
			$existing = $this->so_api->find_by_reference( (string) $order->get_order_number() );
			if ( $existing ) {
				$zoho_so_id = $existing['salesorder_id'];
				$response   = $this->so_api->update_sales_order( $zoho_so_id, $data );
				$action     = 'update';
			} else {
				$response = $this->so_api->create_sales_order( $data );
				$is_new   = true;
			}
		}

		if ( is_wp_error( $response ) ) {
			$this->logger->error( 'order', $order_id, $action, $response->get_error_message() );
			return false;
		}

		$returned_id = $response['salesorder']['salesorder_id'] ?? $zoho_so_id ?? '';

		if ( $returned_id ) {
			$order->update_meta_data( self::META_ZOHO_SO_ID, $returned_id );
			$order->update_meta_data( self::META_ZOHO_SYNCED, current_time( 'mysql' ) );
			$order->save_meta_data();

			// Confirm the draft sales order so it appears as confirmed in Zoho Inventory.
			if ( $is_new ) {
				$confirm = $this->so_api->confirm_sales_order( $returned_id );
				if ( is_wp_error( $confirm ) ) {
					$this->logger->error( 'order', $order_id, 'confirm', $confirm->get_error_message() );
				}
			}
		}

		$this->logger->success( 'order', $order_id, $action, "Order synced to Zoho Inventory SO {$returned_id}", $returned_id );
		return $returned_id;
	}

	/**
	 * Create a Zoho Inventory invoice for a WooCommerce order.
	 *
	 * Syncs the sales order first if it hasn't been synced yet.
	 *
	 * @param  int $order_id
	 * @return string|false  Zoho invoice ID or false.
	 */
	public function create_invoice( int $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return false;
		}

		// Don't create duplicate invoice.
		$existing_invoice = $order->get_meta( self::META_ZOHO_INV_ID, true );
		if ( $existing_invoice ) {
			return $existing_invoice;
		}

		// Make sure we have a confirmed sales order.
		$zoho_so_id = $order->get_meta( self::META_ZOHO_SO_ID, true );
		if ( ! $zoho_so_id ) {
			$zoho_so_id = $this->sync_order( $order_id );
			if ( ! $zoho_so_id ) {
				return false;
			}
		} else {
			// Ensure the existing SO is confirmed — it may have been created as
			// draft before the auto-confirm logic was in place.
			$this->so_api->confirm_sales_order( $zoho_so_id );
		}

		// Build the invoice payload from WC order data and link it to the SO.
		// salesorder_ids (array) links the invoice; we also send line_items explicitly.
		$zoho_contact_id        = $this->ensure_customer_synced( $order );
		$data                   = $this->map_order_to_zoho( $order, $zoho_contact_id );
		$data['salesorder_ids'] = [ $zoho_so_id ];

		$response = $this->inv_api->create_invoice( $data );

		if ( is_wp_error( $response ) ) {
			$this->logger->error( 'order', $order_id, 'invoice', $response->get_error_message() );
			return false;
		}

		$invoice_id = $response['invoice']['invoice_id'] ?? '';

		if ( $invoice_id ) {
			$order->update_meta_data( self::META_ZOHO_INV_ID, $invoice_id );
			$order->save_meta_data();

			// Confirm the invoice (Zoho creates invoices as draft by default).
			$sent = $this->inv_api->mark_as_sent( $invoice_id );
			if ( is_wp_error( $sent ) ) {
				$this->logger->error( 'order', $order_id, 'invoice_confirm', $sent->get_error_message() );
			}
			// Note: Zoho Inventory automatically updates the SO status to "invoiced"
			// when an invoice is created with salesorder_ids linking to that SO.
		}

		$this->logger->success( 'order', $order_id, 'invoice', "Invoice created in Zoho Inventory: {$invoice_id}", $invoice_id );
		return $invoice_id;
	}

	/**
	 * Void the Zoho Inventory invoice (and sales order) for a cancelled/refunded WC order.
	 *
	 * @param  int $order_id
	 * @return bool
	 */
	public function void_order( int $order_id ): bool {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return false;
		}

		// Void the invoice if one exists.
		$invoice_id = $order->get_meta( self::META_ZOHO_INV_ID, true );
		if ( $invoice_id ) {
			$result = $this->inv_api->void_invoice( $invoice_id );
			if ( is_wp_error( $result ) ) {
				$this->logger->error( 'order', $order_id, 'void_invoice', $result->get_error_message() );
			} else {
				$this->logger->success( 'order', $order_id, 'void_invoice', "Invoice {$invoice_id} voided in Zoho Inventory.", $invoice_id );
			}
		}

		// Void the sales order if one exists.
		$so_id = $order->get_meta( self::META_ZOHO_SO_ID, true );
		if ( $so_id ) {
			$result = $this->so_api->void_sales_order( $so_id );
			if ( is_wp_error( $result ) ) {
				$this->logger->error( 'order', $order_id, 'void_so', $result->get_error_message() );
			} else {
				$this->logger->success( 'order', $order_id, 'void_so', "Sales order {$so_id} voided in Zoho Inventory.", $so_id );
			}
		}

		return true;
	}

	/**
	 * Queue all WooCommerce orders for sync.
	 *
	 * @param int $batch_size
	 * @param int $offset
	 */
	public function sync_all_orders( int $batch_size = 50, int $offset = 0 ): void {
		$orders = wc_get_orders(
			[
				'limit'  => $batch_size,
				'offset' => $offset,
				'return' => 'ids',
				'status' => [ 'processing', 'completed', 'on-hold', 'pending' ],
			]
		);

		foreach ( $orders as $order_id ) {
			$this->queue->enqueue( 'order', (int) $order_id, 'sync' );
		}

		if ( count( $orders ) === $batch_size ) {
			wp_schedule_single_event( time() + 30, 'zoho_inventory_sync_process_queue' );
		}

		$this->logger->info( 'Batch order sync queued', [ 'count' => count( $orders ) ] );
	}

	// -------------------------------------------------------------------------
	// Field mapping
	// -------------------------------------------------------------------------

	/**
	 * Map a WC_Order to a Zoho Inventory sales order payload.
	 *
	 * @param  \WC_Order $order
	 * @param  string    $zoho_contact_id
	 * @return array
	 */
	private function map_order_to_zoho( \WC_Order $order, string $zoho_contact_id ): array {
		$line_items = [];

		foreach ( $order->get_items() as $item ) {
			/** @var \WC_Order_Item_Product $item */
			$product      = $item->get_product();
			$zoho_item_id = $product ? $product->get_meta( Product_Sync::META_ZOHO_ID, true ) : '';

			$line = [
				'name'        => $item->get_name(),
				'quantity'    => $item->get_quantity(),
				'rate'        => (float) $order->get_item_subtotal( $item, false, false ),
				'description' => '',
			];

			if ( $zoho_item_id ) {
				$line['item_id'] = $zoho_item_id;
			}

			// Tax.
			$tax_total = (float) $item->get_total_tax();
			if ( $tax_total > 0 ) {
				$line['tax_percentage'] = round( ( $tax_total / (float) $item->get_subtotal() ) * 100, 2 );
			}

			// Discount.
			$discount = (float) $item->get_subtotal() - (float) $item->get_total();
			if ( $discount > 0 ) {
				$line['discount'] = round( ( $discount / (float) $item->get_subtotal() ) * 100, 2 );
			}

			$line_items[] = $line;
		}

		// Shipping as a line item.
		foreach ( $order->get_items( 'shipping' ) as $shipping_item ) {
			/** @var \WC_Order_Item_Shipping $shipping_item */
			if ( (float) $shipping_item->get_total() > 0 ) {
				$line_items[] = [
					'name'        => $shipping_item->get_method_title() ?: __( 'Shipping', 'zoho-inventory-sync' ),
					'description' => __( 'Shipping charge', 'zoho-inventory-sync' ),
					'rate'        => (float) $shipping_item->get_total(),
					'quantity'    => 1,
				];
			}
		}

		$data = [
			'customer_id'      => $zoho_contact_id,
			'reference_number' => (string) $order->get_order_number(),
			'date'             => $order->get_date_created() ? $order->get_date_created()->date( 'Y-m-d' ) : current_time( 'Y-m-d' ),
			'line_items'       => $line_items,
			'notes'            => $order->get_customer_note(),
		];

		// Zoho Inventory Sales Orders API inherits billing/shipping addresses from
		// the linked customer contact. Sending delivery_address as a nested object
		// triggers "delivery_address has less than 100 characters" because the API
		// treats it as a flat string. Omit it entirely — Zoho pulls it from the contact.

		// Order-level discount.
		$discount_total = (float) $order->get_discount_total();
		if ( $discount_total > 0 ) {
			$data['discount']       = $discount_total;
			$data['is_discount_before_tax'] = true;
		}

		return $data;
	}

	/**
	 * Build a Zoho address array from a WC_Order address.
	 *
	 * @param  \WC_Order $order
	 * @param  string    $type 'billing' or 'shipping'.
	 * @return array
	 */
	private function map_order_address( \WC_Order $order, string $type ): array {
		$get = fn( string $field ) => call_user_func( [ $order, "get_{$type}_{$field}" ] );

		return [
			'attention' => $get( 'first_name' ) . ' ' . $get( 'last_name' ),
			'address'   => $get( 'address_1' ),
			'street2'   => $get( 'address_2' ),
			'city'      => $get( 'city' ),
			'state'     => $get( 'state' ),
			'zip'       => $get( 'postcode' ),
			'country'   => $get( 'country' ),
		];
	}

	/**
	 * Ensure the order's customer is synced to Zoho and return the Zoho contact ID.
	 *
	 * @param  \WC_Order $order
	 * @return string  Zoho contact ID (may be empty string if guest order).
	 */
	private function ensure_customer_synced( \WC_Order $order ): string {
		$user_id = $order->get_customer_id();
		$plugin  = \ZohoInventorySync\Includes\Plugin::instance();

		if ( ! $user_id ) {
			// Guest order — find or create a Zoho contact from billing info.
			return $plugin->customer_sync->sync_guest_contact( $order );
		}

		$zoho_contact_id = get_user_meta( $user_id, Customer_Sync::META_ZOHO_ID, true );
		if ( ! $zoho_contact_id ) {
			$zoho_contact_id = (string) $plugin->customer_sync->sync_customer( $user_id );
		}

		return (string) $zoho_contact_id;
	}
}
