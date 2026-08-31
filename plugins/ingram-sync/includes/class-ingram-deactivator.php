<?php
/**
 * Plugin deactivation.
 *
 * @package IngramSync
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Ingram_Sync_Deactivator
 */
class Ingram_Sync_Deactivator {

	/**
	 * Run on plugin deactivation.
	 */
	public static function deactivate() {
		Ingram_Sync_Scheduler::unschedule();
		flush_rewrite_rules();
	}
}
