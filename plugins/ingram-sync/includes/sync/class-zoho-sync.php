<?php
/**
 * Zoho Inventory sync.
 *
 * Creates new Zoho items or updates existing ones (matched by SKU or name).
 * Work is done in small AJAX-safe chunks to avoid Cloudflare 524 timeouts.
 *
 * @package IngramSync
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Ingram_Sync_Zoho_Sync
 */
class Ingram_Sync_Zoho_Sync {

	/**
	 * Items per AJAX chunk (keep well under Cloudflare ~100s limit).
	 */
	const CHUNK_SIZE = 200;

	/**
	 * Transient key for cached Zoho item maps.
	 */
	const MAPS_TRANSIENT = 'ingram_sync_zoho_item_maps';

	/**
	 * Maps TTL — generous vs. a realistic full-sync duration so a long-running
	 * chunked sync never silently loses resolved SKU/name -&gt; item_id mappings
	 * mid-run and falls back to per-item API lookups for everything remaining.
	 */
	const MAPS_TTL = 6 * HOUR_IN_SECONDS;

	/**
	 * @var Ingram_Sync_Zoho_Api
	 */
	private $api;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->api = new Ingram_Sync_Zoho_Api();
	}

	/**
	 * Full sync (cron / CLI). Processes all pending rows in chunks.
	 *
	 * @return array{success: bool, message: string, count?: int}
	 */
	public function sync() {
		if ( 'yes' !== Ingram_Sync_Settings::get( 'zoho_sync_enabled' ) ) {
			return array( 'success' => true, 'message' => __( 'Zoho sync disabled.', 'ingram-sync' ) );
		}

		Ingram_Sync_Manager::prepare_long_request();
		delete_transient( self::MAPS_TRANSIENT );
		// Pre-build maps once for server-side/cron runs (not behind Cloudflare).
		$this->get_or_build_maps( true );

		$created = 0;
		$updated = 0;
		$failed  = 0;
		$guard   = 0;

		do {
			$result = $this->sync_batch();
			if ( ! $result['success'] && ! empty( $result['done'] ) ) {
				return $result;
			}
			$created += (int) ( $result['created'] ?? 0 );
			$updated += (int) ( $result['updated'] ?? 0 );
			$failed  += (int) ( $result['failed'] ?? 0 );
			++$guard;
		} while ( empty( $result['done'] ) && $guard < 5000 );

		return array(
			'success' => true,
			'done'    => true,
			'message' => sprintf(
				/* translators: 1: created, 2: updated, 3: failed */
				__( 'Zoho sync finished. Created: %1$d, Updated: %2$d, Failed: %3$d.', 'ingram-sync' ),
				$created,
				$updated,
				$failed
			),
			'count'   => $created + $updated,
		);
	}

	/**
	 * Sync one small batch (AJAX-safe).
	 *
	 * @param int[] $exclude_ids Row IDs that failed earlier in this browser run.
	 * @return array{success: bool, message: string, done?: bool, created?: int, updated?: int, failed?: int, failed_ids?: int[], downloaded?: int, total?: int, remaining?: int}
	 */
	public function sync_batch( $exclude_ids = array() ) {
		Ingram_Sync_Manager::prepare_long_request();

		if ( 'yes' !== Ingram_Sync_Settings::get( 'zoho_sync_enabled' ) ) {
			return array(
				'success' => true,
				'done'    => true,
				'message' => __( 'Zoho sync disabled.', 'ingram-sync' ),
			);
		}

		global $wpdb;

		$table       = Ingram_Sync_Database::products_table();
		$exclude_ids = array_values( array_filter( array_map( 'intval', (array) $exclude_ids ) ) );
		$chunk       = self::CHUNK_SIZE;

		$maps     = $this->get_or_build_maps( false );
		$sku_map  = $maps['sku'];
		$name_map = $maps['name'];

		$where  = "(zoho_item_id IS NULL OR zoho_item_id = '')";
		$params = array();
		if ( ! empty( $exclude_ids ) ) {
			$placeholders = implode( ',', array_fill( 0, count( $exclude_ids ), '%d' ) );
			$where       .= " AND id NOT IN ({$placeholders})";
			$params       = $exclude_ids;
		}
		$params[] = $chunk;

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE {$where} ORDER BY id ASC LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$params
			)
		);

		$total_pending = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$table} WHERE (zoho_item_id IS NULL OR zoho_item_id = '')" // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);

		if ( empty( $rows ) ) {
			delete_transient( self::MAPS_TRANSIENT );
			Ingram_Sync_Logger::log( 'Zoho', 'Zoho chunked sync complete — no pending items.', Ingram_Sync_Logger::LEVEL_SUCCESS );
			return array(
				'success'    => true,
				'done'       => true,
				'message'    => __( 'Zoho sync complete. No pending items.', 'ingram-sync' ),
				'created'    => 0,
				'updated'    => 0,
				'failed'     => 0,
				'downloaded' => 0,
				'total'      => 0,
				'remaining'  => 0,
			);
		}

		$created    = 0;
		$updated    = 0;
		$failed     = 0;
		$failed_ids = array();

		foreach ( $rows as $row ) {
			$result = $this->sync_single_item( $row, $sku_map, $name_map );
			if ( $result['success'] ) {
				$new_id = $result['item_id'] ?? '';
				$sku    = (string) $row->ingram_part_number;
				$name   = $result['name'] ?? '';
				if ( '' !== $new_id ) {
					if ( '' !== $sku ) {
						$sku_map[ $sku ] = $new_id;
					}
					if ( '' !== $name ) {
						$name_map[ Ingram_Sync_Zoho_Api::normalize_name( $name ) ] = $new_id;
					}
				}
				if ( 'create' === ( $result['action'] ?? '' ) ) {
					++$created;
				} else {
					++$updated;
				}
			} else {
				++$failed;
				$failed_ids[] = (int) $row->id;
			}
		}

		// Persist updated maps for the next chunk.
		set_transient(
			self::MAPS_TRANSIENT,
			array(
				'sku'  => $sku_map,
				'name' => $name_map,
			),
			HOUR_IN_SECONDS
		);

		$processed = $created + $updated + $failed;
		$remaining = max( 0, $total_pending - $created - $updated );

		Ingram_Sync_Logger::log(
			'Zoho',
			sprintf(
				'Zoho chunk: +%d created, +%d updated, +%d failed (remaining ~%d)',
				$created,
				$updated,
				$failed,
				$remaining
			),
			Ingram_Sync_Logger::LEVEL_INFO
		);

		$done = ( $remaining <= 0 );

		if ( $done ) {
			delete_transient( self::MAPS_TRANSIENT );
		}

		return array(
			'success'    => true,
			'done'       => $done,
			'message'    => $done
				? sprintf(
					/* translators: 1: created, 2: updated, 3: failed */
					__( 'Zoho sync finished. Created: %1$d, Updated: %2$d, Failed: %3$d (this chunk).', 'ingram-sync' ),
					$created,
					$updated,
					$failed
				)
				: sprintf(
					/* translators: 1: remaining count */
					__( 'Zoho sync in progress… ~%d items remaining. Continuing…', 'ingram-sync' ),
					$remaining
				),
			'created'    => $created,
			'updated'    => $updated,
			'failed'     => $failed,
			'failed_ids' => $failed_ids,
			'downloaded' => max( 0, $total_pending - $remaining ),
			'total'      => $total_pending,
			'remaining'  => $remaining,
			'count'      => $processed,
		);
	}

	/**
	 * Load maps for this run.
	 *
	 * Avoids a full Zoho catalog fetch on web/AJAX requests (that causes Cloudflare 524).
	 * Maps grow as items are resolved/created during chunks. Cron may pre-build.
	 *
	 * @param bool $allow_full_build Whether to fetch all Zoho items once (cron only).
	 * @return array{sku: array<string,string>, name: array<string,string>}
	 */
	private function get_or_build_maps( $allow_full_build = false ) {
		$cached = get_transient( self::MAPS_TRANSIENT );
		if ( is_array( $cached ) && isset( $cached['sku'], $cached['name'] ) ) {
			// Sliding expiration — a long-running chunked sync keeps its resolved
			// maps alive instead of silently reverting to empty maps mid-run.
			set_transient( self::MAPS_TRANSIENT, $cached, self::MAPS_TTL );
			return $cached;
		}

		if ( $allow_full_build ) {
			Ingram_Sync_Logger::log( 'Zoho', 'Building Zoho SKU + name maps…', Ingram_Sync_Logger::LEVEL_INFO );
			$maps = $this->api->build_item_maps();
			set_transient( self::MAPS_TRANSIENT, $maps, self::MAPS_TTL );
			Ingram_Sync_Logger::log(
				'Zoho',
				sprintf( 'Maps ready — SKUs: %d, names: %d', count( $maps['sku'] ), count( $maps['name'] ) ),
				Ingram_Sync_Logger::LEVEL_INFO
			);
			return $maps;
		}

		// Seed the SKU map from our own products table — every item this plugin
		// has ever created/updated in Zoho already has its item_id stored here,
		// so this costs zero Zoho API calls and covers the common case. Items
		// that exist in Zoho but were never touched by this plugin still fall
		// through to a per-item API lookup, or get picked up by warm_maps_batch().
		$maps = array(
			'sku'  => $this->get_db_sku_map(),
			'name' => array(),
		);
		set_transient( self::MAPS_TRANSIENT, $maps, self::MAPS_TTL );
		return $maps;
	}

	/**
	 * Build a SKU -> Zoho item_id map from already-synced product rows (zero API calls).
	 *
	 * @return array<string,string>
	 */
	private function get_db_sku_map() {
		global $wpdb;

		$table = Ingram_Sync_Database::products_table();
		$rows  = $wpdb->get_results(
			"SELECT ingram_part_number, zoho_item_id FROM {$table} WHERE zoho_item_id IS NOT NULL AND zoho_item_id != ''" // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);

		$map = array();
		foreach ( $rows as $row ) {
			$map[ (string) $row->ingram_part_number ] = (string) $row->zoho_item_id;
		}
		return $map;
	}

	/**
	 * Warm the SKU/name maps one Zoho page at a time (AJAX-safe — avoids a single
	 * long-running full-catalog fetch that risks a Cloudflare ~100s timeout, the
	 * exact reason the maps used to start empty on browser-driven runs). Chain
	 * this to completion before a chunked sync_batch() run to avoid falling back
	 * to a per-item API lookup for every row.
	 *
	 * @param int $page Page number (1-based).
	 * @return array{success: bool, done: bool, page: int, next_page: int, count: int, total?: int, message: string}
	 */
	public function warm_maps_batch( $page = 1 ) {
		Ingram_Sync_Manager::prepare_long_request();

		$page = max( 1, (int) $page );

		$cached = get_transient( self::MAPS_TRANSIENT );
		$maps   = ( is_array( $cached ) && isset( $cached['sku'], $cached['name'] ) )
			? $cached
			: array(
				'sku'  => $this->get_db_sku_map(),
				'name' => array(),
			);

		$result = $this->api->list_items_page( $page );

		if ( ! $result['success'] ) {
			return array(
				'success'   => false,
				'done'      => true,
				'page'      => $page,
				'next_page' => $page,
				'count'     => 0,
				'message'   => $result['message'],
			);
		}

		$added = 0;
		foreach ( $result['items'] as $item ) {
			$id = isset( $item['item_id'] ) ? (string) $item['item_id'] : '';
			if ( '' === $id ) {
				continue;
			}
			if ( ! empty( $item['sku'] ) ) {
				$maps['sku'][ (string) $item['sku'] ] = $id;
				++$added;
			}
			if ( ! empty( $item['name'] ) ) {
				$maps['name'][ Ingram_Sync_Zoho_Api::normalize_name( $item['name'] ) ] = $id;
			}
		}

		set_transient( self::MAPS_TRANSIENT, $maps, self::MAPS_TTL );

		$done = ! $result['has_more'];

		Ingram_Sync_Logger::log(
			'Zoho',
			sprintf( 'Zoho map warm-up page %d (%d SKUs added, %d total).', $page, $added, count( $maps['sku'] ) ),
			Ingram_Sync_Logger::LEVEL_INFO
		);

		return array(
			'success'   => true,
			'done'      => $done,
			'page'      => $page,
			'next_page' => $done ? $page : $page + 1,
			'count'     => $added,
			'total'     => count( $maps['sku'] ),
			'message'   => $done
				? sprintf(
					/* translators: %d: number of cached SKUs */
					__( 'Zoho maps ready — %d SKUs cached.', 'ingram-sync' ),
					count( $maps['sku'] )
				)
				: sprintf(
					/* translators: %d: number of cached SKUs so far */
					__( 'Warming Zoho maps… %d SKUs so far. Continuing…', 'ingram-sync' ),
					count( $maps['sku'] )
				),
		);
	}

	/**
	 * Sync single product to Zoho (create or update).
	 *
	 * @param object               $row      Product row.
	 * @param array<string,string> $sku_map  SKU → item_id.
	 * @param array<string,string> $name_map Normalized name → item_id.
	 * @return array{success: bool, action?: string, item_id?: string, name?: string, message?: string}
	 */
	private function sync_single_item( $row, array &$sku_map, array &$name_map ) {
		global $wpdb;

		$product_data = json_decode( $row->product_data, true ) ?: array();
		$price_data   = json_decode( $row->price_data, true ) ?: array();
		$details_data = json_decode( $row->details_data, true ) ?: array();

		$name    = sanitize_text_field( $product_data['description'] ?? $row->ingram_part_number );
		$price   = $price_data['pricing']['customerPrice'] ?? $price_data['customerPrice'] ?? 0;
		$stock   = $price_data['availability']['totalAvailability'] ?? 0;
		$sku     = sanitize_text_field( $row->ingram_part_number );
		$vendor_pn = sanitize_text_field( $row->vendor_part_number ?? '' );

		$known_id = ! empty( $row->zoho_item_id ) ? (string) $row->zoho_item_id : null;

		$item_data = array(
			'name'          => $name,
			'sku'           => $sku,
			'rate'          => floatval( $price ),
			'item_type'     => 'inventory',
			'product_type'  => 'goods',
			'custom_fields' => array(
				array(
					'customfield_id' => INGRAM_SYNC_ZOHO_CF_SUPPLIER_SOURCE_ID,
					'value'          => INGRAM_SYNC_ZOHO_CF_SUPPLIER_SOURCE_VALUE,
				),
				array(
					'customfield_id' => INGRAM_SYNC_ZOHO_CF_INGRAM_PART_NUMBER_ID,
					'value'          => $sku,
				),
				array(
					'customfield_id' => INGRAM_SYNC_ZOHO_CF_VENDOR_PART_NUMBER_ID,
					'value'          => $vendor_pn,
				),
				array(
					'customfield_id' => INGRAM_SYNC_ZOHO_CF_QUANTITY_ID,
					'value'          => max( 0, (int) $stock ),
				),
			),
		);

		// Zoho rejects an item with opening stock but no positive rate to value it at
		// ("Please enter a positive opening stock rate") — only claim opening stock
		// when we actually have a price to back it, otherwise let it default to zero.
		if ( (int) $stock > 0 && floatval( $price ) > 0 ) {
			$item_data['initial_stock']      = (int) $stock;
			$item_data['initial_stock_rate'] = floatval( $price );
		}

		$item_data = array_merge( $item_data, self::build_fulfilment_fields( $details_data ) );

		$result = $this->api->upsert_item( $item_data, $known_id, $sku_map, $name_map );

		if ( $result['success'] ) {
			$zoho_id = $result['item_id'] ?? ( $result['body']['item']['item_id'] ?? '' );
			if ( '' !== $zoho_id ) {
				$wpdb->update(
					Ingram_Sync_Database::products_table(),
					array( 'zoho_item_id' => $zoho_id ),
					array( 'id' => $row->id ),
					array( '%s' ),
					array( '%d' )
				);
			}
			Ingram_Sync_Settings::update( 'stats_completed', (int) Ingram_Sync_Settings::get( 'stats_completed', 0 ) + 1 );
			return array(
				'success' => true,
				'action'  => $result['action'] ?? 'update',
				'item_id' => (string) $zoho_id,
				'name'    => $name,
			);
		}

		Ingram_Sync_Settings::update( 'stats_failed', (int) Ingram_Sync_Settings::get( 'stats_failed', 0 ) + 1 );
		return array(
			'success' => false,
			'message' => $result['message'] ?? '',
		);
	}

	/**
	 * Build Zoho's Fulfilment Details fields (package_details: length/width/
	 * height/dimension_unit/weight/weight_unit, plus the item's own top-level
	 * `unit` field) from Ingram's details API `additionalInformation` block.
	 * Ingram's catalog data is often incomplete, so each measurement is sent
	 * independently — a missing height doesn't block sending length/width.
	 *
	 * @param array<string,mixed> $details_data Decoded `details_data` JSON.
	 * @return array<string,mixed>
	 */
	private static function build_fulfilment_fields( array $details_data ) {
		$info = $details_data['additionalInformation'] ?? array();
		if ( empty( $info ) || ! is_array( $info ) ) {
			return array();
		}

		$package = array();
		$fields  = array();

		$dims = array(
			'length' => self::parse_measurement( $info['length'] ?? '' ),
			'width'  => self::parse_measurement( $info['width'] ?? '' ),
			'height' => self::parse_measurement( $info['height'] ?? '' ),
		);
		$dimension_unit = null;
		foreach ( $dims as $key => $dim ) {
			if ( ! $dim ) {
				continue;
			}
			$package[ $key ] = (string) $dim['value'];
			if ( null === $dimension_unit ) {
				$dimension_unit = $dim['unit'];
			}
		}
		if ( null !== $dimension_unit ) {
			$package['dimension_unit'] = $dimension_unit;
		}

		// Weight and its unit both come from the structured productWeight entry —
		// weightUnit is the authoritative unit (KG/LB), never inferred or guessed
		// from the free-text netWeight string. The same unit also feeds the
		// item's separate top-level `unit` (unit of measure) field.
		$unit_map = array(
			'kg'       => 'kg',
			'kilogram' => 'kg',
			'lb'       => 'lb',
			'lbs'      => 'lb',
			'pound'    => 'lb',
			'pounds'   => 'lb',
		);
		$raw_weight = $info['productWeight'][0]['weight'] ?? null;
		$unit_word  = strtolower( (string) ( $info['productWeight'][0]['weightUnit'] ?? '' ) );
		if ( ! empty( $raw_weight ) && isset( $unit_map[ $unit_word ] ) && (float) $raw_weight > 0 ) {
			$package['weight']      = (string) (float) $raw_weight;
			$package['weight_unit'] = $unit_map[ $unit_word ];
			$fields['unit']         = $unit_map[ $unit_word ];
		}

		if ( ! empty( $package ) ) {
			$fields['package_details'] = $package;
		}

		return $fields;
	}

	/**
	 * Parse a free-text Ingram dimension string like "11.5 inches" into a
	 * numeric value and a normalized Zoho dimension_unit token.
	 *
	 * @param mixed $raw Raw dimension text.
	 * @return array{value: float, unit: string}|null
	 */
	private static function parse_measurement( $raw ) {
		if ( empty( $raw ) || ! is_string( $raw ) ) {
			return null;
		}
		if ( ! preg_match( '/([\d.]+)\s*([a-zA-Z]+)/', $raw, $m ) ) {
			return null;
		}

		$value = (float) $m[1];
		if ( $value <= 0 ) {
			return null;
		}

		$unit_word = strtolower( $m[2] );
		$map       = array(
			'in'          => 'in',
			'inch'        => 'in',
			'inches'      => 'in',
			'cm'          => 'cm',
			'centimeter'  => 'cm',
			'centimeters' => 'cm',
		);

		if ( ! isset( $map[ $unit_word ] ) ) {
			return null;
		}

		return array( 'value' => $value, 'unit' => $map[ $unit_word ] );
	}
}
