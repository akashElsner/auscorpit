<?php
/**
 * Zoho Inventory Invoices API wrapper.
 *
 * @package ZohoInventorySync\Api
 */

namespace ZohoInventorySync\Api;

use ZohoInventorySync\Includes\OAuth_Manager;

defined( 'ABSPATH' ) || exit;

/**
 * Class Zoho_Invoices_API
 */
class Zoho_Invoices_API {

	/** @var Zoho_API_Client */
	private Zoho_API_Client $client;

	public function __construct( OAuth_Manager $oauth ) {
		$this->client = new Zoho_API_Client( $oauth );
	}

	/**
	 * Create a new invoice in Zoho Inventory.
	 *
	 * @param  array $data Invoice fields.
	 * @return array|\WP_Error
	 */
	public function create_invoice( array $data ) {
		return $this->client->post( 'invoices', $data );
	}

	/**
	 * Update an existing invoice.
	 *
	 * @param  string $invoice_id
	 * @param  array  $data
	 * @return array|\WP_Error
	 */
	public function update_invoice( string $invoice_id, array $data ) {
		return $this->client->put( "invoices/{$invoice_id}", $data );
	}

	/**
	 * Retrieve a single invoice.
	 *
	 * @param  string $invoice_id
	 * @return array|\WP_Error
	 */
	public function get_invoice( string $invoice_id ) {
		return $this->client->get( "invoices/{$invoice_id}" );
	}

	/**
	 * Mark an invoice as sent.
	 *
	 * @param  string $invoice_id
	 * @return array|\WP_Error
	 */
	public function mark_as_sent( string $invoice_id ) {
		return $this->client->post( "invoices/{$invoice_id}/status/sent" );
	}

	/**
	 * Mark an invoice as void.
	 *
	 * @param  string $invoice_id
	 * @return array|\WP_Error
	 */
	public function void_invoice( string $invoice_id ) {
		return $this->client->post( "invoices/{$invoice_id}/status/void" );
	}

	/**
	 * Convert a sales order to an invoice.
	 *
	 * Creates the invoice by posting to /invoices with salesorder_id in the
	 * body — Zoho Inventory automatically pulls the customer and line items from
	 * the linked sales order.
	 *
	 * @param  string $salesorder_id Zoho Sales Order ID.
	 * @param  array  $extra_data    Additional override fields.
	 * @return array|\WP_Error
	 */
	public function create_from_sales_order( string $salesorder_id, array $extra_data = [] ) {
		$data = array_merge(
			[ 'salesorder_id' => $salesorder_id ],
			$extra_data
		);
		return $this->client->post( 'invoices', $data );
	}

	/**
	 * List invoices (all pages).
	 *
	 * @param  array $params
	 * @return array
	 */
	public function list_invoices( array $params = [] ): array {
		return $this->client->get_all_pages( 'invoices', $params, 'invoices' );
	}
}
