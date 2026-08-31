<?php
/**
 * Versioned schema/data migrations.
 *
 * Previously, create_tables() only ever ran from the plugin activation hook —
 * an in-place file update (SFTP, auto-update) with no deactivate/reactivate
 * never applied schema changes, and the ingram_sync_db_version option was
 * written but never compared against anything. Hooking maybe_run() on
 * plugins_loaded makes the very first admin page load after a deploy
 * self-heal the schema instead.
 *
 * @package IngramSync
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Ingram_Sync_Migrations
 */
class Ingram_Sync_Migrations {

	const DB_VERSION_OPTION = 'ingram_sync_db_version';

	/**
	 * Run any pending migrations. Safe to call on every request — it is a
	 * no-op once the stored version has caught up with INGRAM_SYNC_VERSION.
	 */
	public static function maybe_run() {
		$current = get_option( self::DB_VERSION_OPTION, '0' );

		if ( version_compare( $current, INGRAM_SYNC_VERSION, '>=' ) ) {
			return;
		}

		Ingram_Sync_Logger::log(
			'Migrations',
			sprintf( 'Migrating database from %s to %s…', $current, INGRAM_SYNC_VERSION ),
			Ingram_Sync_Logger::LEVEL_INFO
		);

		// dbDelta is additive/idempotent — safe to re-run on every version bump,
		// so schema changes always apply here regardless of which version a site
		// is upgrading from.
		Ingram_Sync_Database::create_tables();

		if ( version_compare( $current, '1.1.0', '<' ) ) {
			self::migrate_to_1_1_0();
		}

		update_option( self::DB_VERSION_OPTION, INGRAM_SYNC_VERSION );

		Ingram_Sync_Logger::log( 'Migrations', 'Migration complete.', Ingram_Sync_Logger::LEVEL_SUCCESS );
	}

	/**
	 * 1.1.0 — drop the dead queue table (its "Retry Failed"/get_pending_batch()
	 * plumbing had zero real callers; products.sync_status is the actual retry
	 * source of truth), and force off wc_delete_missing, which used to be an
	 * inert checkbox and now performs real WooCommerce product deletions —
	 * nobody's site should start deleting products because a previously
	 * no-op setting happened to already be checked.
	 */
	private static function migrate_to_1_1_0() {
		global $wpdb;

		$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'ingram_sync_queue' ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		if ( 'yes' === get_option( 'ingram_wc_delete_missing' ) ) {
			update_option( 'ingram_wc_delete_missing', 'no' );
			Ingram_Sync_Logger::log(
				'Migrations',
				'"Delete Missing Products" was on but had never been wired to real deletion logic — turned off during upgrade. Re-enable it in Settings -> WooCommerce if you want it now that it actually deletes products.',
				Ingram_Sync_Logger::LEVEL_WARNING
			);
		}
	}
}