<?php
/**
 * Main plugin class.
 *
 * @package IngramSync
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Ingram_Sync
 */
final class Ingram_Sync {

	/**
	 * Singleton instance.
	 *
	 * @var Ingram_Sync|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return Ingram_Sync
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
		$this->init_hooks();
	}

	/**
	 * Register hooks.
	 */
	private function init_hooks() {
		add_action( 'plugins_loaded', array( 'Ingram_Sync_Migrations', 'maybe_run' ), 5 );
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
		add_action( 'init', array( $this, 'init' ) );

		if ( is_admin() ) {
			Ingram_Sync_Admin_Menu::init();
			Ingram_Sync_Ajax_Handler::init();
			Ingram_Sync_Woocommerce_Sync::init();
		}

		Ingram_Sync_Scheduler::init();
	}

	/**
	 * Load plugin text domain.
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'ingram-sync', false, dirname( INGRAM_SYNC_PLUGIN_BASENAME ) . '/languages' );
	}

	/**
	 * Initialize plugin components.
	 */
	public function init() {
		// Reserved for future front-end hooks.
	}
}
