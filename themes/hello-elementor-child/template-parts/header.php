<?php
/**
 * The template for displaying header.
 *
 * @package HelloElementor
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$cta_url   = apply_filters( 'auscorp_header_cta_url', home_url( '/contact/' ) );
$cta_label = apply_filters( 'auscorp_header_cta_label', __( 'Book Free Printer Audit', 'hello-elementor-child' ) );

$menu_args = auscorp_header_menu_args( 'auscorp-header-menu' );

$header_nav_menu        = wp_nav_menu( $menu_args );
$header_mobile_nav_menu = wp_nav_menu( auscorp_header_menu_args( 'auscorp-header-mobile-menu' ) );
?>
<div class="auscorp-header-wrap">
	<header id="site-header" class="site-header auscorp-header" role="banner">
		<div class="auscorp-header__inner">

			<div class="auscorp-header__logo site-branding">
				<?php if ( has_custom_logo() ) : ?>
					<div class="site-logo">
						<?php the_custom_logo(); ?>
					</div>
				<?php else : ?>
					<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="auscorp-header__logo-link site-title" rel="home" title="<?php echo esc_attr__( 'Home', 'hello-elementor' ); ?>">
						<span class="auscorp-header__logo-text">auscorp<span class="auscorp-header__logo-accent">IT</span></span>
					</a>
				<?php endif; ?>
			</div>

			<?php if ( $header_nav_menu ) : ?>
				<nav class="auscorp-header__nav site-navigation" aria-label="<?php echo esc_attr__( 'Main menu', 'hello-elementor' ); ?>">
					<?php
					// PHPCS - escaped by WordPress with "wp_nav_menu"
					echo $header_nav_menu; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					?>
				</nav>
			<?php endif; ?>

			<div class="auscorp-header__actions">
				<a href="<?php echo esc_url( $cta_url ); ?>" class="auscorp-header__cta">
					<svg class="auscorp-header__cta-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true" xmlns="http://www.w3.org/2000/svg">
						<path d="M7 3H17C18.1 3 19 3.9 19 5V19C19 20.1 18.1 21 17 21H7C5.9 21 5 20.1 5 19V5C5 3.9 5.9 3 7 3Z" stroke="currentColor" stroke-width="1.5"/>
						<path d="M9 7H15M9 11H15M9 15H12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
					</svg>
					<span><?php echo esc_html( $cta_label ); ?></span>
				</a>

				<?php if ( $header_mobile_nav_menu ) : ?>
					<button
						type="button"
						class="auscorp-header__toggle"
						aria-label="<?php echo esc_attr__( 'Toggle navigation menu', 'hello-elementor-child' ); ?>"
						aria-expanded="false"
						aria-controls="auscorp-header-mobile-nav"
					>
						<span class="auscorp-header__toggle-bar"></span>
						<span class="auscorp-header__toggle-bar"></span>
						<span class="auscorp-header__toggle-bar"></span>
					</button>
				<?php endif; ?>
			</div>

		</div>

		<?php if ( $header_mobile_nav_menu ) : ?>
			<nav
				id="auscorp-header-mobile-nav"
				class="auscorp-header__mobile-nav"
				aria-label="<?php echo esc_attr__( 'Mobile menu', 'hello-elementor' ); ?>"
				aria-hidden="true"
			>
				<?php
				// PHPCS - escaped by WordPress with "wp_nav_menu"
				echo $header_mobile_nav_menu; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				?>
				<a href="<?php echo esc_url( $cta_url ); ?>" class="auscorp-header__cta auscorp-header__cta--mobile">
					<svg class="auscorp-header__cta-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true" xmlns="http://www.w3.org/2000/svg">
						<path d="M7 3H17C18.1 3 19 3.9 19 5V19C19 20.1 18.1 21 17 21H7C5.9 21 5 20.1 5 19V5C5 3.9 5.9 3 7 3Z" stroke="currentColor" stroke-width="1.5"/>
						<path d="M9 7H15M9 11H15M9 15H12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
					</svg>
					<span><?php echo esc_html( $cta_label ); ?></span>
				</a>
			</nav>
		<?php endif; ?>
	</header>
</div>
<div class="auscorp-header-backdrop" aria-hidden="true"></div>
