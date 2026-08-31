<?php
/**
 * Cron scheduler for automatic sync.
 *
 * @package IngramSync
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Ingram_Sync_Scheduler
 */
class Ingram_Sync_Scheduler {

	const CRON_HOOK = 'ingram_sync_cron';

	/**
	 * Initialize scheduler hooks.
	 */
	public static function init() {
		add_action( self::CRON_HOOK, array( __CLASS__, 'run_scheduled_sync' ) );
		add_filter( 'cron_schedules', array( __CLASS__, 'add_cron_schedules' ) );
	}

	/**
	 * Add custom cron schedules.
	 *
	 * @param array<string, array> $schedules Existing schedules.
	 * @return array<string, array>
	 */
	public static function add_cron_schedules( $schedules ) {
		$schedules['ingram_every_6_hours'] = array(
			'interval' => 6 * HOUR_IN_SECONDS,
			'display'  => __( 'Every 6 Hours', 'ingram-sync' ),
		);
		$schedules['ingram_every_12_hours'] = array(
			'interval' => 12 * HOUR_IN_SECONDS,
			'display'  => __( 'Every 12 Hours', 'ingram-sync' ),
		);
		return $schedules;
	}

	/**
	 * Map frequency setting to WP cron schedule.
	 *
	 * @param string $frequency Frequency key.
	 * @return string
	 */
	private static function get_schedule_name( $frequency ) {
		$map = array(
			'hourly'         => 'hourly',
			'every_6_hours'  => 'ingram_every_6_hours',
			'every_12_hours' => 'ingram_every_12_hours',
			'daily'          => 'daily',
			'weekly'         => 'weekly',
		);
		return $map[ $frequency ] ?? 'daily';
	}

	/**
	 * Schedule cron event.
	 *
	 * @param string $frequency Frequency key.
	 * @param string $time      Time in HH:MM format.
	 */
	public static function schedule( $frequency = 'daily', $time = '02:00' ) {
		self::unschedule();

		$schedule = self::get_schedule_name( $frequency );
		$timestamp = self::get_next_run_timestamp( $time );

		wp_schedule_event( $timestamp, $schedule, self::CRON_HOOK );
		Ingram_Sync_Settings::update( 'scheduler_running', 'yes' );

		Ingram_Sync_Logger::log( 'Scheduler', 'Scheduled sync: ' . $frequency . ' at ' . $time, Ingram_Sync_Logger::LEVEL_INFO );
	}

	/**
	 * Unschedule cron event.
	 */
	public static function unschedule() {
		$timestamp = wp_next_scheduled( self::CRON_HOOK );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, self::CRON_HOOK );
		}
		wp_clear_scheduled_hook( self::CRON_HOOK );
		Ingram_Sync_Settings::update( 'scheduler_running', 'no' );
	}

	/**
	 * Calculate next run timestamp from time string.
	 *
	 * @param string $time HH:MM.
	 * @return int
	 */
	private static function get_next_run_timestamp( $time ) {
		$parts = explode( ':', $time );
		$hour  = isset( $parts[0] ) ? (int) $parts[0] : 2;
		$min   = isset( $parts[1] ) ? (int) $parts[1] : 0;

		// Build the target time in the site's configured timezone, then convert
		// to a true UTC timestamp — wp_schedule_event() expects real UTC.
		$timezone  = wp_timezone();
		$now       = new DateTime( 'now', $timezone );
		$scheduled = new DateTime( 'now', $timezone );
		$scheduled->setTime( $hour, $min, 0 );

		if ( $scheduled <= $now ) {
			$scheduled->modify( '+1 day' );
		}

		return $scheduled->getTimestamp();
	}

	/**
	 * Run scheduled sync.
	 */
	public static function run_scheduled_sync() {
		if ( 'yes' !== Ingram_Sync_Settings::get( 'scheduler_enabled', 'no' ) ) {
			return;
		}

		// Cooldown gate — cron only. "Run Now" and the AJAX buttons call
		// Ingram_Sync_Manager::run_complete_sync() directly and are never subject
		// to this check, so a human can always force an immediate run.
		$min_interval_hours = (int) Ingram_Sync_Settings::get( 'min_sync_interval_hours', 4 );
		$last_completed     = (int) Ingram_Sync_Settings::get( 'last_full_sync_completed', 0 );
		if ( $min_interval_hours > 0 && $last_completed > 0 ) {
			$elapsed = time() - $last_completed;
			if ( $elapsed < ( $min_interval_hours * HOUR_IN_SECONDS ) ) {
				Ingram_Sync_Logger::log(
					'Scheduler',
					sprintf(
						'Skipped — last full sync completed %s ago (minimum interval is %d hour(s)).',
						human_time_diff( $last_completed ),
						$min_interval_hours
					),
					Ingram_Sync_Logger::LEVEL_INFO
				);
				return;
			}
		}

		Ingram_Sync_Logger::log( 'Scheduler', 'Automatic sync started', Ingram_Sync_Logger::LEVEL_INFO );

		$manager = new Ingram_Sync_Manager();
		$manager->run_complete_sync();
	}

	/**
	 * Get next scheduled run info.
	 *
	 * @return array{timestamp: int, formatted: string}
	 */
	public static function get_next_run() {
		$timestamp = wp_next_scheduled( self::CRON_HOOK );
		return array(
			'timestamp' => $timestamp ?: 0,
			'formatted' => $timestamp ? wp_date( 'd-M-Y h:i A', $timestamp ) : __( 'Not scheduled', 'ingram-sync' ),
		);
	}

	/**
	 * Run sync immediately (manual trigger).
	 *
	 * @return array{success: bool, message: string}
	 */
	public static function run_now() {
		if ( ! Ingram_Sync_Security::current_user_can_manage() ) {
			return array( 'success' => false, 'message' => __( 'Permission denied.', 'ingram-sync' ) );
		}

		Ingram_Sync_Logger::log( 'Scheduler', 'Manual sync triggered', Ingram_Sync_Logger::LEVEL_INFO );

		$manager = new Ingram_Sync_Manager();
		$result  = $manager->run_complete_sync();

		return array(
			'success' => $result['success'],
			'message' => $result['message'],
		);
	}
}
