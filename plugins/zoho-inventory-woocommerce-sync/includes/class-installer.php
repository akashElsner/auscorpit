<?php
/**
 * Handles plugin activation, deactivation, and database schema creation.
 *
 * @package ZohoInventorySync\Includes
 */

namespace ZohoInventorySync\Includes;

defined( 'ABSPATH' ) || exit;

/**
 * Class Installer
 */
class Installer {

	/** Current DB schema version. */
	const DB_VERSION = '1.0.0';

	/**
	 * Run on plugin activation.
	 */
	public static function activate(): void {
		self::create_tables();
		self::schedule_cron_jobs();
		update_option( 'zoho_inventory_sync_version', ZOHO_INVENTORY_SYNC_VERSION );
		update_option( 'zoho_inventory_sync_db_version', self::DB_VERSION );
	}

	/**
	 * Run on plugin deactivation.
	 */
	public static function deactivate(): void {
		self::unschedule_cron_jobs();
	}

	/**
	 * Create custom database tables using dbDelta.
	 */
	public static function create_tables(): void {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		// Sync log table.
		$logs_table = $wpdb->prefix . 'zoho_inv_logs';
		$sql_logs   = "CREATE TABLE {$logs_table} (
			id           BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			entity_type  VARCHAR(50)         NOT NULL DEFAULT '',
			entity_id    BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			action       VARCHAR(50)         NOT NULL DEFAULT '',
			status       VARCHAR(20)         NOT NULL DEFAULT 'success',
			message      TEXT                         DEFAULT NULL,
			zoho_id      VARCHAR(100)                 DEFAULT NULL,
			created_at   DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY entity_type_id (entity_type, entity_id),
			KEY status (status),
			KEY created_at (created_at)
		) {$charset_collate};";

		// Sync queue table.
		$queue_table = $wpdb->prefix . 'zoho_inv_queue';
		$sql_queue   = "CREATE TABLE {$queue_table} (
			id           BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			entity_type  VARCHAR(50)         NOT NULL DEFAULT '',
			entity_id    BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			action       VARCHAR(50)         NOT NULL DEFAULT 'sync',
			direction    VARCHAR(20)         NOT NULL DEFAULT 'wc_to_zoho',
			status       VARCHAR(20)         NOT NULL DEFAULT 'pending',
			attempts     TINYINT(3) UNSIGNED NOT NULL DEFAULT 0,
			max_attempts TINYINT(3) UNSIGNED NOT NULL DEFAULT 3,
			payload      LONGTEXT                     DEFAULT NULL,
			error        TEXT                         DEFAULT NULL,
			scheduled_at DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
			processed_at DATETIME                     DEFAULT NULL,
			created_at   DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY status_scheduled (status, scheduled_at),
			KEY entity (entity_type, entity_id)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql_logs );
		dbDelta( $sql_queue );
	}

	/**
	 * Schedule recurring WP-Cron jobs.
	 */
	private static function schedule_cron_jobs(): void {
		if ( ! wp_next_scheduled( 'zoho_inventory_sync_process_queue' ) ) {
			wp_schedule_event( time(), 'zoho_every_5_minutes', 'zoho_inventory_sync_process_queue' );
		}

		if ( ! wp_next_scheduled( 'zoho_inventory_sync_poll_zoho' ) ) {
			wp_schedule_event( time(), 'hourly', 'zoho_inventory_sync_poll_zoho' );
		}

		if ( ! wp_next_scheduled( 'zoho_inventory_sync_retry_failed' ) ) {
			wp_schedule_event( time(), 'twicedaily', 'zoho_inventory_sync_retry_failed' );
		}

		// Register custom cron interval.
		add_filter( 'cron_schedules', [ __CLASS__, 'add_cron_intervals' ] );
	}

	/**
	 * Remove scheduled cron jobs.
	 */
	private static function unschedule_cron_jobs(): void {
		foreach ( [ 'zoho_inventory_sync_process_queue', 'zoho_inventory_sync_poll_zoho', 'zoho_inventory_sync_retry_failed' ] as $hook ) {
			$ts = wp_next_scheduled( $hook );
			if ( $ts ) {
				wp_unschedule_event( $ts, $hook );
			}
		}
	}

	/**
	 * Register a custom "every 5 minutes" cron interval.
	 *
	 * @param  array $schedules Existing schedules.
	 * @return array
	 */
	public static function add_cron_intervals( array $schedules ): array {
		$schedules['zoho_every_5_minutes'] = [
			'interval' => 300,
			'display'  => __( 'Every 5 Minutes', 'zoho-inventory-sync' ),
		];
		return $schedules;
	}

	/**
	 * Run any DB upgrade migrations needed.
	 * Called on plugins_loaded if version mismatch is detected.
	 */
	public static function maybe_upgrade(): void {
		$installed = get_option( 'zoho_inventory_sync_db_version', '0' );
		if ( version_compare( $installed, self::DB_VERSION, '<' ) ) {
			self::create_tables();
			update_option( 'zoho_inventory_sync_db_version', self::DB_VERSION );
		}
	}
}
