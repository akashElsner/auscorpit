<?php
/**
 * Uninstall handler.
 *
 * @package IngramSync
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// Drop custom tables.
$tables = array(
	$wpdb->prefix . 'ingram_sync_queue',
	$wpdb->prefix . 'ingram_sync_logs',
	$wpdb->prefix . 'ingram_sync_products',
);

foreach ( $tables as $table ) {
	$wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
}

// Delete all plugin options.
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'ingram\_%'" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

// Clear scheduled events.
wp_clear_scheduled_hook( 'ingram_sync_cron' );

delete_option( 'ingram_sync_db_version' );
