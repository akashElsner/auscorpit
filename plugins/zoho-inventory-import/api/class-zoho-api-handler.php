<?php
/**
 * Zoho Inventory item API handler — uses sync plugin connection.
 *
 * @package ZohoInventoryImport\Api
 */

namespace ZohoInventoryImport\Api;

use ZohoInventoryImport\Includes\Field_Mapping;
use ZohoInventoryImport\Includes\Import_Settings;
use ZohoInventoryImport\Includes\Sync_Bridge;

defined( 'ABSPATH' ) || exit;

/**
 * Class Zoho_API_Handler
 */
class Zoho_API_Handler {

	/** @var Sync_Bridge */
	private Sync_Bridge $bridge;

	/** @var Import_Settings */
	private Import_Settings $settings;

	/**
	 * @param Sync_Bridge     $bridge   Sync plugin bridge.
	 * @param Import_Settings $settings Import settings.
	 */
	public function __construct( Sync_Bridge $bridge, Import_Settings $settings ) {
		$this->bridge   = $bridge;
		$this->settings = $settings;
	}

	/**
	 * Create a new item in Zoho Inventory.
	 *
	 * @param array $data Item payload.
	 * @return array|\WP_Error
	 */
	public function create_item( array $data ) {
		return $this->bridge->get_items_api()->create_item( $data );
	}

	/**
	 * Update an existing item.
	 *
	 * @param string $item_id Zoho item ID.
	 * @param array  $data    Updated fields.
	 * @return array|\WP_Error
	 */
	public function update_item( string $item_id, array $data ) {
		return $this->bridge->get_items_api()->update_item( $item_id, $data );
	}

	/**
	 * Find an existing item by SKU (preferred) or item name.
	 *
	 * @param string $sku  Item SKU.
	 * @param string $name Item name fallback.
	 * @return array|null
	 */
	public function find_existing_item( string $sku, string $name ): ?array {
		if ( '' !== $sku ) {
			$by_sku = $this->find_by_sku( $sku );
			if ( $by_sku ) {
				return $by_sku;
			}
		}

		if ( '' !== $name ) {
			return $this->find_by_name( $name );
		}

		return null;
	}

	/**
	 * @param string $sku SKU value.
	 * @return array|null
	 */
	public function find_by_sku( string $sku ): ?array {
		return $this->bridge->get_items_api()->find_by_sku( $sku );
	}

	/**
	 * @param string $name Item name.
	 * @return array|null
	 */
	public function find_by_name( string $name ): ?array {
		$response = $this->bridge->get_client()->get(
			'items',
			[
				'search_text' => $name,
				'per_page'    => 200,
			]
		);

		if ( is_wp_error( $response ) || empty( $response['items'] ) ) {
			return null;
		}

		foreach ( $response['items'] as $item ) {
			if ( isset( $item['name'] ) && strcasecmp( (string) $item['name'], $name ) === 0 ) {
				return $item;
			}
		}

		return null;
	}

	/**
	 * Map a CSV row to Zoho Inventory item fields.
	 *
	 * @param array $row    Associative CSV row.
	 * @param bool  $is_new Whether this is a new item creation.
	 * @return array|\WP_Error
	 */
	public function map_row_to_zoho( array $row, bool $is_new = true ) {
		$name = $this->get_row_value( $row, Field_Mapping::NAME_COLUMNS );
		if ( '' === $name ) {
			return new \WP_Error(
				'missing_name',
				__( 'Item name is required (CSV column: Name).', 'zoho-inventory-import' )
			);
		}

		$alt_title = $this->get_row_value( $row, Field_Mapping::ALT_TITLE_COLUMNS );
		if ( '' === $alt_title ) {
			$alt_title = $name;
		}

		$sku       = $this->get_row_value( $row, Field_Mapping::SKU_COLUMNS );
		$id_suffix = '' !== $sku ? sanitize_title( $sku ) : substr( md5( $name ), 0, 8 );

		$data = [
			'name'                 => $name,
			'item_type'            => $this->settings->get_item_type_placeholder(),
			'description'          => $alt_title,
			'purchase_description' => $alt_title,
			'unit'                 => $this->settings->get_default_unit(),
			'product_type'         => $this->settings->get_default_product_type(),
			'upc'                  => $this->build_numeric_placeholder( $this->settings->get_dummy_upc(), $id_suffix, 12 ),
			'ean'                  => $this->build_numeric_placeholder( $this->settings->get_dummy_ean(), $id_suffix, 13 ),
			'isbn'                 => $this->settings->get_dummy_isbn() . '-' . $id_suffix,
			'part_number'          => $this->settings->get_dummy_part_number() . '-' . $id_suffix,
		];

		foreach ( Field_Mapping::csv_field_map() as $zoho_field => $csv_aliases ) {
			if ( 'description' === $zoho_field ) {
				continue;
			}

			$value = $this->get_row_value( $row, $csv_aliases );
			if ( '' === $value ) {
				continue;
			}

			if ( in_array( $zoho_field, [ 'rate', 'purchase_rate' ], true ) ) {
				$data[ $zoho_field ] = (float) $value;
			} else {
				$data[ $zoho_field ] = $value;
			}
		}

		if ( $is_new ) {
			$data['initial_stock'] = $this->settings->get_default_initial_stock();
			if ( isset( $data['rate'] ) ) {
				$data['initial_stock_rate'] = (float) $data['rate'];
			} elseif ( isset( $data['purchase_rate'] ) ) {
				$data['initial_stock_rate'] = (float) $data['purchase_rate'];
			}
		}

		return array_filter(
			$data,
			static function ( $value ) {
				return null !== $value && '' !== $value;
			}
		);
	}

	/**
	 * @param string $base   Base dummy value.
	 * @param string $suffix Unique suffix.
	 * @param int    $length Target digit length.
	 * @return string
	 */
	private function build_numeric_placeholder( string $base, string $suffix, int $length ): string {
		$digits = preg_replace( '/\D+/', '', $base . $suffix );
		if ( '' === $digits ) {
			$digits = (string) abs( crc32( $suffix ) );
		}

		return str_pad( substr( $digits, -$length ), $length, '0', STR_PAD_LEFT );
	}

	/**
	 * @param array $row     CSV row.
	 * @param array $aliases Column aliases.
	 * @return string
	 */
	private function get_row_value( array $row, array $aliases ): string {
		$normalized = [];
		foreach ( $row as $key => $value ) {
			$normalized[ strtolower( trim( (string) $key ) ) ] = trim( (string) $value );
		}

		foreach ( $aliases as $alias ) {
			$key = strtolower( trim( $alias ) );
			if ( isset( $normalized[ $key ] ) && '' !== $normalized[ $key ] ) {
				return sanitize_text_field( $normalized[ $key ] );
			}
		}

		return '';
	}
}
