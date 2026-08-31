<?php
/**
 * Core plugin bootstrap class.
 *
 * @package ZohoInventorySync\Includes
 */

namespace ZohoInventorySync\Includes;

use ZohoInventorySync\Admin\Admin;
use ZohoInventorySync\Sync\Customer_Sync;
use ZohoInventorySync\Sync\Product_Sync;
use ZohoInventorySync\Sync\Order_Sync;
use ZohoInventorySync\Sync\Inventory_Sync;
use ZohoInventorySync\Webhooks\Webhook_Handler;

defined( 'ABSPATH' ) || exit;

/**
 * Class Plugin
 *
 * Singleton that boots all sub-systems and registers WordPress hooks.
 */
final class Plugin {

	/** @var Plugin|null Single instance. */
	private static ?Plugin $instance = null;

	/** @var OAuth_Manager */
	public OAuth_Manager $oauth;

	/** @var Logger */
	public Logger $logger;

	/** @var Sync_Queue */
	public Sync_Queue $queue;

	/** @var Customer_Sync */
	public Customer_Sync $customer_sync;

	/** @var Product_Sync */
	public Product_Sync $product_sync;

	/** @var Order_Sync */
	public Order_Sync $order_sync;

	/** @var Inventory_Sync */
	public Inventory_Sync $inventory_sync;

	/** @var Webhook_Handler */
	public Webhook_Handler $webhook_handler;

	/** @var Admin|null Stored to prevent garbage collection of hook registrations. */
	public ?Admin $admin = null;

	/** Private constructor — use instance(). */
	private function __construct() {
		$this->init_services();
		$this->register_hooks();
	}

	/**
	 * Return (and create on first call) the singleton.
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Instantiate all service objects.
	 */
	private function init_services(): void {
		$this->logger          = new Logger();
		$this->oauth           = new OAuth_Manager();
		$this->queue           = new Sync_Queue( $this->logger );
		$this->customer_sync   = new Customer_Sync( $this->oauth, $this->queue, $this->logger );
		$this->product_sync    = new Product_Sync( $this->oauth, $this->queue, $this->logger );
		$this->order_sync      = new Order_Sync( $this->oauth, $this->queue, $this->logger );
		$this->inventory_sync  = new Inventory_Sync( $this->oauth, $this->queue, $this->logger );
		$this->webhook_handler = new Webhook_Handler( $this->customer_sync, $this->product_sync, $this->inventory_sync, $this->logger );
	}

	/**
	 * Register all plugin-level WordPress hooks.
	 */
	private function register_hooks(): void {
		// i18n.
		add_action( 'init', [ $this, 'load_textdomain' ] );

		// Register the custom cron interval on every request so WordPress can
		// reschedule zoho_inventory_sync_process_queue after each run.
		add_filter( 'cron_schedules', [ 'ZohoInventorySync\\Includes\\Installer', 'add_cron_intervals' ] );

		// WooCommerce → Zoho hooks.
		$this->register_woocommerce_hooks();

		// REST API webhook endpoint.
		add_action( 'rest_api_init', [ $this->webhook_handler, 'register_routes' ] );

		// Cron jobs.
		add_action( 'zoho_inventory_sync_process_queue', [ $this->queue,        'process' ] );
		add_action( 'zoho_inventory_sync_poll_zoho',     [ $this,               'poll_zoho' ] );
		add_action( 'zoho_inventory_sync_retry_failed',  [ $this->queue,        'retry_failed' ] );
		add_action( 'zoho_inventory_sync_import_image',  [ $this->product_sync, 'import_image_from_zoho' ], 10, 3 );

		// Admin — stored as a property so PHP never garbage-collects it
		// and all its add_action() registrations remain live.
		if ( is_admin() ) {
			$this->admin = new Admin( $this );
		}

		// HPOS compatibility declaration.
		add_action( 'before_woocommerce_init', [ $this, 'declare_hpos_compatibility' ] );
	}

	/**
	 * Register hooks fired by WooCommerce events.
	 */
	private function register_woocommerce_hooks(): void {
		$settings = $this->get_settings();

		// Customers.
		if ( ! empty( $settings['sync_customers'] ) ) {
			add_action( 'woocommerce_created_customer',        [ $this->customer_sync, 'on_customer_created' ], 10, 1 );
			add_action( 'woocommerce_update_customer',         [ $this->customer_sync, 'on_customer_updated' ], 10, 1 );
			add_action( 'profile_update',                      [ $this->customer_sync, 'on_customer_updated' ], 10, 1 );
		}

		// Products.
		if ( ! empty( $settings['sync_products'] ) ) {
			add_action( 'save_post_product',                   [ $this->product_sync, 'on_product_saved' ], 10, 3 );
			add_action( 'woocommerce_update_product',          [ $this->product_sync, 'on_product_updated' ], 10, 1 );
		}

		// Orders.
		if ( ! empty( $settings['sync_orders'] ) ) {
			// woocommerce_new_order        — admin-created orders and classic checkout.
			// woocommerce_checkout_order_created — classic checkout (fires after all data saved).
			// woocommerce_store_api_checkout_order_processed — block/Gutenberg checkout via Store API.
			// The latter two pass a WC_Order object; on_order_object_created() extracts the ID.
			add_action( 'woocommerce_new_order',                              [ $this->order_sync, 'on_order_created' ],        10, 1 );
			add_action( 'woocommerce_checkout_order_created',                 [ $this->order_sync, 'on_order_object_created' ], 10, 1 );
			add_action( 'woocommerce_store_api_checkout_order_processed',     [ $this->order_sync, 'on_order_object_created' ], 10, 1 );
			add_action( 'woocommerce_order_status_changed',                   [ $this->order_sync, 'on_order_status_changed' ], 10, 3 );
		}

		// Stock / inventory.
		if ( ! empty( $settings['sync_stock'] ) ) {
			add_action( 'woocommerce_product_set_stock',       [ $this->inventory_sync, 'on_stock_changed' ], 10, 1 );
			add_action( 'woocommerce_variation_set_stock',     [ $this->inventory_sync, 'on_stock_changed' ], 10, 1 );
		}

		// Process the queue immediately after any WC entity save — at priority
		// PHP_INT_MAX so all enqueue() calls from the hooks above have already
		// run. Uses a re-entrance guard in process() so only one pass happens
		// per request even when multiple hooks fire (e.g. save_post_product +
		// woocommerce_update_product both fire on a single product save).
		add_action( 'woocommerce_update_product',       [ $this->queue, 'process' ], PHP_INT_MAX );
		add_action( 'save_post_product',               [ $this->queue, 'process' ], PHP_INT_MAX );
		add_action( 'woocommerce_created_customer',     [ $this->queue, 'process' ], PHP_INT_MAX );
		add_action( 'woocommerce_update_customer',      [ $this->queue, 'process' ], PHP_INT_MAX );
		add_action( 'woocommerce_new_order',                          [ $this->queue, 'process' ], PHP_INT_MAX );
		add_action( 'woocommerce_checkout_order_created',             [ $this->queue, 'process' ], PHP_INT_MAX );
		add_action( 'woocommerce_store_api_checkout_order_processed', [ $this->queue, 'process' ], PHP_INT_MAX );
		add_action( 'woocommerce_order_status_changed',               [ $this->queue, 'process' ], PHP_INT_MAX );
		add_action( 'woocommerce_product_set_stock',    [ $this->queue, 'process' ], PHP_INT_MAX );
	}

	/**
	 * Load plugin text domain for translations.
	 */
	public function load_textdomain(): void {
		load_plugin_textdomain(
			'zoho-inventory-sync',
			false,
			dirname( ZOHO_INVENTORY_SYNC_BASENAME ) . '/languages'
		);
	}

	/**
	 * Declare compatibility with WooCommerce High-Performance Order Storage (HPOS).
	 */
	public function declare_hpos_compatibility(): void {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
				'custom_order_tables',
				ZOHO_INVENTORY_SYNC_FILE,
				true
			);
		}
	}

	/**
	 * Retrieve plugin settings with sensible defaults.
	 *
	 * @return array<string,mixed>
	 */
	public function get_settings(): array {
		$defaults = [
			'sync_customers'       => true,
			'sync_products'        => true,
			'sync_orders'          => true,
			'sync_stock'           => true,
			'auto_create_invoice'  => true,
			'debug_mode'           => false,
			'batch_size'           => 50,
			'queue_interval'       => 5, // minutes
		];

		$saved = get_option( 'zoho_inventory_sync_settings', [] );
		return wp_parse_args( $saved, $defaults );
	}

	/**
	 * Trigger Zoho → WooCommerce polling (called by cron).
	 */
	public function poll_zoho(): void {
		if ( ! $this->oauth->is_connected() ) {
			return;
		}
		$this->inventory_sync->poll_zoho_inventory();
	}
}
