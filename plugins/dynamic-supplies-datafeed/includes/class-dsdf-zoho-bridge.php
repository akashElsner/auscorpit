<?php
/**
 * Bridges Dynamic Supplies Datafeed to Zoho Inventory.
 *
 * Prefers the existing "Zoho Inventory WooCommerce Sync" OAuth connection
 * (already configured in wp-admin) and falls back to DSDF direct API credentials.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Adapter around Zoho Inventory WooCommerce Sync plugin APIs.
 */
class DSDF_Zoho_WC_Adapter {

    /** @var \ZohoInventorySync\Includes\OAuth_Manager */
    private $oauth;

    /** @var \ZohoInventorySync\Api\Zoho_Items_API */
    private $items_api;

    public function __construct( \ZohoInventorySync\Includes\OAuth_Manager $oauth ) {
        $this->oauth     = $oauth;
        $this->items_api = new \ZohoInventorySync\Api\Zoho_Items_API( $oauth );
    }

    public function is_configured(): bool {
        return $this->oauth->is_connected() && '' !== $this->oauth->get_organization_id();
    }

    public function get_connection_label(): string {
        return 'Zoho Inventory WooCommerce Sync';
    }

    public function get_org_id(): string {
        return $this->oauth->get_organization_id();
    }

    public function get_data_centre(): string {
        return $this->oauth->get_data_centre();
    }

    public function find_by_sku( string $sku ) {
        return $this->items_api->find_by_sku( $sku );
    }

    public function build_sku_map(): array {
        dsdf_log( 'Zoho: fetching all existing items for SKU map (WC Sync plugin)...' );
        $map   = [];
        $items = $this->items_api->list_items();
        foreach ( $items as $item ) {
            if ( ! empty( $item['sku'] ) && ! empty( $item['item_id'] ) ) {
                $map[ $item['sku'] ] = $item['item_id'];
            }
        }
        dsdf_log( 'Zoho SKU map ready: ' . count( $map ) . ' items.' );
        return $map;
    }

    public function create_item( array $payload ) {
        return $this->items_api->create_item( $payload );
    }

    public function update_item( string $item_id, array $payload ) {
        return $this->items_api->update_item( $item_id, $payload );
    }

    public function upload_item_image( string $item_id, string $file_path ) {
        $res = $this->items_api->upload_image( $item_id, $file_path );
        if ( is_wp_error( $res ) ) {
            return $res;
        }
        return true;
    }

    public function find_account_id_by_name( string $name ): string {
        $oauth = $this->oauth;
        $token = $oauth->get_access_token();
        if ( is_wp_error( $token ) ) {
            return '';
        }

        $books_base = str_replace( '/inventory/v1', '/books/v3', $oauth->get_inventory_api_base_url() );
        $url        = $books_base . '/chartofaccounts'
                    . '?organization_id=' . rawurlencode( $oauth->get_organization_id() )
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
}

/**
 * Return the best available Zoho client (WC Sync plugin preferred).
 *
 * @return DSDF_Zoho_WC_Adapter|DSDF_Zoho_Direct_API
 */
function dsdf_get_zoho_client() {
    static $client = null;
    if ( null !== $client ) {
        return $client;
    }

    if ( class_exists( '\ZohoInventorySync\Includes\Plugin' ) ) {
        $oauth = \ZohoInventorySync\Includes\Plugin::instance()->oauth;
        if ( $oauth->is_connected() && '' !== $oauth->get_organization_id() ) {
            $client = new DSDF_Zoho_WC_Adapter( $oauth );
            dsdf_log( 'Using Zoho connection from WooCommerce Sync plugin (org: ' . $oauth->get_organization_id() . ').' );
            return $client;
        }
    }

    $client = DSDF_Zoho_Direct_API::from_options();
    dsdf_log( 'Using DSDF direct Zoho API credentials.' );
    return $client;
}

function dsdf_zoho_is_connected(): bool {
    return dsdf_get_zoho_client()->is_configured();
}

function dsdf_zoho_connection_info(): array {
    $client = dsdf_get_zoho_client();

    if ( $client instanceof DSDF_Zoho_WC_Adapter ) {
        return [
            'configured' => true,
            'source'     => 'wc_sync',
            'label'      => $client->get_connection_label(),
            'org_id'     => $client->get_org_id(),
            'dc'         => $client->get_data_centre(),
        ];
    }

    return [
        'configured' => $client->is_configured(),
        'source'     => 'direct',
        'label'      => 'DSDF direct API',
        'org_id'     => get_option( 'dsdf_zoho_org_id', '' ),
        'dc'         => get_option( 'dsdf_zoho_dc', 'com' ),
    ];
}
