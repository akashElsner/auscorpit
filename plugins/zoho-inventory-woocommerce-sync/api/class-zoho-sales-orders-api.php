<?php
/**
 * Zoho Inventory Sales Orders API wrapper.
 *
 * @package ZohoInventorySync\Api
 */

namespace ZohoInventorySync\Api;

use ZohoInventorySync\Includes\OAuth_Manager;

defined( 'ABSPATH' ) || exit;

/**
 * Class Zoho_Sales_Orders_API
 */
class Zoho_Sales_Orders_API {

	/** @var Zoho_API_Client */
	private Zoho_API_Client $client;

	public function __construct( OAuth_Manager $oauth ) {
		$this->client = new Zoho_API_Client( $oauth );
	}

	/**
	 * Create a new sales order.
	 *
	 * @param  array $data Sales order fields.
	 * @return array|\WP_Error
	 */
	public function create_sales_order( array $data ) {
		return $this->client->post( 'salesorders', $data );
	}

	/**
	 * Update an existing sales order.
	 *
	 * @param  string $order_id Zoho sales order ID.
	 * @param  array  $data     Updated fields.
	 * @return array|\WP_Error
	 */
	public function update_sales_order( string $order_id, array $data ) {
		return $this->client->put( "salesorders/{$order_id}", $data );
	}

	/**
	 * Retrieve a single sales order.
	 *
	 * @param  string $order_id
	 * @return array|\WP_Error
	 */
	public function get_sales_order( string $order_id ) {
		return $this->client->get( "salesorders/{$order_id}" );
	}

	/**
	 * Search sales orders by custom reference number.
	 *
	 * @param  string $reference_number e.g. WooCommerce order number.
	 * @return array|null
	 */
	public function find_by_reference( string $reference_number ) {
		$response = $this->client->get( 'salesorders', [ 'reference_number' => $reference_number ] );
		if ( is_wp_error( $response ) || empty( $response['salesorders'] ) ) {
			return null;
		}
		return $response['salesorders'][0];
	}

	/**
	 * Confirm a draft sales order.
	 *
	 * @param  string $order_id
	 * @return array|\WP_Error
	 */
	public function confirm_sales_order( string $order_id ) {
		return $this->client->post( "salesorders/{$order_id}/status/confirmed" );
	}

	/**
	 * Mark a sales order as packed.
	 *
	 * @param  string $order_id
	 * @return array|\WP_Error
	 */
	public function mark_packed( string $order_id ) {
		return $this->client->post( "salesorders/{$order_id}/status/packed" );
	}

	/**
	 * Mark a sales order as shipped.
	 *
	 * @param  string $order_id
	 * @return array|\WP_Error
	 */
	public function mark_shipped( string $order_id ) {
		return $this->client->post( "salesorders/{$order_id}/status/shipped" );
	}

	/**
	 * Mark a sales order as delivered.
	 *
	 * @param  string $order_id
	 * @return array|\WP_Error
	 */
	public function mark_delivered( string $order_id ) {
		return $this->client->post( "salesorders/{$order_id}/status/delivered" );
	}

	/**
	 * Void a sales order.
	 *
	 * @param  string $order_id
	 * @return array|\WP_Error
	 */
	public function void_sales_order( string $order_id ) {
		return $this->client->post( "salesorders/{$order_id}/status/void" );
	}

	/**
	 * List all sales orders (all pages).
	 *
	 * @param  array $params
	 * @return array
	 */
	public function list_sales_orders( array $params = [] ): array {
		return $this->client->get_all_pages( 'salesorders', $params, 'salesorders' );
	}
}
