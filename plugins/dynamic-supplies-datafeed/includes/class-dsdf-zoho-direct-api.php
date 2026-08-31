<?php
/**
 * DSDF — Direct Zoho Inventory API client
 *
 * Handles OAuth2 token management and all item CRUD calls
 * without requiring the WooCommerce Zoho Inventory Sync plugin.
 *
 * Supported data centres:
 *   com  → accounts.zoho.com  / inventory.zoho.com
 *   eu   → accounts.zoho.eu   / inventory.zoho.eu
 *   in   → accounts.zoho.in   / inventory.zoho.in
 *   au   → accounts.zoho.com.au / inventory.zoho.com.au
 *   jp   → accounts.zoho.jp   / inventory.zoho.jp
 *   ca   → accounts.zohocloud.ca / inventory.zohocloud.ca
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DSDF_Zoho_Direct_API {

    // -----------------------------------------------------------------------
    // Constants
    // -----------------------------------------------------------------------
    const TOKEN_OPTION       = 'dsdf_zoho_access_token';
    const TOKEN_EXPIRY_OPTION = 'dsdf_zoho_token_expiry';
    const PAGE_SIZE          = 200; // Zoho max per page

    // -----------------------------------------------------------------------
    // DC → base URLs
    // -----------------------------------------------------------------------
    private static array $dc_map = [
        'com' => [ 'accounts' => 'https://accounts.zoho.com',     'api' => 'https://www.zohoapis.com' ],
        'eu'  => [ 'accounts' => 'https://accounts.zoho.eu',      'api' => 'https://www.zohoapis.eu' ],
        'in'  => [ 'accounts' => 'https://accounts.zoho.in',      'api' => 'https://www.zohoapis.in' ],
        'au'  => [ 'accounts' => 'https://accounts.zoho.com.au',  'api' => 'https://www.zohoapis.com.au' ],
        'jp'  => [ 'accounts' => 'https://accounts.zoho.jp',      'api' => 'https://www.zohoapis.jp' ],
        'ca'  => [ 'accounts' => 'https://accounts.zohocloud.ca', 'api' => 'https://www.zohoapis.ca' ],
    ];

    // -----------------------------------------------------------------------
    // Properties
    // -----------------------------------------------------------------------
    private string $client_id;
    private string $client_secret;
    private string $refresh_token;
    private string $org_id;
    private string $dc;
    private string $accounts_url;
    private string $api_base;

    // -----------------------------------------------------------------------
    // Constructor
    // -----------------------------------------------------------------------
    public function __construct(
        string $client_id,
        string $client_secret,
        string $refresh_token,
        string $org_id,
        string $dc = 'com'
    ) {
        $this->client_id     = $client_id;
        $this->client_secret = $client_secret;
        $this->refresh_token = $refresh_token;
        $this->org_id        = $org_id;
        $this->dc            = strtolower( $dc );

        $urls               = self::$dc_map[ $this->dc ] ?? self::$dc_map['com'];
        $this->accounts_url = $urls['accounts'];
        $this->api_base     = $urls['api'];
    }

    // -----------------------------------------------------------------------
    // Public: check credentials are set
    // -----------------------------------------------------------------------
    public function is_configured(): bool {
        return ! empty( $this->client_id )
            && ! empty( $this->client_secret )
            && ! empty( $this->refresh_token )
            && ! empty( $this->org_id );
    }

    // -----------------------------------------------------------------------
    // Token management
    // -----------------------------------------------------------------------

    /**
     * Returns a valid access token, refreshing if needed.
     * Returns WP_Error on failure.
     */
    public function get_access_token() {
        $token  = get_option( self::TOKEN_OPTION, '' );
        $expiry = (int) get_option( self::TOKEN_EXPIRY_OPTION, 0 );

        // Use cached token if still valid (with 60 s buffer)
        if ( $token && time() < ( $expiry - 60 ) ) {
            return $token;
        }

        return $this->refresh_access_token();
    }

    private function refresh_access_token() {
        dsdf_log( 'Zoho: refreshing access token...' );

        $url  = $this->accounts_url . '/oauth/v2/token';
        $body = [
            'grant_type'    => 'refresh_token',
            'client_id'     => $this->client_id,
            'client_secret' => $this->client_secret,
            'refresh_token' => $this->refresh_token,
        ];

        $response = wp_remote_post( $url, [
            'timeout' => 30,
            'body'    => $body,
        ] );

        if ( is_wp_error( $response ) ) {
            dsdf_log( 'Zoho token error: ' . $response->get_error_message() );
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $code !== 200 || empty( $body['access_token'] ) ) {
            $msg = 'Token refresh failed (HTTP ' . $code . '): ' . ( $body['error'] ?? wp_json_encode( $body ) );
            dsdf_log( 'Zoho ERROR: ' . $msg );
            return new WP_Error( 'dsdf_zoho_token', $msg );
        }

        $token   = $body['access_token'];
        $expires = time() + (int) ( $body['expires_in'] ?? 3600 );

        update_option( self::TOKEN_OPTION,        $token );
        update_option( self::TOKEN_EXPIRY_OPTION,  $expires );

        dsdf_log( 'Zoho: token refreshed, expires ' . date( 'Y-m-d H:i:s', $expires ) );
        return $token;
    }

    // -----------------------------------------------------------------------
    // Raw HTTP helpers
    // -----------------------------------------------------------------------

    private function request( string $method, string $endpoint, array $payload = [] ) {
        $token = $this->get_access_token();
        if ( is_wp_error( $token ) ) {
            return $token;
        }

        $url = $this->api_base . '/inventory/v1/' . ltrim( $endpoint, '/' )
             . ( strpos( $endpoint, '?' ) === false ? '?' : '&' )
             . 'organization_id=' . rawurlencode( $this->org_id );

        $args = [
            'method'  => strtoupper( $method ),
            'timeout' => 30,
            'headers' => [
                'Authorization' => 'Zoho-oauthtoken ' . $token,
                'Content-Type'  => 'application/json',
            ],
        ];

        if ( ! empty( $payload ) ) {
            $args['body'] = wp_json_encode( $payload );
        }

        $response = wp_remote_request( $url, $args );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        $data = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $code === 401 ) {
            // Token may have expired mid-run; force refresh once and retry
            dsdf_log( 'Zoho 401 — forcing token refresh and retrying...' );
            delete_option( self::TOKEN_EXPIRY_OPTION );
            $token = $this->refresh_access_token();
            if ( is_wp_error( $token ) ) return $token;

            $args['headers']['Authorization'] = 'Zoho-oauthtoken ' . $token;
            $response = wp_remote_request( $url, $args );
            if ( is_wp_error( $response ) ) return $response;
            $code = wp_remote_retrieve_response_code( $response );
            $data = json_decode( wp_remote_retrieve_body( $response ), true );
        }

        if ( ! is_array( $data ) ) {
            return new WP_Error( 'dsdf_zoho_parse', 'Non-JSON response (HTTP ' . $code . ')' );
        }

        if ( $code < 200 || $code >= 300 ) {
            $msg = $data['message'] ?? "HTTP {$code}";
            return new WP_Error( 'dsdf_zoho_api', $msg, [ 'status' => $code, 'body' => $data ] );
        }

        if ( isset( $data['code'] ) && (int) $data['code'] !== 0 ) {
            return new WP_Error( 'dsdf_zoho_api', $data['message'] ?? 'Zoho API error', [ 'code' => $data['code'] ] );
        }

        return $data;
    }

    // -----------------------------------------------------------------------
    // Items API
    // -----------------------------------------------------------------------

    /**
     * Search for an item by SKU.
     * Returns the item array or null if not found, WP_Error on failure.
     */
    public function find_by_sku( string $sku ) {
        $endpoint = 'items?sku=' . rawurlencode( $sku );
        $res      = $this->request( 'GET', $endpoint );

        if ( is_wp_error( $res ) ) {
            return $res;
        }

        // Zoho returns items[] array — find exact SKU match
        foreach ( (array) ( $res['items'] ?? [] ) as $item ) {
            if ( isset( $item['sku'] ) && $item['sku'] === $sku ) {
                return $item;
            }
        }

        return null;
    }

    /**
     * Fetch all items from Zoho with pagination.
     * Returns [ sku => item_id ] map.
     */
    public function build_sku_map(): array {
        dsdf_log( 'Zoho: fetching all existing items for SKU map...' );
        $map  = [];
        $page = 1;

        do {
            $endpoint = 'items?page=' . $page . '&per_page=' . self::PAGE_SIZE . '&status=active';
            $res      = $this->request( 'GET', $endpoint );

            if ( is_wp_error( $res ) ) {
                dsdf_log( 'Zoho SKU map error: ' . $res->get_error_message() );
                break;
            }

            $items = $res['items'] ?? [];
            foreach ( $items as $item ) {
                if ( ! empty( $item['sku'] ) && ! empty( $item['item_id'] ) ) {
                    $map[ $item['sku'] ] = $item['item_id'];
                }
            }

            $has_more = (bool) ( $res['page_context']['has_more_page'] ?? false );
            $page++;
        } while ( $has_more );

        dsdf_log( 'Zoho SKU map ready: ' . count( $map ) . ' items.' );
        return $map;
    }

    /**
     * Create a new item in Zoho Inventory.
     * Returns Zoho response array or WP_Error.
     */
    public function create_item( array $payload ) {
        $res = $this->request( 'POST', 'items', $payload );
        if ( ! is_wp_error( $res ) && isset( $res['item'] ) ) {
            dsdf_log( sprintf(
                'Zoho CREATE OK: SKU=%s item_id=%s',
                $payload['sku'] ?? '?',
                $res['item']['item_id'] ?? '?'
            ) );
        }
        return $res;
    }

    /**
     * Update an existing item by Zoho item_id.
     * Returns Zoho response array or WP_Error.
     */
    public function update_item( string $item_id, array $payload ) {
        $res = $this->request( 'PUT', 'items/' . $item_id, $payload );
        if ( ! is_wp_error( $res ) && isset( $res['item'] ) ) {
            dsdf_log( sprintf(
                'Zoho UPDATE OK: SKU=%s item_id=%s',
                $payload['sku'] ?? '?',
                $item_id
            ) );
        }
        return $res;
    }

    /**
     * Look up a chart-of-accounts ID by exact account name (Books API).
     */
    public function find_account_id_by_name( string $name ): string {
        $token = $this->get_access_token();
        if ( is_wp_error( $token ) ) {
            return '';
        }

        $url = $this->api_base . '/books/v3/chartofaccounts'
             . '?organization_id=' . rawurlencode( $this->org_id )
             . '&account_name=' . rawurlencode( $name );

        $response = wp_remote_get( $url, [
            'timeout' => 30,
            'headers' => [
                'Authorization' => 'Zoho-oauthtoken ' . $token,
            ],
        ] );

        if ( is_wp_error( $response ) ) {
            dsdf_log( 'Zoho account lookup error: ' . $response->get_error_message() );
            return '';
        }

        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( ! is_array( $data ) ) {
            return '';
        }

        foreach ( (array) ( $data['chartofaccounts'] ?? [] ) as $account ) {
            if ( isset( $account['account_name'] ) && strcasecmp( $account['account_name'], $name ) === 0 ) {
                return (string) ( $account['account_id'] ?? '' );
            }
        }

        return '';
    }

    /**
     * Upload a local image file to a Zoho item (multipart POST).
     *
     * @return true|\WP_Error
     */
    public function upload_item_image( string $item_id, string $file_path ) {
        if ( ! file_exists( $file_path ) || ! is_readable( $file_path ) ) {
            return new WP_Error( 'dsdf_file_not_found', "Image file not found: {$file_path}" );
        }

        $token = $this->get_access_token();
        if ( is_wp_error( $token ) ) {
            return $token;
        }

        $boundary  = '----DSDF' . md5( microtime() );
        $file_data = file_get_contents( $file_path );
        $mime_type = mime_content_type( $file_path ) ?: 'image/jpeg';
        $filename  = basename( $file_path );

        $body  = "--{$boundary}\r\n";
        $body .= "Content-Disposition: form-data; name=\"image\"; filename=\"{$filename}\"\r\n";
        $body .= "Content-Type: {$mime_type}\r\n\r\n";
        $body .= $file_data . "\r\n";
        $body .= "--{$boundary}--\r\n";

        $url = $this->api_base . '/inventory/v1/items/' . rawurlencode( $item_id ) . '/image'
             . '?organization_id=' . rawurlencode( $this->org_id );

        $response = wp_remote_post( $url, [
            'timeout' => 60,
            'headers' => [
                'Authorization' => 'Zoho-oauthtoken ' . $token,
                'Content-Type'  => 'multipart/form-data; boundary=' . $boundary,
            ],
            'body'    => $body,
        ] );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        if ( $code < 200 || $code >= 300 ) {
            $data = json_decode( wp_remote_retrieve_body( $response ), true );
            $msg  = is_array( $data ) ? ( $data['message'] ?? "HTTP {$code}" ) : "HTTP {$code}";
            return new WP_Error( 'dsdf_zoho_upload', $msg );
        }

        dsdf_log( "Zoho IMAGE OK: item_id={$item_id}" );
        return true;
    }

    // -----------------------------------------------------------------------
    // Static factory: build from WP options
    // -----------------------------------------------------------------------
    public static function from_options(): self {
        return new self(
            get_option( 'dsdf_zoho_client_id',     '' ),
            get_option( 'dsdf_zoho_client_secret',  '' ),
            get_option( 'dsdf_zoho_refresh_token',  '' ),
            get_option( 'dsdf_zoho_org_id',         '' ),
            get_option( 'dsdf_zoho_dc',             'com' )
        );
    }
}
