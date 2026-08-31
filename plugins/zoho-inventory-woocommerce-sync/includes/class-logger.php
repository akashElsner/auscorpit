<?php
/**
 * Logging service — writes to the database and optionally to a log file.
 *
 * @package ZohoInventorySync\Includes
 */

namespace ZohoInventorySync\Includes;

defined( 'ABSPATH' ) || exit;

/**
 * Class Logger
 */
class Logger {

	const STATUS_SUCCESS = 'success';
	const STATUS_ERROR   = 'error';
	const STATUS_INFO    = 'info';
	const STATUS_WARNING = 'warning';

	/** @var bool Whether debug file logging is enabled. */
	private bool $debug;

	/** @var string Absolute path to the log file (when file logging is on). */
	private string $log_file;

	public function __construct() {
		$settings       = get_option( 'zoho_inventory_sync_settings', [] );
		$this->debug    = ! empty( $settings['debug_mode'] );
		$this->log_file = WP_CONTENT_DIR . '/zoho-inventory-sync.log';
	}

	/**
	 * Log a successful sync operation.
	 *
	 * @param string $entity_type  e.g. 'customer', 'product', 'order'.
	 * @param int    $entity_id    WooCommerce entity ID.
	 * @param string $action       e.g. 'create', 'update'.
	 * @param string $message      Human-readable message.
	 * @param string $zoho_id      Zoho entity ID if available.
	 */
	public function success( string $entity_type, int $entity_id, string $action, string $message = '', string $zoho_id = '' ): void {
		$this->log( $entity_type, $entity_id, $action, self::STATUS_SUCCESS, $message, $zoho_id );
	}

	/**
	 * Log a sync error.
	 *
	 * @param string $entity_type
	 * @param int    $entity_id
	 * @param string $action
	 * @param string $message
	 */
	public function error( string $entity_type, int $entity_id, string $action, string $message = '' ): void {
		$this->log( $entity_type, $entity_id, $action, self::STATUS_ERROR, $message );
	}

	/**
	 * Log an informational entry.
	 *
	 * @param string $message
	 * @param array  $context  Key-value context for the message.
	 */
	public function info( string $message, array $context = [] ): void {
		$full = $context ? $message . ' | ' . wp_json_encode( $context ) : $message;
		$this->log( 'system', 0, 'info', self::STATUS_INFO, $full );
	}

	/**
	 * Log a warning.
	 *
	 * @param string $message
	 * @param array  $context
	 */
	public function warning( string $message, array $context = [] ): void {
		$full = $context ? $message . ' | ' . wp_json_encode( $context ) : $message;
		$this->log( 'system', 0, 'warning', self::STATUS_WARNING, $full );
	}

	/**
	 * Core log method — insert a row into wp_zoho_inv_logs.
	 */
	private function log(
		string $entity_type,
		int $entity_id,
		string $action,
		string $status,
		string $message = '',
		string $zoho_id = ''
	): void {
		global $wpdb;

		$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prefix . 'zoho_inv_logs',
			[
				'entity_type' => $entity_type,
				'entity_id'   => $entity_id,
				'action'      => $action,
				'status'      => $status,
				'message'     => $message,
				'zoho_id'     => $zoho_id,
				'created_at'  => current_time( 'mysql' ),
			],
			[ '%s', '%d', '%s', '%s', '%s', '%s', '%s' ]
		);

		if ( $this->debug ) {
			$this->write_to_file( $entity_type, $entity_id, $action, $status, $message );
		}
	}

	/**
	 * Append an entry to the log file.
	 */
	private function write_to_file( string $entity_type, int $entity_id, string $action, string $status, string $message ): void {
		$line = sprintf(
			"[%s] [%s] %s #%d | %s | %s\n",
			current_time( 'Y-m-d H:i:s' ),
			strtoupper( $status ),
			$entity_type,
			$entity_id,
			$action,
			$message
		);

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		file_put_contents( $this->log_file, $line, FILE_APPEND | LOCK_EX );
	}

	/**
	 * Retrieve log entries from the database.
	 *
	 * @param  array $args {
	 *     Optional query args.
	 *     @type string $entity_type  Filter by entity type.
	 *     @type int    $entity_id    Filter by entity ID.
	 *     @type string $status       Filter by status.
	 *     @type int    $limit        Number of rows (default 50).
	 *     @type int    $offset       Row offset (default 0).
	 * }
	 * @return array
	 */
	public function get_logs( array $args = [] ): array {
		global $wpdb;

		$limit  = isset( $args['limit'] )  ? (int) $args['limit']  : 50;
		$offset = isset( $args['offset'] ) ? (int) $args['offset'] : 0;

		$where  = '1=1';
		$values = [];

		if ( ! empty( $args['entity_type'] ) ) {
			$where   .= ' AND entity_type = %s';
			$values[] = $args['entity_type'];
		}

		if ( ! empty( $args['entity_id'] ) ) {
			$where   .= ' AND entity_id = %d';
			$values[] = (int) $args['entity_id'];
		}

		if ( ! empty( $args['status'] ) ) {
			$where   .= ' AND status = %s';
			$values[] = $args['status'];
		}

		$table = $wpdb->prefix . 'zoho_inv_logs';

		if ( $values ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$query = $wpdb->prepare( "SELECT * FROM {$table} WHERE {$where} ORDER BY created_at DESC LIMIT %d OFFSET %d", array_merge( $values, [ $limit, $offset ] ) );
		} else {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$query = $wpdb->prepare( "SELECT * FROM {$table} ORDER BY created_at DESC LIMIT %d OFFSET %d", $limit, $offset );
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery
		return $wpdb->get_results( $query, ARRAY_A ) ?: [];
	}

	/**
	 * Purge log entries older than the given number of days.
	 *
	 * @param int $days
	 */
	public function purge_old_logs( int $days = 30 ): void {
		global $wpdb;

		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->prepare(
				"DELETE FROM {$wpdb->prefix}zoho_inv_logs WHERE created_at < %s",
				gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) )
			)
		);
	}
}
