<?php
/**
 * WordPress admin integration — menu, assets, and AJAX handlers.
 *
 * @package ZohoInventorySync\Admin
 */

namespace ZohoInventorySync\Admin;

use ZohoInventorySync\Includes\Plugin;

defined( 'ABSPATH' ) || exit;

/**
 * Class Admin
 */
class Admin {

	/** @var Plugin */
	private Plugin $plugin;

	/** @var Settings_Page */
	private Settings_Page $settings_page;

	public function __construct( Plugin $plugin ) {
		$this->plugin        = $plugin;
		$this->settings_page = new Settings_Page( $plugin );

		add_action( 'admin_menu',            [ $this, 'register_menu' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_action( 'admin_notices',         [ $this, 'maybe_show_notices' ] );

		// Plugin action links.
		add_filter( 'plugin_action_links_' . ZOHO_INVENTORY_SYNC_BASENAME, [ $this, 'add_action_links' ] );

		// Handle OAuth callback — admin-post.php fires this for the logged-in user.
		// Using admin-post.php avoids WordPress intercepting `action=callback` on
		// admin.php, which caused silent failures with the admin_init approach.
		add_action( 'admin_post_zoho_inventory_sync_oauth_callback', [ $this, 'handle_oauth_callback' ] );

		// AJAX handlers.
		add_action( 'wp_ajax_zoho_inv_sync_manual',      [ $this, 'ajax_manual_sync' ] );
		add_action( 'wp_ajax_zoho_inv_sync_get_logs',    [ $this, 'ajax_get_logs' ] );
		add_action( 'wp_ajax_zoho_inv_sync_get_orgs',    [ $this, 'ajax_get_organizations' ] );
		add_action( 'wp_ajax_zoho_inv_sync_disconnect',  [ $this, 'ajax_disconnect' ] );
		add_action( 'wp_ajax_zoho_inv_sync_queue_stats', [ $this, 'ajax_queue_stats' ] );
	}

	// -------------------------------------------------------------------------
	// Menu registration
	// -------------------------------------------------------------------------

	/**
	 * Register the admin menu under WooCommerce.
	 */
	public function register_menu(): void {
		add_submenu_page(
			'woocommerce',
			__( 'Zoho Inventory Sync', 'zoho-inventory-sync' ),
			__( 'Zoho Inventory Sync', 'zoho-inventory-sync' ),
			'manage_woocommerce',
			'zoho-inventory-sync',
			[ $this->settings_page, 'render' ]
		);
	}

	// -------------------------------------------------------------------------
	// Assets
	// -------------------------------------------------------------------------

	/**
	 * Enqueue admin CSS and JS on the plugin settings page.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 */
	public function enqueue_assets( string $hook_suffix ): void {
		if ( strpos( $hook_suffix, 'zoho-inventory-sync' ) === false ) {
			return;
		}

		wp_enqueue_style(
			'zoho-inventory-sync-admin',
			ZOHO_INVENTORY_SYNC_URL . 'assets/css/admin.css',
			[],
			ZOHO_INVENTORY_SYNC_VERSION
		);

		wp_enqueue_script(
			'zoho-inventory-sync-admin',
			ZOHO_INVENTORY_SYNC_URL . 'assets/js/admin.js',
			[ 'jquery' ],
			ZOHO_INVENTORY_SYNC_VERSION,
			true
		);

		wp_localize_script(
			'zoho-inventory-sync-admin',
			'ZohoInventorySync',
			[
				'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
				'nonce'      => wp_create_nonce( 'zoho_inventory_sync_ajax' ),
				'webhookUrl' => rest_url( 'zoho-inventory-sync/v1/webhook' ),
				'i18n'       => [
					'syncing'      => __( 'Syncing…', 'zoho-inventory-sync' ),
					'done'         => __( 'Done!', 'zoho-inventory-sync' ),
					'error'        => __( 'Error. Check logs.', 'zoho-inventory-sync' ),
					'confirmSync'  => __( 'This will queue all items for sync. Continue?', 'zoho-inventory-sync' ),
					'confirmDisc'  => __( 'Disconnect from Zoho Inventory? Tokens will be deleted.', 'zoho-inventory-sync' ),
					'loadingOrgs'  => __( 'Loading organizations…', 'zoho-inventory-sync' ),
				],
			]
		);
	}

	// -------------------------------------------------------------------------
	// Admin notices
	// -------------------------------------------------------------------------

	/**
	 * Show admin notice if the plugin isn't fully configured.
	 */
	public function maybe_show_notices(): void {
		// Only show on the plugin settings page.
		$screen = get_current_screen();
		if ( ! $screen || strpos( $screen->id, 'zoho-inventory-sync' ) === false ) {
			return;
		}

		if ( ! $this->plugin->oauth->is_connected() ) {
			echo '<div class="notice notice-warning"><p>';
			printf(
				/* translators: %s: settings page link */
				esc_html__( 'Zoho Inventory Sync: please %s to start syncing.', 'zoho-inventory-sync' ),
				'<a href="' . esc_url( admin_url( 'admin.php?page=zoho-inventory-sync&tab=oauth' ) ) . '">' . esc_html__( 'connect your Zoho account', 'zoho-inventory-sync' ) . '</a>'
			);
			echo '</p></div>';
		}

		if ( ! $this->plugin->oauth->get_organization_id() && $this->plugin->oauth->is_connected() ) {
			echo '<div class="notice notice-warning"><p>';
			esc_html_e( 'Zoho Inventory Sync: please select a Zoho Inventory organization in the OAuth settings tab.', 'zoho-inventory-sync' );
			echo '</p></div>';
		}
	}

	// -------------------------------------------------------------------------
	// Plugin action links
	// -------------------------------------------------------------------------

	/**
	 * Add a "Settings" link to the plugins list table.
	 *
	 * @param  array $links Existing plugin action links.
	 * @return array
	 */
	public function add_action_links( array $links ): array {
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'admin.php?page=zoho-inventory-sync' ) ),
			esc_html__( 'Settings', 'zoho-inventory-sync' )
		);
		array_unshift( $links, $settings_link );
		return $links;
	}

	// -------------------------------------------------------------------------
	// OAuth callback
	// -------------------------------------------------------------------------

	/**
	 * Process the Zoho OAuth redirect-back.
	 *
	 * Registered on admin_post_zoho_inventory_sync_oauth_callback, which fires when
	 * Zoho redirects to admin-post.php?action=zoho_inventory_sync_oauth_callback.
	 * Using admin-post.php prevents WordPress from misinterpreting the `action`
	 * query parameter that was previously on admin.php.
	 *
	 * No wp_nonce check here — we verify the OAuth state token instead (see
	 * OAuth_Manager::verify_state_token), which is the correct mechanism for
	 * cross-site redirects.
	 */
	public function handle_oauth_callback(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'zoho-inventory-sync' ) );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$code  = sanitize_text_field( wp_unslash( $_GET['code']  ?? '' ) );
		$state = sanitize_text_field( wp_unslash( $_GET['state'] ?? '' ) );
		$error = sanitize_text_field( wp_unslash( $_GET['error'] ?? '' ) );
		// phpcs:enable

		// Zoho sends `error` when the user denies access.
		if ( $error || ! $code ) {
			$msg = $error ?: __( 'Authorization code missing in Zoho callback.', 'zoho-inventory-sync' );
			set_transient( 'zoho_inventory_sync_oauth_error', $msg, 60 );
			wp_safe_redirect( admin_url( 'admin.php?page=zoho-inventory-sync&tab=oauth' ) );
			exit;
		}

		$result = $this->plugin->oauth->handle_callback( $code, $state );

		if ( is_wp_error( $result ) ) {
			set_transient( 'zoho_inventory_sync_oauth_error', $result->get_error_message(), 60 );
		} else {
			set_transient( 'zoho_inventory_sync_oauth_success', true, 60 );
		}

		wp_safe_redirect( admin_url( 'admin.php?page=zoho-inventory-sync&tab=oauth' ) );
		exit;
	}

	// -------------------------------------------------------------------------
	// AJAX handlers
	// -------------------------------------------------------------------------

	/**
	 * AJAX: trigger a manual batch sync for a specific entity type.
	 *
	 * POST: action=zoho_inv_sync_manual, entity=customers|products|orders|inventory
	 */
	public function ajax_manual_sync(): void {
		check_ajax_referer( 'zoho_inventory_sync_ajax', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( [ 'message' => __( 'Permission denied.', 'zoho-inventory-sync' ) ] );
		}

		$entity = sanitize_key( $_POST['entity'] ?? '' );

		switch ( $entity ) {
			case 'customers':
				$this->plugin->customer_sync->sync_all_customers();
				break;
			case 'products':
				$this->plugin->product_sync->sync_all_products();
				break;
			case 'orders':
				$this->plugin->order_sync->sync_all_orders();
				break;
			case 'inventory':
				$this->plugin->inventory_sync->sync_all_inventory();
				break;
			default:
				wp_send_json_error( [ 'message' => __( 'Invalid entity type.', 'zoho-inventory-sync' ) ] );
				return;
		}

		wp_send_json_success( [
			'message' => sprintf(
				/* translators: %s: entity name */
				__( '%s sync queued successfully.', 'zoho-inventory-sync' ),
				ucfirst( $entity )
			),
		] );
	}

	/**
	 * AJAX: return recent sync log entries as HTML rows.
	 */
	public function ajax_get_logs(): void {
		check_ajax_referer( 'zoho_inventory_sync_ajax', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error();
		}

		$logs = $this->plugin->logger->get_logs(
			[
				'limit'       => 50,
				'entity_type' => sanitize_text_field( $_POST['entity_type'] ?? '' ),
				'status'      => sanitize_text_field( $_POST['status']      ?? '' ),
			]
		);

		ob_start();
		foreach ( $logs as $log ) {
			$status_class = 'success' === $log['status'] ? 'color:green' : ( 'error' === $log['status'] ? 'color:red' : '' );
			echo '<tr>';
			echo '<td>' . esc_html( $log['created_at'] ) . '</td>';
			echo '<td>' . esc_html( $log['entity_type'] ) . ' #' . esc_html( $log['entity_id'] ) . '</td>';
			echo '<td>' . esc_html( $log['action'] ) . '</td>';
			echo '<td style="' . esc_attr( $status_class ) . '">' . esc_html( ucfirst( $log['status'] ) ) . '</td>';
			echo '<td>' . esc_html( $log['message'] ) . '</td>';
			echo '</tr>';
		}
		$html = ob_get_clean();

		wp_send_json_success( [ 'html' => $html ] );
	}

	/**
	 * AJAX: fetch Zoho Inventory organizations.
	 */
	public function ajax_get_organizations(): void {
		check_ajax_referer( 'zoho_inventory_sync_ajax', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error();
		}

		$orgs = $this->plugin->oauth->get_organizations();
		if ( is_wp_error( $orgs ) ) {
			wp_send_json_error( [ 'message' => $orgs->get_error_message() ] );
			return;
		}

		wp_send_json_success( [ 'organizations' => $orgs ] );
	}

	/**
	 * AJAX: disconnect Zoho account.
	 */
	public function ajax_disconnect(): void {
		check_ajax_referer( 'zoho_inventory_sync_ajax', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error();
		}

		$this->plugin->oauth->disconnect();
		wp_send_json_success( [ 'message' => __( 'Disconnected from Zoho Inventory.', 'zoho-inventory-sync' ) ] );
	}

	/**
	 * AJAX: return queue statistics.
	 */
	public function ajax_queue_stats(): void {
		check_ajax_referer( 'zoho_inventory_sync_ajax', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error();
		}

		wp_send_json_success( $this->plugin->queue->get_stats() );
	}
}
