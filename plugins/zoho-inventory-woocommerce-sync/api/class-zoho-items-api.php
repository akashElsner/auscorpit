<?php
/**
 * Zoho Inventory Items API wrapper.
 *
 * @package ZohoInventorySync\Api
 */

namespace ZohoInventorySync\Api;

use ZohoInventorySync\Includes\OAuth_Manager;

defined( 'ABSPATH' ) || exit;

/**
 * Class Zoho_Items_API
 */
class Zoho_Items_API {

	/** @var Zoho_API_Client */
	private Zoho_API_Client $client;

	public function __construct( OAuth_Manager $oauth ) {
		$this->client = new Zoho_API_Client( $oauth );
	}

	/**
	 * Create a new item (product) in Zoho Inventory.
	 *
	 * @param  array $data Item fields.
	 * @return array|\WP_Error
	 */
	public function create_item( array $data ) {
		return $this->client->post( 'items', $data );
	}

	/**
	 * Update an existing item.
	 *
	 * @param  string $item_id Zoho item ID.
	 * @param  array  $data    Updated fields.
	 * @return array|\WP_Error
	 */
	public function update_item( string $item_id, array $data ) {
		return $this->client->put( "items/{$item_id}", $data );
	}

	/**
	 * Retrieve a single item by ID.
	 *
	 * @param  string $item_id
	 * @return array|\WP_Error
	 */
	public function get_item( string $item_id ) {
		return $this->client->get( "items/{$item_id}" );
	}

	/**
	 * Search for an item by SKU.
	 *
	 * @param  string $sku
	 * @return array|null
	 */
	public function find_by_sku( string $sku ) {
		$response = $this->client->get( 'items', [ 'sku' => $sku ] );
		if ( is_wp_error( $response ) || empty( $response['items'] ) ) {
			return null;
		}
		return $response['items'][0];
	}

	/**
	 * List all items across all pages.
	 *
	 * @param  array $params Filter params.
	 * @return array
	 */
	public function list_items( array $params = [] ): array {
		return $this->client->get_all_pages( 'items', $params, 'items' );
	}

	/**
	 * Set the absolute stock quantity for an item via a Zoho Inventory adjustment.
	 *
	 * Zoho Inventory does not allow setting stock directly on the item via PUT.
	 * The correct approach is POST /inventoryadjustments with adjustment_type "quantity".
	 * We fetch the current stock first, calculate the delta, and post the adjustment.
	 * If delta is zero no API call is made.
	 *
	 * @param  string $item_id   Zoho item ID.
	 * @param  int    $quantity  Target stock quantity (absolute, not a delta).
	 * @param  float  $rate      Unit cost for the adjustment record.
	 * @return array|\WP_Error
	 */
	public function update_stock( string $item_id, int $quantity, float $rate = 0.00 ) {
		// Get current stock so we can calculate the adjustment delta.
		$item_response = $this->client->get( "items/{$item_id}" );
		if ( is_wp_error( $item_response ) ) {
			return $item_response;
		}

		$current_stock = (int) ( $item_response['item']['stock_on_hand'] ?? 0 );
		$delta         = $quantity - $current_stock;

		if ( $delta === 0 ) {
			return [ 'code' => 0, 'message' => 'Stock already at target.' ];
		}

		return $this->client->post(
			'inventoryadjustments',
			[
				'date'            => gmdate( 'Y-m-d' ),
				'adjustment_type' => 'quantity',
				'reason'          => 'WooCommerce stock sync',
				'line_items'      => [
					[
						'item_id'                    => $item_id,
						'quantity_adjusted'          => $delta,
						'quantity_after_adjustment'  => $quantity,
					],
				],
			]
		);
	}

	/**
	 * Download the image for an item as raw binary data.
	 *
	 * @param  string $item_id Zoho item ID.
	 * @return array|\WP_Error  Array with 'data' and 'content_type', or WP_Error.
	 */
	public function get_image( string $item_id ) {
		return $this->client->download_file( "items/{$item_id}/image" );
	}

	/**
	 * Upload an image for an item.
	 *
	 * Zoho Inventory stores one image per item. Subsequent uploads replace the
	 * existing image. The file must be a local path (not a URL).
	 *
	 * @param  string $item_id   Zoho item ID.
	 * @param  string $file_path Absolute path to the image file.
	 * @return true|\WP_Error
	 */
	public function upload_image( string $item_id, string $file_path ) {
		return $this->client->upload_file( "items/{$item_id}/image", $file_path );
	}

	/**
	 * Delete an item.
	 *
	 * @param  string $item_id
	 * @return array|\WP_Error
	 */
	public function delete_item( string $item_id ) {
		return $this->client->delete( "items/{$item_id}" );
	}
}
