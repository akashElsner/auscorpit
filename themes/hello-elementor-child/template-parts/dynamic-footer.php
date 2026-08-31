<?php
/**
 * The template for displaying footer.
 *
 * @package HelloElementor
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$is_editor = isset( $_GET['elementor-preview'] );
$site_name = get_bloginfo( 'name' );
$tagline   = get_bloginfo( 'description', 'display' );
$footer_class = did_action( 'elementor/loaded' ) ? hello_get_footer_layout_class() : '';
$footer_nav_menu = wp_nav_menu( [
	'theme_location' => 'menu-2',
	'fallback_cb' => false,
	'container' => false,
	'echo' => false,
] );
$footer_nav_menu2 = wp_nav_menu( [
	'menu' => '525',
	'fallback_cb' => false,
	'container' => false,
	'echo' => false,
] );
$footer_nav_menu3 = wp_nav_menu( [
	'menu' => '526',
	'fallback_cb' => false,
	'container' => false,
	'echo' => false,
] );
?>
<footer id="site-footer" class="site-footer dynamic-footer <?php echo esc_attr( $footer_class ); ?>">
	<div class="site-footer-container">
		<div class="footer-inner">
			<div class="site-branding show-<?php echo esc_attr( hello_elementor_get_setting( 'hello_footer_logo_type' ) ); ?>">
				<?php if ( has_custom_logo() && ( 'title' !== hello_elementor_get_setting( 'hello_footer_logo_type' ) || $is_editor ) ) : ?>
					<div class="site-logo <?php echo esc_attr( hello_show_or_hide( 'hello_footer_logo_display' ) ); ?>">
						<?php the_custom_logo(); ?>
					</div>
				<?php endif;

				if ( $site_name && ( 'logo' !== hello_elementor_get_setting( 'hello_footer_logo_type' ) ) || $is_editor ) : ?>
					<div class="site-title <?php echo esc_attr( hello_show_or_hide( 'hello_footer_logo_display' ) ); ?>">
						<a href="<?php echo esc_url( home_url( '/' ) ); ?>" title="<?php echo esc_attr__( 'Home', 'hello-elementor' ); ?>" rel="home">
							<?php echo esc_html( $site_name ); ?>
						</a>
					</div>
				<?php endif;

				if ( $tagline || $is_editor ) : ?>
					<p class="site-description <?php echo esc_attr( hello_show_or_hide( 'hello_footer_tagline_display' ) ); ?>">
						<?php echo esc_html( $tagline ); ?>
					</p>
				<?php endif; ?>
			</div>

			<?php if ( $footer_nav_menu ) : ?>
				<div class="footer-menu-wrap">
					<h4>Services</h4>
					<nav class="site-navigation <?php echo esc_attr( hello_show_or_hide( 'hello_footer_menu_display' ) ); ?>" aria-label="<?php echo esc_attr__( 'Footer menu', 'hello-elementor' ); ?>">
						<?php echo $footer_nav_menu; ?>
					</nav>
				</div>
				<div class="footer-menu-wrap get-in-touch">
					<h4>Get in Touch</h4>
					<nav class="site-navigation <?php echo esc_attr( hello_show_or_hide( 'hello_footer_menu_display' ) ); ?>" aria-label="<?php echo esc_attr__( 'Footer menu', 'hello-elementor' ); ?>">
						<?php echo $footer_nav_menu2; ?>
					</nav>
				</div>
			<?php endif; ?>
			<div class="google-map-wrap">
				<h4>Google Map</h4>
				<iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3388.769792306624!2d115.8946812761288!3d-31.858479074060966!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2a32b1aa37f72169%3A0xcc6e219cec9ab0b4!2s3%2F15%20Industry%20St%2C%20Malaga%20WA%206090%2C%20Australia!5e0!3m2!1sen!2sin!4v1783504999914!5m2!1sen!2sin" allowfullscreen="" loading="lazy" referrerpolicy="strict-origin-when-cross-origin" style="width:100%; height:100%; border:0;"></iframe>
			</div>
				
		</div>
		<?php if ( '' !== hello_elementor_get_setting( 'hello_footer_copyright_text' ) || $is_editor ) : ?>
			<div class="copyright-section">
				<div class="copyright <?php echo esc_attr( hello_show_or_hide( 'hello_footer_copyright_display' ) ); ?>">
					<p><?php echo wp_kses_post( hello_elementor_get_setting( 'hello_footer_copyright_text' ) ); ?></p>
				</div>
				<nav class="site-navigation social-icon-menu <?php echo esc_attr( hello_show_or_hide( 'hello_footer_menu_display' ) ); ?>" aria-label="<?php echo esc_attr__( 'Footer menu', 'hello-elementor' ); ?>">
					<?php
					// PHPCS - escaped by WordPress with "wp_nav_menu"
					echo $footer_nav_menu3; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					?>
				</nav>
			</div>
		<?php endif; ?>
	</div>
</footer>
