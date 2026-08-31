<?php
/**
 * CSV column to Zoho field mapping definitions.
 *
 * @package ZohoInventoryImport\Includes
 */

namespace ZohoInventoryImport\Includes;

defined( 'ABSPATH' ) || exit;

/**
 * Class Field_Mapping
 */
class Field_Mapping {

	/** CSV columns that map to Zoho item name. */
	public const NAME_COLUMNS = [ 'name', 'item_name', 'product_name', 'title' ];

	/** CSV columns that map to Zoho SKU (duplicate check preferred). */
	public const SKU_COLUMNS = [ 'dynamic supplies sku', 'sku', 'item_sku', 'product_sku' ];

	/** CSV columns that map to Zoho sales rate. */
	public const RATE_COLUMNS = [ 'reseller price ex gst', 'rate', 'price', 'sales_price', 'selling_price' ];

	/** CSV columns that map to Zoho purchase rate. */
	public const PURCHASE_RATE_COLUMNS = [ 'rrp (inc gst)', 'purchase_rate', 'cost', 'cost_price', 'purchase_price', 'rrp' ];

	/** CSV columns used for description and purchase_description. */
	public const ALT_TITLE_COLUMNS = [ 'alternative product title', 'description', 'item_description', 'product_description' ];

	/**
	 * Return field map: Zoho field => CSV column aliases (lowercase).
	 *
	 * @return array<string,array<int,string>>
	 */
	public static function csv_field_map(): array {
		return [
			'sku'           => self::SKU_COLUMNS,
			'rate'          => self::RATE_COLUMNS,
			'purchase_rate' => self::PURCHASE_RATE_COLUMNS,
			'description'   => self::ALT_TITLE_COLUMNS,
		];
	}
}
