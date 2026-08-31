<?php
/**
 * Plugin Name:       Zoho Inventory Import
 * Plugin URI:        https://example.com/zoho-inventory-import
 * Description:       Import products/items into Zoho Inventory from CSV. Uses Zoho Inventory WooCommerce Sync for API connection.
 * Version:           1.1.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Elsner
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       zoho-inventory-import
 *
 * @package ZohoInventoryImport
 */

defined( 'ABSPATH' ) || exit;

define( 'ZOHO_INV_IMPORT_VERSION', '1.1.0' );
define( 'ZOHO_INV_IMPORT_FILE', __FILE__ );
define( 'ZOHO_INV_IMPORT_PATH', plugin_dir_path( __FILE__ ) );
define( 'ZOHO_INV_IMPORT_URL', plugin_dir_url( __FILE__ ) );
define( 'ZOHO_INV_IMPORT_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Show notice when the sync plugin is missing.
 */
function zoho_inv_import_missing_sync_notice(): void {
	echo '<div class="notice notice-error"><p>';
	echo esc_html__( 'Zoho Inventory Import requires the Zoho Inventory WooCommerce Sync plugin to be installed and active.', 'zoho-inventory-import' );
	echo '</p></div>';
}

/**
 * Show notice when WooCommerce is missing.
 */
function zoho_inv_import_missing_wc_notice(): void {
	echo '<div class="notice notice-error"><p>';
	echo esc_html__( 'Zoho Inventory Import requires WooCommerce to be installed and active.', 'zoho-inventory-import' );
	echo '</p></div>';
}

/**
 * Initialize after sync plugin has loaded.
 */
function zoho_inv_import_init(): void {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'zoho_inv_import_missing_wc_notice' );
		return;
	}

	if ( ! class_exists( 'ZohoInventorySync\\Includes\\Plugin' ) ) {
		add_action( 'admin_notices', 'zoho_inv_import_missing_sync_notice' );
		return;
	}

	require_once ZOHO_INV_IMPORT_PATH . 'includes/class-plugin.php';
	zoho_inventory_import();
}

add_action( 'plugins_loaded', 'zoho_inv_import_init', 20 );

register_activation_hook(
	__FILE__,
	static function () {
		$upload_dir = wp_upload_dir();
		$import_dir = trailingslashit( $upload_dir['basedir'] ) . 'zoho-inventory-import';

		if ( ! file_exists( $import_dir ) ) {
			wp_mkdir_p( $import_dir );
		}

		$htaccess = $import_dir . '/.htaccess';
		if ( ! file_exists( $htaccess ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			file_put_contents( $htaccess, "Deny from all\n" );
		}
	}
);

/**
 * @return \ZohoInventoryImport\Includes\Plugin|null
 */
function zoho_inventory_import() {
	return \ZohoInventoryImport\Includes\Plugin::instance();
}
