<?php
/**
 * Registers the "Quote Request" custom post type that stores submitted quotes.
 *
 * @package AuscorpRFQ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Auscorp_RFQ_Post_Type
 */
class Auscorp_RFQ_Post_Type {

	const POST_TYPE = 'auscorp_quote';

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
		add_action( 'init', [ __CLASS__, 'register_post_type' ] );
	}

	/**
	 * Register the "auscorp_quote" custom post type.
	 *
	 * Static + callable directly from the activation hook so its rewrite
	 * rules exist before flush_rewrite_rules() runs.
	 *
	 * @return void
	 */
	public static function register_post_type() {
		register_post_type(
			self::POST_TYPE,
			[
				'labels'              => [
					'name'               => __( 'Quote Requests', 'auscorp-rfq' ),
					'singular_name'      => __( 'Quote Request', 'auscorp-rfq' ),
					'menu_name'          => __( 'Quote Requests', 'auscorp-rfq' ),
					'all_items'          => __( 'All Quote Requests', 'auscorp-rfq' ),
					'view_item'          => __( 'View Quote Request', 'auscorp-rfq' ),
					'edit_item'          => __( 'Quote Request', 'auscorp-rfq' ),
					'search_items'       => __( 'Search Quote Requests', 'auscorp-rfq' ),
					'not_found'          => __( 'No quote requests found.', 'auscorp-rfq' ),
				],
				'public'               => false,
				'show_ui'              => true,
				'show_in_menu'         => true,
				'show_in_admin_bar'    => false,
				'show_in_nav_menus'    => false,
				'show_in_rest'         => false,
				'menu_icon'            => 'dashicons-media-spreadsheet',
				'menu_position'        => 56,
				'capability_type'      => 'post',
				'capabilities'         => [
					'create_posts' => 'do_not_allow',
				],
				'map_meta_cap'         => true,
				'supports'             => [ 'title' ],
				'has_archive'          => false,
				'rewrite'              => false,
				'query_var'            => false,
			]
		);
	}
}
