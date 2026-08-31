<?php
/**
 * Import logger — file logs, session history, and parsed error entries.
 *
 * @package ZohoInventoryImport\Includes
 */

namespace ZohoInventoryImport\Includes;

defined( 'ABSPATH' ) || exit;

/**
 * Class Logger
 */
class Logger {

	const STATUS_SUCCESS = 'success';
	const STATUS_ERROR   = 'error';
	const STATUS_INFO    = 'info';
	const STATUS_WARNING = 'warning';

	const HISTORY_OPTION = 'zoho_inv_import_history';
	const MAX_HISTORY    = 50;

	/** @var string Import session identifier. */
	private string $session_id = '';

	/** @var string Absolute path to the log file. */
	private string $log_file = '';

	/**
	 * Start a new import logging session.
	 *
	 * @param string $session_id Unique session ID.
	 */
	public function start_session( string $session_id ): void {
		$this->session_id = sanitize_file_name( $session_id );
		$this->log_file   = $this->get_session_log_path( $this->session_id );

		$this->write_line( self::STATUS_INFO, 'Import session started.' );

		$this->register_session(
			$this->session_id,
			[
				'started_at' => time(),
				'status'     => 'running',
			]
		);
	}

	/**
	 * @return string
	 */
	public function get_session_id(): string {
		return $this->session_id;
	}

	/**
	 * @return string
	 */
	public function get_log_file_path(): string {
		return $this->log_file;
	}

	/**
	 * @param int    $row_number CSV row number.
	 * @param string $message    Error message.
	 * @param array  $context    Optional context.
	 */
	public function log_error( int $row_number, string $message, array $context = [] ): void {
		$line = sprintf( 'Row %d: %s', $row_number, $message );

		if ( $context ) {
			$line .= ' | ' . wp_json_encode( $context );
		}

		$this->write_line( self::STATUS_ERROR, $line );
	}

	/**
	 * Log a system-level error (upload, AJAX, connection).
	 *
	 * @param string $message Error message.
	 * @param array  $context Optional context.
	 */
	public function log_system_error( string $message, array $context = [] ): void {
		$line = $message;
		if ( $context ) {
			$line .= ' | ' . wp_json_encode( $context );
		}
		$this->write_line( self::STATUS_ERROR, $line );
	}

	/**
	 * @param int    $row_number Row number.
	 * @param string $message    Success message.
	 */
	public function log_success( int $row_number, string $message ): void {
		$this->write_line( self::STATUS_SUCCESS, sprintf( 'Row %d: %s', $row_number, $message ) );
	}

	/**
	 * @param string $message Info message.
	 */
	public function log_info( string $message ): void {
		$this->write_line( self::STATUS_INFO, $message );
	}

	/**
	 * @param string $message Warning message.
	 */
	public function log_warning( string $message ): void {
		$this->write_line( self::STATUS_WARNING, $message );
	}

	/**
	 * Mark session complete and store summary stats.
	 *
	 * @param string               $session_id Session ID.
	 * @param array<string,mixed>  $summary    Import summary.
	 */
	public function finalize_session( string $session_id, array $summary ): void {
		$this->attach_session( $session_id );

		$status = (int) ( $summary['failed'] ?? 0 ) > 0 ? 'completed_with_errors' : 'completed';

		$this->register_session(
			$session_id,
			array_merge(
				$summary,
				[
					'finished_at' => time(),
					'status'      => $status,
					'log_file'    => $this->log_file,
				]
			)
		);
	}

	/**
	 * @return string
	 */
	public function get_log_contents(): string {
		if ( '' === $this->log_file || ! file_exists( $this->log_file ) ) {
			return '';
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		return (string) file_get_contents( $this->log_file );
	}

	/**
	 * Parse the current log file into structured entries.
	 *
	 * @return array<int,array{time:string,status:string,message:string}>
	 */
	public function get_parsed_entries(): array {
		$contents = $this->get_log_contents();
		if ( '' === $contents ) {
			return [];
		}

		$entries = [];
		$lines   = preg_split( '/\r\n|\r|\n/', trim( $contents ) ) ?: [];

		foreach ( $lines as $line ) {
			if ( preg_match( '/^\[([^\]]+)\] \[([^\]]+)\] (.+)$/', $line, $matches ) ) {
				$entries[] = [
					'time'    => $matches[1],
					'status'  => strtolower( $matches[2] ),
					'message' => $matches[3],
				];
			}
		}

		return $entries;
	}

	/**
	 * @return array<int,array{time:string,status:string,message:string}>
	 */
	public function get_error_entries(): array {
		return array_values(
			array_filter(
				$this->get_parsed_entries(),
				static function ( array $entry ): bool {
					return self::STATUS_ERROR === $entry['status'] || self::STATUS_WARNING === $entry['status'];
				}
			)
		);
	}

	/**
	 * @param int $limit Max entries.
	 * @return array<int,array{time:string,status:string,message:string}>
	 */
	public function get_recent_entries( int $limit = 20 ): array {
		$entries = $this->get_parsed_entries();
		return array_slice( $entries, -$limit );
	}

	/**
	 * Attach to an existing session log file.
	 *
	 * @param string $session_id Session ID.
	 */
	public function attach_session( string $session_id ): void {
		$this->session_id = sanitize_file_name( $session_id );
		$this->log_file   = $this->get_session_log_path( $this->session_id );
	}

	/**
	 * Return import session history (newest first).
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public function get_import_history(): array {
		$history = get_option( self::HISTORY_OPTION, [] );
		if ( ! is_array( $history ) ) {
			return [];
		}

		uasort(
			$history,
			static function ( array $a, array $b ): int {
				return (int) ( $b['started_at'] ?? 0 ) <=> (int) ( $a['started_at'] ?? 0 );
			}
		);

		return $history;
	}

	/**
	 * Delete a session log file and remove from history.
	 *
	 * @param string $session_id Session ID.
	 * @return bool
	 */
	public function delete_session( string $session_id ): bool {
		$session_id = sanitize_file_name( $session_id );
		$path       = $this->get_session_log_path( $session_id );

		if ( file_exists( $path ) ) {
			wp_delete_file( $path );
		}

		$history = get_option( self::HISTORY_OPTION, [] );
		if ( is_array( $history ) && isset( $history[ $session_id ] ) ) {
			unset( $history[ $session_id ] );
			update_option( self::HISTORY_OPTION, $history, false );
		}

		return true;
	}

	/**
	 * @param string              $session_id Session ID.
	 * @param array<string,mixed> $data       Session metadata.
	 */
	private function register_session( string $session_id, array $data ): void {
		$history = get_option( self::HISTORY_OPTION, [] );
		if ( ! is_array( $history ) ) {
			$history = [];
		}

		$session_id = sanitize_file_name( $session_id );
		$existing   = $history[ $session_id ] ?? [];

		$history[ $session_id ] = array_merge( $existing, $data );

		if ( count( $history ) > self::MAX_HISTORY ) {
			$history = array_slice( $history, 0, self::MAX_HISTORY, true );
		}

		update_option( self::HISTORY_OPTION, $history, false );
	}

	/**
	 * @param string $session_id Session ID.
	 * @return string
	 */
	private function get_session_log_path( string $session_id ): string {
		return $this->get_log_directory() . '/import-' . sanitize_file_name( $session_id ) . '.log';
	}

	/**
	 * @return string
	 */
	private function get_log_directory(): string {
		$upload_dir = wp_upload_dir();
		$dir        = trailingslashit( $upload_dir['basedir'] ) . 'zoho-inventory-import/logs';

		if ( ! file_exists( $dir ) ) {
			wp_mkdir_p( $dir );
		}

		$htaccess = $dir . '/.htaccess';
		if ( ! file_exists( $htaccess ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			file_put_contents( $htaccess, "Deny from all\n" );
		}

		return $dir;
	}

	/**
	 * @param string $status  Log level.
	 * @param string $message Message text.
	 */
	private function write_line( string $status, string $message ): void {
		if ( '' === $this->log_file ) {
			return;
		}

		$line = sprintf(
			"[%s] [%s] %s\n",
			gmdate( 'Y-m-d H:i:s' ),
			strtoupper( $status ),
			$message
		);

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		file_put_contents( $this->log_file, $line, FILE_APPEND | LOCK_EX );
	}
}
