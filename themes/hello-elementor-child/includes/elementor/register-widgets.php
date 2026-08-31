<?php
/**
 * Register Auscorp Elementor widgets.
 *
 * @package HelloElementorChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register custom Elementor category.
 *
 * @param \Elementor\Elements_Manager $elements_manager Elementor elements manager.
 * @return void
 */
function auscorp_register_elementor_category( $elements_manager ) {
	$elements_manager->add_category(
		'auscorp',
		[
			'title' => esc_html__( 'Auscorp', 'hello-elementor-child' ),
			'icon'  => 'fa fa-plug',
		]
	);
}
add_action( 'elementor/elements/categories_registered', 'auscorp_register_elementor_category' );

/**
 * Register widget assets.
 *
 * @return void
 */
function auscorp_register_elementor_widget_assets() {
	wp_register_style(
		'auscorp-hero-slider',
		get_stylesheet_directory_uri() . '/assets/css/auscorp-hero-slider.css',
		[ 'figtree-font', 'font-awesome-6' ],
		filemtime( get_stylesheet_directory() . '/assets/css/auscorp-hero-slider.css' )
	);

	wp_register_script(
		'auscorp-hero-slider',
		get_stylesheet_directory_uri() . '/assets/js/auscorp-hero-slider.js',
		[],
		filemtime( get_stylesheet_directory() . '/assets/js/auscorp-hero-slider.js' ),
		true
	);

	wp_register_style(
		'auscorp-cta-button',
		get_stylesheet_directory_uri() . '/assets/css/auscorp-cta-button.css',
		[ 'figtree-font', 'font-awesome-6' ],
		filemtime( get_stylesheet_directory() . '/assets/css/auscorp-cta-button.css' )
	);

	wp_register_style(
		'auscorp-dual-color-heading',
		get_stylesheet_directory_uri() . '/assets/css/auscorp-dual-color-heading.css',
		[ 'figtree-font' ],
		filemtime( get_stylesheet_directory() . '/assets/css/auscorp-dual-color-heading.css' )
	);

	wp_register_style(
		'auscorp-services-grid',
		get_stylesheet_directory_uri() . '/assets/css/auscorp-services-grid.css',
		[ 'figtree-font', 'font-awesome-6' ],
		filemtime( get_stylesheet_directory() . '/assets/css/auscorp-services-grid.css' )
	);

	wp_register_style(
		'auscorp-products-grid',
		get_stylesheet_directory_uri() . '/assets/css/auscorp-products-grid.css',
		[ 'figtree-font', 'font-awesome-6' ],
		filemtime( get_stylesheet_directory() . '/assets/css/auscorp-products-grid.css' )
	);

	wp_register_style(
		'auscorp-testimonials-slider',
		get_stylesheet_directory_uri() . '/assets/css/auscorp-testimonials-slider.css',
		[ 'figtree-font', 'font-awesome-6' ],
		filemtime( get_stylesheet_directory() . '/assets/css/auscorp-testimonials-slider.css' )
	);

	wp_register_script(
		'auscorp-testimonials-slider',
		get_stylesheet_directory_uri() . '/assets/js/auscorp-testimonials-slider.js',
		[],
		filemtime( get_stylesheet_directory() . '/assets/js/auscorp-testimonials-slider.js' ),
		true
	);

	wp_register_style(
		'auscorp-page-banner',
		get_stylesheet_directory_uri() . '/assets/css/auscorp-page-banner.css',
		[ 'figtree-font' ],
		filemtime( get_stylesheet_directory() . '/assets/css/auscorp-page-banner.css' )
	);
}
add_action( 'wp_enqueue_scripts', 'auscorp_register_elementor_widget_assets', 15 );

/**
 * Register Elementor widgets.
 *
 * @param \Elementor\Widgets_Manager $widgets_manager Widgets manager.
 * @return void
 */
function auscorp_register_elementor_widgets( $widgets_manager ) {
	require_once get_stylesheet_directory() . '/includes/elementor/widgets/hero-slider.php';
	require_once get_stylesheet_directory() . '/includes/elementor/widgets/cta-button.php';
	require_once get_stylesheet_directory() . '/includes/elementor/widgets/dual-color-heading.php';
	require_once get_stylesheet_directory() . '/includes/elementor/widgets/services-grid.php';
	require_once get_stylesheet_directory() . '/includes/elementor/widgets/products-grid.php';
	require_once get_stylesheet_directory() . '/includes/elementor/widgets/testimonials-slider.php';
	require_once get_stylesheet_directory() . '/includes/elementor/widgets/page-banner.php';

	$widgets_manager->register( new Auscorp_Hero_Slider_Widget() );
	$widgets_manager->register( new Auscorp_CTA_Button_Widget() );
	$widgets_manager->register( new Auscorp_Dual_Color_Heading_Widget() );
	$widgets_manager->register( new Auscorp_Services_Grid_Widget() );
	$widgets_manager->register( new Auscorp_Products_Grid_Widget() );
	$widgets_manager->register( new Auscorp_Testimonials_Slider_Widget() );
	$widgets_manager->register( new Auscorp_Page_Banner_Widget() );
}
add_action( 'elementor/widgets/register', 'auscorp_register_elementor_widgets' );
