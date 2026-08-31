<?php
/**
 * Logging system.
 *
 * @package IngramSync
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Ingram_Sync_Logger
 */
class Ingram_Sync_Logger {

	const LEVEL_INFO    = 'info';
	const LEVEL_SUCCESS = 'success';
	const LEVEL_WARNING = 'warning';
	const LEVEL_ERROR   = 'error';

	/**
	 * Setting keys whose live decrypted value should never appear verbatim in a
	 * log message string — sanitize_context() only catches secrets passed via
	 * the $context array; this catches one interpolated directly into $message
	 * (e.g. a caller building "OAuth failed: {$token}").
	 *
	 * @var string[]
	 */
	private static $secret_setting_keys = array(
		'access_token',
		'client_secret',
		'zoho_client_secret',
		'zoho_refresh_token',
		'zoho_access_token',
	);

	/**
	 * Log a message.
	 *
	 * @param string               $source  API or component source.
	 * @param string               $message Log message.
	 * @param string               $level   Log level.
	 * @param array<string, mixed> $context Additional context.
	 */
	public static function log( $source, $message, $level = self::LEVEL_INFO, $context = array() ) {
		global $wpdb;

		$table = Ingram_Sync_Database::logs_table();

		// Strip sensitive data from context and from the message string itself.
		$context = self::sanitize_context( $context );
		$message = self::redact_secrets( (string) $message );

		$wpdb->insert(
			$table,
			array(
				'level'   => sanitize_text_field( $level ),
				'source'  => sanitize_text_field( $source ),
				// Keep punctuation/SKU details intact for debugging (sanitize_text_field strips too much).
				'message' => mb_substr( wp_strip_all_tags( $message ), 0, 1000 ),
				'context' => wp_json_encode( $context ),
			),
			array( '%s', '%s', '%s', '%s' )
		);

		// Keep log table manageable (last 10,000 entries).
		$count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( $count > 10000 ) {
			$wpdb->query( "DELETE FROM {$table} ORDER BY id ASC LIMIT 1000" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}
	}

	/**
	 * Remove sensitive keys from context, recursing into nested arrays.
	 *
	 * @param mixed $context Context array (or scalar, passed through unchanged).
	 * @return mixed
	 */
	private static function sanitize_context( $context ) {
		if ( ! is_array( $context ) ) {
			return $context;
		}

		$sensitive = array( 'access_token', 'client_secret', 'refresh_token', 'authorization', 'password' );

		foreach ( $context as $key => $value ) {
			if ( is_array( $value ) ) {
				$context[ $key ] = self::sanitize_context( $value );
				continue;
			}
			if ( is_string( $key ) && in_array( strtolower( $key ), $sensitive, true ) ) {
				$context[ $key ] = '[REDACTED]';
			}
		}

		return $context;
	}

	/**
	 * Replace any live secret value that ended up interpolated directly into a
	 * log message string.
	 *
	 * @param string $message Raw message.
	 * @return string
	 */
	private static function redact_secrets( $message ) {
		if ( '' === $message ) {
			return $message;
		}

		foreach ( self::$secret_setting_keys as $key ) {
			$value = Ingram_Sync_Settings::get( $key );
			if ( is_string( $value ) && strlen( $value ) >= 8 && false !== strpos( $message, $value ) ) {
				$message = str_replace( $value, '[REDACTED]', $message );
			}
		}

		return $message;
	}

	/**
	 * Get recent logs.
	 *
	 * @param int    $limit  Number of logs.
	 * @param string $source Filter by source.
	 * @return array<object>
	 */
	public static function get_logs( $limit = 100, $source = '' ) {
		global $wpdb;

		$table = Ingram_Sync_Database::logs_table();
		$limit = absint( $limit );

		if ( $source ) {
			return $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$table} WHERE source = %s ORDER BY id DESC LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$source,
					$limit
				)
			);
		}

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} ORDER BY id DESC LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$limit
			)
		);
	}

	/**
	 * Clear all logs.
	 */
	public static function clear() {
		global $wpdb;
		$wpdb->query( 'TRUNCATE TABLE ' . Ingram_Sync_Database::logs_table() ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Get log count by level.
	 *
	 * @param string $level Log level.
	 * @return int
	 */
	public static function count_by_level( $level ) {
		global $wpdb;
		$table = Ingram_Sync_Database::logs_table();
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE level = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$level
			)
		);
	}
}
