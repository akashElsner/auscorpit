<?php
/**
 * Cross-process sync run lock.
 *
 * Prevents the cron-triggered full sync, the "Run Now" form, the AJAX "Run
 * Complete Sync" button, and the five standalone per-stage AJAX buttons from
 * ever running concurrently against the same Ingram/Zoho catalog — without
 * this, overlapping runs each independently re-crawl the full catalog and
 * double (or worse) the day's API call volume.
 *
 * @package IngramSync
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Ingram_Sync_Lock
 */
class Ingram_Sync_Lock {

	const OPTION = 'ingram_sync_run_lock';

	/**
	 * A lock untouched for this long is treated as abandoned (crashed/killed
	 * process) and is auto-cleared on the next acquire attempt. Chunked AJAX
	 * batches and cron stage boundaries all heartbeat well under this.
	 */
	const STALE_SECONDS = 300;

	/**
	 * Try to acquire the lock.
	 *
	 * Uses add_option()'s atomicity (it fails if the option row already exists)
	 * for genuine cross-process mutual exclusion — a check-then-set with
	 * update_option() would not be safe against two processes racing.
	 *
	 * @param string $context Human-readable label for what's running (e.g. 'full_sync', 'download_catalog').
	 * @return string|false Lock token on success, false if busy or the race was lost.
	 */
	public static function acquire( $context ) {
		$existing = get_option( self::OPTION, null );

		if ( is_array( $existing ) && ! self::is_stale( $existing ) ) {
			return false;
		}

		if ( is_array( $existing ) ) {
			Ingram_Sync_Logger::log(
				'Lock',
				sprintf(
					'Auto-clearing stale lock (context=%s, age=%ds) before starting "%s".',
					$existing['context'] ?? 'unknown',
					time() - (int) ( $existing['heartbeat'] ?? $existing['started_at'] ?? time() ),
					$context
				),
				Ingram_Sync_Logger::LEVEL_WARNING
			);
			delete_option( self::OPTION );
		}

		$token = wp_generate_password( 20, false );
		$added = add_option(
			self::OPTION,
			array(
				'token'      => $token,
				'context'    => (string) $context,
				'started_at' => time(),
				'heartbeat'  => time(),
			),
			'',
			'no'
		);

		// add_option() returns false if another process won the race and inserted first.
		return $added ? $token : false;
	}

	/**
	 * Re-stamp the lock's heartbeat so a long-running process doesn't self-expire.
	 *
	 * @param string $token Token returned by acquire().
	 * @return bool False if this process no longer owns the lock (stolen/expired/released) — caller should stop.
	 */
	public static function heartbeat( $token ) {
		$lock = get_option( self::OPTION, null );

		if ( ! is_array( $lock ) || ( $lock['token'] ?? '' ) !== $token ) {
			return false;
		}

		$lock['heartbeat'] = time();
		update_option( self::OPTION, $lock );
		return true;
	}

	/**
	 * Release the lock, only if the given token is the current owner.
	 *
	 * @param string $token Token returned by acquire().
	 * @return bool
	 */
	public static function release( $token ) {
		$lock = get_option( self::OPTION, null );

		if ( ! is_array( $lock ) || ( $lock['token'] ?? '' ) !== $token ) {
			return false;
		}

		return delete_option( self::OPTION );
	}

	/**
	 * Unconditionally clear the lock (admin "Clear Stuck Lock" action).
	 *
	 * @return array{had_lock: bool, context: string, age: int}
	 */
	public static function force_release() {
		$lock = get_option( self::OPTION, null );

		if ( ! is_array( $lock ) ) {
			return array( 'had_lock' => false, 'context' => '', 'age' => 0 );
		}

		$age = time() - (int) ( $lock['started_at'] ?? time() );
		delete_option( self::OPTION );

		Ingram_Sync_Logger::log(
			'Lock',
			sprintf( 'Lock force-cleared by an administrator (context=%s, age=%ds).', $lock['context'] ?? 'unknown', $age ),
			Ingram_Sync_Logger::LEVEL_WARNING
		);

		return array(
			'had_lock' => true,
			'context'  => (string) ( $lock['context'] ?? '' ),
			'age'      => $age,
		);
	}

	/**
	 * Current lock status, for the dashboard.
	 *
	 * @return array{locked: bool, context: string, started_at: int, heartbeat: int, age: int, stale: bool}
	 */
	public static function status() {
		$lock = get_option( self::OPTION, null );

		if ( ! is_array( $lock ) ) {
			return array(
				'locked'     => false,
				'context'    => '',
				'started_at' => 0,
				'heartbeat'  => 0,
				'age'        => 0,
				'stale'      => false,
			);
		}

		return array(
			'locked'     => true,
			'context'    => (string) ( $lock['context'] ?? '' ),
			'started_at' => (int) ( $lock['started_at'] ?? 0 ),
			'heartbeat'  => (int) ( $lock['heartbeat'] ?? 0 ),
			'age'        => time() - (int) ( $lock['started_at'] ?? time() ),
			'stale'      => self::is_stale( $lock ),
		);
	}

	/**
	 * Whether a lock record is old enough to be treated as abandoned.
	 *
	 * @param array<string, mixed> $lock Lock record.
	 * @return bool
	 */
	private static function is_stale( array $lock ) {
		$last_seen = (int) ( $lock['heartbeat'] ?? $lock['started_at'] ?? 0 );
		return ( time() - $last_seen ) > self::STALE_SECONDS;
	}
}