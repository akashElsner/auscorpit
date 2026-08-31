<?php
/**
 * Handles incoming Zoho Inventory webhooks via the WordPress REST API.
 *
 * Endpoint: POST /wp-json/zoho-inventory-sync/v1/webhook
 *
 * Zoho sends a JSON payload. We verify the request using an HMAC-SHA256
 * signature passed in the X-Zoho-Webhook-Token header (a shared secret the
 * admin configures once in both Zoho and the plugin settings).
 *
 * @package ZohoInventorySync\Webhooks
 */

namespace ZohoInventorySync\Webhooks;

use ZohoInventorySync\Includes\Logger;
use ZohoInventorySync\Sync\Customer_Sync;
use ZohoInventorySync\Sync\Inventory_Sync;
use ZohoInventorySync\Sync\Product_Sync;

defined( 'ABSPATH' ) || exit;

/**
 * Class Webhook_Handler
 */
class Webhook_Handler {

	/** REST namespace and route. */
	const NAMESPACE = 'zoho-inventory-sync/v1';
	const ROUTE     = '/webhook';

	/** Option key for the webhook secret. */
	const OPT_SECRET = 'zoho_inventory_sync_webhook_secret';

	/** @var Customer_Sync */
	private Customer_Sync $customer_sync;

	/** @var Product_Sync */
	private Product_Sync $product_sync;

	/** @var Inventory_Sync */
	private Inventory_Sync $inventory_sync;

	/** @var Logger */
	private Logger $logger;

	public function __construct(
		Customer_Sync $customer_sync,
		Product_Sync $product_sync,
		Inventory_Sync $inventory_sync,
		Logger $logger
	) {
		$this->customer_sync  = $customer_sync;
		$this->product_sync   = $product_sync;
		$this->inventory_sync = $inventory_sync;
		$this->logger         = $logger;
	}

	/**
	 * Register the REST API route.
	 */
	public function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			self::ROUTE,
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'handle_webhook' ],
				'permission_callback' => [ $this, 'verify_webhook' ],
			]
		);
	}

	/**
	 * Verify the incoming webhook request.
	 *
	 * Accepts the secret token from either:
	 *  - The X-Zoho-Webhook-Token request header (preferred), or
	 *  - A ?token= query parameter in the webhook URL (useful for Zoho
	 *    Inventory workflow webhooks that don't support custom headers).
	 *
	 * @param  \WP_REST_Request $request
	 * @return bool|\WP_Error
	 */
	public function verify_webhook( \WP_REST_Request $request ) {
		$secret = get_option( self::OPT_SECRET, '' );

		if ( empty( $secret ) ) {
			// If no secret configured, allow but log a warning.
			$this->logger->warning( 'Zoho Inventory webhook received but no secret configured — request accepted without verification.' );
			return true;
		}

		// Accept token from header OR ?token= query param.
		$token = $request->get_header( 'x-zoho-webhook-token' )
				 ?: $request->get_param( 'token' );

		if ( ! hash_equals( $secret, (string) $token ) ) {
			$this->logger->warning( 'Zoho Inventory webhook rejected: invalid token.' );
			return new \WP_Error(
				'invalid_webhook_token',
				__( 'Invalid webhook token.', 'zoho-inventory-sync' ),
				[ 'status' => 401 ]
			);
		}

		return true;
	}

	/**
	 * Route the webhook payload to the correct sync handler.
	 *
	 * @param  \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function handle_webhook( \WP_REST_Request $request ): \WP_REST_Response {
		$payload = $request->get_json_params();

		if ( empty( $payload ) ) {
			return new \WP_REST_Response( [ 'message' => 'Empty payload.' ], 400 );
		}

		$event_type = sanitize_text_field( $payload['event_type'] ?? '' );

		// Zoho Inventory "default payload" does not include event_type — detect
		// the entity type from whichever ID field is present in the payload, and
		// the action from the "action" field (create/edit/delete) if available.
		if ( '' === $event_type ) {
			$event_type = $this->detect_event_type( $payload );
		}

		$this->logger->info( 'Zoho Inventory webhook received', [ 'event_type' => $event_type ] );

		try {
			match ( true ) {
				str_starts_with( $event_type, 'contact.'    ) => $this->handle_contact_event( $event_type, $payload ),
				str_starts_with( $event_type, 'item.'       ) => $this->handle_item_event( $event_type, $payload ),
				str_starts_with( $event_type, 'invoice.'    ) => $this->handle_invoice_event( $event_type, $payload ),
				str_starts_with( $event_type, 'salesorder.' ) => $this->handle_salesorder_event( $event_type, $payload ),
				default                                        => $this->logger->warning( "Unhandled webhook event: {$event_type}. Keys received: " . implode( ', ', array_keys( $payload ) ) ),
			};
		} catch ( \Throwable $e ) {
			$this->logger->error( 'webhook', 0, $event_type, $e->getMessage() );
			return new \WP_REST_Response( [ 'message' => 'Error processing webhook.' ], 500 );
		}

		return new \WP_REST_Response( [ 'message' => 'Webhook processed.' ], 200 );
	}

	// -------------------------------------------------------------------------
	// Event type detection
	// -------------------------------------------------------------------------

	/**
	 * Detect a normalised event_type string from a Zoho Inventory "default
	 * payload" that carries no explicit event_type field.
	 *
	 * Zoho Inventory default payloads include the entity data directly. An
	 * optional "action" field ("create" | "edit" | "delete") indicates what
	 * happened; when absent we fall back to "updated".
	 *
	 * @param  array $payload Raw webhook body.
	 * @return string  e.g. "item.created", "contact.updated"
	 */
	private function detect_event_type( array $payload ): string {
		// Normalise Zoho's action field to a suffix.
		$raw_action = strtolower( $payload['action'] ?? $payload['event'] ?? '' );
		$suffix     = match ( $raw_action ) {
			'create', 'created' => 'created',
			'delete', 'deleted' => 'deleted',
			default             => 'updated',
		};

		// Detect entity from whichever primary ID key is present.
		if ( isset( $payload['item_id'] ) || isset( $payload['item']['item_id'] ) ) {
			return "item.{$suffix}";
		}
		if ( isset( $payload['contact_id'] ) || isset( $payload['contact']['contact_id'] ) ) {
			return "contact.{$suffix}";
		}
		if ( isset( $payload['salesorder_id'] ) || isset( $payload['salesorder']['salesorder_id'] ) ) {
			return "salesorder.{$suffix}";
		}
		if ( isset( $payload['invoice_id'] ) || isset( $payload['invoice']['invoice_id'] ) ) {
			return "invoice.{$suffix}";
		}

		return '';
	}

	// -------------------------------------------------------------------------
	// Event handlers
	// -------------------------------------------------------------------------

	/**
	 * Handle contact.created / contact.updated events.
	 *
	 * @param string $event_type
	 * @param array  $payload
	 */
	private function handle_contact_event( string $event_type, array $payload ): void {
		$contact = $payload['contact'] ?? $payload['data'] ?? [];
		if ( empty( $contact ) && ! empty( $payload['contact_id'] ) ) {
			$contact = $payload;
		}
		if ( empty( $contact ) ) {
			return;
		}

		$contact_id = $contact['contact_id'] ?? '';
		if ( ! $contact_id ) {
			return;
		}

		if ( $event_type === 'contact.created' ) {
			$this->customer_sync->create_from_zoho( $contact );
		} elseif ( $event_type === 'contact.updated' ) {
			// Find linked WC user and re-queue for sync.
			$users = get_users(
				[
					'meta_key'   => Customer_Sync::META_ZOHO_ID,
					'meta_value' => $contact_id,
					'number'     => 1,
					'fields'     => 'ID',
				]
			);

			if ( ! empty( $users ) ) {
				// Update already-linked customer — push Zoho data to WC user meta.
				$this->apply_contact_to_user( (int) $users[0], $contact );
			} else {
				// New contact in Zoho → create WC customer.
				$this->customer_sync->create_from_zoho( $contact );
			}
		}
	}

	/**
	 * Handle item.created / item.updated events.
	 *
	 * @param string $event_type
	 * @param array  $payload
	 */
	private function handle_item_event( string $event_type, array $payload ): void {
		// Zoho Inventory may nest the item under 'item', 'data', or send it at
		// the root level (default payload). Use root if item_id is there directly.
		$item = $payload['item'] ?? $payload['data'] ?? [];
		if ( empty( $item ) && ! empty( $payload['item_id'] ) ) {
			$item = $payload;
		}
		if ( empty( $item ) ) {
			return;
		}

		$settings = get_option( 'zoho_inventory_sync_settings', [] );

		// When "Create WC Products from new Zoho Items" is on, allow creation
		// for both item.created and item.updated — Zoho's default payload often
		// lacks an action field so new items arrive as "updated" events.
		$create = ! empty( $settings['create_products_from_zoho'] );
		$this->product_sync->update_from_zoho( $item, $create );

		// Sync stock if Zoho sent stock_on_hand.
		if ( ! empty( $item['item_id'] ) && isset( $item['stock_on_hand'] ) ) {
			$this->inventory_sync->poll_zoho_inventory();
		}
	}

	/**
	 * Handle salesorder.created events.
	 *
	 * When a sales order is created manually in Zoho Inventory, deduct the ordered
	 * quantities from the matching WooCommerce product stock levels — keeping WC
	 * inventory accurate for orders that originate in Zoho rather than the store.
	 *
	 * Only fires when the "Deduct WC Stock on Zoho Sales Order" setting is on.
	 *
	 * @param string $event_type
	 * @param array  $payload
	 */
	private function handle_salesorder_event( string $event_type, array $payload ): void {
		if ( $event_type !== 'salesorder.created' ) {
			return; // Only deduct on creation, not on updates.
		}

		$settings = get_option( 'zoho_inventory_sync_settings', [] );
		if ( empty( $settings['deduct_stock_from_zoho_so'] ) ) {
			return;
		}

		$so = $payload['salesorder'] ?? $payload['data'] ?? [];
		if ( empty( $so ) && ! empty( $payload['salesorder_id'] ) ) {
			$so = $payload;
		}
		$line_items = $so['line_items'] ?? [];

		if ( empty( $line_items ) ) {
			return;
		}

		$so_number = $so['salesorder_number'] ?? 'unknown';
		$this->logger->info( "Deducting WC stock for Zoho Inventory SO {$so_number}", [ 'items' => count( $line_items ) ] );

		$this->inventory_sync->deduct_salesorder_stock( $line_items );
	}

	/**
	 * Handle invoice.created / invoice.updated events.
	 *
	 * @param string $event_type
	 * @param array  $payload
	 */
	private function handle_invoice_event( string $event_type, array $payload ): void {
		$invoice = $payload['invoice'] ?? $payload['data'] ?? [];
		if ( empty( $invoice ) && ! empty( $payload['invoice_id'] ) ) {
			$invoice = $payload;
		}
		$invoice_id = $invoice['invoice_id'] ?? '';
		$reference  = $invoice['reference_number'] ?? '';

		if ( ! $invoice_id || ! $reference ) {
			return;
		}

		// Find the WC order by reference/order number.
		$orders = wc_get_orders(
			[
				'meta_key'   => '_zoho_inv_salesorder_id',
				'meta_value' => $invoice['salesorder_id'] ?? '',
				'limit'      => 1,
				'return'     => 'ids',
			]
		);

		if ( empty( $orders ) ) {
			// Try by reference number.
			$orders = wc_get_orders(
				[
					'order_number' => $reference,
					'limit'        => 1,
					'return'       => 'ids',
				]
			);
		}

		if ( ! empty( $orders ) ) {
			$order = wc_get_order( $orders[0] );
			if ( $order ) {
				$order->update_meta_data( '_zoho_inv_invoice_id', $invoice_id );
				$order->save_meta_data();
				$this->logger->success( 'order', $order->get_id(), 'invoice_webhook', "Invoice ID {$invoice_id} linked via webhook.", $invoice_id );
			}
		}
	}

	/**
	 * Apply Zoho Inventory contact name/email fields back to a WordPress user.
	 *
	 * @param int   $user_id
	 * @param array $contact
	 */
	private function apply_contact_to_user( int $user_id, array $contact ): void {
		$name  = explode( ' ', $contact['contact_name'] ?? '', 2 );
		$first = $name[0] ?? '';
		$last  = $name[1] ?? '';

		wp_update_user(
			[
				'ID'         => $user_id,
				'first_name' => $first,
				'last_name'  => $last,
			]
		);

		$this->logger->info( "WC user #{$user_id} updated from Zoho Inventory webhook contact." );
	}
}
