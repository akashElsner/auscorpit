<?php
/**
 * The Template for displaying product archives, including the main shop page which is a post type archive
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/archive-product.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 8.6.0
 */

defined('ABSPATH') || exit;

get_header('shop');

/**
 * Hook: woocommerce_before_main_content.
 *
 * @hooked woocommerce_output_content_wrapper - 10 (outputs opening divs for the content)
 * @hooked woocommerce_breadcrumb - 20
 * @hooked WC_Structured_Data::generate_website_data() - 30
 */
do_action('woocommerce_before_main_content');

/**
 * Hook: woocommerce_shop_loop_header.
 *
 * @since 8.6.0
 *
 * @hooked woocommerce_product_taxonomy_archive_header - 10
 */

do_action('woocommerce_shop_loop_header');

$template_id = 19536;
echo \Elementor\Plugin::$instance->frontend->get_builder_content($template_id, true);

if (woocommerce_product_loop()):
	?>

	<div class="custom-shop-wrapper">

		<div class="custom-shop-wrapper-inner">

			<!-- =========================
			 		LEFT SIDEBAR
			========================== -->
			<aside class="shop-sidebar">

				<div class="shop-filter-box">

					<h3>Advanced Filter</h3>

					<?php
					if ( shortcode_exists( 'wpf' ) ) {
						echo do_shortcode( '[wpf]' );
					} elseif ( shortcode_exists( 'woof' ) ) {
						echo do_shortcode( '[woof]' );
					}
					?>

				</div>

				<div class="shop-brands">

					<?php
					// Brand logos shortcode/widget here.
					// echo do_shortcode('[brands]');
					?>

				</div>

			</aside>

			<!-- =========================
			 		RIGHT CONTENT
			========================== -->
			<main class="shop-content">

				<!-- Search -->
				<div class="shop-search">
					<?php get_product_search_form(); ?>
				</div>

				<!-- Result Count + Sorting -->
				<div class="shop-toolbar">

					<div class="shop-result-count">
						<?php woocommerce_result_count(); ?>
					</div>

					<div class="shop-ordering">
						<?php woocommerce_catalog_ordering(); ?>
					</div>

				</div>

				<?php
				woocommerce_product_loop_start();

				while (have_posts()):
					the_post();

					do_action('woocommerce_shop_loop');

					wc_get_template_part('content', 'product');

				endwhile;

				woocommerce_product_loop_end();
				?>

				<div class="shop-pagination">
					<?php woocommerce_pagination(); ?>
				</div>

			</main>
		</div>

	</div>

	<?php
else:
	?>

	<div class="auscorp-shop-empty">
		<i class="fa-solid fa-box-open" aria-hidden="true"></i>
		<?php do_action('woocommerce_no_products_found'); ?>
		<a href="<?php echo esc_url( function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' ) ); ?>" class="auscorp-shop-empty__link">
			<?php esc_html_e( 'Browse All Products', 'hello-elementor-child' ); ?>
		</a>
	</div>

	<?php
endif;

?>

</div>
<?php 

/**
* Hook: woocommerce_after_main_content.
*
* @hooked woocommerce_output_content_wrapper_end - 10 (outputs closing divs for the content)
*/
do_action( 'woocommerce_after_main_content' );

/**
* Hook: woocommerce_sidebar.
*
* @hooked woocommerce_get_sidebar - 10
*/
//do_action( 'woocommerce_sidebar' );

get_footer( 'shop' );