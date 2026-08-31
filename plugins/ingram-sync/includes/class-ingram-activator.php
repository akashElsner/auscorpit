<?php
/**
 * Plugin activation.
 *
 * @package IngramSync
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Ingram_Sync_Activator
 */
class Ingram_Sync_Activator {

	/**
	 * Run on plugin activation.
	 */
	public static function activate() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		Ingram_Sync_Migrations::maybe_run();
		Ingram_Sync_Settings::set_defaults();

		if ( ! wp_next_scheduled( 'ingram_sync_cron' ) ) {
			$enabled   = Ingram_Sync_Settings::get( 'scheduler_enabled', 'no' );
			$frequency = Ingram_Sync_Settings::get( 'scheduler_frequency', 'daily' );
			$time      = Ingram_Sync_Settings::get( 'scheduler_time', '02:00' );

			if ( 'yes' === $enabled ) {
				Ingram_Sync_Scheduler::schedule( $frequency, $time );
			}
		}

		flush_rewrite_rules();
	}
}
