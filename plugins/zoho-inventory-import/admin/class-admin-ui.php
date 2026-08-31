<?php
/**
 * WordPress admin UI — import page and AJAX handlers.
 *
 * @package ZohoInventoryImport\Admin
 */

namespace ZohoInventoryImport\Admin;

use ZohoInventoryImport\Api\Zoho_API_Handler;
use ZohoInventoryImport\Import\CSV_Importer;
use ZohoInventoryImport\Includes\Import_Settings;
use ZohoInventoryImport\Includes\Logger;
use ZohoInventoryImport\Includes\Sync_Bridge;

defined( 'ABSPATH' ) || exit;

/**
 * Class Admin_UI
 */
class Admin_UI {

	const MENU_SLUG         = 'zoho-inventory-import';
	const SETTINGS_SLUG     = 'zoho-inventory-import-settings';
	const ERROR_LOG_SLUG    = 'zoho-inventory-import-error-log';
	const SESSION_TRANSIENT = 'zoho_inv_import_session_';

	/** @var Sync_Bridge */
	private Sync_Bridge $bridge;

	/** @var Import_Settings */
	private Import_Settings $settings;

	/** @var Zoho_API_Handler */
	private Zoho_API_Handler $api;

	/** @var Logger */
	private Logger $logger;

	/** @var CSV_Importer */
	private CSV_Importer $importer;

	public function __construct(
		Sync_Bridge $bridge,
		Import_Settings $settings,
		Zoho_API_Handler $api,
		Logger $logger
	) {
		$this->bridge   = $bridge;
		$this->settings = $settings;
		$this->api      = $api;
		$this->logger   = $logger;
		$this->importer = new CSV_Importer( $api, $logger );

		add_action( 'admin_menu', [ $this, 'register_menus' ] );
		add_action( 'admin_init', [ $this, 'handle_settings_save' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );

		add_action( 'wp_ajax_zoho_inv_import_upload', [ $this, 'ajax_upload_csv' ] );
		add_action( 'wp_ajax_zoho_inv_import_process', [ $this, 'ajax_process_batch' ] );
		add_action( 'wp_ajax_zoho_inv_import_get_log', [ $this, 'ajax_get_log' ] );
		add_action( 'admin_post_zoho_inv_import_download_log', [ $this, 'download_error_log' ] );
		add_action( 'admin_post_zoho_inv_import_delete_log', [ $this, 'delete_error_log' ] );
	}

	/**
	 * Register under WooCommerce alongside the sync plugin.
	 */
	public function register_menus(): void {
		add_submenu_page(
			'woocommerce',
			__( 'Zoho CSV Import', 'zoho-inventory-import' ),
			__( 'Zoho CSV Import', 'zoho-inventory-import' ),
			'manage_woocommerce',
			self::MENU_SLUG,
			[ $this, 'render_import_page' ]
		);

		add_submenu_page(
			'woocommerce',
			__( 'Zoho Import Error Log', 'zoho-inventory-import' ),
			__( 'Zoho Import Error Log', 'zoho-inventory-import' ),
			'manage_woocommerce',
			self::ERROR_LOG_SLUG,
			[ $this, 'render_error_log_page' ]
		);

		add_submenu_page(
			'woocommerce',
			__( 'Zoho Import Settings', 'zoho-inventory-import' ),
			__( 'Zoho Import Settings', 'zoho-inventory-import' ),
			'manage_woocommerce',
			self::SETTINGS_SLUG,
			[ $this, 'render_settings_page' ]
		);
	}

	/**
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_assets( string $hook ): void {
		$import_page_hooks = [
			'woocommerce_page_' . self::MENU_SLUG,
			'woocommerce_page_' . self::SETTINGS_SLUG,
			'woocommerce_page_' . self::ERROR_LOG_SLUG,
		];

		if ( ! in_array( $hook, $import_page_hooks, true ) ) {
			return;
		}

		wp_enqueue_style(
			'zoho-inv-import-admin',
			ZOHO_INV_IMPORT_URL . 'assets/css/admin.css',
			[],
			ZOHO_INV_IMPORT_VERSION
		);

		if ( 'woocommerce_page_' . self::MENU_SLUG === $hook ) {
			wp_enqueue_script(
				'zoho-inv-import-admin',
				ZOHO_INV_IMPORT_URL . 'assets/js/admin-import.js',
				[ 'jquery' ],
				ZOHO_INV_IMPORT_VERSION,
				true
			);

			wp_localize_script(
				'zoho-inv-import-admin',
				'zohoInvImport',
				[
					'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
					'nonce'    => wp_create_nonce( 'zoho_inv_import_ajax' ),
					'messages' => [
						'uploading'    => __( 'Uploading CSV file…', 'zoho-inventory-import' ),
						'processing'   => __( 'Processing import…', 'zoho-inventory-import' ),
						'complete'     => __( 'Import complete.', 'zoho-inventory-import' ),
						'completeWarn' => __( 'Import finished with errors. Review the error log below.', 'zoho-inventory-import' ),
						'error'        => __( 'Import failed. Please check the error log.', 'zoho-inventory-import' ),
						'networkError' => __( 'Network error. Please try again or check the Error Log page.', 'zoho-inventory-import' ),
					],
					'errorLogUrl' => admin_url( 'admin.php?page=' . self::ERROR_LOG_SLUG ),
				]
			);
		}
	}

	public function handle_settings_save(): void {
		if ( ! isset( $_POST['zoho_inv_import_settings_nonce'] ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		check_admin_referer( 'zoho_inv_import_save_settings', 'zoho_inv_import_settings_nonce' );

		$this->settings->save_settings( wp_unslash( $_POST ) );

		add_settings_error(
			'zoho_inv_import_messages',
			'zoho_inv_import_saved',
			__( 'Import settings saved successfully.', 'zoho-inventory-import' ),
			'success'
		);
	}

	public function render_import_page(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'zoho-inventory-import' ) );
		}

		$connection = $this->bridge->get_connection_status();
		include ZOHO_INV_IMPORT_PATH . 'admin/views/import-page.php';
	}

	public function render_settings_page(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'zoho-inventory-import' ) );
		}

		$connection = $this->bridge->get_connection_status();
		include ZOHO_INV_IMPORT_PATH . 'admin/views/settings-page.php';
	}

	public function render_error_log_page(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'zoho-inventory-import' ) );
		}

		$view_id  = sanitize_text_field( wp_unslash( $_GET['session_id'] ?? '' ) );
		$history  = $this->logger->get_import_history();
		$entries  = [];
		$logger   = $this->logger;

		if ( $view_id ) {
			$this->logger->attach_session( $view_id );
			$entries = $this->logger->get_parsed_entries();
		}

		include ZOHO_INV_IMPORT_PATH . 'admin/views/error-log-page.php';
	}

	public function ajax_upload_csv(): void {
		check_ajax_referer( 'zoho_inv_import_ajax', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( [ 'message' => __( 'Permission denied.', 'zoho-inventory-import' ) ] );
		}

		if ( ! $this->bridge->is_configured() ) {
			wp_send_json_error(
				[
					'message' => __(
						'Zoho is not connected. Please connect via WooCommerce → Zoho Inventory Sync first.',
						'zoho-inventory-import'
					),
				]
			);
		}

		if ( empty( $_FILES['csv_file'] ) ) {
			wp_send_json_error( [ 'message' => __( 'No CSV file provided.', 'zoho-inventory-import' ) ] );
		}

		$file = $_FILES['csv_file']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		$stored = $this->importer->store_uploaded_file( $file );
		if ( is_wp_error( $stored ) ) {
			wp_send_json_error( [ 'message' => $stored->get_error_message() ] );
		}

		$valid = $this->importer->validate_csv_file( $stored );
		if ( is_wp_error( $valid ) ) {
			wp_delete_file( $stored );
			wp_send_json_error( [ 'message' => $valid->get_error_message() ] );
		}

		$session_id = wp_generate_password( 16, false );
		$total_rows = $this->importer->count_rows( $stored );

		$this->logger->start_session( $session_id );

		$session = [
			'file_path'  => $stored,
			'total_rows' => $total_rows,
			'offset'     => 0,
			'processed'  => 0,
			'created'    => 0,
			'updated'    => 0,
			'failed'     => 0,
			'errors'     => [],
			'started_at' => time(),
		];

		set_transient( self::SESSION_TRANSIENT . $session_id, $session, HOUR_IN_SECONDS );

		wp_send_json_success(
			[
				'session_id' => $session_id,
				'total_rows' => $total_rows,
				'message'    => sprintf(
					/* translators: %d: number of rows */
					__( 'CSV uploaded successfully. %d rows found.', 'zoho-inventory-import' ),
					$total_rows
				),
			]
		);
	}

	public function ajax_process_batch(): void {
		check_ajax_referer( 'zoho_inv_import_ajax', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( [ 'message' => __( 'Permission denied.', 'zoho-inventory-import' ) ] );
		}

		if ( ! $this->bridge->is_configured() ) {
			wp_send_json_error( [ 'message' => __( 'Zoho is not connected.', 'zoho-inventory-import' ) ] );
		}

		$session_id = sanitize_text_field( wp_unslash( $_POST['session_id'] ?? '' ) );
		if ( '' === $session_id ) {
			wp_send_json_error( [ 'message' => __( 'Invalid import session.', 'zoho-inventory-import' ) ] );
		}

		$session = get_transient( self::SESSION_TRANSIENT . $session_id );
		if ( ! is_array( $session ) || empty( $session['file_path'] ) ) {
			wp_send_json_error( [ 'message' => __( 'Import session expired. Please upload the CSV again.', 'zoho-inventory-import' ) ] );
		}

		$this->logger->attach_session( $session_id );

		$batch = $this->importer->process_batch(
			$session['file_path'],
			(int) $session['offset'],
			CSV_Importer::BATCH_SIZE
		);

		$session['offset']    += $batch['processed'];
		$session['processed'] += $batch['processed'];
		$session['created']   += $batch['created'];
		$session['updated']   += $batch['updated'];
		$session['failed']    += $batch['failed'];

		if ( ! empty( $batch['errors'] ) ) {
			$session['errors'] = array_merge( $session['errors'] ?? [], $batch['errors'] );
		}

		$complete = ! $batch['has_more'];

		if ( $complete ) {
			$this->logger->log_info(
				sprintf(
					'Import finished. Processed: %d, Created: %d, Updated: %d, Failed: %d.',
					$session['processed'],
					$session['created'],
					$session['updated'],
					$session['failed']
				)
			);

			$this->logger->finalize_session(
				$session_id,
				[
					'processed'  => $session['processed'],
					'created'    => $session['created'],
					'updated'    => $session['updated'],
					'failed'     => $session['failed'],
					'total_rows' => $session['total_rows'],
					'started_at' => $session['started_at'] ?? time(),
				]
			);

			delete_transient( self::SESSION_TRANSIENT . $session_id );
		} else {
			set_transient( self::SESSION_TRANSIENT . $session_id, $session, HOUR_IN_SECONDS );
		}

		$total_rows = (int) $session['total_rows'];
		$progress   = $total_rows > 0 ? min( 100, round( ( $session['offset'] / $total_rows ) * 100 ) ) : 100;

		$log_entries = $this->logger->get_recent_entries( 50 );

		wp_send_json_success(
			[
				'complete'    => $complete,
				'progress'    => $progress,
				'processed'   => $session['processed'],
				'created'     => $session['created'],
				'updated'     => $session['updated'],
				'failed'      => $session['failed'],
				'total_rows'  => $total_rows,
				'errors'      => $batch['errors'],
				'all_errors'  => $session['errors'] ?? [],
				'log_entries' => $log_entries,
				'log_url'     => $this->get_log_download_url( $session_id ),
				'log_view_url'=> admin_url( 'admin.php?page=' . self::ERROR_LOG_SLUG . '&session_id=' . rawurlencode( $session_id ) ),
				'session_id'  => $session_id,
			]
		);
	}

	/**
	 * AJAX: return recent log entries for live display during import.
	 */
	public function ajax_get_log(): void {
		check_ajax_referer( 'zoho_inv_import_ajax', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( [ 'message' => __( 'Permission denied.', 'zoho-inventory-import' ) ] );
		}

		$session_id = sanitize_text_field( wp_unslash( $_POST['session_id'] ?? '' ) );
		if ( '' === $session_id ) {
			wp_send_json_error( [ 'message' => __( 'Invalid session.', 'zoho-inventory-import' ) ] );
		}

		$this->logger->attach_session( $session_id );

		wp_send_json_success(
			[
				'entries' => $this->logger->get_recent_entries( 100 ),
				'errors'  => $this->logger->get_error_entries(),
			]
		);
	}

	public function download_error_log(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'zoho-inventory-import' ) );
		}

		$session_id = sanitize_text_field( wp_unslash( $_GET['session_id'] ?? '' ) );
		check_admin_referer( 'zoho_inv_import_download_log_' . $session_id );

		$this->logger->attach_session( $session_id );
		$contents = $this->logger->get_log_contents();

		if ( '' === $contents ) {
			wp_die( esc_html__( 'Log file not found.', 'zoho-inventory-import' ) );
		}

		nocache_headers();
		header( 'Content-Type: text/plain; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="zoho-import-log-' . $session_id . '.txt"' );
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $contents;
		exit;
	}

	/**
	 * Delete an import log session.
	 */
	public function delete_error_log(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'zoho-inventory-import' ) );
		}

		$session_id = sanitize_text_field( wp_unslash( $_GET['session_id'] ?? '' ) );
		check_admin_referer( 'zoho_inv_import_delete_log_' . $session_id );

		$this->logger->delete_session( $session_id );

		wp_safe_redirect(
			add_query_arg(
				[ 'deleted' => '1' ],
				admin_url( 'admin.php?page=' . self::ERROR_LOG_SLUG )
			)
		);
		exit;
	}

	private function get_log_download_url( string $session_id ): string {
		return wp_nonce_url(
			admin_url(
				'admin-post.php?action=zoho_inv_import_download_log&session_id=' . rawurlencode( $session_id )
			),
			'zoho_inv_import_download_log_' . $session_id
		);
	}
}
