<?php
/**
 * The template for displaying header.
 *
 * @package HelloElementor
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! hello_get_header_display() ) {
	return;
}

$is_editor    = isset( $_GET['elementor-preview'] );
$header_class = did_action( 'elementor/loaded' ) ? hello_get_header_layout_class() : '';
$cta_url      = apply_filters( 'auscorp_header_cta_url', home_url( '/contact/' ) );
$cta_label    = apply_filters( 'auscorp_header_cta_label', __( 'Book Free Printer Audit', 'hello-elementor-child' ) );

$menu_args = auscorp_header_menu_args( 'auscorp-header-menu' );

$header_nav_menu        = wp_nav_menu( $menu_args );
$header_mobile_nav_menu = wp_nav_menu( auscorp_header_menu_args( 'auscorp-header-mobile-menu' ) );
?>
<div class="auscorp-header-wrap">
	<header id="site-header" class="site-header dynamic-header auscorp-header <?php echo esc_attr( $header_class ); ?>" role="banner">
		<div class="auscorp-header__inner">

			<div class="auscorp-header__logo site-branding">
				<?php if ( has_custom_logo() && ( 'title' !== hello_elementor_get_setting( 'hello_header_logo_type' ) || $is_editor ) ) : ?>
					<div class="site-logo <?php echo esc_attr( hello_show_or_hide( 'hello_header_logo_display' ) ); ?>">
						<?php the_custom_logo(); ?>
					</div>
				<?php elseif ( get_bloginfo( 'name' ) && ( 'logo' !== hello_elementor_get_setting( 'hello_header_logo_type' ) || $is_editor ) ) : ?>
					<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="auscorp-header__logo-link site-title <?php echo esc_attr( hello_show_or_hide( 'hello_header_logo_display' ) ); ?>" rel="home">
						<span class="auscorp-header__logo-text">auscorp<span class="auscorp-header__logo-accent">IT</span></span>
					</a>
				<?php endif; ?>
			</div>

			<?php if ( $header_nav_menu ) : ?>
				<nav class="auscorp-header__nav site-navigation <?php echo esc_attr( hello_show_or_hide( 'hello_header_menu_display' ) ); ?>" aria-label="<?php echo esc_attr__( 'Main menu', 'hello-elementor' ); ?>">
					<?php
					// PHPCS - escaped by WordPress with "wp_nav_menu"
					echo $header_nav_menu; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					?>
				</nav>
			<?php endif; ?>

			<div class="auscorp-header__actions">

			    <?php if ( is_user_logged_in() ) :

			        $current_user = wp_get_current_user();
			        ?>

			        <div class="auscorp-user-menu">

			            <a href="javascript:void(0);" class="auscorp-user-menu__toggle">
						    <span class="user-avatar">
						        <i class="fa-regular fa-user"></i>
						    </span>
						</a>

			            <div class="auscorp-user-menu__dropdown">

						    <div class="auscorp-user-menu__header">
						        <span class="welcome-text">Hi,</span>
						        <strong><?php echo esc_html( $current_user->display_name ); ?></strong>
						    </div>

						    <a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>">
						        <i class="fa-regular fa-user"></i>
						        Dashboard
						    </a>

						    <a href="<?php echo esc_url( wc_get_cart_url() ); ?>">
						        <i class="fa-solid fa-cart-shopping"></i>
						        Cart
						    </a>

						    <a href="<?php echo esc_url( home_url( '/quote-list/' ) ); ?>">
						        <i class="fa-regular fa-file-lines"></i>
						        Quote List
						    </a>

						    <a href="<?php echo esc_url( wc_logout_url() ); ?>">
						        <i class="fa-solid fa-arrow-right-from-bracket"></i>
						        Logout
						    </a>

						</div>

			        </div>

			    <?php else : ?>

			        <a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" class="auscorp-login-btn">
			            <i class="fa-regular fa-user"></i>
			            Login
			        </a>

			    <?php endif; ?>
			    <a href="<?php echo esc_url( $cta_url ); ?>" class="auscorp-header__cta">
			        <i class="fa-regular fa-clipboard"></i>
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
				class="auscorp-header__mobile-nav <?php echo esc_attr( hello_show_or_hide( 'hello_header_menu_display' ) ); ?>"
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
