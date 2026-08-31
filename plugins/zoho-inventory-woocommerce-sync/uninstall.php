<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * Removes all plugin data from the database: custom tables, options, and cron events.
 *
 * @package ZohoInventorySync
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

global $wpdb;

// Drop custom tables.
$tables = [
	$wpdb->prefix . 'zoho_inv_logs',
	$wpdb->prefix . 'zoho_inv_queue',
];

foreach ( $tables as $table ) {
	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$wpdb->query( "DROP TABLE IF EXISTS `{$table}`" );
}

// Remove all plugin options.
$options = [
	'zoho_inventory_sync_version',
	'zoho_inventory_sync_db_version',
	'zoho_inventory_sync_access_token',
	'zoho_inventory_sync_refresh_token',
	'zoho_inventory_sync_token_expires',
	'zoho_inventory_sync_organization_id',
	'zoho_inventory_sync_client_id',
	'zoho_inventory_sync_client_secret',
	'zoho_inventory_sync_data_centre',
	'zoho_inventory_sync_settings',
	'zoho_inventory_sync_debug',
	'zoho_inventory_sync_webhook_secret',
];

foreach ( $options as $option ) {
	delete_option( $option );
}

// Clear any scheduled cron events.
$cron_hooks = [
	'zoho_inventory_sync_process_queue',
	'zoho_inventory_sync_poll_zoho',
	'zoho_inventory_sync_retry_failed',
];

foreach ( $cron_hooks as $hook ) {
	$timestamp = wp_next_scheduled( $hook );
	if ( $timestamp ) {
		wp_unschedule_event( $timestamp, $hook );
	}
}
