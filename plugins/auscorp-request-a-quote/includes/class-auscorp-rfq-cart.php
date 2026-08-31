<?php
/**
 * Session-backed quote cart for logged-out visitors.
 *
 * Rides on WC()->session (already initialised for every front-end request
 * by WooCommerce) instead of a custom cookie/table, so persistence, expiry
 * and multi-tab behaviour come for free.
 *
 * @package AuscorpRFQ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Auscorp_RFQ_Cart
 */
class Auscorp_RFQ_Cart {

	const SESSION_KEY = 'auscorp_rfq_items';

	/**
	 * Get all items currently in the quote cart.
	 *
	 * @return array<string, array>
	 */
	public static function get_items() {
		if ( ! WC()->session ) {
			return [];
		}

		$items = WC()->session->get( self::SESSION_KEY, [] );

		return is_array( $items ) ? $items : [];
	}

	/**
	 * Build a stable item key from product + variation + variation attributes,
	 * mirroring WC_Cart::generate_cart_id() so identical selections merge.
	 *
	 * @param int    $product_id   Product ID.
	 * @param int    $variation_id Variation ID, 0 for simple products.
	 * @param string $variation_text Human-readable variation summary, used to
	 *                               distinguish otherwise-identical keys.
	 * @return string
	 */
	public static function generate_item_key( $product_id, $variation_id, $variation_text = '' ) {
		return md5( $product_id . '_' . $variation_id . '_' . $variation_text );
	}

	/**
	 * Add a product to the quote cart, merging quantity into an existing line
	 * if the same product/variation is already present.
	 *
	 * @param int    $product_id     Product ID.
	 * @param int    $variation_id   Variation ID, 0 for simple products.
	 * @param int    $quantity       Quantity to add.
	 * @param string $variation_text Human-readable variation summary (e.g. "Size: Large").
	 * @return array|WP_Error Updated item on success.
	 */
	public static function add_item( $product_id, $variation_id, $quantity, $variation_text = '' ) {
		$product_id = absint( $product_id );
		$quantity   = max( 1, absint( $quantity ) );
		$product    = wc_get_product( $variation_id ? $variation_id : $product_id );

		if ( ! $product || 'publish' !== get_post_status( $product_id ) ) {
			return new WP_Error( 'auscorp_rfq_invalid_product', __( 'This product is no longer available.', 'auscorp-rfq' ) );
		}

		$item_key = self::generate_item_key( $product_id, $variation_id, $variation_text );
		$items    = self::get_items();

		if ( isset( $items[ $item_key ] ) ) {
			$items[ $item_key ]['quantity'] += $quantity;
		} else {
			$parent_product = $variation_id ? wc_get_product( $product_id ) : $product;

			$items[ $item_key ] = [
				'product_id'     => $product_id,
				'variation_id'   => absint( $variation_id ),
				'name'           => $parent_product ? $parent_product->get_name() : $product->get_name(),
				'sku'            => $product->get_sku(),
				'variation_text' => sanitize_text_field( $variation_text ),
				'quantity'       => $quantity,
				'price'          => (float) $product->get_price(),
			];
		}

		self::save_items( $items );

		return $items[ $item_key ];
	}

	/**
	 * Update the quantity of an existing line. A quantity of 0 removes it.
	 *
	 * @param string $item_key Item key.
	 * @param int    $quantity New quantity.
	 * @return array|WP_Error
	 */
	public static function update_quantity( $item_key, $quantity ) {
		$items    = self::get_items();
		$quantity = absint( $quantity );

		if ( ! isset( $items[ $item_key ] ) ) {
			return new WP_Error( 'auscorp_rfq_missing_item', __( 'That item is no longer in your quote list.', 'auscorp-rfq' ) );
		}

		if ( 0 === $quantity ) {
			unset( $items[ $item_key ] );
			self::save_items( $items );

			return [ 'removed' => true ];
		}

		$items[ $item_key ]['quantity'] = $quantity;
		self::save_items( $items );

		return $items[ $item_key ];
	}

	/**
	 * Remove a single line from the quote cart.
	 *
	 * @param string $item_key Item key.
	 * @return void
	 */
	public static function remove_item( $item_key ) {
		$items = self::get_items();
		unset( $items[ $item_key ] );
		self::save_items( $items );
	}

	/**
	 * Empty the quote cart, used after a quote request has been submitted.
	 *
	 * @return void
	 */
	public static function clear() {
		self::save_items( [] );
	}

	/**
	 * Total quantity across all lines, used for the header badge.
	 *
	 * @return int
	 */
	public static function get_count() {
		$count = 0;

		foreach ( self::get_items() as $item ) {
			$count += (int) $item['quantity'];
		}

		return $count;
	}

	/**
	 * Sum of (price × quantity) across all lines.
	 *
	 * @return float
	 */
	public static function get_total() {
		$total = 0.0;

		foreach ( self::get_items() as $item ) {
			$total += (float) $item['price'] * (int) $item['quantity'];
		}

		return $total;
	}

	/**
	 * Persist items back to the WooCommerce session.
	 *
	 * @param array $items Items keyed by item key.
	 * @return void
	 */
	private static function save_items( $items ) {
		if ( ! WC()->session ) {
			return;
		}

		WC()->session->set( self::SESSION_KEY, $items );

		// A brand-new guest has no session cookie yet, so WC_Session_Handler's
		// generic shutdown save skips persisting (has_session() is false until
		// a cookie exists). Force the cookie now, same as WC_Cart::add_to_cart()
		// does, so the very first add actually survives the next page load.
		if ( method_exists( WC()->session, 'set_customer_session_cookie' ) ) {
			WC()->session->set_customer_session_cookie( true );
		}
	}
}
