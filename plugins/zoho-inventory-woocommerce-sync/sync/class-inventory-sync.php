<?php
/**
 * Inventory / stock synchronisation between WooCommerce and Zoho Inventory.
 *
 * Handles:
 *  - WC stock change → Zoho Inventory item stock update.
 *  - Zoho Inventory → WC product stock polling (scheduled).
 *
 * @package ZohoInventorySync\Sync
 */

namespace ZohoInventorySync\Sync;

use ZohoInventorySync\Api\Zoho_Items_API;
use ZohoInventorySync\Includes\Logger;
use ZohoInventorySync\Includes\OAuth_Manager;
use ZohoInventorySync\Includes\Sync_Queue;

defined( 'ABSPATH' ) || exit;

/**
 * Class Inventory_Sync
 */
class Inventory_Sync {

	/** @var Zoho_Items_API */
	private Zoho_Items_API $api;

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
		$this->api    = new Zoho_Items_API( $oauth );
	}

	// -------------------------------------------------------------------------
	// Hook callbacks
	// -------------------------------------------------------------------------

	/**
	 * Called when WooCommerce updates product or variation stock.
	 *
	 * @param \WC_Product $product
	 */
	public function on_stock_changed( \WC_Product $product ): void {
		if ( ! $this->oauth->is_connected() ) {
			return;
		}

		$settings = get_option( 'zoho_inventory_sync_settings', [] );
		if ( empty( $settings['sync_stock'] ) ) {
			return;
		}

		$this->queue->enqueue( 'inventory', $product->get_id(), 'update_stock' );
	}

	// -------------------------------------------------------------------------
	// Sync methods
	// -------------------------------------------------------------------------

	/**
	 * Push the current WooCommerce stock level for a product to Zoho Inventory.
	 *
	 * @param  int $product_id WooCommerce product ID.
	 * @return bool
	 */
	public function sync_inventory( int $product_id ): bool {
		$product = wc_get_product( $product_id );
		if ( ! $product || ! $product->managing_stock() ) {
			return false;
		}

		$zoho_item_id = $product->get_meta( Product_Sync::META_ZOHO_ID, true );
		if ( ! $zoho_item_id ) {
			// Product not yet in Zoho — trigger a full product sync.
			$plugin       = \ZohoInventorySync\Includes\Plugin::instance();
			$zoho_item_id = (string) $plugin->product_sync->sync_product( $product_id );
			if ( ! $zoho_item_id ) {
				return false;
			}
		}

		$stock    = (int) $product->get_stock_quantity();
		$response = $this->api->update_stock( $zoho_item_id, $stock, (float) $product->get_price() );

		if ( is_wp_error( $response ) ) {
			$this->logger->error( 'inventory', $product_id, 'update_stock', $response->get_error_message() );
			return false;
		}

		$this->logger->success( 'inventory', $product_id, 'update_stock', "Stock updated to {$stock} in Zoho Inventory item {$zoho_item_id}", $zoho_item_id );
		return true;
	}

	/**
	 * Sync all managed-stock products to Zoho Inventory.
	 *
	 * @param int $batch_size
	 * @param int $offset
	 */
	public function sync_all_inventory( int $batch_size = 50, int $offset = 0 ): void {
		$products = wc_get_products(
			[
				'manage_stock' => true,
				'status'       => 'publish',
				'limit'        => $batch_size,
				'offset'       => $offset,
				'return'       => 'ids',
			]
		);

		foreach ( $products as $product_id ) {
			$this->queue->enqueue( 'inventory', (int) $product_id, 'update_stock' );
		}

		if ( count( $products ) === $batch_size ) {
			wp_schedule_single_event( time() + 30, 'zoho_inventory_sync_process_queue' );
		}
	}

	/**
	 * Poll Zoho Inventory for items with updated stock and apply to WooCommerce.
	 *
	 * Runs hourly via zoho_inventory_sync_poll_zoho cron hook.
	 * Reads the aggregate stock_on_hand field which reflects total across all
	 * warehouses in Zoho Inventory.
	 */
	public function poll_zoho_inventory(): void {
		// Fetch items modified in the last hour.
		$last_modified = gmdate( 'Y-m-dTH:i:s+0000', time() - HOUR_IN_SECONDS );
		$items         = $this->api->list_items( [ 'last_modified_time' => $last_modified ] );

		if ( empty( $items ) ) {
			return;
		}

		$this->logger->info( 'Polling Zoho Inventory stock', [ 'count' => count( $items ) ] );

		foreach ( $items as $item ) {
			$this->apply_zoho_stock_to_wc( $item );
		}
	}

	/**
	 * Deduct line item quantities of a Zoho Inventory sales order from WC stock.
	 *
	 * Called when a sales order is created manually in Zoho Inventory so that WC
	 * stock levels stay accurate without waiting for a WooCommerce order.
	 *
	 * @param array $line_items  Line items array from the Zoho SO payload.
	 */
	public function deduct_salesorder_stock( array $line_items ): void {
		$plugin = \ZohoInventorySync\Includes\Plugin::instance();

		// Suppress sync-back hooks to avoid WC → Zoho loop.
		remove_action( 'woocommerce_product_set_stock',   [ $plugin->inventory_sync, 'on_stock_changed' ] );
		remove_action( 'woocommerce_variation_set_stock', [ $plugin->inventory_sync, 'on_stock_changed' ] );

		foreach ( $line_items as $line_item ) {
			$zoho_item_id = $line_item['item_id'] ?? '';
			$qty          = (int) ( $line_item['quantity'] ?? 0 );

			if ( ! $zoho_item_id || $qty <= 0 ) {
				continue;
			}

			$products = wc_get_products(
				[
					'meta_key'   => Product_Sync::META_ZOHO_ID,
					'meta_value' => $zoho_item_id,
					'limit'      => 1,
					'return'     => 'ids',
				]
			);

			if ( empty( $products ) ) {
				continue;
			}

			$product = wc_get_product( (int) $products[0] );
			if ( ! $product || ! $product->managing_stock() ) {
				continue;
			}

			$old_stock = (int) $product->get_stock_quantity();
			wc_update_product_stock( $product, $qty, 'subtract' );
			$new_stock = (int) $product->get_stock_quantity();

			$this->logger->success(
				'inventory',
				$product->get_id(),
				'so_deduct',
				"Zoho Inventory SO deducted {$qty} units: {$old_stock} → {$new_stock}",
				$zoho_item_id
			);
		}

		// Re-attach hooks.
		add_action( 'woocommerce_product_set_stock',   [ $plugin->inventory_sync, 'on_stock_changed' ], 10, 1 );
		add_action( 'woocommerce_variation_set_stock', [ $plugin->inventory_sync, 'on_stock_changed' ], 10, 1 );
	}

	/**
	 * Apply a Zoho Inventory item's stock_on_hand to the matching WooCommerce product.
	 *
	 * stock_on_hand reflects total stock across all warehouses in Zoho Inventory.
	 *
	 * @param array $item Zoho item array.
	 */
	private function apply_zoho_stock_to_wc( array $item ): void {
		$zoho_item_id = $item['item_id'] ?? '';
		if ( ! $zoho_item_id ) {
			return;
		}

		// Find the WC product linked to this Zoho item.
		$products = wc_get_products(
			[
				'meta_key'   => Product_Sync::META_ZOHO_ID,
				'meta_value' => $zoho_item_id,
				'limit'      => 1,
				'return'     => 'ids',
			]
		);

		if ( empty( $products ) ) {
			return;
		}

		$product_id = (int) $products[0];
		$product    = wc_get_product( $product_id );

		if ( ! $product || ! $product->managing_stock() ) {
			return;
		}

		$zoho_stock = isset( $item['stock_on_hand'] ) ? (int) $item['stock_on_hand'] : null;
		if ( $zoho_stock === null ) {
			return;
		}

		$wc_stock = (int) $product->get_stock_quantity();
		if ( $wc_stock === $zoho_stock ) {
			return; // No change needed.
		}

		// Suppress the on_stock_changed hook to avoid a sync loop.
		remove_action( 'woocommerce_product_set_stock',   [ \ZohoInventorySync\Includes\Plugin::instance()->inventory_sync, 'on_stock_changed' ] );
		remove_action( 'woocommerce_variation_set_stock', [ \ZohoInventorySync\Includes\Plugin::instance()->inventory_sync, 'on_stock_changed' ] );

		wc_update_product_stock( $product, $zoho_stock, 'set' );

		// Re-attach hooks.
		add_action( 'woocommerce_product_set_stock',   [ \ZohoInventorySync\Includes\Plugin::instance()->inventory_sync, 'on_stock_changed' ], 10, 1 );
		add_action( 'woocommerce_variation_set_stock', [ \ZohoInventorySync\Includes\Plugin::instance()->inventory_sync, 'on_stock_changed' ], 10, 1 );

		$this->logger->success( 'inventory', $product_id, 'zoho_to_wc', "Stock updated from Zoho Inventory: {$wc_stock} → {$zoho_stock}", $zoho_item_id );
	}
}
