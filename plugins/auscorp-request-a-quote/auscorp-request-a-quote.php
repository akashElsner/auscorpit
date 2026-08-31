<?php
/**
 * Plugin Name:       Auscorp Request a Quote
 * Description:       Lets logged-out visitors build a quote list, submit it with their name/email/note, and lets admins manage incoming quote requests.
 * Version:           1.0.1
 * Requires Plugins:  woocommerce
 * Author:             Auscorp
 * Text Domain:        auscorp-rfq
 *
 * @package AuscorpRFQ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'AUSCORP_RFQ_VERSION', '1.0.1' );
define( 'AUSCORP_RFQ_FILE', __FILE__ );
define( 'AUSCORP_RFQ_PATH', plugin_dir_path( __FILE__ ) );
define( 'AUSCORP_RFQ_URL', plugin_dir_url( __FILE__ ) );

/**
 * Bail with an admin notice if WooCommerce isn't active — the quote cart
 * leans on WC()->session and wc_get_product() throughout.
 *
 * @return void
 */
function auscorp_rfq_missing_woocommerce_notice() {
	?>
	<div class="notice notice-error">
		<p><?php esc_html_e( 'Auscorp Request a Quote requires WooCommerce to be installed and active.', 'auscorp-rfq' ); ?></p>
	</div>
	<?php
}

/**
 * Load the plugin once plugins are loaded, so WooCommerce is available to check against.
 *
 * @return void
 */
function auscorp_rfq_bootstrap() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'auscorp_rfq_missing_woocommerce_notice' );
		return;
	}

	require_once AUSCORP_RFQ_PATH . 'includes/class-auscorp-rfq-cart.php';
	require_once AUSCORP_RFQ_PATH . 'includes/class-auscorp-rfq-post-type.php';
	require_once AUSCORP_RFQ_PATH . 'includes/class-auscorp-rfq-admin.php';
	require_once AUSCORP_RFQ_PATH . 'includes/class-auscorp-rfq-ajax.php';
	require_once AUSCORP_RFQ_PATH . 'includes/class-auscorp-rfq-emails.php';
	require_once AUSCORP_RFQ_PATH . 'includes/class-auscorp-rfq-frontend.php';

	Auscorp_RFQ_Post_Type::instance();
	Auscorp_RFQ_Admin::instance();
	Auscorp_RFQ_Ajax::instance();
	Auscorp_RFQ_Frontend::instance();
}
add_action( 'plugins_loaded', 'auscorp_rfq_bootstrap' );

/**
 * Create the "Request a Quote" page on activation and register the CPT so
 * its rewrite rules exist before the flush.
 *
 * @return void
 */
function auscorp_rfq_activate() {
	require_once AUSCORP_RFQ_PATH . 'includes/class-auscorp-rfq-post-type.php';
	Auscorp_RFQ_Post_Type::register_post_type();

	$page_id = (int) get_option( 'auscorp_rfq_page_id' );

	if ( ! $page_id || ! get_post( $page_id ) ) {
		$page_id = wp_insert_post(
			[
				'post_title'   => __( 'Request a Quote', 'auscorp-rfq' ),
				'post_content' => '[auscorp_quote_cart]',
				'post_status'  => 'publish',
				'post_type'    => 'page',
			]
		);

		if ( $page_id && ! is_wp_error( $page_id ) ) {
			update_option( 'auscorp_rfq_page_id', $page_id );
		}
	}

	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'auscorp_rfq_activate' );

/**
 * Flush rewrite rules on deactivation so the CPT's rules don't linger.
 *
 * @return void
 */
function auscorp_rfq_deactivate() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'auscorp_rfq_deactivate' );
