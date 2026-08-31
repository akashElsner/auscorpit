<?php
/**
 * Sync orchestration manager.
 *
 * @package IngramSync
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Ingram_Sync_Manager
 */
class Ingram_Sync_Manager {

	/**
	 * @var Ingram_Sync_Api
	 */
	private $api;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->api = new Ingram_Sync_Api();
	}

	/**
	 * Raise PHP limits for long-running sync work.
	 */
	public static function prepare_long_request() {
		if ( function_exists( 'ignore_user_abort' ) ) {
			ignore_user_abort( true );
		}
		if ( function_exists( 'wp_raise_memory_limit' ) ) {
			wp_raise_memory_limit( 'admin' );
		}
		if ( function_exists( 'set_time_limit' ) ) {
			set_time_limit( 0 );
		}
	}

	/**
	 * Run complete sync pipeline (used by cron / full server-side runs).
	 *
	 * @return array{success: bool, message: string}
	 */
	/**
	 * Record a failed sync run for the dashboard/admin notice, and optionally
	 * email the site admin — this is the only way an unattended cron failure
	 * becomes visible without someone manually checking the Logs page.
	 *
	 * @param array{message?: string} $result Failure result from a sync stage.
	 */
	private static function notify_failure( $result ) {
		$message = (string) ( $result['message'] ?? '' );

		Ingram_Sync_Settings::update(
			'last_sync_failure',
			array(
				'time'    => time(),
				'message' => $message,
			)
		);

		if ( 'yes' !== Ingram_Sync_Settings::get( 'notify_on_failure', 'no' ) ) {
			return;
		}

		wp_mail(
			get_option( 'admin_email' ),
			sprintf(
				/* translators: %s: site name */
				__( '[%s] Ingram Sync failed', 'ingram-sync' ),
				get_bloginfo( 'name' )
			),
			sprintf(
				/* translators: %s: failure message */
				__( "An Ingram Sync run failed:\n\n%s", 'ingram-sync' ),
				$message
			)
		);
	}

	public function run_complete_sync() {
		self::prepare_long_request();

		$token = Ingram_Sync_Lock::acquire( 'full_sync' );
		if ( ! $token ) {
			$status = Ingram_Sync_Lock::status();
			$message = sprintf(
				/* translators: 1: what is currently running, 2: how long ago it started */
				__( 'A sync is already in progress (%1$s, started %2$s ago). Please wait for it to finish.', 'ingram-sync' ),
				$status['context'] ?: __( 'unknown', 'ingram-sync' ),
				human_time_diff( $status['started_at'] ?: time() )
			);
			Ingram_Sync_Logger::log( 'Sync', 'Skipped — ' . $message, Ingram_Sync_Logger::LEVEL_WARNING );
			return array(
				'success' => false,
				'done'    => true,
				'message' => $message,
			);
		}

		try {
			Ingram_Sync_Settings::update( 'stats_running', (int) Ingram_Sync_Settings::get( 'stats_running', 0 ) + 1 );

			$page       = 1;
			$total      = 0;
			$downloaded = 0;
			do {
				$catalog = $this->download_catalog_page( $page, $downloaded );
				if ( ! $catalog['success'] ) {
					Ingram_Sync_Settings::update( 'stats_running', max( 0, (int) Ingram_Sync_Settings::get( 'stats_running', 0 ) - 1 ) );
					self::notify_failure( $catalog );
					return $catalog;
				}
				$downloaded = (int) ( $catalog['downloaded'] ?? ( $downloaded + (int) ( $catalog['count'] ?? 0 ) ) );
				$total      = (int) ( $catalog['total'] ?? $downloaded );
				$page       = (int) ( $catalog['next_page'] ?? ( $page + 1 ) );
			} while ( empty( $catalog['done'] ) );

			Ingram_Sync_Lock::heartbeat( $token );

			$offset = 0;
			do {
				$price = $this->download_price_batch( $offset );
				if ( ! $price['success'] ) {
					Ingram_Sync_Settings::update( 'stats_running', max( 0, (int) Ingram_Sync_Settings::get( 'stats_running', 0 ) - 1 ) );
					self::notify_failure( $price );
					return $price;
				}
				$offset = (int) ( $price['next_offset'] ?? ( $offset + 1 ) );
			} while ( empty( $price['done'] ) );

			Ingram_Sync_Lock::heartbeat( $token );

			// Guarded the same way the catalog stage caps at page < 500 — without
			// this, an unexpected pagination bug could turn the per-SKU details
			// stage into an unbounded, hours-long crawl.
			$details_guard = 0;
			do {
				$details = $this->download_details_batch();
				if ( ! $details['success'] ) {
					Ingram_Sync_Settings::update( 'stats_running', max( 0, (int) Ingram_Sync_Settings::get( 'stats_running', 0 ) - 1 ) );
					self::notify_failure( $details );
					return $details;
				}
				++$details_guard;
			} while ( empty( $details['done'] ) && $details_guard < 1000 );

			if ( $details_guard >= 1000 && empty( $details['done'] ) ) {
				Ingram_Sync_Logger::log( 'Details', 'Details stage stopped after 1000 batches without finishing — investigate a possible pagination bug.', Ingram_Sync_Logger::LEVEL_ERROR );
			}

			Ingram_Sync_Lock::heartbeat( $token );

			$wc_result   = array( 'success' => true, 'message' => 'Skipped' );
			$zoho_result = array( 'success' => true, 'message' => 'Skipped' );

			if ( 'yes' === Ingram_Sync_Settings::get( 'wc_sync_enabled' ) ) {
				$wc_sync   = new Ingram_Sync_Woocommerce_Sync();
				$wc_result = $wc_sync->sync();
			}

			Ingram_Sync_Lock::heartbeat( $token );

			if ( 'yes' === Ingram_Sync_Settings::get( 'zoho_sync_enabled' ) ) {
				$zoho_sync = new Ingram_Sync_Zoho_Sync();
				$zoho_result = $zoho_sync->sync();
			}

			Ingram_Sync_Settings::update( 'stats_running', max( 0, (int) Ingram_Sync_Settings::get( 'stats_running', 0 ) - 1 ) );
			Ingram_Sync_Settings::update( 'last_full_sync_completed', time() );
			Ingram_Sync_Settings::update( 'last_sync_failure', array() );

			Ingram_Sync_Logger::log( 'Sync', 'Complete sync finished', Ingram_Sync_Logger::LEVEL_SUCCESS );

			return array(
				'success' => true,
				'done'    => true,
				'message' => sprintf(
					/* translators: 1: catalog count, 2: WC message, 3: Zoho message */
					__( 'Sync complete. Catalog: %1$d items. WooCommerce: %2$s. Zoho: %3$s', 'ingram-sync' ),
					$downloaded,
					$wc_result['message'],
					$zoho_result['message']
				),
			);
		} finally {
			Ingram_Sync_Lock::release( $token );
		}
	}

	/**
	 * Download one catalog page (chunked for AJAX).
	 *
	 * @param int $page       Page number (1-based).
	 * @param int $downloaded Products already downloaded in this run.
	 * @return array{success: bool, message: string, count?: int, done?: bool, next_page?: int, total?: int, downloaded?: int, remaining?: int}
	 */
	public function download_catalog_page( $page = 1, $downloaded = 0 ) {
		self::prepare_long_request();

		$page       = max( 1, (int) $page );
		$downloaded = max( 0, (int) $downloaded );
		$batch_size = (int) Ingram_Sync_Settings::get( 'batch_size', 50 );
		$result     = $this->api->get_catalog( $page, $batch_size );

		// Ingram often returns HTTP 404 once pagination goes past the last page
		// (especially when the final page has exactly batch_size items).
		if ( ! $result['success'] ) {
			$status = (int) ( $result['status'] ?? 0 );
			if ( $page > 1 && $downloaded > 0 && in_array( $status, array( 404, 400 ), true ) ) {
				Ingram_Sync_Logger::log(
					'Catalog',
					sprintf( 'Page %d returned %d — treating as end of catalog (%d products saved).', $page, $status, $downloaded ),
					Ingram_Sync_Logger::LEVEL_SUCCESS
				);

				return array(
					'success'    => true,
					'done'       => true,
					'count'      => 0,
					'page'       => $page,
					'next_page'  => $page,
					'total'      => $downloaded,
					'downloaded' => $downloaded,
					'remaining'  => 0,
					'message'    => sprintf(
						/* translators: %d: downloaded product count */
						__( 'Catalog download finished. %d products saved.', 'ingram-sync' ),
						$downloaded
					),
				);
			}

			return array(
				'success'    => false,
				'done'       => true,
				'count'      => 0,
				'total'      => $downloaded,
				'downloaded' => $downloaded,
				'remaining'  => -1,
				'message'    => $result['message'],
			);
		}

		$body          = is_array( $result['body'] ) ? $result['body'] : array();
		$products      = $body['catalog'] ?? $body['products'] ?? array();
		$records_found = isset( $body['recordsFound'] ) ? (int) $body['recordsFound'] : 0;
		$count         = 0;

		foreach ( $products as $product ) {
			$part_number = $product['ingramPartNumber'] ?? $product['sku'] ?? '';
			if ( ! $part_number ) {
				continue;
			}

			self::upsert_product_cache( $part_number, $product );
			++$count;
		}

		$downloaded += $count;

		// Empty page = end of catalog.
		if ( 0 === count( $products ) ) {
			return array(
				'success'    => true,
				'done'       => true,
				'count'      => 0,
				'page'       => $page,
				'next_page'  => $page,
				'total'      => $records_found > 0 ? $records_found : $downloaded,
				'downloaded' => $downloaded,
				'remaining'  => 0,
				'message'    => sprintf(
					/* translators: %d: downloaded product count */
					__( 'Catalog download finished. %d products saved.', 'ingram-sync' ),
					$downloaded
				),
			);
		}

		if ( $records_found > 0 ) {
			$has_more  = $downloaded < $records_found && $count > 0 && $page < 500;
			// If this page returned fewer than batch size, there are no more pages.
			if ( $count < $batch_size ) {
				$has_more = false;
			}
			$remaining = max( 0, $records_found - $downloaded );
		} else {
			$has_more  = $count >= $batch_size && $page < 500;
			$remaining = $has_more ? null : 0;
		}

		Ingram_Sync_Logger::log(
			'Catalog',
			sprintf(
				'Downloaded page %d (%d products). Total progress: %d%s',
				$page,
				$count,
				$downloaded,
				$records_found > 0 ? '/' . $records_found : ''
			),
			Ingram_Sync_Logger::LEVEL_SUCCESS
		);

		if ( $has_more ) {
			$message = $records_found > 0
				? sprintf(
					/* translators: 1: page, 2: downloaded, 3: total, 4: remaining */
					__( 'Catalog page %1$d — downloaded %2$d of %3$d (%4$d remaining). Continuing…', 'ingram-sync' ),
					$page,
					$downloaded,
					$records_found,
					$remaining
				)
				: sprintf(
					/* translators: 1: page, 2: downloaded so far */
					__( 'Catalog page %1$d — downloaded %2$d so far. Continuing…', 'ingram-sync' ),
					$page,
					$downloaded
				);
		} else {
			$message = $records_found > 0
				? sprintf(
					/* translators: 1: downloaded, 2: total from Ingram */
					__( 'Catalog download finished. %1$d of %2$d products saved.', 'ingram-sync' ),
					$downloaded,
					max( $records_found, $downloaded )
				)
				: sprintf(
					/* translators: %d: downloaded count */
					__( 'Catalog download finished. %d products saved.', 'ingram-sync' ),
					$downloaded
				);
		}

		return array(
			'success'    => true,
			'done'       => ! $has_more,
			'count'      => $count,
			'page'       => $page,
			'next_page'  => $page + 1,
			'total'      => $records_found > 0 ? $records_found : $downloaded,
			'downloaded' => $downloaded,
			'remaining'  => null === $remaining ? -1 : (int) $remaining,
			'message'    => $message,
		);
	}

	/**
	 * Backward-compatible full catalog download.
	 *
	 * @return array{success: bool, message: string, count?: int}
	 */
	public function download_catalog() {
		$page       = 1;
		$total      = 0;
		$downloaded = 0;

		do {
			$result = $this->download_catalog_page( $page, $downloaded );
			if ( ! $result['success'] ) {
				return $result;
			}
			$total      = (int) ( $result['total'] ?? $total );
			$downloaded = (int) ( $result['downloaded'] ?? ( $downloaded + (int) ( $result['count'] ?? 0 ) ) );
			$page       = (int) ( $result['next_page'] ?? ( $page + 1 ) );
		} while ( empty( $result['done'] ) );

		return array(
			'success'    => true,
			'done'       => true,
			'total'      => $total,
			'downloaded' => $downloaded,
			'remaining'  => 0,
			'message'    => sprintf( __( 'Downloaded %d products.', 'ingram-sync' ), $downloaded ),
			'count'      => $downloaded,
		);
	}

	/**
	 * Download one price batch (chunked for AJAX).
	 *
	 * @param int $offset Row offset.
	 * @return array{success: bool, message: string, count?: int, done?: bool, next_offset?: int}
	 */
	public function download_price_batch( $offset = 0 ) {
		self::prepare_long_request();

		global $wpdb;

		$table      = Ingram_Sync_Database::products_table();
		$batch_size = (int) Ingram_Sync_Settings::get( 'batch_size', 50 );
		$offset     = max( 0, (int) $offset );
		$updated    = 0;
		$total      = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT ingram_part_number FROM {$table} ORDER BY id ASC LIMIT %d OFFSET %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$batch_size,
				$offset
			)
		);

		if ( empty( $rows ) ) {
			return array(
				'success'     => true,
				'done'        => true,
				'count'       => 0,
				'total'       => $total,
				'downloaded'  => $total,
				'remaining'   => 0,
				'next_offset' => $offset,
				'message'     => sprintf(
					/* translators: %d: total products */
					__( 'Price download finished. %d products processed.', 'ingram-sync' ),
					$total
				),
			);
		}

		$part_numbers = wp_list_pluck( $rows, 'ingram_part_number' );
		$result       = $this->api->get_price_availability( $part_numbers );

		if ( ! $result['success'] ) {
			return array(
				'success'     => false,
				'done'        => true,
				'total'       => $total,
				'downloaded'  => $offset,
				'remaining'   => max( 0, $total - $offset ),
				'message'     => $result['message'],
				'next_offset' => $offset,
			);
		}

		if ( ! empty( $result['body'] ) ) {
			$price_items = $result['body']['products'] ?? $result['body'] ?? array();
			foreach ( $price_items as $item ) {
				$pn = $item['ingramPartNumber'] ?? '';
				if ( $pn ) {
					$wpdb->update(
						$table,
						array( 'price_data' => wp_json_encode( $item ) ),
						array( 'ingram_part_number' => $pn ),
						array( '%s' ),
						array( '%s' )
					);
					++$updated;
				}
			}
		}

		$done        = count( $rows ) < $batch_size;
		$next_offset = $offset + $batch_size;
		$downloaded  = min( $total, $offset + count( $rows ) );
		$remaining   = max( 0, $total - $downloaded );

		Ingram_Sync_Logger::log(
			'Price',
			sprintf( 'Updated price batch offset %d (%d products)', $offset, $updated ),
			Ingram_Sync_Logger::LEVEL_SUCCESS
		);

		return array(
			'success'     => true,
			'done'        => $done,
			'count'       => $updated,
			'total'       => $total,
			'downloaded'  => $downloaded,
			'remaining'   => $remaining,
			'offset'      => $offset,
			'next_offset' => $next_offset,
			'message'     => $done
				? sprintf(
					/* translators: 1: downloaded, 2: total */
					__( 'Price download finished. %1$d of %2$d products updated.', 'ingram-sync' ),
					$downloaded,
					$total
				)
				: sprintf(
					/* translators: 1: downloaded, 2: total, 3: remaining */
					__( 'Price: %1$d of %2$d updated (%3$d remaining). Continuing…', 'ingram-sync' ),
					$downloaded,
					$total,
					$remaining
				),
		);
	}

	/**
	 * Backward-compatible full price download.
	 *
	 * @return array{success: bool, message: string}
	 */
	public function download_price() {
		$offset  = 0;
		$updated = 0;

		do {
			$result = $this->download_price_batch( $offset );
			if ( ! $result['success'] ) {
				return $result;
			}
			$updated += (int) ( $result['count'] ?? 0 );
			$offset   = (int) ( $result['next_offset'] ?? ( $offset + 1 ) );
		} while ( empty( $result['done'] ) );

		return array(
			'success' => true,
			'done'    => true,
			'message' => sprintf( __( 'Updated price for %d products.', 'ingram-sync' ), $updated ),
		);
	}

	/**
	 * Download details for one batch of products missing details.
	 *
	 * @return array{success: bool, message: string, count?: int, done?: bool}
	 */
	public function download_details_batch() {
		self::prepare_long_request();

		global $wpdb;

		$table      = Ingram_Sync_Database::products_table();
		$batch_size = max( 1, min( 25, (int) Ingram_Sync_Settings::get( 'batch_size', 50 ) ) );
		$updated    = 0;
		$failed     = 0;

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT ingram_part_number FROM {$table} WHERE details_data IS NULL OR details_data = '' ORDER BY id ASC LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$batch_size
			)
		);

		if ( empty( $rows ) ) {
			return array(
				'success' => true,
				'done'    => true,
				'count'   => 0,
				'message' => __( 'Details download finished.', 'ingram-sync' ),
			);
		}

		foreach ( $rows as $row ) {
			$result = $this->api->get_product_details( $row->ingram_part_number );
			if ( $result['success'] && ! empty( $result['body'] ) ) {
				$wpdb->update(
					$table,
					array( 'details_data' => wp_json_encode( $result['body'] ) ),
					array( 'ingram_part_number' => $row->ingram_part_number ),
					array( '%s' ),
					array( '%s' )
				);
				++$updated;
			} else {
				// Mark empty JSON so we do not retry the same failing SKU forever in this run.
				$wpdb->update(
					$table,
					array( 'details_data' => '{}' ),
					array( 'ingram_part_number' => $row->ingram_part_number ),
					array( '%s' ),
					array( '%s' )
				);
				++$failed;
			}
		}

		$remaining = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$table} WHERE details_data IS NULL OR details_data = ''" // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);
		$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$downloaded = max( 0, $total - $remaining );
		$done       = $remaining < 1;

		Ingram_Sync_Logger::log(
			'Details',
			sprintf( 'Details batch: %d updated, %d failed, %d remaining', $updated, $failed, $remaining ),
			Ingram_Sync_Logger::LEVEL_SUCCESS
		);

		return array(
			'success'    => true,
			'done'       => $done,
			'count'      => $updated,
			'total'      => $total,
			'downloaded' => $downloaded,
			'remaining'  => $remaining,
			'message'    => $done
				? sprintf(
					/* translators: 1: downloaded, 2: total */
					__( 'Details download finished. %1$d of %2$d products processed.', 'ingram-sync' ),
					$downloaded,
					$total
				)
				: sprintf(
					/* translators: 1: downloaded, 2: total, 3: remaining */
					__( 'Details: %1$d of %2$d done (%3$d remaining). Continuing…', 'ingram-sync' ),
					$downloaded,
					$total,
					$remaining
				),
		);
	}

	/**
	 * Backward-compatible full details download.
	 *
	 * @return array{success: bool, message: string}
	 */
	public function download_details() {
		$updated = 0;
		$guard   = 0;

		do {
			$result = $this->download_details_batch();
			if ( ! $result['success'] ) {
				return $result;
			}
			$updated += (int) ( $result['count'] ?? 0 );
			++$guard;
		} while ( empty( $result['done'] ) && $guard < 1000 );

		return array(
			'success' => true,
			'done'    => true,
			'message' => sprintf( __( 'Updated details for %d products.', 'ingram-sync' ), $updated ),
		);
	}

	/**
	 * Upsert product in cache table.
	 *
	 * @param string               $part_number Part number.
	 * @param array<string, mixed> $product     Product data.
	 */
	public static function upsert_product_cache( $part_number, $product ) {
		global $wpdb;

		$table = Ingram_Sync_Database::products_table();
		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table} WHERE ingram_part_number = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$part_number
			)
		);

		$data = array(
			'ingram_part_number' => sanitize_text_field( $part_number ),
			'vendor_part_number' => sanitize_text_field( $product['vendorPartNumber'] ?? '' ),
			'product_data'       => wp_json_encode( $product ),
			'sync_status'        => 'pending',
		);

		if ( $existing ) {
			$wpdb->update( $table, $data, array( 'id' => $existing ) );
		} else {
			$wpdb->insert( $table, $data );
		}
	}
}
