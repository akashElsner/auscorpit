<?php
/**
 * Plugin Name:       Zoho Inventory WooCommerce Sync
 * Plugin URI:        https://store.elsner.com
 * Description:       Bi-directional synchronisation between WooCommerce and Zoho Inventory. Syncs customers, products, orders, and inventory.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            Elsner Technologies Pvt Tld
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       zoho-inventory-sync
 * Domain Path:       /languages
 * WC requires at least: 7.0
 * WC tested up to:   8.x
 *
 * @package ZohoInventorySync
 */

defined( 'ABSPATH' ) || exit;

// Plugin constants.
define( 'ZOHO_INVENTORY_SYNC_VERSION', '1.0.0' );
define( 'ZOHO_INVENTORY_SYNC_FILE', __FILE__ );
define( 'ZOHO_INVENTORY_SYNC_PATH', plugin_dir_path( __FILE__ ) );
define( 'ZOHO_INVENTORY_SYNC_URL', plugin_dir_url( __FILE__ ) );
define( 'ZOHO_INVENTORY_SYNC_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Check that WooCommerce is active before loading the plugin.
 */
function zoho_inventory_sync_check_dependencies(): bool {
	return class_exists( 'WooCommerce' );
}

/**
 * Show admin notice when WooCommerce is missing.
 */
function zoho_inventory_sync_missing_wc_notice(): void {
	echo '<div class="notice notice-error"><p>';
	echo esc_html__( 'Zoho Inventory WooCommerce Sync requires WooCommerce to be installed and active.', 'zoho-inventory-sync' );
	echo '</p></div>';
}

/**
 * Bootstrap the plugin after all plugins are loaded.
 */
function zoho_inventory_sync_init(): void {
	if ( ! zoho_inventory_sync_check_dependencies() ) {
		add_action( 'admin_notices', 'zoho_inventory_sync_missing_wc_notice' );
		return;
	}

	// Autoload classes.
	spl_autoload_register( 'zoho_inventory_sync_autoloader' );

	// Boot the plugin.
	\ZohoInventorySync\Includes\Plugin::instance();
}
add_action( 'plugins_loaded', 'zoho_inventory_sync_init' );

/**
 * Simple PSR-4 style autoloader for the ZohoInventorySync namespace.
 *
 * Namespace  → directory map:
 *   ZohoInventorySync\Includes  → includes/
 *   ZohoInventorySync\Api       → api/
 *   ZohoInventorySync\Sync      → sync/
 *   ZohoInventorySync\Webhooks  → webhooks/
 *   ZohoInventorySync\Admin     → admin/
 */
function zoho_inventory_sync_autoloader( string $class ): void {
	$prefix = 'ZohoInventorySync\\';

	if ( strpos( $class, $prefix ) !== 0 ) {
		return;
	}

	$relative = substr( $class, strlen( $prefix ) );
	$parts    = explode( '\\', $relative );

	// Convert namespace segment to directory name (e.g. "Includes" → "includes").
	$dir = strtolower( array_shift( $parts ) );

	// Convert class name to file name (e.g. "OAuth_Manager" → "class-oauth-manager.php").
	$class_name = array_pop( $parts );
	$file_name  = 'class-' . strtolower( str_replace( '_', '-', $class_name ) ) . '.php';

	// Build path with any intermediate sub-namespaces as sub-directories.
	$sub_path = ! empty( $parts ) ? implode( '/', array_map( 'strtolower', $parts ) ) . '/' : '';

	$file = ZOHO_INVENTORY_SYNC_PATH . $dir . '/' . $sub_path . $file_name;

	if ( file_exists( $file ) ) {
		require_once $file;
	}
}

/**
 * Plugin activation hook.
 */
function zoho_inventory_sync_activate(): void {
	spl_autoload_register( 'zoho_inventory_sync_autoloader' );
	\ZohoInventorySync\Includes\Installer::activate();
}
register_activation_hook( __FILE__, 'zoho_inventory_sync_activate' );

/**
 * Plugin deactivation hook.
 */
function zoho_inventory_sync_deactivate(): void {
	spl_autoload_register( 'zoho_inventory_sync_autoloader' );
	\ZohoInventorySync\Includes\Installer::deactivate();
}
register_deactivation_hook( __FILE__, 'zoho_inventory_sync_deactivate' );
