<?php
/**
 * Theme functions and definitions.
 *
 * For additional information on potential customization options,
 * read the developers' documentation:
 *
 * https://developers.elementor.com/docs/hello-elementor-theme/
 *
 * @package HelloElementorChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'HELLO_ELEMENTOR_CHILD_VERSION', '2.0.0' );

/**
 * Load Elementor custom widgets.
 *
 * @return void
 */
function auscorp_load_elementor_widgets() {
	if ( ! did_action( 'elementor/loaded' ) ) {
		return;
	}

	require_once get_stylesheet_directory() . '/includes/elementor/register-widgets.php';
}
add_action( 'init', 'auscorp_load_elementor_widgets' );

/**
 * Hide default Hello Elementor page title on all pages.
 *
 * @return bool
 */
add_filter( 'hello_elementor_page_title', '__return_false' );

/**
 * Shared wp_nav_menu args for Auscorp header.
 *
 * @param string $menu_id Menu element ID.
 * @return array
 */
function auscorp_header_menu_args( $menu_id ) {
	$args = [
		'container'      => false,
		'menu_class'     => 'menu auscorp-header__menu',
		'menu_id'        => $menu_id,
		'submenu_class'  => 'sub-menu',
		'fallback_cb'    => false,
		'depth'          => 0,
		'echo'           => false,
	];

	foreach ( [ 'menu-1', 'primary' ] as $location ) {
		if ( has_nav_menu( $location ) ) {
			$args['theme_location'] = $location;
			break;
		}
	}

	if ( empty( $args['theme_location'] ) ) {
		$args['theme_location'] = 'menu-1';
	}

	return $args;
}

/**
 * Load child theme scripts & styles.
 *
 * @return void
 */
function hello_elementor_child_scripts_styles() {

	wp_enqueue_style(
		'figtree-font',
		'https://fonts.googleapis.com/css2?family=Figtree:wght@700;800&display=swap',
		[],
		null
	);

	wp_enqueue_style(
		'barlow-condensed-font',
		'https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@100;200;300;400;500;600;700;800;900&display=swap',
		[],
		null
	);

	wp_enqueue_style(
		'font-awesome-6',
		'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css',
		[],
		'6.5.1'
	);

	wp_enqueue_style(
		'hello-elementor-child-style',
		get_stylesheet_directory_uri() . '/style.css',
		[
			'hello-elementor-theme-style',
			'figtree-font',
			'barlow-condensed-font',
		],
		filemtime(get_stylesheet_directory() . '/style.css')
	);

	wp_register_script( 'auscorp-header', false, [], HELLO_ELEMENTOR_CHILD_VERSION, true );
	wp_enqueue_script( 'auscorp-header' );
	wp_add_inline_script( 'auscorp-header', auscorp_header_inline_script() );
}
add_action( 'wp_enqueue_scripts', 'hello_elementor_child_scripts_styles', 20 );

/**
 * Force submenu visibility — loaded after Hello Elementor & Elementor CSS.
 *
 * @return void
 */
function auscorp_header_submenu_styles() {
	$css = '
		/* Desktop submenus */
		.auscorp-header .auscorp-header__nav.site-navigation ul.menu li ul.sub-menu {
			display: none !important;
			position: absolute !important;
			top: calc(100% + 10px) !important;
			left: 0 !important;
			min-width: 260px !important;
			margin: 0 !important;
			padding: 10px !important;
			background: #fff !important;
			border-radius: 18px !important;
			border: 1px solid #eef2f7 !important;
			box-shadow: 0 16px 40px rgba(15,23,42,.12) !important;
			z-index: 9999 !important;
			opacity: 1 !important;
			visibility: visible !important;
			transform: none !important;
			pointer-events: auto !important;
		}
		.auscorp-header .auscorp-header__nav.site-navigation ul.menu > li:hover > ul.sub-menu,
		.auscorp-header .auscorp-header__nav.site-navigation ul.menu > li:focus-within > ul.sub-menu,
		.auscorp-header .auscorp-header__nav.site-navigation ul.menu > li.submenu-open > ul.sub-menu {
			display: block !important;
		}
		.auscorp-header .auscorp-header__nav.site-navigation ul.menu .sub-menu li {
			display: block !important;
			position: relative !important;
		}
		.auscorp-header .auscorp-header__nav.site-navigation ul.menu .sub-menu .sub-menu {
			top: 0 !important;
			left: calc(100% + 8px) !important;
		}
		.auscorp-header .auscorp-header__nav.site-navigation ul.menu .sub-menu li:hover > .sub-menu,
		.auscorp-header .auscorp-header__nav.site-navigation ul.menu .sub-menu li:focus-within > .sub-menu,
		.auscorp-header .auscorp-header__nav.site-navigation ul.menu .sub-menu li.submenu-open > .sub-menu {
			display: block !important;
		}
		.auscorp-header .auscorp-header__nav.site-navigation ul.menu > li.menu-item-has-children::after {
			display: none !important;
			content: none !important;
		}
		/* Mobile submenus — animation handled in child theme CSS */
		.auscorp-header .auscorp-header__mobile-nav ul.menu > li li {
			max-height: none !important;
			transform: none !important;
		}
	';

	wp_add_inline_style( 'hello-elementor-child-style', $css );

	if ( wp_style_is( 'elementor-frontend', 'registered' ) ) {
		wp_add_inline_style( 'elementor-frontend', $css );
	}
}
add_action( 'wp_enqueue_scripts', 'auscorp_header_submenu_styles', 999 );

/**
 * Header navigation inline script.
 *
 * @return string
 */
function auscorp_header_inline_script() {
	return <<<'JS'
(function () {
	'use strict';

	var wrap      = document.querySelector('.auscorp-header-wrap');
	var toggle    = document.querySelector('.auscorp-header__toggle');
	var mobileNav = document.getElementById('auscorp-header-mobile-nav');
	var backdrop  = document.querySelector('.auscorp-header-backdrop');
	var mqDesktop = window.matchMedia('(min-width: 1101px)');

	if (!wrap) {
		return;
	}

	function resetSubmenuPanel(panel) {
		if (!panel) {
			return;
		}

		panel.classList.remove('is-open');
		panel.style.maxHeight = '0px';
	}

	function closeSubmenuState(panel, sub, item, btn, animate) {
		if (!panel) {
			return;
		}

		if (sub) {
			sub.classList.remove('is-open');
		}

		if (item) {
			item.classList.remove('is-expanded');
		}

		if (btn) {
			btn.classList.remove('is-active');
			btn.setAttribute('aria-expanded', 'false');
		}

		if (!animate) {
			resetSubmenuPanel(panel);
			return;
		}

		panel.style.maxHeight = panel.scrollHeight + 'px';

		requestAnimationFrame(function () {
			panel.style.maxHeight = '0px';
		});

		var onEnd = function (e) {
			if (e.propertyName !== 'max-height') {
				return;
			}

			panel.removeEventListener('transitionend', onEnd);
			resetSubmenuPanel(panel);
		};

		panel.addEventListener('transitionend', onEnd);
	}

	function openSubmenuState(panel, sub, item, btn) {
		panel.classList.add('is-open');
		sub.classList.add('is-open');
		item.classList.add('is-expanded');
		btn.classList.add('is-active');
		btn.setAttribute('aria-expanded', 'true');

		panel.style.maxHeight = '0px';

		requestAnimationFrame(function () {
			panel.style.maxHeight = panel.scrollHeight + 'px';
		});

		var onEnd = function (e) {
			if (e.propertyName !== 'max-height') {
				return;
			}

			panel.removeEventListener('transitionend', onEnd);

			if (panel.classList.contains('is-open')) {
				panel.style.maxHeight = panel.scrollHeight + 'px';
			}
		};

		panel.addEventListener('transitionend', onEnd);
	}

	function closeAllMobileSubmenus() {
		if (!mobileNav) {
			return;
		}

		mobileNav.querySelectorAll('.auscorp-header__submenu-panel').forEach(function (panel) {
			var sub  = panel.querySelector(':scope > .sub-menu') || panel.querySelector('.sub-menu');
			var item = panel.parentElement;
			var row  = item ? getDirectChild(item, '.auscorp-header__mobile-row') : null;
			var btn  = row ? row.querySelector('.auscorp-header__submenu-toggle') : null;

			closeSubmenuState(panel, sub, item, btn, false);
		});
	}

	function closeMenu() {
		if (!toggle || !mobileNav) {
			return;
		}

		toggle.classList.remove('is-active');
		mobileNav.classList.remove('is-open');
		toggle.setAttribute('aria-expanded', 'false');
		mobileNav.setAttribute('aria-hidden', 'true');
		document.body.classList.remove('auscorp-header-menu-open');

		if (backdrop) {
			backdrop.classList.remove('is-visible');
			backdrop.setAttribute('aria-hidden', 'true');
		}

		closeAllMobileSubmenus();
	}

	function openMenu() {
		if (!toggle || !mobileNav) {
			return;
		}

		toggle.classList.add('is-active');
		mobileNav.classList.add('is-open');
		toggle.setAttribute('aria-expanded', 'true');
		mobileNav.setAttribute('aria-hidden', 'false');
		document.body.classList.add('auscorp-header-menu-open');

		if (backdrop) {
			backdrop.classList.add('is-visible');
			backdrop.setAttribute('aria-hidden', 'false');
		}
	}

	function getDirectChild(parent, selector) {
		if (parent.querySelector(':scope > ' + selector)) {
			return parent.querySelector(':scope > ' + selector);
		}

		var className = selector.replace('.', '');
		for (var i = 0; i < parent.children.length; i++) {
			if (parent.children[i].classList.contains(className)) {
				return parent.children[i];
			}
		}

		return null;
	}

	function setupDesktopSubmenus() {
		var desktopNav = document.querySelector('.auscorp-header__nav');

		if (!desktopNav || !mqDesktop.matches) {
			return;
		}

		desktopNav.querySelectorAll('.menu-item-has-children').forEach(function (item) {
			item.addEventListener('mouseenter', function () {
				item.classList.add('submenu-open');
			});

			item.addEventListener('mouseleave', function () {
				item.classList.remove('submenu-open');
			});

			item.addEventListener('focusin', function () {
				item.classList.add('submenu-open');
			});

			item.addEventListener('focusout', function () {
				item.classList.remove('submenu-open');
			});
		});
	}

	function closeSiblingSubmenus(item) {
		var parentList = item.parentElement;

		if (!parentList) {
			return;
		}

		Array.prototype.forEach.call(parentList.children, function (sibling) {
			if (!sibling.classList.contains('menu-item-has-children') || sibling === item) {
				return;
			}

			var siblingSub   = getDirectChild(sibling, '.sub-menu');
			var siblingPanel = siblingSub ? siblingSub.closest('.auscorp-header__submenu-panel') : null;
			var siblingRow   = getDirectChild(sibling, '.auscorp-header__mobile-row');
			var siblingBtn   = siblingRow ? siblingRow.querySelector('.auscorp-header__submenu-toggle') : null;

			closeSubmenuState(siblingPanel, siblingSub, sibling, siblingBtn, false);
		});
	}

	function setupMobileSubmenus() {
		if (!mobileNav) {
			return;
		}

		mobileNav.querySelectorAll('.menu-item-has-children').forEach(function (item) {
			if (getDirectChild(item, '.auscorp-header__mobile-row')) {
				return;
			}

			var link = getDirectChild(item, 'a');
			var sub  = getDirectChild(item, '.sub-menu');

			if (!link || !sub) {
				return;
			}

			var panel = sub.closest('.auscorp-header__submenu-panel');

			if (!panel) {
				panel = document.createElement('div');
				panel.className = 'auscorp-header__submenu-panel';
				sub.parentNode.insertBefore(panel, sub);
				panel.appendChild(sub);
			}

			var row = document.createElement('div');
			row.className = 'auscorp-header__mobile-row';

			link.parentNode.insertBefore(row, link);
			row.appendChild(link);

			var btn = document.createElement('button');
			btn.type = 'button';
			btn.className = 'auscorp-header__submenu-toggle';
			btn.setAttribute('aria-expanded', 'false');
			btn.setAttribute('aria-label', 'Toggle submenu');
			btn.innerHTML = '<i class="fa-solid fa-chevron-down" aria-hidden="true"></i>';
			row.appendChild(btn);

			btn.addEventListener('click', function (e) {
				e.preventDefault();
				e.stopPropagation();

				var isOpen = panel.classList.contains('is-open');

				if (isOpen) {
					closeSubmenuState(panel, sub, item, btn, true);
					return;
				}

				closeSiblingSubmenus(item);
				openSubmenuState(panel, sub, item, btn);
			});
		});
	}

	if (toggle && mobileNav) {
		setupMobileSubmenus();
		setupDesktopSubmenus();

		toggle.addEventListener('click', function () {
			if (mobileNav.classList.contains('is-open')) {
				closeMenu();
			} else {
				openMenu();
				setupMobileSubmenus();
			}
		});

		mobileNav.addEventListener('click', function (e) {
			var link = e.target.closest('a');
			if (!link) {
				return;
			}

			var parentItem = link.closest('.menu-item-has-children');
			if (!parentItem || link.closest('.sub-menu')) {
				closeMenu();
			}
		});

		if (backdrop) {
			backdrop.addEventListener('click', closeMenu);
		}

		document.addEventListener('keydown', function (e) {
			if (e.key === 'Escape') {
				closeMenu();
			}
		});

		mqDesktop.addEventListener('change', function () {
			if (mqDesktop.matches) {
				closeMenu();
				setupDesktopSubmenus();
			}
		});
	}

	setupDesktopSubmenus();

	var stickyOffset = wrap.offsetTop || 20;

	window.addEventListener('scroll', function () {
		if (window.scrollY > stickyOffset) {
			wrap.classList.add('is-sticky');
		} else {
			wrap.classList.remove('is-sticky');
		}
	}, { passive: true });
})();
JS;
}

/**
 * Enqueue the product card styles on shop / product archive pages.
 *
 * The stylesheet is normally only enqueued by the Elementor products-grid
 * widget, but the shop page renders cards via woocommerce/content-product.php
 * directly, so it needs to be enqueued explicitly here.
 *
 * @return void
 */
function auscorp_enqueue_shop_card_styles() {
	if ( function_exists( 'is_shop' ) && ( is_shop() || is_product_taxonomy() || is_product_category() || is_product_tag() ) ) {
		wp_enqueue_style( 'auscorp-products-grid' );
	}
}
add_action( 'wp_enqueue_scripts', 'auscorp_enqueue_shop_card_styles', 20 );

/**
 * Render the shop card action button.
 *
 * Shows "Add to Cart" for logged-in users and "Add to Quote" for guests.
 *
 * @param \WC_Product $product Product object.
 * @return void
 */
function auscorp_render_shop_card_button( $product ) {
	if ( is_user_logged_in() ) {
		auscorp_render_shop_add_to_cart_button( $product );
		return;
	}

	auscorp_render_shop_quote_button( $product );
}

/**
 * Render the "Add to Cart" button for logged-in users.
 *
 * @param \WC_Product $product Product object.
 * @return void
 */
function auscorp_render_shop_add_to_cart_button( $product ) {
	$can_ajax_add = $product->is_type( 'simple' ) && $product->is_purchasable() && $product->is_in_stock();

	if ( ! $can_ajax_add ) {
		?>
		<a href="<?php echo esc_url( get_permalink( $product->get_id() ) ); ?>" class="auscorp-product-card__btn">
			<span class="auscorp-product-card__btn-icon" aria-hidden="true"><i class="fa-solid fa-cart-shopping" aria-hidden="true"></i></span>
			<span><?php esc_html_e( 'View Product', 'hello-elementor-child' ); ?></span>
		</a>
		<?php
		return;
	}
	?>
	<a href="<?php echo esc_url( $product->add_to_cart_url() ); ?>"
		class="auscorp-product-card__btn add_to_cart_button ajax_add_to_cart product_type_simple"
		data-product_id="<?php echo esc_attr( $product->get_id() ); ?>"
		data-product_sku="<?php echo esc_attr( $product->get_sku() ); ?>"
		data-quantity="1"
		rel="nofollow">
		<span class="auscorp-product-card__btn-icon" aria-hidden="true"><i class="fa-solid fa-cart-shopping" aria-hidden="true"></i></span>
		<span><?php esc_html_e( 'Add to Cart', 'hello-elementor-child' ); ?></span>
	</a>
	<?php
}

/**
 * Render the "Add to Quote" button for guests, with quote plugin compatibility.
 *
 * @param \WC_Product $product Product object.
 * @return void
 */
function auscorp_render_shop_quote_button( $product ) {
	global $dvin_wcql_obj, $post;

	$product_id  = $product->get_id();
	$backup_post = $post;

	if ( class_exists( 'Dvin_Wcql_UI' ) && isset( $dvin_wcql_obj ) && is_object( $dvin_wcql_obj ) ) {
		$post = get_post( $product_id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		setup_postdata( $post );

		$exists     = $dvin_wcql_obj->isExists( $product_id, '' );
		$type       = $product->is_type( 'variable' ) ? 'variable' : 'simple';
		$quote_html = Dvin_Wcql_UI::get_qlist_shoplink( $dvin_wcql_obj->get_url(), $type, $exists, (string) $product_id );

		echo wp_kses_post( $quote_html );

		wp_reset_postdata();
		$post = $backup_post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		return;
	}

	$type_class = $product->is_type( 'variable' ) ? 'product_type_variable' : 'product_type_simple';
	?>
	<button type="button"
		class="auscorp-product-card__btn addquotelistbutton <?php echo esc_attr( $type_class ); ?>"
		data-product_id="<?php echo esc_attr( $product_id ); ?>"
		data-product_sku="<?php echo esc_attr( $product->get_sku() ); ?>"
		data-quantity="1">
		<span class="auscorp-product-card__btn-icon" aria-hidden="true"><i class="fa-solid fa-th" aria-hidden="true"></i></span>
		<span><?php esc_html_e( 'Add to Quote', 'hello-elementor-child' ); ?></span>
	</button>
	<?php
}

// ************************************************** //
//  Remove this hook woocommerce default page heading //
// ************************************************** //
add_action( 'template_redirect', function() {
    remove_action( 'woocommerce_shop_loop_header', 'woocommerce_product_taxonomy_archive_header', 10 );
} );
remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );

