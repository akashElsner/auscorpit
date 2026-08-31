<?php
/**
 * Admin menu registration.
 *
 * @package IngramSync
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Ingram_Sync_Admin_Menu
 */
class Ingram_Sync_Admin_Menu {

	const MENU_SLUG = 'ingram-sync';

	/**
	 * Initialize admin menu.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_action( 'admin_init', array( __CLASS__, 'handle_form_submissions' ) );
		add_action( 'admin_notices', array( __CLASS__, 'maybe_show_missing_woocommerce_notice' ) );
		add_action( 'admin_notices', array( __CLASS__, 'maybe_show_sync_failure_notice' ) );
	}

	/**
	 * Show the last sync failure on the plugin's own admin pages — this is what
	 * makes an unattended (cron-triggered) failure visible without someone
	 * manually opening the Logs page. Cleared automatically on the next
	 * successful run (see Ingram_Sync_Manager::run_complete_sync()).
	 */
	public static function maybe_show_sync_failure_notice() {
		$screen = get_current_screen();
		if ( ! $screen || false === strpos( $screen->id, 'ingram-sync' ) || ! Ingram_Sync_Security::current_user_can_manage() ) {
			return;
		}

		$failure = Ingram_Sync_Settings::get( 'last_sync_failure', array() );
		if ( empty( $failure['time'] ) ) {
			return;
		}

		printf(
			'<div class="notice notice-error"><p>%s</p></div>',
			wp_kses_post(
				sprintf(
					/* translators: 1: how long ago, 2: failure message */
					__( 'Ingram Sync: last sync failed %1$s ago — %2$s', 'ingram-sync' ),
					esc_html( human_time_diff( (int) $failure['time'] ) ),
					esc_html( (string) ( $failure['message'] ?? '' ) )
				)
			)
		);
	}

	/**
	 * Warn (don't block — Zoho-only setups don't need WooCommerce at all) when
	 * WooCommerce sync is enabled but WooCommerce isn't active, since that
	 * combination otherwise silently no-ops with no explanation.
	 */
	public static function maybe_show_missing_woocommerce_notice() {
		if ( class_exists( 'WooCommerce' ) || ! Ingram_Sync_Security::current_user_can_manage() ) {
			return;
		}

		if ( 'yes' !== Ingram_Sync_Settings::get( 'wc_sync_enabled', 'yes' ) ) {
			return;
		}

		printf(
			'<div class="notice notice-warning"><p>%s</p></div>',
			wp_kses_post(
				sprintf(
					/* translators: %s: Ingram Sync WooCommerce settings URL */
					__( 'Ingram Sync: WooCommerce sync is enabled but WooCommerce is not active, so product sync is being skipped. Install/activate WooCommerce, or turn off WooCommerce sync in <a href="%s">Settings → WooCommerce</a>.', 'ingram-sync' ),
					esc_url( admin_url( 'admin.php?page=ingram-sync-settings&tab=woocommerce' ) )
				)
			)
		);
	}

	/**
	 * Register admin menu pages.
	 */
	public static function register_menu() {
		if ( ! Ingram_Sync_Security::current_user_can_manage() ) {
			return;
		}

		add_menu_page(
			__( 'Ingram Sync', 'ingram-sync' ),
			__( 'Ingram Sync', 'ingram-sync' ),
			'manage_options',
			self::MENU_SLUG,
			array( 'Ingram_Sync_Admin_Pages', 'render_dashboard' ),
			'dashicons-update',
			56
		);

		$submenus = array(
			array( 'dashboard', __( 'Dashboard', 'ingram-sync' ), array( 'Ingram_Sync_Admin_Pages', 'render_dashboard' ) ),
			array( 'settings', __( 'Settings', 'ingram-sync' ), array( 'Ingram_Sync_Admin_Pages', 'render_settings' ) ),
			array( 'products', __( 'Products', 'ingram-sync' ), array( 'Ingram_Sync_Admin_Pages', 'render_products' ) ),
			array( 'logs', __( 'Logs', 'ingram-sync' ), array( 'Ingram_Sync_Admin_Pages', 'render_logs' ) ),
			array( 'scheduler', __( 'Scheduler', 'ingram-sync' ), array( 'Ingram_Sync_Admin_Pages', 'render_scheduler' ) ),
			array( 'tools', __( 'Tools', 'ingram-sync' ), array( 'Ingram_Sync_Admin_Pages', 'render_tools' ) ),
		);

		foreach ( $submenus as $item ) {
			add_submenu_page(
				self::MENU_SLUG,
				$item[1],
				$item[1],
				'manage_options',
				self::MENU_SLUG . ( 'dashboard' === $item[0] ? '' : '-' . $item[0] ),
				$item[2]
			);
		}
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public static function enqueue_assets( $hook ) {
		if ( false === strpos( $hook, 'ingram-sync' ) ) {
			return;
		}

		wp_enqueue_style(
			'ingram-sync-admin',
			INGRAM_SYNC_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			INGRAM_SYNC_VERSION
		);

		wp_enqueue_script(
			'ingram-sync-admin',
			INGRAM_SYNC_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			INGRAM_SYNC_VERSION,
			true
		);

		wp_localize_script(
			'ingram-sync-admin',
			'ingramSync',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'ingram_sync_ajax' ),
				'i18n'    => array(
					'confirmReset'   => __( 'This will reset all plugin data. Are you sure?', 'ingram-sync' ),
					'confirmDelete'  => __( 'This will delete all synced WooCommerce products. Are you sure?', 'ingram-sync' ),
					'processing'     => __( 'Processing...', 'ingram-sync' ),
					'success'        => __( 'Success', 'ingram-sync' ),
					'error'          => __( 'Error', 'ingram-sync' ),
					'timeout'        => __( 'Request timed out. Try again — progress is saved between batches.', 'ingram-sync' ),
					'totalProcessed' => __( 'Total processed:', 'ingram-sync' ),
					'downloaded'     => __( 'downloaded', 'ingram-sync' ),
					'total'          => __( 'total', 'ingram-sync' ),
					'remaining'      => __( 'remaining', 'ingram-sync' ),
				),
			)
		);
	}

	/**
	 * Handle settings form POST submissions.
	 */
	public static function handle_form_submissions() {
		if ( ! Ingram_Sync_Security::current_user_can_manage() ) {
			return;
		}

		if ( empty( $_POST['ingram_sync_action'] ) ) {
			return;
		}

		$action = sanitize_text_field( wp_unslash( $_POST['ingram_sync_action'] ) );

		if ( ! Ingram_Sync_Security::verify_nonce( 'ingram_sync_save_' . $action ) ) {
			wp_die( esc_html__( 'Security check failed.', 'ingram-sync' ) );
		}

		switch ( $action ) {
			case 'authentication':
			case 'api_urls':
			case 'ingram':
			case 'sync':
			case 'woocommerce':
			case 'zoho':
			case 'scheduler':
				Ingram_Sync_Settings::save_from_post( wp_unslash( $_POST ), $action );
				add_settings_error( 'ingram_sync', 'saved', __( 'Settings saved.', 'ingram-sync' ), 'success' );
				break;

			case 'refresh_token':
				$result = Ingram_Sync_Oauth::generate_token();
				$type   = $result['success'] ? 'success' : 'error';
				add_settings_error( 'ingram_sync', 'token', $result['message'], $type );
				break;

			case 'zoho_token':
				$zoho   = new Ingram_Sync_Zoho_Api();
				$result = $zoho->refresh_token();
				$type   = $result['success'] ? 'success' : 'error';
				add_settings_error( 'ingram_sync', 'zoho_token', $result['message'], $type );
				break;

			case 'run_now':
				$result = Ingram_Sync_Scheduler::run_now();
				$type   = $result['success'] ? 'success' : 'error';
				add_settings_error( 'ingram_sync', 'run_now', $result['message'], $type );
				break;

			case 'stop_scheduler':
				Ingram_Sync_Settings::update( 'scheduler_enabled', 'no' );
				Ingram_Sync_Scheduler::unschedule();
				add_settings_error( 'ingram_sync', 'stopped', __( 'Cron disabled.', 'ingram-sync' ), 'success' );
				break;

			case 'start_scheduler':
				Ingram_Sync_Settings::update( 'scheduler_enabled', 'yes' );
				Ingram_Sync_Scheduler::schedule(
					Ingram_Sync_Settings::get( 'scheduler_frequency' ),
					Ingram_Sync_Settings::get( 'scheduler_time' )
				);
				add_settings_error( 'ingram_sync', 'started', __( 'Cron enabled.', 'ingram-sync' ), 'success' );
				break;
		}
	}
}
