<?php
/**
 * Email notifications sent when a quote request is submitted.
 *
 * @package AuscorpRFQ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Auscorp_RFQ_Emails
 */
class Auscorp_RFQ_Emails {

	/**
	 * Send the admin notification and the customer confirmation for a
	 * newly submitted quote.
	 *
	 * @param int   $post_id Quote post ID.
	 * @param array $data    Submitted data: name, email, note, items.
	 * @return void
	 */
	public static function send( $post_id, $data ) {
		self::send_admin_notification( $post_id, $data );
		self::send_customer_confirmation( $post_id, $data );
	}

	/**
	 * Notify the site admin that a new quote request has come in.
	 *
	 * @param int   $post_id Quote post ID.
	 * @param array $data    Submitted data.
	 * @return void
	 */
	private static function send_admin_notification( $post_id, $data ) {
		$to      = apply_filters( 'auscorp_rfq_admin_email', get_option( 'admin_email' ) );
		$subject = sprintf(
			/* translators: %s: customer name */
			__( 'New quote request from %s', 'auscorp-rfq' ),
			$data['name']
		);

		$body  = '<p>' . esc_html__( 'A new quote request has been submitted.', 'auscorp-rfq' ) . '</p>';
		$body .= self::render_customer_block( $data );
		$body .= self::render_items_table( $data['items'] );
		$body .= '<p><a href="' . esc_url( admin_url( 'post.php?post=' . $post_id . '&action=edit' ) ) . '">' . esc_html__( 'View this quote request', 'auscorp-rfq' ) . '</a></p>';

		self::mail( $to, $subject, $body );
	}

	/**
	 * Send the customer a copy of their quote request for their records.
	 *
	 * @param int   $post_id Quote post ID.
	 * @param array $data    Submitted data.
	 * @return void
	 */
	private static function send_customer_confirmation( $post_id, $data ) {
		unset( $post_id );

		$subject = sprintf(
			/* translators: %s: site name */
			__( 'Your quote request to %s', 'auscorp-rfq' ),
			get_bloginfo( 'name' )
		);

		$body  = '<p>' . sprintf(
			/* translators: %s: customer name */
			esc_html__( 'Hi %s,', 'auscorp-rfq' ),
			esc_html( $data['name'] )
		) . '</p>';
		$body .= '<p>' . esc_html__( 'Thanks for your quote request. Here is a copy of what you submitted — our team will be in touch shortly.', 'auscorp-rfq' ) . '</p>';
		$body .= self::render_items_table( $data['items'] );

		if ( ! empty( $data['note'] ) ) {
			$body .= '<p><strong>' . esc_html__( 'Your note:', 'auscorp-rfq' ) . '</strong><br />' . esc_html( $data['note'] ) . '</p>';
		}

		self::mail( $data['email'], $subject, $body );
	}

	/**
	 * Render the customer details block used in the admin email.
	 *
	 * @param array $data Submitted data.
	 * @return string
	 */
	private static function render_customer_block( $data ) {
		$html  = '<p>';
		$html .= '<strong>' . esc_html__( 'Name:', 'auscorp-rfq' ) . '</strong> ' . esc_html( $data['name'] ) . '<br />';
		$html .= '<strong>' . esc_html__( 'Email:', 'auscorp-rfq' ) . '</strong> ' . esc_html( $data['email'] ) . '<br />';

		if ( ! empty( $data['note'] ) ) {
			$html .= '<strong>' . esc_html__( 'Note:', 'auscorp-rfq' ) . '</strong> ' . esc_html( $data['note'] );
		}

		$html .= '</p>';

		return $html;
	}

	/**
	 * Render the requested items as an HTML table shared by both emails.
	 *
	 * @param array $items Quote line items.
	 * @return string
	 */
	private static function render_items_table( $items ) {
		$html  = '<table cellpadding="8" cellspacing="0" border="1" style="border-collapse:collapse;width:100%;">';
		$html .= '<thead><tr>';
		$html .= '<th style="text-align:left;">' . esc_html__( 'Product', 'auscorp-rfq' ) . '</th>';
		$html .= '<th style="text-align:left;">' . esc_html__( 'SKU', 'auscorp-rfq' ) . '</th>';
		$html .= '<th style="text-align:right;">' . esc_html__( 'Price', 'auscorp-rfq' ) . '</th>';
		$html .= '<th style="text-align:right;">' . esc_html__( 'Qty', 'auscorp-rfq' ) . '</th>';
		$html .= '<th style="text-align:right;">' . esc_html__( 'Subtotal', 'auscorp-rfq' ) . '</th>';
		$html .= '</tr></thead><tbody>';

		$total = 0.0;

		foreach ( $items as $item ) {
			$subtotal = (float) $item['price'] * (int) $item['quantity'];
			$total   += $subtotal;

			$name = esc_html( $item['name'] );
			if ( ! empty( $item['variation_text'] ) ) {
				$name .= '<br /><small>' . esc_html( $item['variation_text'] ) . '</small>';
			}

			$html .= '<tr>';
			$html .= '<td>' . $name . '</td>';
			$html .= '<td>' . esc_html( $item['sku'] ) . '</td>';
			$html .= '<td style="text-align:right;">' . wp_kses_post( wc_price( $item['price'] ) ) . '</td>';
			$html .= '<td style="text-align:right;">' . esc_html( $item['quantity'] ) . '</td>';
			$html .= '<td style="text-align:right;">' . wp_kses_post( wc_price( $subtotal ) ) . '</td>';
			$html .= '</tr>';
		}

		$html .= '</tbody><tfoot><tr>';
		$html .= '<th colspan="4" style="text-align:right;">' . esc_html__( 'Total', 'auscorp-rfq' ) . '</th>';
		$html .= '<th style="text-align:right;">' . wp_kses_post( wc_price( $total ) ) . '</th>';
		$html .= '</tr></tfoot></table>';

		return $html;
	}

	/**
	 * Send an HTML email using WooCommerce's mailer so it inherits the
	 * store's email styling.
	 *
	 * @param string $to      Recipient address.
	 * @param string $subject Email subject.
	 * @param string $body    Email body (HTML).
	 * @return void
	 */
	private static function mail( $to, $subject, $body ) {
		$mailer      = WC()->mailer();
		$wrapped     = $mailer->wrap_message( $subject, $body );
		$headers     = [ 'Content-Type: text/html; charset=UTF-8' ];

		$mailer->send( $to, $subject, $wrapped, $headers );
	}
}
