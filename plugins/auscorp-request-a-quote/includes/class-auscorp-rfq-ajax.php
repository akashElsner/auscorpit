<?php
/**
 * AJAX endpoints backing the quote cart: add, update quantity, remove, submit.
 *
 * @package AuscorpRFQ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Auscorp_RFQ_Ajax
 */
class Auscorp_RFQ_Ajax {

	const NONCE_ACTION = 'auscorp_rfq_nonce';

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @return self
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		foreach ( [ 'add', 'update', 'remove', 'submit' ] as $action ) {
			add_action( 'wp_ajax_auscorp_rfq_' . $action, [ $this, $action ] );
			add_action( 'wp_ajax_nopriv_auscorp_rfq_' . $action, [ $this, $action ] );
		}
	}

	/**
	 * Verify the shared front-end nonce, halting the request on failure.
	 *
	 * @return void
	 */
	private function verify_nonce() {
		if ( ! check_ajax_referer( self::NONCE_ACTION, 'nonce', false ) ) {
			wp_send_json_error( [ 'message' => __( 'Security check failed, please refresh the page and try again.', 'auscorp-rfq' ) ] );
		}
	}

	/**
	 * Build the JSON payload describing the current cart, sent back after
	 * every mutating request so the front-end can re-sync its badge/totals.
	 *
	 * @return array
	 */
	private function cart_state() {
		return [
			'count' => Auscorp_RFQ_Cart::get_count(),
			'total' => self::format_price( Auscorp_RFQ_Cart::get_total() ),
		];
	}

	/**
	 * Plain-text price for JSON responses. wc_price() encodes the currency
	 * symbol as an HTML entity (e.g. "&#36;") so it displays correctly when
	 * inserted as HTML; since the front-end writes these values via
	 * textContent (not innerHTML), the entity must be decoded here or it
	 * shows up literally in the browser.
	 *
	 * @param float $amount Amount to format.
	 * @return string
	 */
	private static function format_price( $amount ) {
		return html_entity_decode( wp_strip_all_tags( wc_price( $amount ) ), ENT_QUOTES, 'UTF-8' );
	}

	/**
	 * Add a product (optionally a variation) to the quote cart.
	 *
	 * @return void
	 */
	public function add() {
		$this->verify_nonce();

		$product_id     = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
		$variation_id    = isset( $_POST['variation_id'] ) ? absint( $_POST['variation_id'] ) : 0;
		$quantity        = isset( $_POST['quantity'] ) ? absint( $_POST['quantity'] ) : 1;
		$variation_text  = isset( $_POST['variation_text'] ) ? sanitize_text_field( wp_unslash( $_POST['variation_text'] ) ) : '';

		if ( ! $product_id ) {
			wp_send_json_error( [ 'message' => __( 'Missing product.', 'auscorp-rfq' ) ] );
		}

		$result = Auscorp_RFQ_Cart::add_item( $product_id, $variation_id, $quantity, $variation_text );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( [ 'message' => $result->get_error_message() ] );
		}

		wp_send_json_success(
			array_merge(
				[ 'message' => __( 'Added to your quote list.', 'auscorp-rfq' ) ],
				$this->cart_state()
			)
		);
	}

	/**
	 * Update the quantity of a line in the quote cart (0 removes it).
	 *
	 * @return void
	 */
	public function update() {
		$this->verify_nonce();

		$item_key = isset( $_POST['item_key'] ) ? sanitize_text_field( wp_unslash( $_POST['item_key'] ) ) : '';
		$quantity = isset( $_POST['quantity'] ) ? absint( $_POST['quantity'] ) : 0;

		if ( ! $item_key ) {
			wp_send_json_error( [ 'message' => __( 'Missing item.', 'auscorp-rfq' ) ] );
		}

		$result = Auscorp_RFQ_Cart::update_quantity( $item_key, $quantity );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( [ 'message' => $result->get_error_message() ] );
		}

		$line_subtotal = '';

		if ( empty( $result['removed'] ) ) {
			$line_subtotal = self::format_price( (float) $result['price'] * (int) $result['quantity'] );
		}

		wp_send_json_success(
			array_merge(
				[
					'removed'       => ! empty( $result['removed'] ),
					'line_subtotal' => $line_subtotal,
				],
				$this->cart_state()
			)
		);
	}

	/**
	 * Remove a line from the quote cart entirely.
	 *
	 * @return void
	 */
	public function remove() {
		$this->verify_nonce();

		$item_key = isset( $_POST['item_key'] ) ? sanitize_text_field( wp_unslash( $_POST['item_key'] ) ) : '';

		if ( ! $item_key ) {
			wp_send_json_error( [ 'message' => __( 'Missing item.', 'auscorp-rfq' ) ] );
		}

		Auscorp_RFQ_Cart::remove_item( $item_key );

		wp_send_json_success( $this->cart_state() );
	}

	/**
	 * Submit the quote request: validate the form, create the quote post,
	 * fire off the notification emails, and empty the cart.
	 *
	 * @return void
	 */
	public function submit() {
		$this->verify_nonce();

		$items = Auscorp_RFQ_Cart::get_items();

		if ( empty( $items ) ) {
			wp_send_json_error( [ 'message' => __( 'Your quote list is empty.', 'auscorp-rfq' ) ] );
		}

		$name  = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		$note  = isset( $_POST['note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['note'] ) ) : '';

		if ( '' === $name ) {
			wp_send_json_error( [ 'message' => __( 'Please enter your name.', 'auscorp-rfq' ) ] );
		}

		if ( '' === $email || ! is_email( $email ) ) {
			wp_send_json_error( [ 'message' => __( 'Please enter a valid email address.', 'auscorp-rfq' ) ] );
		}

		$post_id = wp_insert_post(
			[
				'post_type'   => Auscorp_RFQ_Post_Type::POST_TYPE,
				'post_status' => 'publish',
				/* translators: %s: customer name */
				'post_title'  => sprintf( __( 'Quote request from %s', 'auscorp-rfq' ), $name ),
			],
			true
		);

		if ( is_wp_error( $post_id ) ) {
			wp_send_json_error( [ 'message' => __( 'Could not submit your quote request, please try again.', 'auscorp-rfq' ) ] );
		}

		update_post_meta( $post_id, '_customer_name', $name );
		update_post_meta( $post_id, '_customer_email', $email );
		update_post_meta( $post_id, '_customer_note', $note );
		update_post_meta( $post_id, '_quote_items', array_values( $items ) );
		update_post_meta( $post_id, '_quote_status', 'pending' );

		Auscorp_RFQ_Emails::send(
			$post_id,
			[
				'name'  => $name,
				'email' => $email,
				'note'  => $note,
				'items' => array_values( $items ),
			]
		);

		Auscorp_RFQ_Cart::clear();

		wp_send_json_success(
			[
				'message' => __( 'Your quote request has been sent. We will be in touch shortly.', 'auscorp-rfq' ),
			]
		);
	}
}
