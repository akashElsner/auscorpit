<?php
/**
 * WooCommerce product sync.
 *
 * @package IngramSync
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Ingram_Sync_Woocommerce_Sync
 */
class Ingram_Sync_Woocommerce_Sync {

	const META_INGRAM_PN      = '_ingram_part_number';
	const META_VENDOR_PN      = '_ingram_vendor_part_number';
	const META_PRODUCT_CLASS  = '_ingram_product_class';
	const META_CUSTOMER_PN    = '_ingram_customer_part_number';
	const META_AUTHORIZED     = '_ingram_product_authorized';
	const META_UPC            = '_ingram_upc';
	const META_VENDOR_NAME    = '_ingram_vendor_name';
	const META_VENDOR_NUMBER  = '_ingram_vendor_number';
	const META_STATUS_CODE    = '_ingram_product_status_code';
	const META_CATEGORY       = '_ingram_product_category';
	const META_SUBCATEGORY    = '_ingram_product_subcategory';

	/**
	 * Sync one batch of products to WooCommerce (no OFFSET — mutating rows breaks OFFSET pagination).
	 *
	 * @param int[] $exclude_ids Row ids to skip (already attempted and failed earlier in this run).
	 * @return array{success: bool, message: string, count?: int, done?: bool, failed_ids?: int[]}
	 */
	public function sync_batch( $exclude_ids = array() ) {
		Ingram_Sync_Manager::prepare_long_request();

		if ( ! class_exists( 'WooCommerce' ) ) {
			return array(
				'success' => false,
				'done'    => true,
				'message' => __( 'WooCommerce is not active.', 'ingram-sync' ),
			);
		}

		global $wpdb;

		$table       = Ingram_Sync_Database::products_table();
		$batch_size  = max( 1, (int) Ingram_Sync_Settings::get( 'wc_products_per_batch', 100 ) );
		$update      = 'yes' === Ingram_Sync_Settings::get( 'wc_update_existing', 'yes' );
		$synced      = 0;
		$failed      = 0;
		$exclude_ids = array_map( 'intval', (array) $exclude_ids );

		// Always take the next pending/failed rows from the top — never OFFSET after updates.
		// Rows that already failed earlier in this run are excluded so a permanently-failing
		// item cannot keep matching this WHERE clause forever (infinite loop).
		//
		// A "completed" row whose linked WooCommerce product was deleted (post no longer
		// exists) must also be picked up again, otherwise a product removed outside the
		// plugin is skipped forever and never gets recreated. We detect that with a LEFT
		// JOIN against wp_posts.
		$where  = "(
			p.sync_status IN ('pending', 'failed')
			OR ( p.sync_status = 'completed' AND p.wc_product_id IS NOT NULL AND wcp.ID IS NULL )
		)";
		$params = array();
		if ( ! empty( $exclude_ids ) ) {
			$placeholders = implode( ',', array_fill( 0, count( $exclude_ids ), '%d' ) );
			$where       .= " AND p.id NOT IN ({$placeholders})";
			$params       = $exclude_ids;
		}
		$params[] = $batch_size;

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT p.* FROM {$table} p
				LEFT JOIN {$wpdb->posts} wcp ON wcp.ID = p.wc_product_id AND wcp.post_type = 'product'
				WHERE {$where} ORDER BY p.id ASC LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$params
			)
		);

		if ( empty( $rows ) ) {
			return array(
				'success'    => true,
				'done'       => true,
				'count'      => 0,
				'failed_ids' => array(),
				'message'    => __( 'WooCommerce sync finished. No pending products.', 'ingram-sync' ),
			);
		}

		// Time-box the batch so a single request always returns well within typical
		// host/CDN timeouts (e.g. Cloudflare's ~100s), regardless of how slow each
		// individual product save is. Unprocessed rows are simply picked up on the
		// next poll — nothing is skipped, the batch just gets split further.
		$time_budget = 40;
		$start_time  = time();

		$newly_failed = array();
		foreach ( $rows as $row ) {
			$result = $this->sync_single_product( $row, $update );
			if ( $result ) {
				++$synced;
			} else {
				++$failed;
				$newly_failed[] = (int) $row->id;
			}

			if ( ( time() - $start_time ) >= $time_budget ) {
				break;
			}
		}

		$all_excluded  = array_merge( $exclude_ids, $newly_failed );
		$exclude_where = '';
		$count_params  = array();
		if ( ! empty( $all_excluded ) ) {
			$placeholders  = implode( ',', array_fill( 0, count( $all_excluded ), '%d' ) );
			$exclude_where = " AND p.id NOT IN ({$placeholders})";
			$count_params  = $all_excluded;
		}

		$remaining_sql = "SELECT COUNT(*) FROM {$table} p
			LEFT JOIN {$wpdb->posts} wcp ON wcp.ID = p.wc_product_id AND wcp.post_type = 'product'
			WHERE (
				p.sync_status IN ('pending', 'failed')
				OR ( p.sync_status = 'completed' AND p.wc_product_id IS NOT NULL AND wcp.ID IS NULL )
			){$exclude_where}";
		$remaining     = (int) ( empty( $count_params )
			? $wpdb->get_var( $remaining_sql ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			: $wpdb->get_var( $wpdb->prepare( $remaining_sql, $count_params ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$downloaded = max( 0, $total - $remaining );
		$done       = $remaining < 1;

		Ingram_Sync_Logger::log(
			'WooCommerce',
			sprintf( 'Synced batch: %d ok, %d failed, %d remaining', $synced, $failed, $remaining ),
			Ingram_Sync_Logger::LEVEL_SUCCESS
		);

		return array(
			'success'    => true,
			'done'       => $done,
			'count'      => $synced,
			'total'      => $total,
			'downloaded' => $downloaded,
			'remaining'  => $remaining,
			'failed_ids' => $newly_failed,
			'message'    => $done
				? sprintf(
					/* translators: 1: synced, 2: total */
					__( 'WooCommerce sync finished. %1$d of %2$d products synced.', 'ingram-sync' ),
					$downloaded,
					$total
				)
				: sprintf(
					/* translators: 1: synced, 2: total, 3: remaining */
					__( 'WooCommerce: %1$d of %2$d synced (%3$d remaining). Continuing…', 'ingram-sync' ),
					$downloaded,
					$total,
					$remaining
				),
		);
	}

	/**
	 * Sync all pending products to WooCommerce.
	 *
	 * @return array{success: bool, message: string, count?: int}
	 */
	public function sync() {
		$synced      = 0;
		$guard       = 0;
		$exclude_ids = array();

		do {
			$result = $this->sync_batch( $exclude_ids );
			if ( ! $result['success'] ) {
				return $result;
			}
			$synced      += (int) ( $result['count'] ?? 0 );
			$exclude_ids  = array_merge( $exclude_ids, $result['failed_ids'] ?? array() );
			++$guard;
		} while ( empty( $result['done'] ) && $guard < 1000 );

		// Only runs at the end of a full sync (never from the standalone "Sync
		// WooCommerce" chunk button), and only once every product has had a
		// chance to be created/updated above — deleting mid-sync would remove
		// products that are simply queued for later in this same run.
		$deleted = 0;
		if ( 'yes' === Ingram_Sync_Settings::get( 'wc_delete_missing', 'no' ) ) {
			$deleted = $this->delete_missing_products();
		}

		return array(
			'success' => true,
			'done'    => true,
			'message' => $deleted > 0
				? sprintf(
					/* translators: 1: synced count, 2: deleted count */
					__( 'Synced %1$d products to WooCommerce. Deleted %2$d no longer in the catalog.', 'ingram-sync' ),
					$synced,
					$deleted
				)
				: sprintf( __( 'Synced %d products to WooCommerce.', 'ingram-sync' ), $synced ),
			'count'   => $synced,
		);
	}

	/**
	 * Sync a single product row to WooCommerce.
	 *
	 * @param object $row    Product row from DB.
	 * @param bool   $update Whether to update existing.
	 * @return bool
	 */
	private function sync_single_product( $row, $update = true ) {
		global $wpdb;

		$product_data = json_decode( $row->product_data, true ) ?: array();
		$price_data   = json_decode( $row->price_data, true ) ?: array();
		$details_data = json_decode( $row->details_data, true ) ?: array();

		$part_number = $row->ingram_part_number;
		$wc_id       = ! empty( $row->wc_product_id ) ? (int) $row->wc_product_id : 0;

		// The linked product may have been deleted outside the plugin — treat as unsynced
		// so it gets recreated below instead of being skipped or falsely marked completed.
		if ( $wc_id && ! wc_get_product( $wc_id ) ) {
			$wc_id = 0;
		}

		if ( ! $wc_id ) {
			$wc_id = $this->find_existing_product( $part_number );
		}

		if ( $wc_id && ! $update ) {
			$wpdb->update(
				Ingram_Sync_Database::products_table(),
				array(
					'wc_product_id' => $wc_id,
					'sync_status'   => 'completed',
				),
				array( 'id' => $row->id ),
				array( '%d', '%s' ),
				array( '%d' )
			);
			Ingram_Sync_Settings::update( 'stats_completed', (int) Ingram_Sync_Settings::get( 'stats_completed', 0 ) + 1 );
			return true;
		}

		$name        = $details_data['description'] ?? $product_data['description'] ?? $part_number;
		$description = (string) ( $details_data['description'] ?? $product_data['description'] ?? '' );

		$price = 0;
		if ( isset( $price_data['pricing']['customerPrice'] ) ) {
			$price = $price_data['pricing']['customerPrice'];
		} elseif ( isset( $price_data['customerPrice'] ) ) {
			$price = $price_data['customerPrice'];
		}

		$stock = 0;
		if ( isset( $price_data['availability']['totalAvailability'] ) ) {
			$stock = $price_data['availability']['totalAvailability'];
		} elseif ( isset( $price_data['totalAvailability'] ) ) {
			$stock = $price_data['totalAvailability'];
		}

		try {
			$product = null;
			if ( $wc_id ) {
				$product = wc_get_product( $wc_id );
				if ( ! $product ) {
					$wc_id = 0;
				}
			}

			if ( ! $wc_id ) {
				$product = new WC_Product_Simple();
			}

			$this->populate_product( $product, $name, $price, $description, $stock );

			// Set SKU carefully — duplicate SKUs throw in WooCommerce.
			try {
				$product->set_sku( sanitize_text_field( (string) $part_number ) );
			} catch ( Exception $sku_error ) {
				$existing_id = wc_get_product_id_by_sku( $part_number );
				if ( $existing_id && (int) $existing_id !== (int) $product->get_id() ) {
					$product = wc_get_product( $existing_id );
					if ( ! $product ) {
						throw $sku_error;
					}
					$this->populate_product( $product, $name, $price, $description, $stock );
				} else {
					throw $sku_error;
				}
			}

			$new_id = $product->save();

			$this->update_ingram_meta( $new_id, $row, $product_data, $details_data );

			$wpdb->update(
				Ingram_Sync_Database::products_table(),
				array(
					'wc_product_id' => $new_id,
					'sync_status'   => 'completed',
				),
				array( 'id' => $row->id ),
				array( '%d', '%s' ),
				array( '%d' )
			);

			Ingram_Sync_Settings::update( 'stats_completed', (int) Ingram_Sync_Settings::get( 'stats_completed', 0 ) + 1 );
			return true;

		} catch ( Exception $e ) {
			$wpdb->update(
				Ingram_Sync_Database::products_table(),
				array( 'sync_status' => 'failed' ),
				array( 'id' => $row->id ),
				array( '%s' ),
				array( '%d' )
			);
			Ingram_Sync_Logger::log( 'WooCommerce', 'Sync failed for ' . $part_number . ': ' . $e->getMessage(), Ingram_Sync_Logger::LEVEL_ERROR );
			Ingram_Sync_Settings::update( 'stats_failed', (int) Ingram_Sync_Settings::get( 'stats_failed', 0 ) + 1 );
			return false;
		}
	}

	/**
	 * Populate core WC product fields, including dimensions/weight from additionalInformation.
	 *
	 * @param WC_Product $product     Product instance.
	 * @param string     $name        Product name.
	 * @param mixed      $price       Regular price.
	 * @param string     $description Product description.
	 * @param mixed      $stock       Stock quantity.
	 */
	private function populate_product( $product, $name, $price, $description, $stock ) {
		$product->set_name( sanitize_text_field( (string) $name ) );
		$product->set_regular_price( (string) floatval( $price ) );
		$product->set_description( wp_kses_post( $description ) );
		$product->set_manage_stock( true );
		$product->set_stock_quantity( (int) $stock );
		$product->set_stock_status( (int) $stock > 0 ? 'instock' : 'outofstock' );
		$product->set_status( 'publish' );
	}

	/**
	 * Normalize a dimension unit word to one of WooCommerce's dimension_unit values.
	 *
	 * @param string $word Lowercased unit word.
	 * @return string|null
	 */
	private function normalize_dimension_unit( $word ) {
		$map = array(
			'in'          => 'in',
			'inch'        => 'in',
			'inches'      => 'in',
			'cm'          => 'cm',
			'centimeter'  => 'cm',
			'centimeters' => 'cm',
			'mm'          => 'mm',
			'millimeter'  => 'mm',
			'millimeters' => 'mm',
			'm'           => 'm',
			'meter'       => 'm',
			'meters'      => 'm',
			'yd'          => 'yd',
			'yard'        => 'yd',
			'yards'       => 'yd',
		);
		return $map[ $word ] ?? null;
	}

	/**
	 * Normalize a weight unit word to one of WooCommerce's weight_unit values.
	 *
	 * @param string $word Lowercased unit word.
	 * @return string|null
	 */
	private function normalize_weight_unit( $word ) {
		$map = array(
			'kg'        => 'kg',
			'kilogram'  => 'kg',
			'kilograms' => 'kg',
			'g'         => 'g',
			'gram'      => 'g',
			'grams'     => 'g',
			'lb'        => 'lbs',
			'lbs'       => 'lbs',
			'pound'     => 'lbs',
			'pounds'    => 'lbs',
			'oz'        => 'oz',
			'ounce'     => 'oz',
			'ounces'    => 'oz',
		);
		return $map[ $word ] ?? null;
	}

	/**
	 * Convert a value between two units of the same measurement type.
	 *
	 * @param float  $value     Source value.
	 * @param string $from_unit Normalized source unit.
	 * @param string $to_unit   Normalized target unit.
	 * @param string $type      'dimension' or 'weight'.
	 * @return float|null
	 */
	private function convert_measurement_value( $value, $from_unit, $to_unit, $type ) {
		if ( $from_unit === $to_unit ) {
			return round( $value, 4 );
		}
		// Conversion factors to a common base unit (cm for dimensions, kg for weight).
		$to_base = ( 'dimension' === $type )
			? array( 'in' => 2.54, 'cm' => 1, 'mm' => 0.1, 'm' => 100, 'yd' => 91.44 )
			: array( 'kg' => 1, 'g' => 0.001, 'lbs' => 0.453592, 'oz' => 0.0283495 );
		if ( ! isset( $to_base[ $from_unit ], $to_base[ $to_unit ] ) ) {
			return null;
		}
		return round( ( $value * $to_base[ $from_unit ] ) / $to_base[ $to_unit ], 4 );
	}

	/**
	 * Parse a free-text Ingram measurement string like "11.5 inches" and convert
	 * it into WooCommerce's configured unit for that measurement type.
	 *
	 * @param mixed  $raw         Raw measurement text.
	 * @param string $type        'dimension' or 'weight'.
	 * @param string $target_unit WooCommerce's configured unit for this type.
	 * @return float|null
	 */
	private function convert_measurement( $raw, $type, $target_unit ) {
		if ( ! is_string( $raw ) || ! preg_match( '/([\d.]+)\s*([a-zA-Z]+)/', $raw, $m ) ) {
			return null;
		}
		$value = (float) $m[1];
		if ( $value <= 0 ) {
			return null;
		}
		$unit_word = strtolower( $m[2] );
		$unit      = ( 'dimension' === $type )
			? $this->normalize_dimension_unit( $unit_word )
			: $this->normalize_weight_unit( $unit_word );
		if ( null === $unit ) {
			return null;
		}
		return $this->convert_measurement_value( $value, $unit, $target_unit, $type );
	}

	/**
	 * Find an existing product_cat term by exact name + parent, or create it —
	 * prevents re-creating the same Ingram category/subcategory on every sync.
	 *
	 * @param string $name   Term name.
	 * @param int    $parent Parent term id (0 for top-level).
	 * @return int Term id, or 0 on failure.
	 */
	private function find_or_create_product_cat_term( $name, $parent = 0 ) {
		$existing = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'name'       => $name,
				'parent'     => $parent,
				'hide_empty' => false,
				'fields'     => 'ids',
			)
		);
		if ( ! is_wp_error( $existing ) && ! empty( $existing ) ) {
			return (int) $existing[0];
		}

		$inserted = wp_insert_term( $name, 'product_cat', array( 'parent' => $parent ) );
		if ( is_wp_error( $inserted ) ) {
			$term_id = $inserted->get_error_data( 'term_exists' );
			return $term_id ? (int) $term_id : 0;
		}

		return (int) $inserted['term_id'];
	}

	/**
	 * Assign Ingram's productCategory/productSubCategory to the product as real
	 * product_cat taxonomy terms (previously only stored as post meta). When both
	 * exist, the subcategory is created as a child of the category and both are
	 * assigned so the product also appears under the parent category archive.
	 *
	 * @param int                  $wc_id        WooCommerce product ID.
	 * @param array<string, mixed> $product_data Decoded catalog data.
	 */
	private function assign_ingram_categories( $wc_id, array $product_data ) {
		$category    = sanitize_text_field( (string) ( $product_data['productCategory'] ?? '' ) );
		$subcategory = sanitize_text_field( (string) ( $product_data['productSubCategory'] ?? '' ) );

		if ( '' === $category && '' === $subcategory ) {
			return;
		}

		$term_ids  = array();
		$parent_id = 0;

		if ( '' !== $category ) {
			$parent_id = $this->find_or_create_product_cat_term( $category );
			if ( $parent_id ) {
				$term_ids[] = $parent_id;
			}
		}

		if ( '' !== $subcategory ) {
			$sub_id = $this->find_or_create_product_cat_term( $subcategory, $parent_id );
			if ( $sub_id ) {
				$term_ids[] = $sub_id;
			}
		}

		if ( ! empty( $term_ids ) ) {
			wp_set_object_terms( $wc_id, $term_ids, 'product_cat', false );
		}
	}

	/**
	 * Store Ingram catalog/details fields as custom post meta, sync WC
	 * dimensions/weight (converted into the store's configured units), and
	 * assign the product_cat taxonomy terms for category/subcategory.
	 *
	 * @param int    $wc_id         WooCommerce product ID.
	 * @param object $row           Product row from DB.
	 * @param array  $product_data  Decoded catalog data.
	 * @param array  $details_data  Decoded details data.
	 */
	private function update_ingram_meta( $wc_id, $row, $product_data, $details_data ) {
		$meta_map = array(
			self::META_INGRAM_PN     => $product_data['ingramPartNumber'] ?? $row->ingram_part_number,
			self::META_VENDOR_PN     => $product_data['vendorPartNumber'] ?? $row->vendor_part_number,
			self::META_PRODUCT_CLASS => $product_data['productClass'] ?? '',
			self::META_CUSTOMER_PN   => $product_data['customerPartNumber'] ?? '',
			self::META_AUTHORIZED    => $product_data['productAuthorized'] ?? '',
			self::META_UPC           => $product_data['upc'] ?? '',
			self::META_VENDOR_NAME   => $product_data['vendorName'] ?? '',
			self::META_VENDOR_NUMBER => $product_data['vendorNumber'] ?? '',
			self::META_STATUS_CODE   => $product_data['productStatusCode'] ?? '',
			self::META_CATEGORY      => $product_data['productCategory'] ?? '',
			self::META_SUBCATEGORY   => $product_data['productSubCategory'] ?? '',
		);

		foreach ( $meta_map as $meta_key => $value ) {
			update_post_meta( $wc_id, $meta_key, sanitize_text_field( (string) $value ) );
		}

		$this->assign_ingram_categories( $wc_id, $product_data );

		$additional = $details_data['additionalInformation'] ?? array();
		if ( is_array( $additional ) ) {
			$product = wc_get_product( $wc_id );
			if ( $product ) {
				$dim_unit    = get_option( 'woocommerce_dimension_unit', 'cm' );
				$weight_unit = get_option( 'woocommerce_weight_unit', 'kg' );

				$height = $this->convert_measurement( $additional['height'] ?? '', 'dimension', $dim_unit );
				$width  = $this->convert_measurement( $additional['width'] ?? '', 'dimension', $dim_unit );
				$length = $this->convert_measurement( $additional['length'] ?? '', 'dimension', $dim_unit );

				// Prefer the structured per-plant shipping weight (with its own explicit
				// unit) over the free-text net weight string.
				$weight = null;
				if ( ! empty( $additional['productWeight'][0]['weight'] ) ) {
					$src_unit = $this->normalize_weight_unit( strtolower( (string) ( $additional['productWeight'][0]['weightUnit'] ?? '' ) ) );
					if ( null !== $src_unit ) {
						$weight = $this->convert_measurement_value( (float) $additional['productWeight'][0]['weight'], $src_unit, $weight_unit, 'weight' );
					}
				}
				if ( null === $weight ) {
					$weight = $this->convert_measurement( $additional['netWeight'] ?? '', 'weight', $weight_unit );
				}

				if ( null !== $height ) {
					$product->set_height( (string) $height );
				}
				if ( null !== $width ) {
					$product->set_width( (string) $width );
				}
				if ( null !== $length ) {
					$product->set_length( (string) $length );
				}
				if ( null !== $weight ) {
					$product->set_weight( (string) $weight );
				}

				$product->save();
			}
		}
	}

	/**
	 * Find existing WC product by Ingram part number.
	 *
	 * @param string $part_number Part number.
	 * @return int
	 */
	private function find_existing_product( $part_number ) {
		// Try the indexed SKU lookup first — cheap, and matches in the overwhelming
		// common case since sync_single_product() always keeps the SKU and the
		// _ingram_part_number meta in sync. The meta tag stays authoritative on a
		// mismatch (e.g. the SKU was reused/edited outside this plugin), so fall
		// back to the unindexed meta scan whenever the SKU lookup misses or the
		// meta doesn't confirm it — same end result as before, just skipping the
		// full postmeta scan on the common path.
		$product_id = wc_get_product_id_by_sku( $part_number );
		if ( $product_id && (string) get_post_meta( $product_id, self::META_INGRAM_PN, true ) === (string) $part_number ) {
			return (int) $product_id;
		}

		$posts = get_posts(
			array(
				'post_type'      => 'product',
				'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
				'posts_per_page' => 1,
				'meta_key'       => self::META_INGRAM_PN,
				'meta_value'     => $part_number,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'orderby'        => 'none',
			)
		);

		if ( ! empty( $posts ) ) {
			return (int) $posts[0];
		}

		return $product_id ? (int) $product_id : 0;
	}

	/**
	 * Delete WooCommerce products synced from Ingram.
	 *
	 * @return int Number deleted.
	 */
	public function delete_synced_products() {
		$posts = get_posts(
			array(
				'post_type'      => 'product',
				'posts_per_page' => -1,
				'meta_key'       => self::META_INGRAM_PN,
				'fields'         => 'ids',
			)
		);

		$deleted = 0;
		foreach ( $posts as $post_id ) {
			if ( wp_delete_post( $post_id, true ) ) {
				++$deleted;
			}
		}

		Ingram_Sync_Logger::log( 'WooCommerce', "Deleted {$deleted} products", Ingram_Sync_Logger::LEVEL_WARNING );
		return $deleted;
	}

	/**
	 * Delete WooCommerce products tagged as Ingram-sourced whose part number is
	 * no longer present in the current Ingram catalog cache. Gated behind the
	 * wc_delete_missing setting and only called once, at the end of a full sync.
	 *
	 * @return int Number deleted.
	 */
	public function delete_missing_products() {
		global $wpdb;

		$current_pns = $wpdb->get_col( 'SELECT ingram_part_number FROM ' . Ingram_Sync_Database::products_table() ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$current_pns = array_map( 'strval', $current_pns );

		$posts = get_posts(
			array(
				'post_type'      => 'product',
				'posts_per_page' => -1,
				'meta_key'       => self::META_INGRAM_PN,
				'fields'         => 'ids',
			)
		);

		$deleted = 0;
		foreach ( $posts as $post_id ) {
			$pn = get_post_meta( $post_id, self::META_INGRAM_PN, true );
			if ( '' === $pn || in_array( (string) $pn, $current_pns, true ) ) {
				continue;
			}

			if ( wp_delete_post( $post_id, true ) ) {
				++$deleted;
				Ingram_Sync_Logger::log(
					'WooCommerce',
					sprintf( 'Deleted product #%d (SKU %s) — no longer in Ingram catalog.', $post_id, $pn ),
					Ingram_Sync_Logger::LEVEL_WARNING
				);
			}
		}

		return $deleted;
	}

	/**
	 * Register admin hooks for the Ingram data meta box on the product edit page.
	 */
	public static function init() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_box' ) );
	}

	/**
	 * Add the "Ingram Micro Data" meta box to the product edit screen.
	 */
	public static function add_meta_box() {
		add_meta_box(
			'ingram_sync_product_data',
			__( 'Ingram Micro Data', 'ingram-sync' ),
			array( __CLASS__, 'render_meta_box' ),
			'product',
			'normal',
			'default'
		);
	}

	/**
	 * Render the read-only Ingram data meta box.
	 *
	 * @param WP_Post $post Current product post.
	 */
	public static function render_meta_box( $post ) {
		$fields = array(
			__( 'Ingram Part Number', 'ingram-sync' )  => self::META_INGRAM_PN,
			__( 'Vendor Part Number', 'ingram-sync' )  => self::META_VENDOR_PN,
			__( 'Product Class', 'ingram-sync' )       => self::META_PRODUCT_CLASS,
			__( 'Customer Part Number', 'ingram-sync' ) => self::META_CUSTOMER_PN,
			__( 'Product Authorized', 'ingram-sync' )  => self::META_AUTHORIZED,
			__( 'UPC', 'ingram-sync' )                  => self::META_UPC,
			__( 'Vendor Name', 'ingram-sync' )         => self::META_VENDOR_NAME,
			__( 'Vendor Number', 'ingram-sync' )       => self::META_VENDOR_NUMBER,
			__( 'Product Status Code', 'ingram-sync' ) => self::META_STATUS_CODE,
			__( 'Product Category', 'ingram-sync' )    => self::META_CATEGORY,
			__( 'Product Sub Category', 'ingram-sync' ) => self::META_SUBCATEGORY,
		);

		echo '<table class="widefat ingram-sync-meta-table" style="border:0;">';
		foreach ( $fields as $label => $meta_key ) {
			$value = get_post_meta( $post->ID, $meta_key, true );
			printf(
				'<tr><th style="width:220px;text-align:left;">%1$s</th><td>%2$s</td></tr>',
				esc_html( $label ),
				esc_html( $value )
			);
		}
		echo '</table>';
	}
}
