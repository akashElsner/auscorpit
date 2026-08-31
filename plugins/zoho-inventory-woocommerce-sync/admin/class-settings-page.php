<?php
/**
 * Renders the admin settings page and handles form submissions.
 *
 * Tabs:
 *  - oauth        → Connect / disconnect Zoho, select organization.
 *  - settings     → Sync toggles, batch size, debug mode, webhook secret.
 *  - manual-sync  → Manually trigger batch syncs, view queue stats.
 *  - logs         → Browse sync log table.
 *
 * @package ZohoInventorySync\Admin
 */

namespace ZohoInventorySync\Admin;

use ZohoInventorySync\Includes\Plugin;

defined( 'ABSPATH' ) || exit;

/**
 * Class Settings_Page
 */
class Settings_Page {

	/** @var Plugin */
	private Plugin $plugin;

	public function __construct( Plugin $plugin ) {
		$this->plugin = $plugin;
		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_action( 'admin_post_zoho_inventory_sync_save_credentials', [ $this, 'handle_save_credentials' ] );
		add_action( 'admin_post_zoho_inventory_sync_save_org',         [ $this, 'handle_save_org' ] );
		add_action( 'admin_post_zoho_inventory_sync_save_settings',    [ $this, 'handle_save_settings' ] );
	}

	/**
	 * Register the settings using the Settings API.
	 */
	public function register_settings(): void {
		register_setting(
			'zoho_inventory_sync_settings_group',
			'zoho_inventory_sync_settings',
			[
				'sanitize_callback' => [ $this, 'sanitize_settings' ],
				'default'           => [],
			]
		);

		register_setting(
			'zoho_inventory_sync_webhook_group',
			'zoho_inventory_sync_webhook_secret',
			[ 'sanitize_callback' => 'sanitize_text_field' ]
		);
	}

	/**
	 * Sanitize the main settings array.
	 *
	 * @param  mixed $input Raw input from the form.
	 * @return array
	 */
	public function sanitize_settings( $input ): array {
		if ( ! is_array( $input ) ) {
			return [];
		}

		$bools = [ 'sync_customers', 'sync_products', 'create_products_from_zoho', 'sync_orders', 'sync_stock', 'deduct_stock_from_zoho_so', 'auto_create_invoice', 'sync_purchase_orders', 'debug_mode' ];
		$out   = [];

		foreach ( $bools as $key ) {
			$out[ $key ] = ! empty( $input[ $key ] );
		}

		$out['batch_size']      = isset( $input['batch_size'] ) ? max( 1, min( 200, (int) $input['batch_size'] ) ) : 50;
		$out['queue_interval']  = isset( $input['queue_interval'] ) ? max( 1, (int) $input['queue_interval'] ) : 5;

		$valid_trigger_statuses = [ 'confirmed', 'packed', 'shipped', 'delivered' ];
		$out['invoice_trigger_status'] = isset( $input['invoice_trigger_status'] ) && in_array( $input['invoice_trigger_status'], $valid_trigger_statuses, true )
			? $input['invoice_trigger_status']
			: 'delivered';

		return $out;
	}

	// -------------------------------------------------------------------------
	// Form handlers (admin-post)
	// -------------------------------------------------------------------------

	/**
	 * Handle saving Zoho API credentials (client ID, client secret, data centre).
	 *
	 * Rules:
	 *  - client_id     → required only when no credentials exist yet.
	 *  - client_secret → optional on subsequent saves (blank = keep stored value).
	 *  - data_centre   → always saved; defaults to zoho.com.
	 */
	public function handle_save_credentials(): void {
		check_admin_referer( 'zoho_inv_save_credentials' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'zoho-inventory-sync' ) );
		}

		$client_id     = sanitize_text_field( wp_unslash( $_POST['client_id']     ?? '' ) );
		$client_secret = sanitize_text_field( wp_unslash( $_POST['client_secret'] ?? '' ) );
		$data_centre   = sanitize_text_field( wp_unslash( $_POST['data_centre']   ?? 'zoho.com' ) );

		$has_existing  = $this->plugin->oauth->has_credentials();

		// client_id is mandatory on first save; optional on subsequent saves
		// (blank = keep stored value, handled inside save_credentials).
		if ( ! $has_existing && empty( $client_id ) ) {
			add_settings_error(
				'zoho_inventory_sync',
				'missing_client_id',
				__( 'Client ID is required.', 'zoho-inventory-sync' ),
				'error'
			);
			set_transient( 'zoho_inventory_sync_settings_errors', get_settings_errors( 'zoho_inventory_sync' ), 30 );
			wp_safe_redirect( admin_url( 'admin.php?page=zoho-inventory-sync&tab=oauth' ) );
			exit;
		}

		// client_secret is mandatory only on first save.
		if ( ! $has_existing && empty( $client_secret ) ) {
			add_settings_error(
				'zoho_inventory_sync',
				'missing_client_secret',
				__( 'Client Secret is required on initial setup.', 'zoho-inventory-sync' ),
				'error'
			);
			set_transient( 'zoho_inventory_sync_settings_errors', get_settings_errors( 'zoho_inventory_sync' ), 30 );
			wp_safe_redirect( admin_url( 'admin.php?page=zoho-inventory-sync&tab=oauth' ) );
			exit;
		}

		$this->plugin->oauth->save_credentials( $client_id, $client_secret, $data_centre );
		add_settings_error( 'zoho_inventory_sync', 'saved', __( 'Credentials saved successfully.', 'zoho-inventory-sync' ), 'success' );

		set_transient( 'zoho_inventory_sync_settings_errors', get_settings_errors( 'zoho_inventory_sync' ), 30 );
		wp_safe_redirect( admin_url( 'admin.php?page=zoho-inventory-sync&tab=oauth' ) );
		exit;
	}

	/**
	 * Handle saving the selected Zoho organization.
	 */
	public function handle_save_org(): void {
		check_admin_referer( 'zoho_inv_save_org' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'zoho-inventory-sync' ) );
		}

		$org_id = sanitize_text_field( wp_unslash( $_POST['organization_id'] ?? '' ) );
		if ( $org_id ) {
			$this->plugin->oauth->save_organization( $org_id );
			add_settings_error( 'zoho_inventory_sync', 'org_saved', __( 'Organization saved.', 'zoho-inventory-sync' ), 'success' );
		}

		set_transient( 'zoho_inventory_sync_settings_errors', get_settings_errors( 'zoho_inventory_sync' ), 30 );
		wp_safe_redirect( admin_url( 'admin.php?page=zoho-inventory-sync&tab=oauth' ) );
		exit;
	}

	/**
	 * Handle saving sync settings (toggles, batch size, debug, webhook secret).
	 */
	public function handle_save_settings(): void {
		check_admin_referer( 'zoho_inv_save_settings' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'zoho-inventory-sync' ) );
		}

		// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$raw = wp_unslash( $_POST['zoho_settings'] ?? [] );
		// phpcs:enable

		update_option( 'zoho_inventory_sync_settings', $this->sanitize_settings( $raw ) );

		$webhook_secret = sanitize_text_field( wp_unslash( $_POST['webhook_secret'] ?? '' ) );
		update_option( 'zoho_inventory_sync_webhook_secret', $webhook_secret );

		add_settings_error( 'zoho_inventory_sync', 'settings_saved', __( 'Settings saved.', 'zoho-inventory-sync' ), 'success' );
		set_transient( 'zoho_inventory_sync_settings_errors', get_settings_errors( 'zoho_inventory_sync' ), 30 );
		wp_safe_redirect( admin_url( 'admin.php?page=zoho-inventory-sync&tab=settings' ) );
		exit;
	}

	// -------------------------------------------------------------------------
	// Page render
	// -------------------------------------------------------------------------

	/**
	 * Render the full settings page, routing to the correct tab view.
	 */
	public function render(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'zoho-inventory-sync' ) );
		}

		$active_tab = sanitize_key( $_GET['tab'] ?? 'oauth' ); // phpcs:ignore

		$tabs = [
			'oauth'       => __( 'Zoho Connection', 'zoho-inventory-sync' ),
			'settings'    => __( 'Sync Settings', 'zoho-inventory-sync' ),
			'manual-sync' => __( 'Manual Sync', 'zoho-inventory-sync' ),
			'logs'        => __( 'Sync Logs', 'zoho-inventory-sync' ),
		];

		// Display any stored notices.
		$errors = get_transient( 'zoho_inventory_sync_settings_errors' );
		if ( $errors ) {
			delete_transient( 'zoho_inventory_sync_settings_errors' );
			foreach ( $errors as $e ) {
				printf(
					'<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
					esc_attr( $e['type'] ),
					esc_html( $e['message'] )
				);
			}
		}

		echo '<div class="wrap zoho-inventory-sync-wrap">';
		echo '<h1>' . esc_html__( 'Zoho Inventory WooCommerce Sync', 'zoho-inventory-sync' ) . '</h1>';

		// Tab navigation.
		echo '<nav class="nav-tab-wrapper woo-nav-tab-wrapper">';
		foreach ( $tabs as $slug => $label ) {
			$url   = admin_url( "admin.php?page=zoho-inventory-sync&tab={$slug}" );
			$class = $active_tab === $slug ? 'nav-tab nav-tab-active' : 'nav-tab';
			printf( '<a href="%s" class="%s">%s</a>', esc_url( $url ), esc_attr( $class ), esc_html( $label ) );
		}
		echo '</nav>';

		// Tab content.
		$view = ZOHO_INVENTORY_SYNC_PATH . "admin/views/{$active_tab}.php";
		if ( file_exists( $view ) ) {
			include $view;
		} else {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'View not found.', 'zoho-inventory-sync' ) . '</p></div>';
		}

		echo '</div>';
	}
}
