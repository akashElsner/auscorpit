<?php
/**
 * Synchronise WooCommerce products ↔ Zoho Inventory items.
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
 * Class Product_Sync
 */
class Product_Sync {

	/** Product meta key for the linked Zoho item ID. */
	const META_ZOHO_ID     = '_zoho_inv_item_id';
	const META_ZOHO_SYNCED = '_zoho_inv_synced_at';

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
	 * Fires on save_post_product.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 * @param bool     $update  Whether this is an update.
	 */
	public function on_product_saved( int $post_id, \WP_Post $post, bool $update ): void {
		if ( ! $this->oauth->is_connected() ) {
			return;
		}
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}
		if ( 'publish' !== $post->post_status ) {
			return;
		}
		$this->queue->enqueue( 'product', $post_id, $update ? 'update' : 'create' );
	}

	/**
	 * Fires on woocommerce_update_product.
	 *
	 * @param int $product_id
	 */
	public function on_product_updated( int $product_id ): void {
		if ( ! $this->oauth->is_connected() ) {
			return;
		}
		$this->queue->enqueue( 'product', $product_id, 'update' );
	}

	// -------------------------------------------------------------------------
	// Sync methods
	// -------------------------------------------------------------------------

	/**
	 * Sync a single product to Zoho Inventory.
	 *
	 * @param  int $product_id WooCommerce product ID.
	 * @return string|false    Zoho item ID on success, false on failure.
	 */
	public function sync_product( int $product_id ) {
		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			$this->logger->error( 'product', $product_id, 'sync', 'Product not found.' );
			return false;
		}

		// Skip variable parent — sync individual variations instead.
		if ( $product->is_type( 'variable' ) ) {
			foreach ( $product->get_children() as $variation_id ) {
				$this->sync_product( $variation_id );
			}
			return false;
		}

		$zoho_id = $product->get_meta( self::META_ZOHO_ID, true );
		$action  = 'create';
		$is_new  = false;

		if ( $zoho_id ) {
			$data     = $this->map_to_zoho( $product, false );
			$response = $this->api->update_item( $zoho_id, $data );
			$action   = 'update';
		} else {
			// Try to match by SKU.
			$sku      = $product->get_sku();
			$existing = $sku ? $this->api->find_by_sku( $sku ) : null;

			if ( $existing ) {
				$zoho_id  = $existing['item_id'];
				$data     = $this->map_to_zoho( $product, false );
				$response = $this->api->update_item( $zoho_id, $data );
				$action   = 'update';
			} else {
				$is_new   = true;
				$data     = $this->map_to_zoho( $product, true );
				$response = $this->api->create_item( $data );
			}
		}

		if ( is_wp_error( $response ) ) {
			$this->logger->error( 'product', $product_id, $action, $response->get_error_message() );
			return false;
		}

		$returned_id = $response['item']['item_id'] ?? $zoho_id ?? '';

		if ( $returned_id ) {
			$product->update_meta_data( self::META_ZOHO_ID, $returned_id );
			$product->update_meta_data( self::META_ZOHO_SYNCED, current_time( 'mysql' ) );
			$product->save_meta_data();

			// Upload the featured image to Zoho Inventory.
			$this->maybe_upload_image( $product, $returned_id );
		}

		$this->logger->success( 'product', $product_id, $action, "Product synced to Zoho Inventory item {$returned_id}", $returned_id );
		return $returned_id;
	}

	/**
	 * Queue all published products for sync.
	 *
	 * @param int $batch_size
	 * @param int $offset
	 */
	public function sync_all_products( int $batch_size = 50, int $offset = 0 ): void {
		$products = wc_get_products(
			[
				'status'  => 'publish',
				'limit'   => $batch_size,
				'offset'  => $offset,
				'return'  => 'ids',
				'type'    => [ 'simple', 'variation' ],
			]
		);

		foreach ( $products as $product_id ) {
			$this->queue->enqueue( 'product', (int) $product_id, 'sync' );
		}

		if ( count( $products ) === $batch_size ) {
			wp_schedule_single_event( time() + 30, 'zoho_inventory_sync_process_queue' );
		}

		$this->logger->info( 'Batch product sync queued', [ 'count' => count( $products ) ] );
	}

	/**
	 * Update or optionally create a WooCommerce product from a Zoho Inventory item.
	 * Used for Zoho → WooCommerce sync direction.
	 *
	 * @param  array $item             Zoho item array.
	 * @param  bool  $create_if_missing When true, creates a new WC product if none
	 *                                  is currently linked to this Zoho item ID.
	 *                                  When false (default), skips items with no
	 *                                  existing WC product link.
	 * @return int|false   WooCommerce product ID, or false if skipped/failed.
	 */
	public function update_from_zoho( array $item, bool $create_if_missing = false ) {
		$zoho_item_id = $item['item_id'] ?? '';
		if ( ! $zoho_item_id ) {
			return false;
		}

		// Try to find existing product by Zoho item ID.
		$products = wc_get_products(
			[
				'meta_key'   => self::META_ZOHO_ID,
				'meta_value' => $zoho_item_id,
				'limit'      => 1,
				'return'     => 'ids',
			]
		);

		$product_id = ! empty( $products ) ? (int) $products[0] : 0;

		if ( ! $product_id ) {
			if ( ! $create_if_missing ) {
				$this->logger->warning( "Zoho item {$zoho_item_id} has no linked WC product — skipped. Sync the product WC → Zoho first to establish the link." );
				return false;
			}
			$product = new \WC_Product_Simple();
			$action  = 'zoho_to_wc_create';
		} else {
			$product = wc_get_product( $product_id );
			$action  = 'zoho_to_wc';
		}

		$product->set_name( $item['name'] ?? '' );
		$product->set_sku( $item['sku'] ?? '' );

		$regular_price  = (string) ( $item['rate'] ?? $item['sales_rate'] ?? '' );
		$pricebook_rate = isset( $item['pricebook_rate'] ) ? (float) $item['pricebook_rate'] : 0.0;
		$rate           = (float) $regular_price;

		$product->set_regular_price( $regular_price );

		// Map pricebook_rate to WC sale price when it is lower than the regular
		// price — i.e. Zoho has a discounted pricebook entry for this item.
		if ( $pricebook_rate > 0 && $pricebook_rate < $rate ) {
			$product->set_sale_price( (string) $pricebook_rate );
		} else {
			$product->set_sale_price( '' );
		}

		$product->set_description( $item['description'] ?? $item['purchase_description'] ?? '' );

		// Map purchase cost if WooCommerce cost-of-goods meta is needed.
		if ( ! empty( $item['purchase_rate'] ) ) {
			$product->update_meta_data( '_wc_cog_cost', (string) $item['purchase_rate'] );
		}

		// Map Zoho category to WC product category.
		if ( ! empty( $item['category_name'] ) ) {
			$term = get_term_by( 'name', $item['category_name'], 'product_cat' );
			if ( ! $term ) {
				$term_result = wp_insert_term( $item['category_name'], 'product_cat' );
				$term_id     = ! is_wp_error( $term_result ) ? $term_result['term_id'] : 0;
			} else {
				$term_id = $term->term_id;
			}
			if ( $term_id ) {
				$product->set_category_ids( [ $term_id ] );
			}
		}

		if ( isset( $item['stock_on_hand'] ) ) {
			$product->set_manage_stock( true );
			$product->set_stock_quantity( (int) $item['stock_on_hand'] );
		}

		$product->update_meta_data( self::META_ZOHO_ID, $zoho_item_id );
		$new_id = $product->save();

		// Schedule image import as a background cron event so the webhook
		// response is returned to Zoho immediately (Zoho times out after ~5s
		// if we download the image synchronously during the webhook request).
		// Only schedule when the item has an image and the product needs one.
		if ( $new_id && ( ! empty( $item['image_name'] ) || ! empty( $item['image_type'] ) ) ) {
			$saved_product   = wc_get_product( $new_id );
			$already_has_img = $saved_product && $saved_product->get_image_id();
			if ( ! $already_has_img || $action === 'zoho_to_wc_create' ) {
				wp_schedule_single_event(
					time(),
					'zoho_inventory_sync_import_image',
					[ $new_id, $zoho_item_id, $item['image_name'] ?? '' ]
				);
			}
		}

		$this->logger->success( 'product', $new_id, $action, 'Product synced from Zoho Inventory item.', $zoho_item_id );
		return $new_id;
	}

	// -------------------------------------------------------------------------
	// Image upload / import
	// -------------------------------------------------------------------------

	/**
	 * Download the item image from Zoho Inventory and set it as the WC product's
	 * featured image. Skips silently if the download fails or returns no data.
	 *
	 * @param int    $product_id   WooCommerce product ID.
	 * @param string $zoho_item_id Zoho item ID.
	 * @param string $image_name   Original filename hint from Zoho (may be empty).
	 */
	public function import_image_from_zoho( int $product_id, string $zoho_item_id, string $image_name = '' ): void {
		$result = $this->api->get_image( $zoho_item_id );

		if ( is_wp_error( $result ) ) {
			// Silently skip — item may simply have no image yet.
			return;
		}

		$image_data   = $result['data'] ?? '';
		$content_type = $result['content_type'] ?? 'image/jpeg';

		if ( empty( $image_data ) ) {
			return;
		}

		// Determine file extension from content-type.
		$ext_map = [
			'image/jpeg' => 'jpg',
			'image/jpg'  => 'jpg',
			'image/png'  => 'png',
			'image/gif'  => 'gif',
			'image/webp' => 'webp',
		];
		$mime = strtolower( strtok( $content_type, ';' ) );
		$ext  = $ext_map[ $mime ] ?? ( $image_name ? pathinfo( $image_name, PATHINFO_EXTENSION ) : 'jpg' );

		// Write to a temp file in the WP uploads directory.
		$upload_dir = wp_upload_dir();
		$filename   = $image_name ?: "zoho-item-{$zoho_item_id}.{$ext}";
		$file_path  = trailingslashit( $upload_dir['path'] ) . sanitize_file_name( $filename );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		if ( false === file_put_contents( $file_path, $image_data ) ) {
			$this->logger->error( 'product', $product_id, 'image_import', 'Failed to write image to uploads directory.' );
			return;
		}

		// Create a WP attachment from the saved file.
		$attachment_id = wp_insert_attachment(
			[
				'post_mime_type' => $mime,
				'post_title'     => sanitize_file_name( pathinfo( $filename, PATHINFO_FILENAME ) ),
				'post_status'    => 'inherit',
			],
			$file_path,
			$product_id
		);

		if ( is_wp_error( $attachment_id ) || ! $attachment_id ) {
			$this->logger->error( 'product', $product_id, 'image_import', 'Failed to create WP attachment.' );
			return;
		}

		// Generate attachment metadata (thumbnails etc.).
		if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
			require_once ABSPATH . 'wp-admin/includes/image.php';
		}
		wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $file_path ) );

		// Set as featured image.
		set_post_thumbnail( $product_id, $attachment_id );

		$this->logger->success( 'product', $product_id, 'image_import', "Image imported from Zoho item {$zoho_item_id}.", $zoho_item_id );
	}

	/**
	 * Upload the product's featured image to Zoho Inventory if one is set.
	 *
	 * Zoho's image endpoint expects a local file path, so we resolve the WP
	 * attachment to its filesystem path. Non-image attachments and products
	 * without a featured image are silently skipped.
	 *
	 * @param \WC_Product $product   The WooCommerce product.
	 * @param string      $zoho_id   Zoho item ID to attach the image to.
	 */
	private function maybe_upload_image( \WC_Product $product, string $zoho_id ): void {
		$image_id = $product->get_image_id();
		if ( ! $image_id ) {
			return;
		}

		$file_path = get_attached_file( (int) $image_id );
		if ( ! $file_path || ! file_exists( $file_path ) ) {
			return;
		}

		// Only upload recognised image types.
		$allowed = [ 'jpg', 'jpeg', 'png', 'gif', 'webp' ];
		$ext     = strtolower( pathinfo( $file_path, PATHINFO_EXTENSION ) );
		if ( ! in_array( $ext, $allowed, true ) ) {
			return;
		}

		$result = $this->api->upload_image( $zoho_id, $file_path );
		if ( is_wp_error( $result ) ) {
			// Log but do not fail the whole sync — image upload is best-effort.
			$this->logger->error( 'product', $product->get_id(), 'image_upload', $result->get_error_message() );
		}
	}

	// -------------------------------------------------------------------------
	// Field mapping
	// -------------------------------------------------------------------------

	/**
	 * Map a WC_Product to a Zoho Inventory item array.
	 *
	 * @param  \WC_Product $product
	 * @return array
	 */
	/**
	 * Map a WC_Product to a Zoho Inventory item array.
	 *
	 * @param  \WC_Product $product
	 * @param  bool        $is_new  True when creating a new item (includes initial_stock).
	 *                              False for updates (initial_stock is a creation-only field
	 *                              that causes Zoho to reject the entire PUT if included).
	 * @return array
	 */
	private function map_to_zoho( \WC_Product $product, bool $is_new = false ): array {
		$data = [
			'name'        => $product->get_name(),
			'item_type'   => 'sales_and_purchases',
			'rate'        => (float) $product->get_regular_price(),
			'description' => wp_strip_all_tags( $product->get_description() ),
			'sku'         => $product->get_sku(),
		];

		// Only set is_taxable when true — omitting it for non-taxable avoids
		// Zoho requiring a tax_exemption_id that WooCommerce doesn't track.
		if ( $product->is_taxable() ) {
			$data['is_taxable'] = true;
		}

		// initial_stock and initial_stock_rate are creation-only fields in Zoho
		// Inventory. Sending them in a PUT update causes the entire request to be
		// rejected. Stock changes on existing items must go through inventory adjustments.
		if ( $is_new && $product->managing_stock() ) {
			$data['initial_stock']      = max( 0, (int) $product->get_stock_quantity() );
			$data['initial_stock_rate'] = (float) ( $product->get_price() ?: 0 );
		}

		// Purchase price (cost price).
		$purchase_price = $product->get_meta( '_purchase_price', true );
		if ( $purchase_price ) {
			$data['purchase_rate'] = (float) $purchase_price;
		}

		return array_filter( $data, fn( $v ) => $v !== '' && $v !== null );
	}
}
