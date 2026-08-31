<?php
/**
 * Front-end surface: the [auscorp_quote_cart] page, header badge helper,
 * and the guest-only hijack of the single-product add-to-cart form.
 *
 * @package AuscorpRFQ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Auscorp_RFQ_Frontend
 */
class Auscorp_RFQ_Frontend {

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
		add_shortcode( 'auscorp_quote_cart', [ $this, 'render_shortcode' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );

		if ( ! is_user_logged_in() ) {
			add_filter( 'woocommerce_product_single_add_to_cart_text', [ $this, 'add_to_quote_text' ] );
			add_filter( 'woocommerce_product_add_to_cart_text', [ $this, 'add_to_quote_text' ] );
		}
	}

	/**
	 * URL of the auto-created "Request a Quote" page.
	 *
	 * @return string
	 */
	public static function get_page_url() {
		$page_id = (int) get_option( 'auscorp_rfq_page_id' );

		if ( $page_id && get_post( $page_id ) ) {
			return get_permalink( $page_id );
		}

		return home_url( '/' );
	}

	/**
	 * Swap the add-to-cart button label to "Add to Quote" for guests.
	 *
	 * @param string $text Default button text.
	 * @return string
	 */
	public function add_to_quote_text( $text ) {
		unset( $text );

		return __( 'Add to Quote', 'auscorp-rfq' );
	}

	/**
	 * Enqueue the shared quote script/style on the front end.
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		if ( is_user_logged_in() ) {
			return;
		}

		wp_enqueue_style( 'auscorp-rfq', AUSCORP_RFQ_URL . 'assets/css/quote.css', [], AUSCORP_RFQ_VERSION );

		wp_enqueue_script( 'auscorp-rfq', AUSCORP_RFQ_URL . 'assets/js/quote.js', [], AUSCORP_RFQ_VERSION, true );

		wp_localize_script(
			'auscorp-rfq',
			'AuscorpRFQ',
			[
				'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
				'nonce'        => wp_create_nonce( Auscorp_RFQ_Ajax::NONCE_ACTION ),
				'quotePageUrl' => self::get_page_url(),
				'i18n'         => [
					'confirmRemove' => __( 'Remove this item from your quote list?', 'auscorp-rfq' ),
					'genericError'  => __( 'Something went wrong, please try again.', 'auscorp-rfq' ),
					'added'         => __( 'Added!', 'auscorp-rfq' ),
					'selectOptions' => __( 'Please select product options first.', 'auscorp-rfq' ),
				],
			]
		);
	}

	/**
	 * Render the [auscorp_quote_cart] shortcode: the item table plus the
	 * name/email/note submission form.
	 *
	 * @return string
	 */
	public function render_shortcode() {
		if ( is_user_logged_in() ) {
			return '<p class="auscorp-rfq-notice">' . esc_html__( 'You are logged in — use your cart at checkout instead of the quote list.', 'auscorp-rfq' ) . '</p>';
		}

		$items = Auscorp_RFQ_Cart::get_items();

		ob_start();
		?>
		<div class="auscorp-rfq-page">
			<div class="auscorp-rfq-header">
				<h1 class="auscorp-rfq-page-title"><?php esc_html_e( 'Your Quote List', 'auscorp-rfq' ); ?></h1>
				<p class="auscorp-rfq-page-subtitle"><?php esc_html_e( "Review the items below and send us your details — we'll get back to you with pricing.", 'auscorp-rfq' ); ?></p>
			</div>

			<?php if ( empty( $items ) ) : ?>
				<div class="auscorp-rfq-card auscorp-rfq-card--empty">
					<p class="auscorp-rfq-empty"><?php esc_html_e( 'Your quote list is empty.', 'auscorp-rfq' ); ?></p>
					<a class="auscorp-rfq-btn auscorp-rfq-btn--secondary" href="<?php echo esc_url( get_permalink( wc_get_page_id( 'shop' ) ) ); ?>"><?php esc_html_e( 'Continue Browsing', 'auscorp-rfq' ); ?></a>
				</div>
			<?php else : ?>
				<div class="auscorp-rfq-card auscorp-rfq-table-wrap">
					<table class="auscorp-rfq-table">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Product', 'auscorp-rfq' ); ?></th>
								<th><?php esc_html_e( 'Price', 'auscorp-rfq' ); ?></th>
								<th><?php esc_html_e( 'Quantity', 'auscorp-rfq' ); ?></th>
								<th><?php esc_html_e( 'Subtotal', 'auscorp-rfq' ); ?></th>
								<th></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $items as $item_key => $item ) : ?>
								<?php $this->render_row( $item_key, $item ); ?>
							<?php endforeach; ?>
						</tbody>
						<tfoot>
							<tr>
								<th colspan="3"><?php esc_html_e( 'Total', 'auscorp-rfq' ); ?></th>
								<th class="auscorp-rfq-total" colspan="2"><?php echo wp_kses_post( wc_price( Auscorp_RFQ_Cart::get_total() ) ); ?></th>
							</tr>
						</tfoot>
					</table>
				</div>

				<form class="auscorp-rfq-form auscorp-rfq-card" novalidate="novalidate">
					<h3><?php esc_html_e( 'Request a Quote', 'auscorp-rfq' ); ?></h3>

					<p class="auscorp-rfq-field">
						<label for="auscorp_rfq_name"><?php esc_html_e( 'Name', 'auscorp-rfq' ); ?> <span class="required">*</span></label>
						<input type="text" id="auscorp_rfq_name" name="name" required="required" />
					</p>

					<p class="auscorp-rfq-field">
						<label for="auscorp_rfq_email"><?php esc_html_e( 'Email', 'auscorp-rfq' ); ?> <span class="required">*</span></label>
						<input type="email" id="auscorp_rfq_email" name="email" required="required" />
					</p>

					<p class="auscorp-rfq-field">
						<label for="auscorp_rfq_note"><?php esc_html_e( 'Note (optional)', 'auscorp-rfq' ); ?></label>
						<textarea id="auscorp_rfq_note" name="note" rows="4"></textarea>
					</p>

					<p class="auscorp-rfq-response" role="status" aria-live="polite"></p>

					<button type="submit" class="auscorp-rfq-btn"><?php esc_html_e( 'Request a Quote', 'auscorp-rfq' ); ?></button>
				</form>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render a single cart row.
	 *
	 * @param string $item_key Item key.
	 * @param array  $item     Item data.
	 * @return void
	 */
	private function render_row( $item_key, $item ) {
		$product  = wc_get_product( $item['variation_id'] ? $item['variation_id'] : $item['product_id'] );
		$subtotal = (float) $item['price'] * (int) $item['quantity'];
		?>
		<tr class="auscorp-rfq-row" data-item-key="<?php echo esc_attr( $item_key ); ?>">
			<td class="auscorp-rfq-row__product">
				<span class="auscorp-rfq-row__thumb">
					<?php if ( $product ) : ?>
						<?php echo wp_kses_post( $product->get_image( 'thumbnail' ) ); ?>
					<?php endif; ?>
				</span>
				<span class="auscorp-rfq-row__name">
					<?php echo esc_html( $item['name'] ); ?>
					<?php if ( ! empty( $item['variation_text'] ) ) : ?>
						<br /><small><?php echo esc_html( $item['variation_text'] ); ?></small>
					<?php endif; ?>
				</span>
			</td>
			<td class="auscorp-rfq-row__price"><?php echo wp_kses_post( wc_price( $item['price'] ) ); ?></td>
			<td class="auscorp-rfq-row__qty">
				<input type="number" min="1" step="1" value="<?php echo esc_attr( $item['quantity'] ); ?>" class="auscorp-rfq-qty-input" />
			</td>
			<td class="auscorp-rfq-row__subtotal"><?php echo wp_kses_post( wc_price( $subtotal ) ); ?></td>
			<td class="auscorp-rfq-row__remove">
				<button type="button" class="auscorp-rfq-remove" aria-label="<?php esc_attr_e( 'Remove item', 'auscorp-rfq' ); ?>">&times;</button>
			</td>
		</tr>
		<?php
	}
}
