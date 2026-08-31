<?php
/**
 * Manages Zoho OAuth 2.0 authentication flow with multi-data-centre support.
 *
 * Zoho operates independent infrastructure in different regions. Each region
 * uses its own domain for both the Accounts (OAuth) server and the Inventory API.
 * The admin selects their data centre once when entering credentials.
 *
 * Supported data centres and their domains:
 *   United States → zoho.com
 *   Europe        → zoho.eu
 *   India         → zoho.in
 *   Australia     → zoho.com.au
 *   Japan         → zoho.jp
 *   Canada        → zoho.ca
 *   China         → zoho.com.cn
 *   Saudi Arabia  → zoho.sa
 *
 * Flow:
 *  1. Admin saves Client ID, Client Secret, and Data Centre.
 *  2. Admin clicks "Connect" → redirected to Zoho consent screen (correct DC).
 *  3. Zoho redirects back with ?code=.
 *  4. We exchange the code for access + refresh tokens and store them.
 *  5. On every API call we check token expiry and refresh if needed.
 *
 * @package ZohoInventorySync\Includes
 */

namespace ZohoInventorySync\Includes;

defined( 'ABSPATH' ) || exit;

/**
 * Class OAuth_Manager
 */
class OAuth_Manager {

	// -------------------------------------------------------------------------
	// Data centres
	// -------------------------------------------------------------------------

	/**
	 * All supported Zoho data centres.
	 *
	 * Each entry:
	 *   'accounts_url'   → OAuth / Accounts server base URL  (accounts.zoho.{tld})
	 *   'inventory_url'  → Zoho Inventory REST API base URL  (www.zohoapis.{tld}/inventory/v1)
	 *   'label'          → Human-readable name shown in the dropdown.
	 *
	 * NOTE: The Inventory API domain is zohoapis.{tld}, NOT inventory.zoho.{tld}.
	 *
	 * @return array<string,array{accounts_url:string,inventory_url:string,label:string}>
	 */
	public static function get_data_centres(): array {
		return [
			'zoho.com'    => [
				'accounts_url'  => 'https://accounts.zoho.com',
				'inventory_url' => 'https://www.zohoapis.com/inventory/v1',
				'label'         => __( 'United States (zoho.com)', 'zoho-inventory-sync' ),
			],
			'zoho.eu'     => [
				'accounts_url'  => 'https://accounts.zoho.eu',
				'inventory_url' => 'https://www.zohoapis.eu/inventory/v1',
				'label'         => __( 'Europe (zoho.eu)', 'zoho-inventory-sync' ),
			],
			'zoho.in'     => [
				'accounts_url'  => 'https://accounts.zoho.in',
				'inventory_url' => 'https://www.zohoapis.in/inventory/v1',
				'label'         => __( 'India (zoho.in)', 'zoho-inventory-sync' ),
			],
			'zoho.com.au' => [
				'accounts_url'  => 'https://accounts.zoho.com.au',
				'inventory_url' => 'https://www.zohoapis.com.au/inventory/v1',
				'label'         => __( 'Australia (zoho.com.au)', 'zoho-inventory-sync' ),
			],
			'zoho.jp'     => [
				'accounts_url'  => 'https://accounts.zoho.jp',
				'inventory_url' => 'https://www.zohoapis.jp/inventory/v1',
				'label'         => __( 'Japan (zoho.jp)', 'zoho-inventory-sync' ),
			],
			'zoho.ca'     => [
				'accounts_url'  => 'https://accounts.zoho.ca',
				'inventory_url' => 'https://www.zohoapis.ca/inventory/v1',
				'label'         => __( 'Canada (zoho.ca)', 'zoho-inventory-sync' ),
			],
			'zoho.com.cn' => [
				'accounts_url'  => 'https://accounts.zoho.com.cn',
				'inventory_url' => 'https://www.zohoapis.com.cn/inventory/v1',
				'label'         => __( 'China (zoho.com.cn)', 'zoho-inventory-sync' ),
			],
			'zoho.sa'     => [
				'accounts_url'  => 'https://accounts.zoho.sa',
				'inventory_url' => 'https://www.zohoapis.sa/inventory/v1',
				'label'         => __( 'Saudi Arabia (zoho.sa)', 'zoho-inventory-sync' ),
			],
		];
	}

	// -------------------------------------------------------------------------
	// OAuth scopes
	// -------------------------------------------------------------------------

	/**
	 * OAuth scope required by this plugin.
	 *
	 * ZohoInventory.fullaccess.all grants full access to all Zoho Inventory
	 * API resources: contacts, items, sales orders, invoices, purchase orders.
	 */
	const SCOPES = 'ZohoInventory.fullaccess.all';

	// -------------------------------------------------------------------------
	// WordPress option keys
	// -------------------------------------------------------------------------

	const OPT_CLIENT_ID     = 'zoho_inventory_sync_client_id';
	const OPT_CLIENT_SECRET = 'zoho_inventory_sync_client_secret';
	const OPT_DATA_CENTRE   = 'zoho_inventory_sync_data_centre';
	const OPT_ACCESS_TOKEN  = 'zoho_inventory_sync_access_token';
	const OPT_REFRESH_TOKEN = 'zoho_inventory_sync_refresh_token';
	const OPT_TOKEN_EXPIRES = 'zoho_inventory_sync_token_expires';
	const OPT_ORG_ID        = 'zoho_inventory_sync_organization_id';

	// -------------------------------------------------------------------------
	// Dynamic URL helpers (data-centre-aware)
	// -------------------------------------------------------------------------

	/**
	 * Return the stored data centre key (defaults to zoho.com).
	 */
	public function get_data_centre(): string {
		$dc      = (string) get_option( self::OPT_DATA_CENTRE, 'zoho.com' );
		$centres = self::get_data_centres();
		return isset( $centres[ $dc ] ) ? $dc : 'zoho.com';
	}

	/**
	 * Return the Accounts server base URL for the current data centre.
	 * e.g. "https://accounts.zoho.eu"
	 */
	public function get_accounts_base_url(): string {
		return self::get_data_centres()[ $this->get_data_centre() ]['accounts_url'];
	}

	/**
	 * Return the Zoho Inventory API base URL for the current data centre.
	 * e.g. "https://www.zohoapis.eu/inventory/v1"
	 *
	 * The full path is already embedded in the data centre registry —
	 * do NOT append anything extra.
	 */
	public function get_inventory_api_base_url(): string {
		return self::get_data_centres()[ $this->get_data_centre() ]['inventory_url'];
	}

	/** Transient key prefix for OAuth state tokens. */
	const STATE_TRANSIENT_PREFIX = 'zoho_oauth_state_';

	/** Transient TTL — Zoho auth codes expire in 2 min; give 10 min leeway. */
	const STATE_TTL = 600;

	/**
	 * Return the OAuth redirect URI that must be registered in the Zoho API Console.
	 *
	 * Uses admin-post.php which is purpose-built for admin action handlers and
	 * avoids the interference that occurs when WordPress sees `action=callback`
	 * on admin.php.
	 */
	public function get_redirect_uri(): string {
		return admin_url( 'admin-post.php?action=zoho_inventory_sync_oauth_callback' );
	}

	/**
	 * Generate a cryptographically random state token, store it in a transient,
	 * and return it.
	 *
	 * Using a transient (rather than wp_create_nonce) avoids session-dependency
	 * issues that can cause silent failures when Zoho redirects back.
	 *
	 * @return string Random hex token.
	 */
	public function generate_state_token(): string {
		$token = bin2hex( random_bytes( 16 ) ); // 32 hex chars
		set_transient( self::STATE_TRANSIENT_PREFIX . $token, 1, self::STATE_TTL );
		return $token;
	}

	/**
	 * Verify that a state token was previously issued by this site.
	 *
	 * Deletes the transient on the first successful check (one-time use).
	 *
	 * @param  string $token Token returned from Zoho in the `state` param.
	 * @return bool
	 */
	public function verify_state_token( string $token ): bool {
		if ( '' === $token ) {
			return false;
		}
		$key   = self::STATE_TRANSIENT_PREFIX . sanitize_key( $token );
		$valid = (bool) get_transient( $key );
		if ( $valid ) {
			delete_transient( $key );
		}
		return $valid;
	}

	/**
	 * Build the Zoho authorization URL to redirect the admin to.
	 */
	public function get_authorization_url(): string {
		$params = [
			'response_type' => 'code',
			'client_id'     => $this->get_client_id(),
			'scope'         => self::SCOPES,
			'redirect_uri'  => $this->get_redirect_uri(),
			'access_type'   => 'offline',
			// Force consent so Zoho issues a refresh token again on reconnect.
			'prompt'        => 'consent',
			'state'         => $this->generate_state_token(),
		];

		return $this->get_accounts_base_url() . '/oauth/v2/auth?' . http_build_query( $params );
	}

	// -------------------------------------------------------------------------
	// Public credential accessors
	// -------------------------------------------------------------------------

	/**
	 * Return the plain-text Client ID (safe to display in UI).
	 */
	public function get_client_id(): string {
		return (string) get_option( self::OPT_CLIENT_ID, '' );
	}

	/**
	 * Return whether credentials (Client ID + Secret) have been saved.
	 */
	public function has_credentials(): bool {
		return '' !== $this->get_client_id() && '' !== get_option( self::OPT_CLIENT_SECRET, '' );
	}

	// -------------------------------------------------------------------------
	// OAuth flow
	// -------------------------------------------------------------------------

	/**
	 * Handle the OAuth callback: exchange authorization code for tokens.
	 *
	 * @param  string $code  Authorization code from Zoho.
	 * @param  string $state State token to verify (generated by generate_state_token()).
	 * @return true|\WP_Error
	 */
	public function handle_callback( string $code, string $state ) {
		if ( ! $this->verify_state_token( $state ) ) {
			return new \WP_Error(
				'invalid_state',
				__( 'Invalid or expired OAuth state token. Please try connecting again.', 'zoho-inventory-sync' )
			);
		}

		$response = wp_remote_post(
			$this->get_accounts_base_url() . '/oauth/v2/token',
			[
				'timeout' => 30,
				'body'    => [
					'grant_type'    => 'authorization_code',
					'client_id'     => $this->get_client_id(),
					'client_secret' => $this->get_client_secret(),
					'redirect_uri'  => $this->get_redirect_uri(),
					'code'          => sanitize_text_field( $code ),
				],
			]
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( empty( $body['access_token'] ) ) {
			$code  = $body['error']             ?? 'unknown_error';
			$desc  = $body['error_description'] ?? '';
			$msg   = $desc ? "{$code}: {$desc}" : $code;
			return new \WP_Error( 'token_error', $msg );
		}

		// The plugin's connected state depends on a refresh token being available.
		if ( empty( $body['refresh_token'] ) && '' === get_option( self::OPT_REFRESH_TOKEN, '' ) ) {
			return new \WP_Error(
				'missing_refresh_token',
				__( 'Zoho did not return a refresh token. Please click Connect again and approve consent to allow offline access.', 'zoho-inventory-sync' )
			);
		}

		$this->store_tokens( $body );
		return true;
	}

	/**
	 * Return a valid access token, refreshing it if necessary.
	 *
	 * @return string|\WP_Error
	 */
	public function get_access_token() {
		if ( $this->is_token_expired() ) {
			$refreshed = $this->refresh_token();
			if ( is_wp_error( $refreshed ) ) {
				return $refreshed;
			}
		}

		$token = get_option( self::OPT_ACCESS_TOKEN, '' );
		if ( empty( $token ) ) {
			return new \WP_Error( 'no_token', __( 'No access token available. Please reconnect Zoho.', 'zoho-inventory-sync' ) );
		}

		return $this->decrypt( $token );
	}

	/**
	 * Use the stored refresh token to obtain a new access token.
	 *
	 * @return true|\WP_Error
	 */
	public function refresh_token() {
		$refresh_token = $this->get_refresh_token();

		if ( empty( $refresh_token ) ) {
			return new \WP_Error( 'no_refresh_token', __( 'No refresh token stored. Please reconnect Zoho.', 'zoho-inventory-sync' ) );
		}

		$response = wp_remote_post(
			$this->get_accounts_base_url() . '/oauth/v2/token',
			[
				'timeout' => 30,
				'body'    => [
					'grant_type'    => 'refresh_token',
					'client_id'     => $this->get_client_id(),
					'client_secret' => $this->get_client_secret(),
					'refresh_token' => $refresh_token,
				],
			]
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( empty( $body['access_token'] ) ) {
			$error = $body['error'] ?? __( 'Unknown error during token refresh.', 'zoho-inventory-sync' );
			return new \WP_Error( 'refresh_error', $error );
		}

		$this->store_tokens( $body );
		return true;
	}

	/**
	 * Fetch the list of Zoho Inventory organizations for the connected account.
	 *
	 * @return array|\WP_Error
	 */
	public function get_organizations() {
		$token = $this->get_access_token();
		if ( is_wp_error( $token ) ) {
			return $token;
		}

		$url = $this->get_inventory_api_base_url() . '/organizations';

		$response = wp_remote_get(
			$url,
			[
				'timeout' => 30,
				'headers' => [
					'Authorization' => 'Zoho-oauthtoken ' . $token,
				],
			]
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$http_code = wp_remote_retrieve_response_code( $response );
		$raw_body  = wp_remote_retrieve_body( $response );
		$body      = json_decode( $raw_body, true );

		// Surface the real Zoho error message instead of a generic fallback.
		if ( $http_code < 200 || $http_code >= 300 ) {
			$msg = ( $body['message'] ?? '' ) ?: "HTTP {$http_code}";
			return new \WP_Error( 'zoho_api_error', $msg );
		}

		if ( ! empty( $body['code'] ) && (int) $body['code'] !== 0 ) {
			$msg = $body['message'] ?? __( 'Zoho API error (code ' . (int) $body['code'] . ').', 'zoho-inventory-sync' );
			return new \WP_Error( 'zoho_api_error', $msg );
		}

		if ( empty( $body['organizations'] ) ) {
			return new \WP_Error( 'no_orgs', __( 'No Zoho Inventory organizations found for this account.', 'zoho-inventory-sync' ) );
		}

		return $body['organizations'];
	}

	// -------------------------------------------------------------------------
	// Credential / setting persistence
	// -------------------------------------------------------------------------

	/**
	 * Save API credentials and data centre from the settings form.
	 *
	 * Rules:
	 *  - client_id is always saved when provided.
	 *  - client_secret is only updated when non-empty (avoids wiping a stored
	 *    secret when the admin re-saves just the data centre or client ID).
	 *  - data_centre is always saved.
	 *  - Changing any credential clears stored tokens (forces re-authentication).
	 *
	 * @param string $client_id     Zoho client ID.
	 * @param string $client_secret Zoho client secret (blank = keep existing).
	 * @param string $data_centre   Data centre key from get_data_centres().
	 */
	public function save_credentials( string $client_id, string $client_secret, string $data_centre ): void {
		$centres = self::get_data_centres();
		if ( ! isset( $centres[ $data_centre ] ) ) {
			$data_centre = 'zoho.com';
		}

		$client_id = sanitize_text_field( $client_id );

		$current_id = $this->get_client_id();
		$current_dc = $this->get_data_centre();

		// Detect whether anything security-relevant changed.
		$id_changed      = ( $client_id !== $current_id ) && '' !== $client_id;
		$dc_changed      = $data_centre !== $current_dc;
		$secret_changing = '' !== $client_secret;

		// Save client ID.
		if ( '' !== $client_id ) {
			update_option( self::OPT_CLIENT_ID, $client_id );
		}

		// Save client secret (only when provided).
		if ( $secret_changing ) {
			update_option( self::OPT_CLIENT_SECRET, $this->encrypt( sanitize_text_field( $client_secret ) ) );
		}

		// Save data centre.
		update_option( self::OPT_DATA_CENTRE, $data_centre );

		// If the client ID, secret, or data centre changed, tokens are no
		// longer valid — clear them so the admin must re-authorise.
		if ( $id_changed || $dc_changed || $secret_changing ) {
			$this->clear_tokens();
		}
	}

	/**
	 * Save the selected Zoho Inventory organization ID.
	 *
	 * @param string $org_id
	 */
	public function save_organization( string $org_id ): void {
		update_option( self::OPT_ORG_ID, sanitize_text_field( $org_id ) );
	}

	/**
	 * Disconnect from Zoho by clearing stored tokens.
	 * Credentials (client ID/secret) and the data centre are retained.
	 */
	public function disconnect(): void {
		$this->clear_tokens();
		delete_option( self::OPT_ORG_ID );
	}

	/**
	 * Check whether the plugin is connected to Zoho (has a refresh token).
	 */
	public function is_connected(): bool {
		return '' !== get_option( self::OPT_REFRESH_TOKEN, '' );
	}

	/**
	 * Get the configured Zoho Inventory organization ID.
	 */
	public function get_organization_id(): string {
		return (string) get_option( self::OPT_ORG_ID, '' );
	}

	// -------------------------------------------------------------------------
	// Private helpers
	// -------------------------------------------------------------------------

	private function get_client_secret(): string {
		$encrypted = get_option( self::OPT_CLIENT_SECRET, '' );
		return $encrypted ? $this->decrypt( $encrypted ) : '';
	}

	private function get_refresh_token(): string {
		$encrypted = get_option( self::OPT_REFRESH_TOKEN, '' );
		return $encrypted ? $this->decrypt( $encrypted ) : '';
	}

	private function is_token_expired(): bool {
		$expires = (int) get_option( self::OPT_TOKEN_EXPIRES, 0 );
		return time() >= ( $expires - 300 ); // Refresh 5 min early.
	}

	/** Delete only the OAuth tokens (not credentials or org). */
	private function clear_tokens(): void {
		delete_option( self::OPT_ACCESS_TOKEN );
		delete_option( self::OPT_REFRESH_TOKEN );
		delete_option( self::OPT_TOKEN_EXPIRES );
	}

	/**
	 * Persist tokens returned from Zoho.
	 *
	 * @param array $body Decoded JSON body from the token endpoint.
	 */
	private function store_tokens( array $body ): void {
		if ( ! empty( $body['access_token'] ) ) {
			update_option( self::OPT_ACCESS_TOKEN, $this->encrypt( $body['access_token'] ) );
			$expires_in = isset( $body['expires_in'] ) ? (int) $body['expires_in'] : 3600;
			update_option( self::OPT_TOKEN_EXPIRES, time() + $expires_in );
		}

		// Zoho only returns refresh_token on the very first authorization.
		if ( ! empty( $body['refresh_token'] ) ) {
			update_option( self::OPT_REFRESH_TOKEN, $this->encrypt( $body['refresh_token'] ) );
		}
	}

	/**
	 * Encode a value for storage in the options table.
	 *
	 * Base64 keeps the value non-plaintext in DB exports/logs while avoiding
	 * the key-consistency issues that XOR encryption introduces (XOR requires
	 * the same LOGGED_IN_KEY at write and read time, which can silently break
	 * on dev environments or after WordPress secret-key regeneration).
	 *
	 * The options table is already protected by WordPress authentication, so
	 * base64 obfuscation is the right trade-off for stored OAuth credentials.
	 *
	 * @param  string $value Plain-text value.
	 * @return string        Base64-encoded string.
	 */
	private function encrypt( string $value ): string {
		if ( '' === $value ) {
			return '';
		}
		return base64_encode( $value ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
	}

	/**
	 * Decode a value stored by encrypt().
	 *
	 * @param  string $value Base64-encoded string.
	 * @return string        Plain-text value.
	 */
	private function decrypt( string $value ): string {
		if ( '' === $value ) {
			return '';
		}
		$decoded = base64_decode( $value, true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		return false !== $decoded ? $decoded : '';
	}
}
