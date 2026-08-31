<?php
/**
 * Import-specific settings (defaults/placeholders only — Zoho OAuth lives in sync plugin).
 *
 * @package ZohoInventoryImport\Includes
 */

namespace ZohoInventoryImport\Includes;

defined( 'ABSPATH' ) || exit;

/**
 * Class Import_Settings
 */
class Import_Settings {

	const OPT_ITEM_TYPE            = 'zoho_inv_import_item_type_placeholder';
	const OPT_DEFAULT_UNIT         = 'zoho_inv_import_default_unit';
	const OPT_DEFAULT_STOCK        = 'zoho_inv_import_default_initial_stock';
	const OPT_DUMMY_UPC            = 'zoho_inv_import_dummy_upc';
	const OPT_DUMMY_EAN            = 'zoho_inv_import_dummy_ean';
	const OPT_DUMMY_ISBN           = 'zoho_inv_import_dummy_isbn';
	const OPT_DUMMY_PART_NUMBER    = 'zoho_inv_import_dummy_part_number';
	const OPT_DEFAULT_PRODUCT_TYPE = 'zoho_inv_import_default_product_type';

	/**
	 * Valid Zoho item_type values.
	 *
	 * @return array<string,string>
	 */
	public static function get_item_type_options(): array {
		return [
			'sales_and_purchases' => __( 'Sales and Purchases', 'zoho-inventory-import' ),
			'inventory'           => __( 'Inventory', 'zoho-inventory-import' ),
			'sales'               => __( 'Sales', 'zoho-inventory-import' ),
			'purchases'           => __( 'Purchases', 'zoho-inventory-import' ),
		];
	}

	/**
	 * Save import default settings from the admin form.
	 *
	 * @param array $input Raw POST data.
	 */
	public function save_settings( array $input ): void {
		$item_type = sanitize_text_field( $input['item_type_placeholder'] ?? 'inventory' );
		$options   = self::get_item_type_options();
		if ( ! isset( $options[ $item_type ] ) ) {
			$item_type = 'inventory';
		}
		update_option( self::OPT_ITEM_TYPE, $item_type );

		update_option( self::OPT_DEFAULT_UNIT, sanitize_text_field( $input['default_unit'] ?? '10' ) );
		update_option( self::OPT_DEFAULT_STOCK, max( 0, (float) ( $input['default_initial_stock'] ?? 10 ) ) );

		update_option( self::OPT_DUMMY_UPC, sanitize_text_field( $input['dummy_upc'] ?? '000000000000' ) );
		update_option( self::OPT_DUMMY_EAN, sanitize_text_field( $input['dummy_ean'] ?? '0000000000000' ) );
		update_option( self::OPT_DUMMY_ISBN, sanitize_text_field( $input['dummy_isbn'] ?? 'TEMP-ISBN' ) );
		update_option( self::OPT_DUMMY_PART_NUMBER, sanitize_text_field( $input['dummy_part_number'] ?? 'TEMP-PN' ) );

		$product_type = sanitize_text_field( $input['default_product_type'] ?? 'goods' );
		update_option(
			self::OPT_DEFAULT_PRODUCT_TYPE,
			in_array( $product_type, [ 'goods', 'service' ], true ) ? $product_type : 'goods'
		);
	}

	/**
	 * Required Zoho field not present in CSV — item_type.
	 */
	public function get_item_type_placeholder(): string {
		$value = (string) get_option( self::OPT_ITEM_TYPE, 'inventory' );
		return '' !== $value ? $value : 'inventory';
	}

	public function get_default_unit(): string {
		return (string) get_option( self::OPT_DEFAULT_UNIT, '10' );
	}

	public function get_default_initial_stock(): float {
		return (float) get_option( self::OPT_DEFAULT_STOCK, 10 );
	}

	public function get_dummy_upc(): string {
		return (string) get_option( self::OPT_DUMMY_UPC, '000000000000' );
	}

	public function get_dummy_ean(): string {
		return (string) get_option( self::OPT_DUMMY_EAN, '0000000000000' );
	}

	public function get_dummy_isbn(): string {
		return (string) get_option( self::OPT_DUMMY_ISBN, 'TEMP-ISBN' );
	}

	public function get_dummy_part_number(): string {
		return (string) get_option( self::OPT_DUMMY_PART_NUMBER, 'TEMP-PN' );
	}

	public function get_default_product_type(): string {
		$value = (string) get_option( self::OPT_DEFAULT_PRODUCT_TYPE, 'goods' );
		return in_array( $value, [ 'goods', 'service' ], true ) ? $value : 'goods';
	}
}
