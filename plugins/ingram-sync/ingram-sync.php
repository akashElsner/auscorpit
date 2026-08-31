<?php
/**
 * Plugin Name:       Ingram Sync
 * Plugin URI:        https://example.com/ingram-sync
 * Description:       Enterprise Ingram Micro catalog sync with WooCommerce and Zoho integration. Fully configurable from WordPress admin.
 * Version:           1.1.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            AusCorp IT
 * License:           GPL-2.0+
 * Text Domain:       ingram-sync
 *
 * @package IngramSync
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'INGRAM_SYNC_VERSION', '1.1.0' );
define( 'INGRAM_SYNC_PLUGIN_FILE', __FILE__ );
define( 'INGRAM_SYNC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'INGRAM_SYNC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'INGRAM_SYNC_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/** Zoho Inventory custom fields — field IDs are org-specific (from this org's item schema). */
define( 'INGRAM_SYNC_ZOHO_CF_SUPPLIER_SOURCE_ID', '5860785000001106001' );
define( 'INGRAM_SYNC_ZOHO_CF_SUPPLIER_SOURCE_VALUE', 'Ingram' );
define( 'INGRAM_SYNC_ZOHO_CF_INGRAM_PART_NUMBER_ID', '5860785000001231003' );
define( 'INGRAM_SYNC_ZOHO_CF_VENDOR_PART_NUMBER_ID', '5860785000001231006' );
define( 'INGRAM_SYNC_ZOHO_CF_QUANTITY_ID', '5860785000000843179' );

require_once INGRAM_SYNC_PLUGIN_DIR . 'includes/class-ingram-autoloader.php';
require_once INGRAM_SYNC_PLUGIN_DIR . 'includes/class-ingram-activator.php';
require_once INGRAM_SYNC_PLUGIN_DIR . 'includes/class-ingram-deactivator.php';
require_once INGRAM_SYNC_PLUGIN_DIR . 'includes/class-ingram-sync.php';

Ingram_Sync_Autoloader::register();

register_activation_hook( __FILE__, array( 'Ingram_Sync_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Ingram_Sync_Deactivator', 'deactivate' ) );

/**
 * Returns the main plugin instance.
 *
 * @return Ingram_Sync
 */
function ingram_sync() {
	return Ingram_Sync::instance();
}

ingram_sync();
