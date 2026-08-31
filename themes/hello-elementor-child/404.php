<?php
/**
 * The template for displaying 404 pages (not found)
 *
 * @package HelloElementorChild
 */

defined( 'ABSPATH' ) || exit;

get_header();

$template_id = 19536;
echo \Elementor\Plugin::$instance->frontend->get_builder_content( $template_id, true );
?>

<div class="auscorp-404-page">
	<div class="auscorp-404-page__card">
		<span class="auscorp-404-page__code">404</span>

		<h1 class="auscorp-404-page__title"><?php esc_html_e( 'Page Not Found', 'hello-elementor-child' ); ?></h1>

		<p class="auscorp-404-page__desc">
			<?php esc_html_e( 'Sorry, the page you\'re looking for doesn\'t exist or may have been moved.', 'hello-elementor-child' ); ?>
		</p>

		<div class="auscorp-404-page__actions">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="auscorp-404-page__btn auscorp-404-page__btn--primary">
				<?php esc_html_e( 'Back to Home', 'hello-elementor-child' ); ?>
			</a>

			<?php if ( function_exists( 'wc_get_page_permalink' ) ) : ?>
				<a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="auscorp-404-page__btn auscorp-404-page__btn--secondary">
					<?php esc_html_e( 'Visit Shop', 'hello-elementor-child' ); ?>
				</a>
			<?php endif; ?>
		</div>


		
	</div>
</div>

<?php get_footer(); ?>
