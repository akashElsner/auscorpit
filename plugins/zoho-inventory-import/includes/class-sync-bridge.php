<?php
/**
 * Bridge to Zoho Inventory WooCommerce Sync for OAuth and API access.
 *
 * @package ZohoInventoryImport\Includes
 */

namespace ZohoInventoryImport\Includes;

use ZohoInventorySync\Api\Zoho_API_Client;
use ZohoInventorySync\Api\Zoho_Items_API;
use ZohoInventorySync\Includes\OAuth_Manager;
use ZohoInventorySync\Includes\Plugin as Sync_Plugin;

defined( 'ABSPATH' ) || exit;

/**
 * Class Sync_Bridge
 */
class Sync_Bridge {

	/** @var Zoho_Items_API|null */
	private ?Zoho_Items_API $items_api = null;

	/** @var Zoho_API_Client|null */
	private ?Zoho_API_Client $client = null;

	/**
	 * Whether the sync plugin is loaded.
	 */
	public function is_available(): bool {
		return class_exists( Sync_Plugin::class );
	}

	/**
	 * Whether Zoho is connected via the sync plugin.
	 */
	public function is_configured(): bool {
		if ( ! $this->is_available() ) {
			return false;
		}

		$oauth = $this->get_oauth();
		return $oauth->is_connected() && '' !== $oauth->get_organization_id();
	}

	/**
	 * @return OAuth_Manager
	 */
	public function get_oauth(): OAuth_Manager {
		return Sync_Plugin::instance()->oauth;
	}

	/**
	 * @return Zoho_Items_API
	 */
	public function get_items_api(): Zoho_Items_API {
		if ( null === $this->items_api ) {
			$this->items_api = new Zoho_Items_API( $this->get_oauth() );
		}

		return $this->items_api;
	}

	/**
	 * @return Zoho_API_Client
	 */
	public function get_client(): Zoho_API_Client {
		if ( null === $this->client ) {
			$this->client = new Zoho_API_Client( $this->get_oauth() );
		}

		return $this->client;
	}

	/**
	 * URL to the sync plugin settings page.
	 */
	public function get_sync_settings_url(): string {
		return admin_url( 'admin.php?page=zoho-inventory-sync' );
	}

	/**
	 * Human-readable connection status for the admin UI.
	 *
	 * @return array{connected:bool,message:string,org_id:string}
	 */
	public function get_connection_status(): array {
		if ( ! $this->is_available() ) {
			return [
				'connected' => false,
				'message'   => __( 'Zoho Inventory WooCommerce Sync plugin is not active.', 'zoho-inventory-import' ),
				'org_id'    => '',
			];
		}

		$oauth = $this->get_oauth();
		$org   = $oauth->get_organization_id();

		if ( ! $oauth->is_connected() ) {
			return [
				'connected' => false,
				'message'   => __( 'Zoho is not connected. Connect via WooCommerce → Zoho Inventory Sync.', 'zoho-inventory-import' ),
				'org_id'    => $org,
			];
		}

		if ( '' === $org ) {
			return [
				'connected' => false,
				'message'   => __( 'Zoho is connected but no Organization ID is selected in sync settings.', 'zoho-inventory-import' ),
				'org_id'    => '',
			];
		}

		return [
			'connected' => true,
			'message'   => __( 'Connected via Zoho Inventory WooCommerce Sync.', 'zoho-inventory-import' ),
			'org_id'    => $org,
		];
	}
}
