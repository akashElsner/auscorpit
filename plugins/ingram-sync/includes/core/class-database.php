<?php
/**
 * Database table management.
 *
 * @package IngramSync
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Ingram_Sync_Database
 */
class Ingram_Sync_Database {

	/**
	 * Get logs table name.
	 *
	 * @return string
	 */
	public static function logs_table() {
		global $wpdb;
		return $wpdb->prefix . 'ingram_sync_logs';
	}

	/**
	 * Get products cache table name.
	 *
	 * @return string
	 */
	public static function products_table() {
		global $wpdb;
		return $wpdb->prefix . 'ingram_sync_products';
	}

	/**
	 * Create plugin tables.
	 */
	public static function create_tables() {
		global $wpdb;

		$charset = $wpdb->get_charset_collate();

		$logs     = self::logs_table();
		$products = self::products_table();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$sql_logs = "CREATE TABLE {$logs} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			level varchar(20) NOT NULL DEFAULT 'info',
			source varchar(50) NOT NULL DEFAULT '',
			message text NOT NULL,
			context longtext,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY source (source),
			KEY level (level),
			KEY created_at (created_at)
		) {$charset};";

		$sql_products = "CREATE TABLE {$products} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			ingram_part_number varchar(100) NOT NULL DEFAULT '',
			vendor_part_number varchar(100) NOT NULL DEFAULT '',
			product_data longtext,
			price_data longtext,
			details_data longtext,
			wc_product_id bigint(20) unsigned DEFAULT NULL,
			zoho_item_id varchar(100) DEFAULT NULL,
			sync_status varchar(20) NOT NULL DEFAULT 'pending',
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY ingram_part_number (ingram_part_number),
			KEY sync_status (sync_status),
			KEY wc_product_id (wc_product_id),
			KEY zoho_item_id (zoho_item_id)
		) {$charset};";

		dbDelta( $sql_logs );
		dbDelta( $sql_products );
	}

	/**
	 * Truncate products table.
	 */
	public static function truncate_products() {
		global $wpdb;
		$wpdb->query( 'TRUNCATE TABLE ' . self::products_table() ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}
}
