<?php
/**
 * AJAX request handler.
 *
 * @package IngramSync
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Ingram_Sync_Ajax_Handler
 */
class Ingram_Sync_Ajax_Handler {

	/**
	 * Initialize AJAX hooks.
	 */
	public static function init() {
		$actions = array(
			'test_oauth'       => 'handle_test_oauth',
			'test_catalog'     => 'handle_test_catalog',
			'test_price'       => 'handle_test_price',
			'test_details'     => 'handle_test_details',
			'generate_token'   => 'handle_generate_token',
			'sync_from_token'  => 'handle_sync_from_token',
			'download_catalog' => 'handle_download_catalog',
			'download_price'   => 'handle_download_price',
			'download_details' => 'handle_download_details',
			'sync_woocommerce' => 'handle_sync_woocommerce',
			'warm_zoho_maps'   => 'handle_warm_zoho_maps',
			'sync_zoho'        => 'handle_sync_zoho',
			'run_complete_sync'=> 'handle_run_complete_sync',
			'clear_lock'       => 'handle_clear_lock',
			'clear_logs'       => 'handle_clear_logs',
			'delete_products'  => 'handle_delete_products',
			'reset_plugin'     => 'handle_reset_plugin',
		);

		foreach ( $actions as $action => $method ) {
			add_action( 'wp_ajax_ingram_sync_' . $action, array( __CLASS__, $method ) );
		}
	}

	/**
	 * Verify AJAX request security.
	 *
	 * @return bool
	 */
	private static function verify_request() {
		if ( ! Ingram_Sync_Security::current_user_can_manage() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ingram-sync' ) ), 403 );
		}

		if ( ! check_ajax_referer( 'ingram_sync_ajax', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid nonce.', 'ingram-sync' ) ), 403 );
		}

		return true;
	}

	/**
	 * Handle test OAuth.
	 */
	public static function handle_test_oauth() {
		self::verify_request();
		$result = Ingram_Sync_Oauth::test_connection();
		wp_send_json( $result );
	}

	/**
	 * Handle test catalog API.
	 */
	public static function handle_test_catalog() {
		self::verify_request();
		$api    = new Ingram_Sync_Api();
		$result = $api->test_catalog();
		wp_send_json( $result );
	}

	/**
	 * Handle test price API.
	 */
	public static function handle_test_price() {
		self::verify_request();
		$api    = new Ingram_Sync_Api();
		$result = $api->test_price();
		wp_send_json( $result );
	}

	/**
	 * Handle test details API.
	 */
	public static function handle_test_details() {
		self::verify_request();
		$api    = new Ingram_Sync_Api();
		$result = $api->test_details();
		wp_send_json( $result );
	}

	/**
	 * Handle generate token.
	 */
	public static function handle_generate_token() {
		self::verify_request();
		$result = Ingram_Sync_Oauth::generate_token();
		wp_send_json( $result );
	}

	/**
	 * Sync customer number/country from JWT token claims.
	 */
	public static function handle_sync_from_token() {
		self::verify_request();
		$result = Ingram_Sync_Oauth::sync_customer_from_token();
		wp_send_json( $result );
	}

	/**
	 * Acquire the shared sync lock on the first chunk of a request, or heartbeat
	 * it on subsequent chunks (threaded through the same lock_token the client
	 * already carries forward alongside page/offset/exclude_ids). Without this,
	 * two browser tabs — or a tab and a concurrent cron/"Run Now" — could drive
	 * the same or conflicting stages at once. See Ingram_Sync_Lock.
	 *
	 * @param string $context Human-readable label for what's running.
	 * @return string|false Token to echo back to the client, or false if busy/lost ownership.
	 */
	private static function acquire_or_continue_lock( $context ) {
		$existing_token = isset( $_POST['lock_token'] ) ? sanitize_text_field( wp_unslash( $_POST['lock_token'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		if ( '' === $existing_token ) {
			return Ingram_Sync_Lock::acquire( $context );
		}

		return Ingram_Sync_Lock::heartbeat( $existing_token ) ? $existing_token : false;
	}

	/**
	 * Send a terminal "a sync is already running" response and stop.
	 */
	private static function send_lock_busy_response() {
		$status = Ingram_Sync_Lock::status();
		wp_send_json(
			array(
				'success' => false,
				'done'    => true,
				'message' => sprintf(
					/* translators: 1: what is currently running, 2: how long ago it started */
					__( 'A sync is already in progress (%1$s, started %2$s ago). Please wait for it to finish.', 'ingram-sync' ),
					$status['context'] ?: __( 'unknown', 'ingram-sync' ),
					human_time_diff( $status['started_at'] ?: time() )
				),
			)
		);
	}

	/**
	 * Attach the lock token to a chunk response so the client threads it into the
	 * next request, or release the lock if this was the terminal chunk.
	 *
	 * @param array<string,mixed> $result Chunk result.
	 * @param string              $token  Lock token held for this chunk.
	 * @return array<string,mixed>
	 */
	private static function finish_chunk_with_lock( $result, $token ) {
		if ( empty( $result['success'] ) || ! empty( $result['done'] ) ) {
			Ingram_Sync_Lock::release( $token );
		} else {
			$result['lock_token'] = $token;
		}
		return $result;
	}

	/**
	 * Handle download catalog.
	 */
	public static function handle_download_catalog() {
		self::verify_request();
		Ingram_Sync_Manager::prepare_long_request();

		$token = self::acquire_or_continue_lock( 'download_catalog' );
		if ( ! $token ) {
			self::send_lock_busy_response();
		}

		$manager    = new Ingram_Sync_Manager();
		$page       = isset( $_POST['page'] ) ? absint( $_POST['page'] ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$downloaded = isset( $_POST['downloaded'] ) ? absint( $_POST['downloaded'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$result     = $manager->download_catalog_page( $page, $downloaded );
		wp_send_json( self::finish_chunk_with_lock( $result, $token ) );
	}

	/**
	 * Handle download price.
	 */
	public static function handle_download_price() {
		self::verify_request();
		Ingram_Sync_Manager::prepare_long_request();

		$token = self::acquire_or_continue_lock( 'download_price' );
		if ( ! $token ) {
			self::send_lock_busy_response();
		}

		$manager = new Ingram_Sync_Manager();
		$offset  = isset( $_POST['offset'] ) ? absint( $_POST['offset'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$result  = $manager->download_price_batch( $offset );
		wp_send_json( self::finish_chunk_with_lock( $result, $token ) );
	}

	/**
	 * Handle download details.
	 */
	public static function handle_download_details() {
		self::verify_request();
		Ingram_Sync_Manager::prepare_long_request();

		$token = self::acquire_or_continue_lock( 'download_details' );
		if ( ! $token ) {
			self::send_lock_busy_response();
		}

		$manager = new Ingram_Sync_Manager();
		$result  = $manager->download_details_batch();
		wp_send_json( self::finish_chunk_with_lock( $result, $token ) );
	}

	/**
	 * Handle WooCommerce sync.
	 */
	public static function handle_sync_woocommerce() {
		self::verify_request();
		Ingram_Sync_Manager::prepare_long_request();

		$token = self::acquire_or_continue_lock( 'sync_woocommerce' );
		if ( ! $token ) {
			self::send_lock_busy_response();
		}

		$sync   = new Ingram_Sync_Woocommerce_Sync();
		$result = $sync->sync_batch();
		wp_send_json( self::finish_chunk_with_lock( $result, $token ) );
	}

	/**
	 * Handle Zoho SKU/name map warm-up (chunked — one Zoho catalog page per AJAX call).
	 * Run this to completion before sync_zoho so items get resolved from the cached
	 * map instead of falling back to a per-item Zoho API lookup.
	 */
	public static function handle_warm_zoho_maps() {
		self::verify_request();
		Ingram_Sync_Manager::prepare_long_request();

		$token = self::acquire_or_continue_lock( 'warm_zoho_maps' );
		if ( ! $token ) {
			self::send_lock_busy_response();
		}

		$sync   = new Ingram_Sync_Zoho_Sync();
		$page   = isset( $_POST['page'] ) ? absint( $_POST['page'] ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$result = $sync->warm_maps_batch( $page );
		wp_send_json( self::finish_chunk_with_lock( $result, $token ) );
	}

	/**
	 * Handle Zoho sync (chunked — one small batch per AJAX call to avoid Cloudflare 524).
	 */
	public static function handle_sync_zoho() {
		self::verify_request();
		Ingram_Sync_Manager::prepare_long_request();

		$token = self::acquire_or_continue_lock( 'sync_zoho' );
		if ( ! $token ) {
			self::send_lock_busy_response();
		}

		$sync        = new Ingram_Sync_Zoho_Sync();
		$exclude_ids = array();
		if ( isset( $_POST['exclude_ids'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$raw = wp_unslash( $_POST['exclude_ids'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			if ( is_string( $raw ) ) {
				$decoded = json_decode( $raw, true );
				$raw     = is_array( $decoded ) ? $decoded : explode( ',', $raw );
			}
			$exclude_ids = array_map( 'absint', (array) $raw );
		}
		$result = $sync->sync_batch( $exclude_ids );
		wp_send_json( self::finish_chunk_with_lock( $result, $token ) );
	}

	/**
	 * Handle complete sync.
	 */
	public static function handle_run_complete_sync() {
		self::verify_request();
		Ingram_Sync_Manager::prepare_long_request();
		$manager = new Ingram_Sync_Manager();
		$result  = $manager->run_complete_sync();
		wp_send_json( $result );
	}

	/**
	 * Handle force-clearing a stuck sync lock.
	 */
	public static function handle_clear_lock() {
		self::verify_request();
		$cleared = Ingram_Sync_Lock::force_release();
		if ( ! $cleared['had_lock'] ) {
			wp_send_json_success( array( 'message' => __( 'No lock was held — nothing to clear.', 'ingram-sync' ) ) );
		}
		wp_send_json_success( array(
			'message' => sprintf(
				/* translators: 1: what was running, 2: how long it had been running */
				__( 'Cleared stuck lock (%1$s, running for %2$s).', 'ingram-sync' ),
				$cleared['context'] ?: __( 'unknown', 'ingram-sync' ),
				human_time_diff( time() - $cleared['age'] )
			),
		) );
	}

	/**
	 * Handle clear logs.
	 */
	public static function handle_clear_logs() {
		self::verify_request();
		Ingram_Sync_Logger::clear();
		wp_send_json_success( array( 'message' => __( 'Logs cleared.', 'ingram-sync' ) ) );
	}

	/**
	 * Handle delete synced products.
	 */
	public static function handle_delete_products() {
		self::verify_request();
		$sync    = new Ingram_Sync_Woocommerce_Sync();
		$deleted = $sync->delete_synced_products();
		wp_send_json_success( array(
			'message' => sprintf( __( 'Deleted %d products.', 'ingram-sync' ), $deleted ),
		) );
	}

	/**
	 * Handle reset plugin.
	 */
	public static function handle_reset_plugin() {
		self::verify_request();
		Ingram_Sync_Settings::reset_plugin();
		wp_send_json_success( array( 'message' => __( 'Plugin reset complete.', 'ingram-sync' ) ) );
	}
}
