<?php
/**
 * Queue-based sync processing with retry support.
 *
 * Items are inserted into wp_zoho_inv_queue and processed
 * by a WP-Cron job every five minutes.
 *
 * @package ZohoInventorySync\Includes
 */

namespace ZohoInventorySync\Includes;

defined( 'ABSPATH' ) || exit;

/**
 * Class Sync_Queue
 */
class Sync_Queue {

	const STATUS_PENDING    = 'pending';
	const STATUS_PROCESSING = 'processing';
	const STATUS_COMPLETED  = 'completed';
	const STATUS_FAILED     = 'failed';

	const DIRECTION_WC_TO_ZOHO   = 'wc_to_zoho';
	const DIRECTION_ZOHO_TO_WC   = 'zoho_to_wc';

	/** @var Logger */
	private Logger $logger;

	/** Max items to process in a single cron run. */
	private int $batch_size = 50;

	public function __construct( Logger $logger ) {
		$this->logger = $logger;
		$settings     = get_option( 'zoho_inventory_sync_settings', [] );
		if ( ! empty( $settings['batch_size'] ) ) {
			$this->batch_size = (int) $settings['batch_size'];
		}
	}

	/**
	 * Add a sync task to the queue.
	 *
	 * If an identical pending/processing item already exists it is skipped
	 * to avoid duplicates.
	 *
	 * @param  string $entity_type  e.g. 'customer', 'product', 'order'.
	 * @param  int    $entity_id    WooCommerce entity ID.
	 * @param  string $action       e.g. 'create', 'update', 'sync'.
	 * @param  string $direction    DIRECTION_WC_TO_ZOHO or DIRECTION_ZOHO_TO_WC.
	 * @param  array  $payload      Optional extra data serialised in the queue row.
	 * @param  int    $delay        Delay in seconds before the item should be processed.
	 * @return int|false            Inserted row ID or false on failure.
	 */
	public function enqueue(
		string $entity_type,
		int $entity_id,
		string $action = 'sync',
		string $direction = self::DIRECTION_WC_TO_ZOHO,
		array $payload = [],
		int $delay = 0
	) {
		global $wpdb;

		// De-duplicate: don't queue the same pending item twice.
		$existing = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				"SELECT id FROM {$wpdb->prefix}zoho_inv_queue
				 WHERE entity_type = %s AND entity_id = %d AND action = %s AND status IN ('pending','processing')
				 LIMIT 1",
				$entity_type,
				$entity_id,
				$action
			)
		);

		if ( $existing ) {
			return (int) $existing;
		}

		$scheduled_at = $delay > 0
			? gmdate( 'Y-m-d H:i:s', time() + $delay )
			: current_time( 'mysql' );

		$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prefix . 'zoho_inv_queue',
			[
				'entity_type'  => $entity_type,
				'entity_id'    => $entity_id,
				'action'       => $action,
				'direction'    => $direction,
				'status'       => self::STATUS_PENDING,
				'attempts'     => 0,
				'max_attempts' => 3,
				'payload'      => ! empty( $payload ) ? wp_json_encode( $payload ) : null,
				'scheduled_at' => $scheduled_at,
				'created_at'   => current_time( 'mysql' ),
			],
			[ '%s', '%d', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s' ]
		);

		$insert_id = $wpdb->insert_id ?: false;

		return $insert_id ?: false;
	}

	/**
	 * Process the next batch of pending queue items.
	 *
	 * Called by the zoho_inventory_sync_process_queue cron hook AND directly by
	 * late-priority WooCommerce save hooks (so items are processed in the same
	 * request that triggered them, without waiting for cron).
	 *
	 * The re-entrance guard prevents double-processing when this is hooked to
	 * multiple WC events that all fire within a single request.
	 */
	public function process(): void {
		static $running = false;
		if ( $running ) {
			return;
		}
		$running = true;
		global $wpdb;

		$items = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}zoho_inv_queue
				 WHERE status = 'pending' AND scheduled_at <= %s
				 ORDER BY scheduled_at ASC
				 LIMIT %d",
				current_time( 'mysql' ),
				$this->batch_size
			),
			ARRAY_A
		);

		if ( empty( $items ) ) {
			$running = false;
			return;
		}

		$this->logger->info( 'Queue processing started', [ 'count' => count( $items ) ] );

		foreach ( $items as $item ) {
			$this->process_item( $item );
		}

		$running = false;
	}

	/**
	 * Retry all failed items that still have remaining attempts.
	 *
	 * Called by the zoho_inventory_sync_retry_failed cron hook.
	 */
	public function retry_failed(): void {
		global $wpdb;

		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			"UPDATE {$wpdb->prefix}zoho_inv_queue
			 SET status = 'pending', scheduled_at = NOW()
			 WHERE status = 'failed' AND attempts < max_attempts"
		);
	}

	/**
	 * Mark a queue item as completed.
	 *
	 * @param int $id Queue row ID.
	 */
	public function complete( int $id ): void {
		global $wpdb;

		$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prefix . 'zoho_inv_queue',
			[
				'status'       => self::STATUS_COMPLETED,
				'processed_at' => current_time( 'mysql' ),
				'error'        => null,
			],
			[ 'id' => $id ],
			[ '%s', '%s', '%s' ],
			[ '%d' ]
		);
	}

	/**
	 * Mark a queue item as failed and increment its attempt counter.
	 *
	 * @param int    $id    Queue row ID.
	 * @param string $error Error message.
	 */
	public function fail( int $id, string $error = '' ): void {
		global $wpdb;

		$item = $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare( "SELECT * FROM {$wpdb->prefix}zoho_inv_queue WHERE id = %d", $id ),
			ARRAY_A
		);

		if ( ! $item ) {
			return;
		}

		$attempts     = (int) $item['attempts'] + 1;
		$max_attempts = (int) $item['max_attempts'];

		$status = $attempts >= $max_attempts ? self::STATUS_FAILED : self::STATUS_PENDING;

		// Exponential back-off: delay = 2^attempts minutes.
		$delay_seconds = (int) pow( 2, $attempts ) * 60;
		$scheduled_at  = $status === self::STATUS_PENDING
			? gmdate( 'Y-m-d H:i:s', time() + $delay_seconds )
			: current_time( 'mysql' );

		$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prefix . 'zoho_inv_queue',
			[
				'status'       => $status,
				'attempts'     => $attempts,
				'error'        => $error,
				'scheduled_at' => $scheduled_at,
				'processed_at' => current_time( 'mysql' ),
			],
			[ 'id' => $id ],
			[ '%s', '%d', '%s', '%s', '%s' ],
			[ '%d' ]
		);
	}

	/**
	 * Get queue statistics for the admin dashboard.
	 *
	 * @return array<string,int>
	 */
	public function get_stats(): array {
		global $wpdb;

		$rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			"SELECT status, COUNT(*) as cnt FROM {$wpdb->prefix}zoho_inv_queue GROUP BY status",
			ARRAY_A
		) ?: [];

		$stats = [
			'pending'    => 0,
			'processing' => 0,
			'completed'  => 0,
			'failed'     => 0,
		];

		foreach ( $rows as $row ) {
			$stats[ $row['status'] ] = (int) $row['cnt'];
		}

		return $stats;
	}

	// -------------------------------------------------------------------------
	// Private helpers
	// -------------------------------------------------------------------------

	/**
	 * Dispatch a single queue item to the correct sync handler.
	 *
	 * @param array $item Queue row as associative array.
	 */
	private function process_item( array $item ): void {
		global $wpdb;

		$id = (int) $item['id'];

		// Lock the row.
		$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prefix . 'zoho_inv_queue',
			[ 'status' => self::STATUS_PROCESSING ],
			[ 'id' => $id ],
			[ '%s' ],
			[ '%d' ]
		);

		$plugin = \ZohoInventorySync\Includes\Plugin::instance();

		try {
			$payload     = ! empty( $item['payload'] ) ? json_decode( $item['payload'], true ) : [];
			$entity_type = $item['entity_type'];
			$entity_id   = (int) $item['entity_id'];
			$action      = $item['action'];

			match ( $entity_type ) {
				'customer'  => $plugin->customer_sync->sync_customer( $entity_id ),
				'product'   => $plugin->product_sync->sync_product( $entity_id ),
				'order'     => match ( $action ) {
					'invoice' => $plugin->order_sync->create_invoice( $entity_id ),
					'void'    => $plugin->order_sync->void_order( $entity_id ),
					default   => $plugin->order_sync->sync_order( $entity_id ),
				},
				'inventory' => $plugin->inventory_sync->sync_inventory( $entity_id ),
				default     => throw new \RuntimeException( "Unknown entity type: {$entity_type}" ),
			};

			$this->complete( $id );
		} catch ( \Throwable $e ) {
			$this->fail( $id, $e->getMessage() );
			$this->logger->error( $item['entity_type'], (int) $item['entity_id'], $item['action'], $e->getMessage() );
		}
	}
}
