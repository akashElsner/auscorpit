<?php
/**
 * Admin page renderers.
 *
 * @package IngramSync
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Ingram_Sync_Admin_Pages
 */
class Ingram_Sync_Admin_Pages {

	/**
	 * Render dashboard page.
	 */
	public static function render_dashboard() {
		self::render_page( 'dashboard' );
	}

	/**
	 * Render settings page.
	 */
	public static function render_settings() {
		$tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'authentication'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! array_key_exists( $tab, self::get_settings_tabs() ) ) {
			$tab = 'authentication';
		}
		self::render_page( 'settings', array( 'tab' => $tab ) );
	}

	/**
	 * Render products page.
	 */
	public static function render_products() {
		self::render_page( 'products' );
	}

	/**
	 * Render logs page.
	 */
	public static function render_logs() {
		self::render_page( 'logs' );
	}

	/**
	 * Render scheduler page.
	 */
	public static function render_scheduler() {
		self::render_page( 'scheduler' );
	}

	/**
	 * Render tools page.
	 */
	public static function render_tools() {
		self::render_page( 'tools' );
	}

	/**
	 * Load and render a view template.
	 *
	 * @param string               $view View name.
	 * @param array<string, mixed> $vars Variables to pass to view.
	 */
	private static function render_page( $view, $vars = array() ) {
		if ( ! Ingram_Sync_Security::current_user_can_manage() ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'ingram-sync' ) );
		}

		$vars['settings'] = Ingram_Sync_Settings::get_all( false );

		$file = INGRAM_SYNC_PLUGIN_DIR . 'includes/admin/views/' . $view . '.php';
		if ( ! file_exists( $file ) ) {
			echo '<div class="wrap"><h1>' . esc_html__( 'Page not found.', 'ingram-sync' ) . '</h1></div>';
			return;
		}

		extract( $vars ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract

		echo '<div class="wrap ingram-sync-wrap">';
		settings_errors( 'ingram_sync' );
		include $file;
		echo '</div>';
	}

	/**
	 * Format token expiry for display.
	 *
	 * @param int $timestamp Unix timestamp.
	 * @return string
	 */
	public static function format_expiry( $timestamp ) {
		if ( ! $timestamp ) {
			return __( 'Not set', 'ingram-sync' );
		}
		return wp_date( 'd-M-Y h:i A', $timestamp );
	}

	/**
	 * Human-readable relative time.
	 *
	 * @param int $timestamp Unix timestamp.
	 * @return string
	 */
	public static function human_time_until( $timestamp ) {
		if ( ! $timestamp ) {
			return '';
		}
		$diff = $timestamp - time();
		if ( $diff < 0 ) {
			return __( 'Expired', 'ingram-sync' );
		}
		if ( $diff < DAY_IN_SECONDS ) {
			return __( 'Today', 'ingram-sync' ) . ' ' . wp_date( 'h:i A', $timestamp );
		}
		if ( $diff < 2 * DAY_IN_SECONDS ) {
			return __( 'Tomorrow', 'ingram-sync' ) . ' ' . wp_date( 'h:i A', $timestamp );
		}
		return wp_date( 'd-M-Y h:i A', $timestamp );
	}

	/**
	 * List of valid settings tab slugs and their labels.
	 *
	 * @return array<string, string>
	 */
	public static function get_settings_tabs() {
		return array(
			'authentication' => __( 'Authentication', 'ingram-sync' ),
			'api_urls'       => __( 'API URLs', 'ingram-sync' ),
			'ingram'         => __( 'Ingram', 'ingram-sync' ),
			'sync'           => __( 'Sync', 'ingram-sync' ),
			'woocommerce'    => __( 'WooCommerce', 'ingram-sync' ),
			'zoho'           => __( 'Zoho', 'ingram-sync' ),
			'api_test'       => __( 'API Test', 'ingram-sync' ),
		);
	}

	/**
	 * Render settings tabs navigation.
	 *
	 * @param string $current Current tab.
	 */
	public static function render_settings_tabs( $current ) {
		$tabs = self::get_settings_tabs();

		echo '<nav class="nav-tab-wrapper ingram-sync-tabs">';
		foreach ( $tabs as $slug => $label ) {
			$url   = admin_url( 'admin.php?page=ingram-sync-settings&tab=' . $slug );
			$class = ( $current === $slug ) ? 'nav-tab nav-tab-active' : 'nav-tab';
			printf( '<a href="%s" class="%s">%s</a>', esc_url( $url ), esc_attr( $class ), esc_html( $label ) );
		}
		echo '</nav>';
	}
}
