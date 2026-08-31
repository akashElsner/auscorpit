<?php
/**
 * Zoho Inventory Contacts API wrapper.
 *
 * @package ZohoInventorySync\Api
 */

namespace ZohoInventorySync\Api;

use ZohoInventorySync\Includes\OAuth_Manager;

defined( 'ABSPATH' ) || exit;

/**
 * Class Zoho_Contacts_API
 */
class Zoho_Contacts_API {

	/** @var Zoho_API_Client */
	private Zoho_API_Client $client;

	public function __construct( OAuth_Manager $oauth ) {
		$this->client = new Zoho_API_Client( $oauth );
	}

	/**
	 * Create a new contact in Zoho Inventory.
	 *
	 * @param  array $data Contact fields.
	 * @return array|\WP_Error Response body or error.
	 */
	public function create_contact( array $data ) {
		return $this->client->post( 'contacts', $data );
	}

	/**
	 * Update an existing contact.
	 *
	 * @param  string $contact_id Zoho contact ID.
	 * @param  array  $data       Updated fields.
	 * @return array|\WP_Error
	 */
	public function update_contact( string $contact_id, array $data ) {
		return $this->client->put( "contacts/{$contact_id}", $data );
	}

	/**
	 * Retrieve a single contact by ID.
	 *
	 * @param  string $contact_id
	 * @return array|\WP_Error
	 */
	public function get_contact( string $contact_id ) {
		return $this->client->get( "contacts/{$contact_id}" );
	}

	/**
	 * Search for a contact by email address.
	 *
	 * @param  string $email
	 * @return array|null Contact array or null if not found.
	 */
	public function find_by_email( string $email ) {
		$response = $this->client->get( 'contacts', [ 'email' => $email ] );
		if ( is_wp_error( $response ) || empty( $response['contacts'] ) ) {
			return null;
		}
		return $response['contacts'][0];
	}

	/**
	 * List all contacts (all pages).
	 *
	 * @param  array $params Optional filter params.
	 * @return array
	 */
	public function list_contacts( array $params = [] ): array {
		return $this->client->get_all_pages( 'contacts', $params, 'contacts' );
	}

	/**
	 * Delete a contact.
	 *
	 * @param  string $contact_id
	 * @return array|\WP_Error
	 */
	public function delete_contact( string $contact_id ) {
		return $this->client->delete( "contacts/{$contact_id}" );
	}
}
