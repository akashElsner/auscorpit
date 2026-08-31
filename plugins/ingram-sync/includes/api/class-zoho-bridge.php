<?php
/**
 * Bridges Ingram Sync to Zoho Inventory.
 *
 * Prefers the existing "Zoho Inventory WooCommerce Sync" OAuth connection
 * (already configured in wp-admin) and falls back to Ingram Sync direct credentials.
 *
 * @package IngramSync
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Ingram_Sync_Zoho_Bridge
 */
class Ingram_Sync_Zoho_Bridge {

	/**
	 * Whether the Zoho Inventory WooCommerce Sync plugin is available and connected.
	 *
	 * @return bool
	 */
	public static function is_wc_sync_available() {
		return class_exists( '\ZohoInventorySync\Includes\Plugin' );
	}

	/**
	 * Get OAuth manager from Zoho Inventory WooCommerce Sync, if connected.
	 *
	 * @return \ZohoInventorySync\Includes\OAuth_Manager|null
	 */
	public static function get_wc_sync_oauth() {
		if ( ! self::is_wc_sync_available() ) {
			return null;
		}

		$oauth = \ZohoInventorySync\Includes\Plugin::instance()->oauth;
		if ( ! $oauth->is_connected() || '' === $oauth->get_organization_id() ) {
			return null;
		}

		return $oauth;
	}

	/**
	 * Whether Zoho is usable (WC Sync connection or direct fallback credentials).
	 *
	 * @return bool
	 */
	public static function is_configured() {
		if ( null !== self::get_wc_sync_oauth() ) {
			return true;
		}

		return self::has_direct_credentials();
	}

	/**
	 * Whether direct (fallback) Client ID + Secret + Refresh Token + Org ID are set.
	 *
	 * Access token is never required — it is obtained automatically from the refresh token.
	 *
	 * @return bool
	 */
	public static function has_direct_credentials() {
		return '' !== (string) Ingram_Sync_Settings::get( 'zoho_client_id' )
			&& '' !== (string) Ingram_Sync_Settings::get( 'zoho_client_secret' )
			&& '' !== (string) Ingram_Sync_Settings::get( 'zoho_refresh_token' )
			&& '' !== (string) Ingram_Sync_Settings::get( 'zoho_organization_id' );
	}

	/**
	 * Connection info for the admin UI.
	 *
	 * @return array{configured: bool, source: string, label: string, org_id: string, dc: string}
	 */
	public static function connection_info() {
		$oauth = self::get_wc_sync_oauth();
		if ( $oauth ) {
			return array(
				'configured' => true,
				'source'     => 'wc_sync',
				'label'      => __( 'Zoho Inventory WooCommerce Sync', 'ingram-sync' ),
				'org_id'     => $oauth->get_organization_id(),
				'dc'         => $oauth->get_data_centre(),
			);
		}

		return array(
			'configured' => self::has_direct_credentials(),
			'source'     => 'direct',
			'label'      => __( 'Ingram Sync direct API', 'ingram-sync' ),
			'org_id'     => (string) Ingram_Sync_Settings::get( 'zoho_organization_id' ),
			'dc'         => (string) Ingram_Sync_Settings::get( 'zoho_dc', 'com' ),
		);
	}

	/**
	 * Admin URL for Zoho Inventory Sync OAuth settings.
	 *
	 * @return string
	 */
	public static function get_wc_sync_settings_url() {
		return admin_url( 'admin.php?page=zoho-inventory-sync&tab=oauth' );
	}

	/**
	 * Map Ingram Sync DC keys to Zoho accounts / API base URLs.
	 *
	 * @return array<string, array{accounts: string, api: string, label: string}>
	 */
	public static function get_data_centres() {
		return array(
			'com' => array(
				'accounts' => 'https://accounts.zoho.com',
				'api'      => 'https://www.zohoapis.com/inventory/v1',
				'label'    => __( 'Global (com)', 'ingram-sync' ),
			),
			'eu'  => array(
				'accounts' => 'https://accounts.zoho.eu',
				'api'      => 'https://www.zohoapis.eu/inventory/v1',
				'label'    => __( 'Europe (eu)', 'ingram-sync' ),
			),
			'in'  => array(
				'accounts' => 'https://accounts.zoho.in',
				'api'      => 'https://www.zohoapis.in/inventory/v1',
				'label'    => __( 'India (in)', 'ingram-sync' ),
			),
			'au'  => array(
				'accounts' => 'https://accounts.zoho.com.au',
				'api'      => 'https://www.zohoapis.com.au/inventory/v1',
				'label'    => __( 'Australia (au)', 'ingram-sync' ),
			),
			'jp'  => array(
				'accounts' => 'https://accounts.zoho.jp',
				'api'      => 'https://www.zohoapis.jp/inventory/v1',
				'label'    => __( 'Japan (jp)', 'ingram-sync' ),
			),
			'ca'  => array(
				'accounts' => 'https://accounts.zohocloud.ca',
				'api'      => 'https://www.zohoapis.ca/inventory/v1',
				'label'    => __( 'Canada (ca)', 'ingram-sync' ),
			),
		);
	}
}
