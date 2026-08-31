<?php
/**
 * Plugin Name: Dynamic Supplies Datafeed
 * Plugin URI:  https://example.com
 * Description: Downloads Dynamic Supplies CSV via SFTP once daily. Syncs to Zoho Inventory only when manually triggered.
 * Version:     2.1.0
 * Author:      Your Name
 * License:     GPL-2.0+
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'DSDF_VERSION',    '2.1.0' );
define( 'DSDF_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

require_once DSDF_PLUGIN_DIR . 'includes/class-dsdf-zoho-direct-api.php';
require_once DSDF_PLUGIN_DIR . 'includes/class-dsdf-zoho-bridge.php';

define( 'DSDF_RATE_LIMIT_SLEEP',   30   ); // seconds to sleep on transient rate-limit
define( 'DSDF_RATE_LIMIT_RETRIES', 1    ); // max retries on transient rate-limit
define( 'DSDF_DEFAULT_BUDGET',     3000 ); // leave headroom below Zoho org limit of 7,500/day
define( 'DSDF_ZOHO_ORG_DAILY_CAP', 7500 ); // Zoho hard limit per organisation per day
define( 'DSDF_INVENTORY_ACCOUNT_NAME', 'Dynamic Supply' );
define( 'DSDF_CSV_MAIN_IMAGE_COLUMN', 'Main Image URL' );
define( 'DSDF_WC_META_ZOHO_ITEM_ID', '_dsdf_zoho_item_id' );
define( 'DSDF_ZOHO_CF_SUPPLIER_SOURCE_ID', '5860785000001106001' );
define( 'DSDF_ZOHO_CF_INGRAM_PART_NUMBER_ID', '5860785000001231003' );
define( 'DSDF_ZOHO_CF_VENDOR_PART_NUMBER_ID', '5860785000001231006' );
define( 'DSDF_ZOHO_CF_AVAILABLE_QTY_ID', '5860785000000843179' );
define( 'DSDF_SUPPLIER_SOURCE_DEFAULT', 'Dynamic Supplier' );
define( 'DSDF_AVAILABLE_QTY_DEFAULT', 10 );
define( 'DSDF_WC_META_SUPPLIER_SOURCE', '_dsdf_supplier_source' );
define( 'DSDF_WC_META_INGRAM_PART_NUMBER', '_dsdf_ingram_part_number' );
define( 'DSDF_WC_META_VENDOR_PART_NUMBER', '_dsdf_vendor_part_number' );
define( 'DSDF_WC_META_AVAILABLE_QTY', '_dsdf_available_qty' );

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------
function dsdf_upload_dir(): string {
    return wp_upload_dir()['basedir'] . '/dynamic-supplies/';
}

function dsdf_get_opt( string $key, string $default = '' ): string {
    return (string) get_option( 'dsdf_' . $key, $default );
}

function dsdf_get_inventory_account_name(): string {
    $name = trim( (string) get_option( 'dsdf_inventory_account_name', DSDF_INVENTORY_ACCOUNT_NAME ) );
    return '' !== $name ? $name : DSDF_INVENTORY_ACCOUNT_NAME;
}

function dsdf_log( string $message ): void {
    $dir = dsdf_upload_dir();
    if ( ! file_exists( $dir ) ) {
        wp_mkdir_p( $dir );
    }
    $line = '[' . date( 'Y-m-d H:i:s' ) . '] ' . $message . PHP_EOL;
    file_put_contents( $dir . 'dsdf.log', $line, FILE_APPEND | LOCK_EX );
}

// ---------------------------------------------------------------------------
// Sync state
// ---------------------------------------------------------------------------
function dsdf_is_sync_running(): bool {
    return (bool) get_option( 'dsdf_sync_running', false );
}

function dsdf_set_sync_running( bool $state, int $total = 0 ): void {
    if ( $state ) {
        @unlink( dsdf_upload_dir() . 'dsdf-stop.lock' );
        update_option( 'dsdf_sync_running',  true );
        update_option( 'dsdf_sync_stop',     false );
        update_option( 'dsdf_sync_started',  time() );
        update_option( 'dsdf_sync_total',    $total );
        update_option( 'dsdf_sync_progress', 0 );
    } else {
        update_option( 'dsdf_sync_running', false );
        update_option( 'dsdf_sync_stop',    false );
        @unlink( dsdf_upload_dir() . 'dsdf-stop.lock' );
    }
}

function dsdf_sync_stop_requested(): bool {
    if ( file_exists( dsdf_upload_dir() . 'dsdf-stop.lock' ) ) return true;
    return (bool) get_option( 'dsdf_sync_stop', false );
}

/** Signal a running background sync to stop (flags stay set until the job exits). */
function dsdf_request_sync_stop(): void {
    $dir = dsdf_upload_dir();
    if ( ! file_exists( $dir ) ) {
        wp_mkdir_p( $dir );
    }
    update_option( 'dsdf_sync_stop', true );
    file_put_contents( $dir . 'dsdf-stop.lock', '1', LOCK_EX );
    $ts = wp_next_scheduled( 'dsdf_background_sync' );
    if ( $ts ) {
        wp_unschedule_event( $ts, 'dsdf_background_sync' );
    }
}

function dsdf_update_sync_progress( int $done ): void {
    update_option( 'dsdf_sync_progress', $done );
}

function dsdf_is_zoho_to_wc_sync_running(): bool {
    return (bool) get_option( 'dsdf_zoho_to_wc_running', false );
}

function dsdf_set_zoho_to_wc_sync_running( bool $state ): void {
    if ( $state ) {
        update_option( 'dsdf_zoho_to_wc_running', true );
        update_option( 'dsdf_zoho_to_wc_started', time() );
        update_option( 'dsdf_zoho_to_wc_stop', false );
        update_option( 'dsdf_zoho_to_wc_queued', false );
    } else {
        update_option( 'dsdf_zoho_to_wc_running', false );
        update_option( 'dsdf_zoho_to_wc_stop', false );
        update_option( 'dsdf_zoho_to_wc_queued', false );
    }
}

function dsdf_zoho_to_wc_stop_requested(): bool {
    return (bool) get_option( 'dsdf_zoho_to_wc_stop', false );
}

/** Signal a running Zoho -> WooCommerce sync to stop gracefully. */
function dsdf_request_zoho_to_wc_stop(): void {
    update_option( 'dsdf_zoho_to_wc_stop', true );
    update_option( 'dsdf_zoho_to_wc_queued', false );
    $ts = wp_next_scheduled( 'dsdf_background_zoho_to_wc_sync' );
    if ( $ts ) {
        wp_unschedule_event( $ts, 'dsdf_background_zoho_to_wc_sync' );
    }
}

function dsdf_sync_lock_file(): string {
    return dsdf_upload_dir() . 'dsdf-sync.lock';
}

/** Prevent two sync processes running at the same time. */
function dsdf_acquire_sync_lock(): bool {
    $dir = dsdf_upload_dir();
    if ( ! file_exists( $dir ) ) {
        wp_mkdir_p( $dir );
    }
    $fp = @fopen( dsdf_sync_lock_file(), 'c+' );
    if ( ! $fp ) {
        return false;
    }
    if ( ! flock( $fp, LOCK_EX | LOCK_NB ) ) {
        fclose( $fp );
        return false;
    }
    fwrite( $fp, (string) getmypid() );
    fflush( $fp );
    // Keep lock until process ends — fclose in release.
    $GLOBALS['dsdf_sync_lock_fp'] = $fp;
    return true;
}

function dsdf_release_sync_lock(): void {
    if ( ! empty( $GLOBALS['dsdf_sync_lock_fp'] ) ) {
        flock( $GLOBALS['dsdf_sync_lock_fp'], LOCK_UN );
        fclose( $GLOBALS['dsdf_sync_lock_fp'] );
        $GLOBALS['dsdf_sync_lock_fp'] = null;
    }
    @unlink( dsdf_sync_lock_file() );
}

// ---------------------------------------------------------------------------
// Daily API usage tracker (Zoho org limit is 7,500/day across ALL plugins)
// ---------------------------------------------------------------------------
function dsdf_api_usage_today(): int {
    $data = get_option( 'dsdf_api_usage', [] );
    if ( ! is_array( $data ) || ( $data['date'] ?? '' ) !== gmdate( 'Y-m-d' ) ) {
        return 0;
    }
    return (int) ( $data['count'] ?? 0 );
}

function dsdf_track_api_call( int $count = 1 ): void {
    $today = gmdate( 'Y-m-d' );
    $data  = get_option( 'dsdf_api_usage', [] );
    if ( ! is_array( $data ) || ( $data['date'] ?? '' ) !== $today ) {
        $data = [ 'date' => $today, 'count' => 0 ];
    }
    $data['count'] = (int) $data['count'] + $count;
    update_option( 'dsdf_api_usage', $data );
}

function dsdf_api_budget_remaining(): int {
    $budget = (int) get_option( 'dsdf_daily_api_budget', DSDF_DEFAULT_BUDGET );
    return max( 0, $budget - dsdf_api_usage_today() );
}

function dsdf_is_zoho_daily_limit_error( string $message ): bool {
    $msg = strtolower( $message );
    return str_contains( $msg, 'maximum call rate limit' )
        || str_contains( $msg, '7,500' )
        || str_contains( $msg, '7500' );
}

function dsdf_is_zoho_rate_limit_error( string $message ): bool {
  return stripos( $message, 'rate limit' ) !== false
      || stripos( $message, 'too many requests' ) !== false;
}

function dsdf_set_zoho_daily_blocked(): void {
    update_option( 'dsdf_zoho_daily_limit_until', strtotime( 'tomorrow midnight UTC' ) );
}

function dsdf_is_zoho_daily_blocked(): bool {
    return (int) get_option( 'dsdf_zoho_daily_limit_until', 0 ) > time();
}

function dsdf_zoho_error_message( $res ): string {
    if ( is_wp_error( $res ) ) {
        return $res->get_error_message();
    }
    return (string) ( $res['message'] ?? '' );
}

function dsdf_sku_map_cache_file(): string {
    return dsdf_upload_dir() . 'dsdf-sku-map-cache.json';
}

/** Cached Zoho SKU map — avoids re-fetching thousands of items on every sync run. */
function dsdf_load_sku_map_cache( int $max_age_secs = 21600 ): array {
    $file = dsdf_sku_map_cache_file();
    if ( ! file_exists( $file ) ) {
        return [];
    }
    if ( ( time() - filemtime( $file ) ) > $max_age_secs ) {
        return [];
    }
    $data = json_decode( file_get_contents( $file ), true );
    return is_array( $data ) ? $data : [];
}

function dsdf_save_sku_map_cache( array $map ): void {
    file_put_contents( dsdf_sku_map_cache_file(), json_encode( $map ), LOCK_EX );
}

/** Auto-clears stuck "running" flag if older than $hours. */
function dsdf_reset_stale_sync( bool $force = false, int $hours = 2 ): bool {
    if ( ! get_option( 'dsdf_sync_running', false ) ) return false;
    $age = time() - (int) get_option( 'dsdf_sync_started', 0 );
    if ( $force || $age > $hours * 3600 ) {
        update_option( 'dsdf_sync_running', false );
        update_option( 'dsdf_sync_stop',    false );
        @unlink( dsdf_upload_dir() . 'dsdf-stop.lock' );
        $ts = wp_next_scheduled( 'dsdf_background_sync' );
        if ( $ts ) wp_unschedule_event( $ts, 'dsdf_background_sync' );
        dsdf_log( 'Stale sync state auto-reset (age: ' . round( $age / 3600, 1 ) . 'h).' );
        return true;
    }
    return false;
}

/** Auto-clears stale Zoho -> WooCommerce running flag. */
function dsdf_reset_stale_zoho_to_wc_sync( bool $force = false, int $hours = 4 ): bool {
    if ( ! get_option( 'dsdf_zoho_to_wc_running', false ) ) {
        return false;
    }
    $age = time() - (int) get_option( 'dsdf_zoho_to_wc_started', 0 );
    if ( $force || $age > $hours * 3600 ) {
        update_option( 'dsdf_zoho_to_wc_running', false );
        update_option( 'dsdf_zoho_to_wc_stop', false );
        update_option( 'dsdf_zoho_to_wc_queued', false );
        dsdf_log( 'Stale Zoho -> WooCommerce sync state auto-reset (age: ' . round( $age / 3600, 1 ) . 'h).' );
        return true;
    }
    return false;
}

// ---------------------------------------------------------------------------
// Hash cache  (skip unchanged rows)
// ---------------------------------------------------------------------------
function dsdf_hash_cache_file(): string {
    return dsdf_upload_dir() . 'dsdf-hash-cache.json';
}

function dsdf_load_hash_cache(): array {
    $f = dsdf_hash_cache_file();
    if ( ! file_exists( $f ) ) return [];
    $d = json_decode( file_get_contents( $f ), true );
    return is_array( $d ) ? $d : [];
}

function dsdf_save_hash_cache( array $cache ): void {
    file_put_contents( dsdf_hash_cache_file(), json_encode( $cache ), LOCK_EX );
}

function dsdf_image_hash_cache_file(): string {
    return dsdf_upload_dir() . 'dsdf-image-hash-cache.json';
}

function dsdf_load_image_hash_cache(): array {
    $f = dsdf_image_hash_cache_file();
    if ( ! file_exists( $f ) ) {
        return [];
    }
    $d = json_decode( file_get_contents( $f ), true );
    return is_array( $d ) ? $d : [];
}

function dsdf_save_image_hash_cache( array $cache ): void {
    file_put_contents( dsdf_image_hash_cache_file(), json_encode( $cache ), LOCK_EX );
}

/** True when Zoho returned a successful create/update (item key optional on PUT). */
function dsdf_zoho_api_succeeded( $res ): bool {
    if ( is_wp_error( $res ) ) {
        return false;
    }
    if ( ! empty( $res['item'] ) ) {
        return true;
    }
    return isset( $res['code'] ) && (int) $res['code'] === 0;
}

// ---------------------------------------------------------------------------
// Rate-limit wrapper
// ---------------------------------------------------------------------------
function dsdf_zoho_call( callable $fn, string $label ) {
    if ( dsdf_sync_stop_requested() ) {
        return new WP_Error( 'dsdf_sync_stopped', 'Sync stopped by user.' );
    }

    $res     = $fn();
    $err_msg = dsdf_zoho_error_message( $res );

    if ( dsdf_is_zoho_daily_limit_error( $err_msg ) ) {
        dsdf_log( "Zoho daily limit hit [{$label}] — stopping sync until tomorrow." );
        dsdf_set_zoho_daily_blocked();
        return $res;
    }

    if ( ! dsdf_is_zoho_rate_limit_error( $err_msg ) ) {
        return $res;
    }

    $sleep = DSDF_RATE_LIMIT_SLEEP;
    for ( $i = 1; $i <= DSDF_RATE_LIMIT_RETRIES; $i++ ) {
        dsdf_log( "Transient rate limit [{$label}] attempt {$i}/" . DSDF_RATE_LIMIT_RETRIES . " — sleeping {$sleep}s" );
        sleep( $sleep );
        if ( dsdf_sync_stop_requested() ) {
            return new WP_Error( 'dsdf_sync_stopped', 'Sync stopped by user.' );
        }
        $res     = $fn();
        $err_msg = dsdf_zoho_error_message( $res );
        if ( dsdf_is_zoho_daily_limit_error( $err_msg ) ) {
            dsdf_set_zoho_daily_blocked();
            return $res;
        }
        if ( ! dsdf_is_zoho_rate_limit_error( $err_msg ) ) {
            return $res;
        }
        $sleep *= 2;
    }
    return $res;
}

// ---------------------------------------------------------------------------
// Activation / deactivation
// ---------------------------------------------------------------------------
register_activation_hook( __FILE__, 'dsdf_activate' );
register_deactivation_hook( __FILE__, 'dsdf_deactivate' );

function dsdf_activate(): void {
    $defaults = [
        'host'        => 'dsdatafeeds.blob.core.windows.net',
        'port'        => '22',
        'username'    => 'dsdatafeeds.auswid',
        'password'    => '',
        'remote_path' => '/AUSWID/ds-standard-datafeed.csv',
        'local_file'  => 'ds-standard-datafeed.csv',
    ];
    foreach ( $defaults as $key => $val ) {
        if ( get_option( 'dsdf_' . $key ) === false ) {
            add_option( 'dsdf_' . $key, $val );
        }
    }
    if ( ! file_exists( dsdf_upload_dir() ) ) wp_mkdir_p( dsdf_upload_dir() );

    // Schedule daily CSV download only (NO auto-sync)
    if ( ! wp_next_scheduled( 'dsdf_daily_download' ) ) {
        wp_schedule_event( strtotime( 'tomorrow midnight' ), 'daily', 'dsdf_daily_download' );
    }
}

function dsdf_deactivate(): void {
    $ts = wp_next_scheduled( 'dsdf_daily_download' );
    if ( $ts ) wp_unschedule_event( $ts, 'dsdf_daily_download' );
    $ts = wp_next_scheduled( 'dsdf_background_sync' );
    if ( $ts ) wp_unschedule_event( $ts, 'dsdf_background_sync' );
}

// ---------------------------------------------------------------------------
// Cron — daily CSV download ONLY (no Zoho sync)
// ---------------------------------------------------------------------------
add_action( 'dsdf_daily_download', 'dsdf_cron_download_only' );

function dsdf_cron_download_only(): void {
    dsdf_log( 'Cron: starting daily CSV download...' );
    $result = dsdf_run_download();
    if ( is_wp_error( $result ) ) {
        dsdf_log( 'Cron: download FAILED — ' . $result->get_error_message() );
    } else {
        dsdf_log( 'Cron: download complete → ' . $result );
        dsdf_log( 'Cron: Zoho sync NOT started automatically. Use the "Sync to Zoho" button in the admin.' );
    }
}

// Background sync job — triggered only by the manual "Sync to Zoho" button
add_action( 'dsdf_background_sync', 'dsdf_background_sync_job' );

function dsdf_background_sync_job(): void {
    if ( ! dsdf_acquire_sync_lock() ) {
        dsdf_log( 'Background sync skipped — another sync holds the lock.' );
        return;
    }
    if ( dsdf_is_zoho_daily_blocked() ) {
        dsdf_log( 'Background sync skipped — Zoho daily API limit reached. Retry tomorrow.' );
        dsdf_release_sync_lock();
        return;
    }
    $csv_path = dsdf_upload_dir() . ( dsdf_get_opt( 'local_file' ) ?: 'ds-standard-datafeed.csv' );
    if ( ! ini_get( 'safe_mode' ) ) {
        @set_time_limit( 0 );
    }
    try {
        dsdf_sync_to_zoho( $csv_path );
    } finally {
        dsdf_release_sync_lock();
    }
}

// Background Zoho -> WooCommerce sync job — triggered by manual button.
add_action( 'dsdf_background_zoho_to_wc_sync', 'dsdf_background_zoho_to_wc_sync_job' );

function dsdf_background_zoho_to_wc_sync_job(): void {
    if ( dsdf_is_zoho_to_wc_sync_running() ) {
        dsdf_log( 'Zoho -> WooCommerce sync skipped — another Zoho -> WooCommerce sync is already running.' );
        return;
    }

    update_option( 'dsdf_zoho_to_wc_queued', false );
    dsdf_set_zoho_to_wc_sync_running( true );
    dsdf_log( 'Zoho -> WooCommerce sync started.' );

    try {
        $result = dsdf_sync_zoho_to_wc();
        if ( is_wp_error( $result ) ) {
            $summary = 'Zoho -> WooCommerce sync failed: ' . $result->get_error_message();
            dsdf_log( 'ERROR: ' . $summary );
            update_option( 'dsdf_last_zoho_to_wc_sync_stats', $summary );
            return;
        }

        $summary = sprintf(
            'Zoho -> WooCommerce sync done%s | Zoho items: %d | Synced: %d | Created in WC: %d | Updated in WC: %d | Skipped: %d | Errors: %d',
            ! empty( $result['stopped'] ) ? ' (stopped by user)' : '',
            (int) ( $result['total'] ?? 0 ),
            (int) ( $result['synced'] ?? 0 ),
            (int) ( $result['created'] ?? 0 ),
            (int) ( $result['updated'] ?? 0 ),
            (int) ( $result['skipped'] ?? 0 ),
            (int) ( $result['errors'] ?? 0 )
        );
        dsdf_log( $summary );
        update_option( 'dsdf_last_zoho_to_wc_sync_stats', $summary );
    } finally {
        dsdf_set_zoho_to_wc_sync_running( false );
    }
}

function dsdf_find_wc_product_id_by_meta( string $meta_key, string $meta_value ): int {
    if ( '' === $meta_value ) {
        return 0;
    }
    $found = wc_get_products(
        [
            'meta_key'   => $meta_key,
            'meta_value' => $meta_value,
            'limit'      => 1,
            'return'     => 'ids',
        ]
    );
    return ! empty( $found ) ? (int) $found[0] : 0;
}

function dsdf_find_wc_product_id_for_zoho_item( array $item ): int {
    $zoho_item_id = (string) ( $item['item_id'] ?? '' );
    if ( '' !== $zoho_item_id ) {
        $id = dsdf_find_wc_product_id_by_meta( DSDF_WC_META_ZOHO_ITEM_ID, $zoho_item_id );
        if ( $id > 0 ) {
            return $id;
        }

        $id = dsdf_find_wc_product_id_by_meta( \ZohoInventorySync\Sync\Product_Sync::META_ZOHO_ID, $zoho_item_id );
        if ( $id > 0 ) {
            return $id;
        }
    }

    $sku = (string) ( $item['sku'] ?? '' );
    if ( '' !== $sku && function_exists( 'wc_get_product_id_by_sku' ) ) {
        $id = (int) wc_get_product_id_by_sku( $sku );
        if ( $id > 0 ) {
            return $id;
        }
    }

    return 0;
}

/** Parse comma/newline separated test identifiers (SKU and/or Zoho item_id). */
function dsdf_parse_test_identifiers( string $raw ): array {
    $parts = preg_split( '/[\s,]+/', trim( $raw ) ) ?: [];
    $parts = array_values( array_unique( array_filter( array_map( 'trim', $parts ), fn( $v ) => '' !== $v ) ) );
    return $parts;
}

/** Check whether a Zoho item matches a test-only filter list. */
function dsdf_zoho_item_matches_test_filter( array $item, array $identifiers ): bool {
    if ( empty( $identifiers ) ) {
        return true;
    }
    $sku = strtolower( trim( (string) ( $item['sku'] ?? '' ) ) );
    $id  = strtolower( trim( (string) ( $item['item_id'] ?? '' ) ) );
    foreach ( $identifiers as $needle ) {
        $n = strtolower( $needle );
        if ( '' !== $sku && $n === $sku ) {
            return true;
        }
        if ( '' !== $id && $n === $id ) {
            return true;
        }
    }
    return false;
}

/** Get Zoho custom field value by customfield_id from an item payload. */
function dsdf_zoho_custom_field_value( array $item, string $customfield_id ): string {
    if ( '' === $customfield_id ) {
        return '';
    }
    foreach ( (array) ( $item['custom_fields'] ?? [] ) as $field ) {
        if ( ! is_array( $field ) ) {
            continue;
        }
        if ( (string) ( $field['customfield_id'] ?? '' ) !== $customfield_id ) {
            continue;
        }
        $value = $field['value'] ?? '';
        return is_scalar( $value ) ? trim( (string) $value ) : '';
    }
    return '';
}

/** Save DSDF custom-field mapping values on a WooCommerce product. */
function dsdf_sync_zoho_custom_fields_to_wc_meta( int $wc_product_id, array $item ): void {
    if ( $wc_product_id <= 0 ) {
        return;
    }

    $supplier_source = dsdf_zoho_custom_field_value( $item, DSDF_ZOHO_CF_SUPPLIER_SOURCE_ID );
    $ingram_part     = dsdf_zoho_custom_field_value( $item, DSDF_ZOHO_CF_INGRAM_PART_NUMBER_ID );
    $vendor_part     = dsdf_zoho_custom_field_value( $item, DSDF_ZOHO_CF_VENDOR_PART_NUMBER_ID );
    $available_qty   = dsdf_zoho_custom_field_value( $item, DSDF_ZOHO_CF_AVAILABLE_QTY_ID );

    update_post_meta( $wc_product_id, DSDF_WC_META_SUPPLIER_SOURCE, $supplier_source );
    update_post_meta( $wc_product_id, DSDF_WC_META_INGRAM_PART_NUMBER, $ingram_part );
    update_post_meta( $wc_product_id, DSDF_WC_META_VENDOR_PART_NUMBER, $vendor_part );
    update_post_meta( $wc_product_id, DSDF_WC_META_AVAILABLE_QTY, $available_qty );
}

/**
 * Sync all Zoho items to WooCommerce products using Zoho Inventory WooCommerce Sync plugin.
 *
 * @return array|\WP_Error
 */
function dsdf_sync_zoho_to_wc() {
    if ( ! class_exists( '\ZohoInventorySync\Includes\Plugin' ) || ! class_exists( '\ZohoInventorySync\Api\Zoho_Items_API' ) ) {
        return new WP_Error(
            'dsdf_zoho_wc_plugin_missing',
            'Zoho Inventory WooCommerce Sync plugin is required for Zoho -> WooCommerce sync.'
        );
    }

    $plugin = \ZohoInventorySync\Includes\Plugin::instance();
    if ( ! $plugin->oauth->is_connected() || '' === $plugin->oauth->get_organization_id() ) {
        return new WP_Error(
            'dsdf_zoho_wc_not_connected',
            'Zoho Inventory WooCommerce Sync is not connected to an organisation.'
        );
    }

    $items_api = new \ZohoInventorySync\Api\Zoho_Items_API( $plugin->oauth );
    $items     = $items_api->list_items( [ 'status' => 'active' ] );

    if ( ! is_array( $items ) ) {
        return new WP_Error( 'dsdf_zoho_items_list_failed', 'Could not fetch items from Zoho Inventory.' );
    }

    // One-time test mode: sync only specific SKU(s) / item_id(s) when provided from admin action.
    $test_filter_raw  = (string) get_option( 'dsdf_zoho_to_wc_test_filter', '' );
    $test_identifiers = dsdf_parse_test_identifiers( $test_filter_raw );
    delete_option( 'dsdf_zoho_to_wc_test_filter' );
    if ( ! empty( $test_identifiers ) ) {
        $items = array_values( array_filter( $items, fn( $item ) => dsdf_zoho_item_matches_test_filter( (array) $item, $test_identifiers ) ) );
        dsdf_log( 'Zoho -> WooCommerce test mode: syncing only ' . count( $items ) . ' filtered item(s): ' . implode( ', ', $test_identifiers ) );
    }

    $stats = [
        'total'   => count( $items ),
        'synced'  => 0,
        'created' => 0,
        'updated' => 0,
        'skipped' => 0,
        'errors'  => 0,
    ];

    dsdf_log( 'Zoho -> WooCommerce: fetched ' . $stats['total'] . ' active Zoho item(s).' );

    foreach ( $items as $index => $item ) {
        if ( dsdf_zoho_to_wc_stop_requested() ) {
            $stats['stopped'] = 1;
            dsdf_log( "Zoho -> WooCommerce: stop requested by user at {$index}/{$stats['total']}." );
            break;
        }

        $zoho_item_id = (string) ( $item['item_id'] ?? '' );
        if ( '' === $zoho_item_id ) {
            $stats['skipped']++;
            continue;
        }

        // Fetch full item payload so image fields and other details are available.
        $full_item = $item;
        $item_res  = $items_api->get_item( $zoho_item_id );
        if ( ! is_wp_error( $item_res ) && ! empty( $item_res['item'] ) && is_array( $item_res['item'] ) ) {
            $full_item = array_merge( $full_item, $item_res['item'] );
        }

        $existing_product_id = dsdf_find_wc_product_id_for_zoho_item( $full_item );
        $already_linked      = $existing_product_id > 0;
        if ( $already_linked ) {
            update_post_meta( $existing_product_id, \ZohoInventorySync\Sync\Product_Sync::META_ZOHO_ID, $zoho_item_id );
            update_post_meta( $existing_product_id, DSDF_WC_META_ZOHO_ITEM_ID, $zoho_item_id );
        }

        // Create only when product truly doesn't exist in WooCommerce (by Zoho link or SKU).
        $wc_product_id = $plugin->product_sync->update_from_zoho( $full_item, ! $already_linked );
        if ( ! $wc_product_id ) {
            $stats['errors']++;
            continue;
        }

        // Keep a dedicated custom field with Zoho item id on WooCommerce product.
        update_post_meta( (int) $wc_product_id, DSDF_WC_META_ZOHO_ITEM_ID, $zoho_item_id );
        dsdf_sync_zoho_custom_fields_to_wc_meta( (int) $wc_product_id, $full_item );

        $stats['synced']++;
        if ( $already_linked ) {
            $stats['updated']++;
        } else {
            $stats['created']++;
        }

        if ( ( $index + 1 ) % 100 === 0 ) {
            dsdf_log(
                sprintf(
                    'Zoho -> WooCommerce progress: %d/%d | Synced: %d | Created: %d | Updated: %d | Errors: %d',
                    $index + 1,
                    $stats['total'],
                    $stats['synced'],
                    $stats['created'],
                    $stats['updated'],
                    $stats['errors']
                )
            );
        }
    }

    return $stats;
}

// ---------------------------------------------------------------------------
// AJAX handlers
// ---------------------------------------------------------------------------

// Poll sync progress
add_action( 'wp_ajax_dsdf_sync_progress', function () {
    check_ajax_referer( 'dsdf_nonce', 'nonce' );
    wp_send_json_success( [
        'running'  => dsdf_is_sync_running(),
        'done'     => (int) get_option( 'dsdf_sync_progress', 0 ),
        'total'    => (int) get_option( 'dsdf_sync_total', 0 ),
        'summary'  => get_option( 'dsdf_last_sync_stats', '' ),
    ] );
} );

// Stop sync
add_action( 'wp_ajax_dsdf_stop_sync', function () {
    check_ajax_referer( 'dsdf_nonce', 'nonce' );
    dsdf_request_sync_stop();
    $at    = (int) get_option( 'dsdf_sync_progress', 0 );
    $total = (int) get_option( 'dsdf_sync_total', 0 );
    dsdf_log( "Sync stop requested by user at row {$at}/{$total}." );
    update_option( 'dsdf_last_sync_stats', "Stop requested at row {$at}/{$total} — finishing current item..." );
    wp_send_json_success( [ 'message' => "Stop requested at row {$at}. Finishing current item..." ] );
} );

// Stop Zoho -> WooCommerce sync
add_action( 'wp_ajax_dsdf_stop_zoho_to_wc_sync', function () {
    check_ajax_referer( 'dsdf_nonce', 'nonce' );
    dsdf_request_zoho_to_wc_stop();
    dsdf_log( 'Zoho -> WooCommerce stop requested by user.' );
    update_option( 'dsdf_last_zoho_to_wc_sync_stats', 'Stop requested for Zoho -> WooCommerce sync — finishing current item...' );
    wp_send_json_success( [ 'message' => 'Stop requested. Finishing current Zoho item...' ] );
} );

// Reset stuck sync flag
add_action( 'wp_ajax_dsdf_reset_sync', function () {
    check_ajax_referer( 'dsdf_nonce', 'nonce' );
    dsdf_reset_stale_sync( true );
    dsdf_log( 'Sync state manually reset by user.' );
    wp_send_json_success( [ 'message' => 'Sync state reset. You can now start a new sync.' ] );
} );

// Clear hash cache (force full re-sync)
add_action( 'wp_ajax_dsdf_clear_cache', function () {
    check_ajax_referer( 'dsdf_nonce', 'nonce' );
    @unlink( dsdf_hash_cache_file() );
    @unlink( dsdf_image_hash_cache_file() );
    dsdf_log( 'Hash cache cleared — next sync will push all products.' );
    wp_send_json_success( [ 'message' => 'Cache cleared. Next sync will push all products and images.' ] );
} );

// Reset Zoho daily-limit flag (use after midnight UTC if sync is still blocked)
add_action( 'wp_ajax_dsdf_reset_api_limit', function () {
    check_ajax_referer( 'dsdf_nonce', 'nonce' );
    delete_option( 'dsdf_zoho_daily_limit_until' );
    dsdf_log( 'Zoho daily-limit flag cleared by user.' );
    wp_send_json_success( [ 'message' => 'API limit flag cleared. You can sync again if Zoho quota has reset.' ] );
} );

// Clear error log
add_action( 'wp_ajax_dsdf_clear_errors', function () {
    check_ajax_referer( 'dsdf_nonce', 'nonce' );
    @unlink( dsdf_upload_dir() . 'dsdf-errors.json' );
    dsdf_log( 'Error log cleared by user.' );
    wp_send_json_success( [ 'message' => 'Error log cleared.' ] );
} );

// ---------------------------------------------------------------------------
// Admin menu + styles + JS
// ---------------------------------------------------------------------------
add_action( 'admin_menu',   'dsdf_admin_menu' );
add_action( 'admin_head',   'dsdf_admin_styles' );
add_action( 'admin_footer', 'dsdf_admin_js' );
add_action( 'woocommerce_product_options_general_product_data', 'dsdf_wc_show_zoho_item_id_field' );
add_action( 'woocommerce_variation_options_pricing', 'dsdf_wc_show_zoho_item_id_variation_field', 10, 3 );
add_action( 'woocommerce_process_product_meta', 'dsdf_wc_save_custom_fields', 10, 1 );

function dsdf_admin_menu(): void {
    add_menu_page(
        'DS Datafeed', 'DS Datafeed', 'manage_options',
        'dynamic-supplies-datafeed', 'dsdf_admin_page',
        'dashicons-download', 56
    );
}

/** Show Zoho item ID in WooCommerce product edit (General tab). */
function dsdf_wc_show_zoho_item_id_field(): void {
    global $post;
    if ( ! $post || empty( $post->ID ) || ! function_exists( 'woocommerce_wp_text_input' ) ) {
        return;
    }

    $product_id = (int) $post->ID;
    $value      = (string) get_post_meta( $product_id, DSDF_WC_META_ZOHO_ITEM_ID, true );
    if ( '' === $value ) {
        // Fallback to the Zoho sync plugin field so merchants can always see the linked item.
        $value = (string) get_post_meta( $product_id, '_zoho_inv_item_id', true );
    }

    woocommerce_wp_text_input(
        [
            'id'                => 'dsdf_zoho_item_id_view',
            'label'             => __( 'Zoho Item ID', 'dynamic-supplies-datafeed' ),
            'value'             => $value,
            'desc_tip'          => true,
            'description'       => __( 'Read-only Zoho Inventory item ID linked to this product.', 'dynamic-supplies-datafeed' ),
            'custom_attributes' => [
                'readonly' => 'readonly',
            ],
        ]
    );

    woocommerce_wp_text_input(
        [
            'id'          => 'dsdf_supplier_source',
            'label'       => __( 'Supplier Source', 'dynamic-supplies-datafeed' ),
            'value'       => (string) get_post_meta( $product_id, DSDF_WC_META_SUPPLIER_SOURCE, true ),
            'desc_tip'    => true,
            'description' => __( 'Synced from Zoho custom field.', 'dynamic-supplies-datafeed' ),
        ]
    );

    woocommerce_wp_text_input(
        [
            'id'          => 'dsdf_ingram_part_number',
            'label'       => __( 'Ingram Part Number', 'dynamic-supplies-datafeed' ),
            'value'       => (string) get_post_meta( $product_id, DSDF_WC_META_INGRAM_PART_NUMBER, true ),
            'desc_tip'    => true,
            'description' => __( 'Synced from Zoho custom field.', 'dynamic-supplies-datafeed' ),
        ]
    );

    woocommerce_wp_text_input(
        [
            'id'          => 'dsdf_vendor_part_number',
            'label'       => __( 'Vendor Part Number', 'dynamic-supplies-datafeed' ),
            'value'       => (string) get_post_meta( $product_id, DSDF_WC_META_VENDOR_PART_NUMBER, true ),
            'desc_tip'    => true,
            'description' => __( 'Synced from Zoho custom field.', 'dynamic-supplies-datafeed' ),
        ]
    );

    woocommerce_wp_text_input(
        [
            'id'                => 'dsdf_available_qty',
            'label'             => __( 'Available Qty', 'dynamic-supplies-datafeed' ),
            'type'              => 'number',
            'value'             => (string) get_post_meta( $product_id, DSDF_WC_META_AVAILABLE_QTY, true ),
            'desc_tip'          => true,
            'description'       => __( 'Synced from Zoho custom field.', 'dynamic-supplies-datafeed' ),
            'custom_attributes' => [
                'step' => '1',
            ],
        ]
    );
}

/** Show Zoho item ID in each WooCommerce variation pricing panel. */
function dsdf_wc_show_zoho_item_id_variation_field( $loop, $variation_data, $variation ): void {
    if ( ! $variation || empty( $variation->ID ) || ! function_exists( 'woocommerce_wp_text_input' ) ) {
        return;
    }

    $variation_id = (int) $variation->ID;
    $value        = (string) get_post_meta( $variation_id, DSDF_WC_META_ZOHO_ITEM_ID, true );
    if ( '' === $value ) {
        $value = (string) get_post_meta( $variation_id, '_zoho_inv_item_id', true );
    }

    woocommerce_wp_text_input(
        [
            'id'                => "dsdf_zoho_item_id_view_{$variation_id}",
            'label'             => __( 'Zoho Item ID', 'dynamic-supplies-datafeed' ),
            'value'             => $value,
            'wrapper_class'     => 'form-row form-row-full',
            'desc_tip'          => true,
            'description'       => __( 'Read-only Zoho Inventory item ID linked to this variation.', 'dynamic-supplies-datafeed' ),
            'custom_attributes' => [
                'readonly' => 'readonly',
            ],
        ]
    );
}

/** Save DSDF custom fields from WooCommerce product edit page. */
function dsdf_wc_save_custom_fields( int $product_id ): void {
    if ( isset( $_POST['dsdf_supplier_source'] ) ) {
        update_post_meta(
            $product_id,
            DSDF_WC_META_SUPPLIER_SOURCE,
            sanitize_text_field( wp_unslash( $_POST['dsdf_supplier_source'] ) )
        );
    }

    if ( isset( $_POST['dsdf_ingram_part_number'] ) ) {
        update_post_meta(
            $product_id,
            DSDF_WC_META_INGRAM_PART_NUMBER,
            sanitize_text_field( wp_unslash( $_POST['dsdf_ingram_part_number'] ) )
        );
    }

    if ( isset( $_POST['dsdf_vendor_part_number'] ) ) {
        update_post_meta(
            $product_id,
            DSDF_WC_META_VENDOR_PART_NUMBER,
            sanitize_text_field( wp_unslash( $_POST['dsdf_vendor_part_number'] ) )
        );
    }

    if ( isset( $_POST['dsdf_available_qty'] ) ) {
        $qty = trim( (string) wp_unslash( $_POST['dsdf_available_qty'] ) );
        update_post_meta( $product_id, DSDF_WC_META_AVAILABLE_QTY, '' === $qty ? '' : (string) (int) $qty );
    }
}

function dsdf_admin_styles(): void {
    $screen = get_current_screen();
    if ( ! $screen || strpos( $screen->id, 'dynamic-supplies-datafeed' ) === false ) return;
    ?>
    <style>
    .dsdf-wrap{max-width:960px}
    .dsdf-header{display:flex;align-items:center;gap:18px;background:#fff;border:1px solid #e0e0e0;
        border-radius:8px;padding:18px 24px;margin-bottom:22px;box-shadow:0 1px 4px rgba(0,0,0,.07)}
    .dsdf-header img{height:50px;width:auto}
    .dsdf-header h1{margin:0 0 3px;font-size:1.35rem;color:#1d2327}
    .dsdf-header p{margin:0;color:#646970;font-size:.84rem}
    .dsdf-cards{display:flex;flex-wrap:wrap;gap:12px;margin-bottom:22px}
    .dsdf-card{flex:1 1 170px;background:#fff;border:1px solid #e0e0e0;border-radius:8px;
        padding:14px 18px;box-shadow:0 1px 4px rgba(0,0,0,.05)}
    .dsdf-card-icon{font-size:1.4rem;margin-bottom:4px}
    .dsdf-card-label{font-size:.7rem;text-transform:uppercase;letter-spacing:.05em;color:#646970;margin-bottom:3px}
    .dsdf-card-value{font-size:.88rem;font-weight:600;color:#1d2327;word-break:break-all}
    .dsdf-badge{display:inline-block;padding:2px 10px;border-radius:20px;font-size:.74rem;font-weight:600}
    .dsdf-badge.green {background:#d1fae5;color:#065f46}
    .dsdf-badge.orange{background:#fef3c7;color:#92400e}
    .dsdf-badge.red   {background:#fee2e2;color:#991b1b}
    .dsdf-badge.blue  {background:#dbeafe;color:#1e40af}
    .dsdf-badge.grey  {background:#f3f4f6;color:#374151}
    .dsdf-section{background:#fff;border:1px solid #e0e0e0;border-radius:8px;
        padding:20px 24px;margin-bottom:22px;box-shadow:0 1px 4px rgba(0,0,0,.05)}
    .dsdf-section h2{margin-top:0;font-size:1rem;border-bottom:1px solid #f0f0f0;
        padding-bottom:10px;margin-bottom:16px;display:flex;align-items:center;gap:10px;flex-wrap:wrap}
    .dsdf-actions-row{display:flex;gap:10px;flex-wrap:wrap;align-items:center}
    .dsdf-cred-table{width:100%;border-collapse:collapse}
    .dsdf-cred-table td{padding:7px 4px;vertical-align:middle}
    .dsdf-cred-table td:first-child{width:160px;font-weight:600;color:#1d2327;font-size:.87rem}
    .dsdf-cred-table input[type=text],.dsdf-cred-table input[type=password],
    .dsdf-cred-table input[type=number],.dsdf-cred-table select{width:100%;max-width:380px}
    .dsdf-cred-table small{color:#646970;display:block;margin-top:2px}
    /* Progress */
    .dsdf-progress-wrap{margin-top:14px;display:none}
    .dsdf-progress-wrap.visible{display:block}
    .dsdf-progress-bar-bg{background:#f0f0f0;border-radius:6px;height:18px;overflow:hidden;margin-bottom:5px}
    .dsdf-progress-bar{background:#2271b1;height:18px;border-radius:6px;width:0%;transition:width .4s}
    .dsdf-progress-label{font-size:.81rem;color:#646970}
    #dsdf-stop-btn,#dsdf-stop-zoho-wc-btn{opacity:.4;cursor:not-allowed;pointer-events:none}
    #dsdf-stop-btn.active,#dsdf-stop-zoho-wc-btn.active{opacity:1;cursor:pointer;pointer-events:auto}
    /* Log */
    .dsdf-log-box{background:#1d2327;color:#a8c5a0;padding:14px 16px;border-radius:6px;
        max-height:300px;overflow-y:auto;font-size:.77rem;line-height:1.6;margin:0;
        white-space:pre-wrap;word-break:break-all}
    /* Error table */
    .dsdf-error-table{width:100%;border-collapse:collapse;font-size:.81rem}
    .dsdf-error-table th{background:#fef2f2;color:#991b1b;padding:6px 10px;text-align:left;
        border-bottom:2px solid #fecaca}
    .dsdf-error-table td{padding:6px 10px;border-bottom:1px solid #f3f4f6;vertical-align:top}
    .dsdf-error-table tr:hover td{background:#fff7f7}
    .dsdf-json-toggle{cursor:pointer;color:#2271b1;text-decoration:underline;font-size:.77rem}
    .dsdf-json-block{display:none;background:#f9fafb;border:1px solid #e5e7eb;border-radius:4px;
        padding:8px;margin-top:4px;font-family:monospace;font-size:.74rem;white-space:pre-wrap;word-break:break-all}
    </style>
    <?php
}

function dsdf_admin_js(): void {
    $screen = get_current_screen();
    if ( ! $screen || strpos( $screen->id, 'dynamic-supplies-datafeed' ) === false ) return;
    $nonce = wp_create_nonce( 'dsdf_nonce' );
    ?>
    <script>
    (function($){
        var pollTimer = null;

        function startPolling() {
            $('#dsdf-stop-btn').addClass('active');
            $('.dsdf-progress-wrap').addClass('visible');
            pollProgress();
            if ( pollTimer ) return;
            pollTimer = setInterval( pollProgress, 1500 );
        }

        function stopPolling() {
            if ( pollTimer ) { clearInterval(pollTimer); pollTimer = null; }
            $('#dsdf-stop-btn').removeClass('active');
        }

        function pollProgress() {
            $.post( ajaxurl, { action: 'dsdf_sync_progress', nonce: '<?php echo esc_js($nonce); ?>' }, function(r) {
                if ( ! r.success ) return;
                var d   = r.data;
                var pct = d.total > 0 ? Math.round( (d.done / d.total) * 100 ) : 0;
                $('.dsdf-progress-bar').css( 'width', pct + '%' );
                $('.dsdf-progress-label').text(
                    d.running
                        ? ( d.total > 0
                            ? 'Syncing... ' + d.done + ' / ' + d.total + ' (' + pct + '%)'
                            : 'Sync queued... preparing rows to sync.' )
                        : ( d.summary || 'Sync complete.' )
                );
                if ( ! d.running ) {
                    stopPolling();
                    $('.dsdf-progress-bar').css({ width: '100%', background: '#00a32a' });
                    setTimeout( function(){ location.reload(); }, 1800 );
                }
            });
        }

        // Sync button submit → start polling
        $('#dsdf-sync-form').on('submit', function(){
            startPolling();
        });

        // Stop button
        $('#dsdf-stop-btn').on('click', function(e){
            e.preventDefault();
            $(this).prop('disabled', true).text('Stopping...');
            $.post( ajaxurl, { action: 'dsdf_stop_sync', nonce: '<?php echo esc_js($nonce); ?>' }, function(r){
                stopPolling();
                $('.dsdf-progress-bar').css({ width: '100%', background: '#d63638' });
                $('.dsdf-progress-label').text( r.success ? r.data.message : 'Stopped.' );
                setTimeout( function(){ location.reload(); }, 1500 );
            });
        });

        $('#dsdf-stop-zoho-wc-btn').on('click', function(e){
            e.preventDefault();
            $(this).prop('disabled', true).text('Stopping...');
            $.post( ajaxurl, { action: 'dsdf_stop_zoho_to_wc_sync', nonce: '<?php echo esc_js($nonce); ?>' }, function(r){
                alert( r.success ? r.data.message : 'Stop request failed.' );
                setTimeout( function(){ location.reload(); }, 1200 );
            });
        });

        // Reset stuck sync
        $('#dsdf-reset-btn').on('click', function(e){
            e.preventDefault();
            if ( ! confirm('Reset the "running" flag?\nOnly use this if the sync is frozen and not actually running.') ) return;
            $(this).prop('disabled', true).text('Resetting...');
            $.post( ajaxurl, { action: 'dsdf_reset_sync', nonce: '<?php echo esc_js($nonce); ?>' }, function(r){
                alert( r.success ? r.data.message : 'Reset failed.' );
                location.reload();
            });
        });

        // Clear hash cache
        $('#dsdf-clear-cache-btn').on('click', function(e){
            e.preventDefault();
            if ( ! confirm('This will force ALL products to be re-pushed to Zoho on the next sync.\nContinue?') ) return;
            $(this).prop('disabled', true).text('Clearing...');
            $.post( ajaxurl, { action: 'dsdf_clear_cache', nonce: '<?php echo esc_js($nonce); ?>' }, function(r){
                alert( r.success ? r.data.message : 'Failed.' );
                location.reload();
            });
        });

        // Reset Zoho daily API limit flag
        $('#dsdf-reset-api-limit-btn').on('click', function(e){
            e.preventDefault();
            if ( ! confirm('Clear the Zoho daily-limit block?\nOnly do this after midnight UTC when Zoho quota has reset.') ) return;
            $(this).prop('disabled', true).text('Resetting...');
            $.post( ajaxurl, { action: 'dsdf_reset_api_limit', nonce: '<?php echo esc_js($nonce); ?>' }, function(r){
                alert( r.success ? r.data.message : 'Failed.' );
                location.reload();
            });
        });

        // Clear error log
        $('#dsdf-clear-errors-btn').on('click', function(e){
            e.preventDefault();
            if ( ! confirm('Clear all error log entries?') ) return;
            $(this).prop('disabled', true).text('Clearing...');
            $.post( ajaxurl, { action: 'dsdf_clear_errors', nonce: '<?php echo esc_js($nonce); ?>' }, function(r){
                if ( r.success ) {
                    $('#dsdf-error-section').slideUp( 200, function(){ $(this).remove(); });
                } else {
                    alert('Failed.');
                    location.reload();
                }
            });
        });

        // JSON row toggles
        $(document).on('click', '.dsdf-json-toggle', function(){
            $(this).next('.dsdf-json-block').toggle();
            $(this).text( $(this).text() === 'Show' ? 'Hide' : 'Show' );
        });

        // Auto-start polling if sync was already in progress when page loaded
        <?php if ( dsdf_is_sync_running() ) : ?>
        startPolling();
        <?php endif; ?>

    }(jQuery));
    </script>
    <?php
}

// ---------------------------------------------------------------------------
// Admin page
// ---------------------------------------------------------------------------
function dsdf_admin_page(): void {

    // Auto-clear stale running flag (crashed sync)
    dsdf_reset_stale_sync();
    dsdf_reset_stale_zoho_to_wc_sync();

    // ---- Save SFTP settings ----
    if ( isset( $_POST['dsdf_save_sftp'] ) && check_admin_referer( 'dsdf_save_sftp', 'dsdf_sftp_nonce' ) ) {
        foreach ( ['host','port','username','password','remote_path','local_file'] as $f ) {
            if ( isset( $_POST["dsdf_{$f}"] ) ) {
                update_option( "dsdf_{$f}", sanitize_text_field( wp_unslash( $_POST["dsdf_{$f}"] ) ) );
            }
        }
        echo '<div class="notice notice-success is-dismissible"><p>✅ SFTP settings saved.</p></div>';
    }

    // ---- Save Zoho API credentials ----
    if ( isset( $_POST['dsdf_save_zoho'] ) && check_admin_referer( 'dsdf_save_zoho', 'dsdf_zoho_nonce' ) ) {
        foreach ( ['zoho_client_id','zoho_client_secret','zoho_refresh_token','zoho_org_id'] as $f ) {
            if ( isset( $_POST["dsdf_{$f}"] ) ) {
                update_option( "dsdf_{$f}", sanitize_text_field( wp_unslash( $_POST["dsdf_{$f}"] ) ) );
            }
        }
        if ( isset( $_POST['dsdf_inventory_account_name'] ) ) {
            update_option( 'dsdf_inventory_account_name', sanitize_text_field( wp_unslash( $_POST['dsdf_inventory_account_name'] ) ) );
            delete_option( 'dsdf_inventory_account_id' ); // account name changed -> force account id re-resolve
        }
        if ( isset( $_POST['dsdf_zoho_dc'] ) ) {
            $dc = sanitize_key( $_POST['dsdf_zoho_dc'] );
            update_option( 'dsdf_zoho_dc', in_array( $dc, ['com','eu','in','au','jp','ca'], true ) ? $dc : 'com' );
        }
        if ( isset( $_POST['dsdf_api_throttle_ms'] ) ) {
            update_option( 'dsdf_api_throttle_ms', max( 0, (int) $_POST['dsdf_api_throttle_ms'] ) );
        }
        if ( isset( $_POST['dsdf_daily_api_budget'] ) ) {
            update_option( 'dsdf_daily_api_budget', max( 100, min( 7400, (int) $_POST['dsdf_daily_api_budget'] ) ) );
        }
        // Flush cached token
        delete_option( DSDF_Zoho_Direct_API::TOKEN_OPTION );
        delete_option( DSDF_Zoho_Direct_API::TOKEN_EXPIRY_OPTION );
        echo '<div class="notice notice-success is-dismissible"><p>✅ Zoho credentials saved.</p></div>';
    }

    // ---- Manual CSV download ----
    if ( isset( $_POST['dsdf_download_now'] ) && check_admin_referer( 'dsdf_download_now', 'dsdf_dl_nonce' ) ) {
        $res = dsdf_run_download();
        if ( is_wp_error( $res ) ) {
            echo '<div class="notice notice-error is-dismissible"><p>❌ Download failed: ' . esc_html( $res->get_error_message() ) . '</p></div>';
        } else {
            echo '<div class="notice notice-success is-dismissible"><p>✅ CSV downloaded: <code>' . esc_html( $res ) . '</code></p></div>';
        }
    }

    // ---- Manual sync trigger ----
    if ( isset( $_POST['dsdf_sync_now'] ) && check_admin_referer( 'dsdf_sync_now', 'dsdf_sync_nonce' ) ) {
        if ( dsdf_is_sync_running() || file_exists( dsdf_sync_lock_file() ) ) {
            echo '<div class="notice notice-warning is-dismissible"><p>⚠️ A sync is already running.</p></div>';
        } elseif ( dsdf_is_zoho_daily_blocked() ) {
            $until = (int) get_option( 'dsdf_zoho_daily_limit_until', 0 );
            echo '<div class="notice notice-error is-dismissible"><p>❌ Zoho daily API limit (7,500/day) reached. Sync again after '
                . esc_html( gmdate( 'Y-m-d H:i', $until ) ) . ' UTC.</p></div>';
        } elseif ( dsdf_api_budget_remaining() <= 0 ) {
            echo '<div class="notice notice-error is-dismissible"><p>❌ Today\'s API budget is used up ('
                . esc_html( (string) dsdf_api_usage_today() ) . ' calls). Increase budget or wait until tomorrow.</p></div>';
        } elseif ( wp_next_scheduled( 'dsdf_background_sync' ) ) {
            echo '<div class="notice notice-warning is-dismissible"><p>⚠️ A sync is already queued. Please wait.</p></div>';
        } else {
            $zoho = dsdf_get_zoho_client();
            if ( ! $zoho->is_configured() ) {
                echo '<div class="notice notice-error is-dismissible"><p>❌ Zoho is not connected. Connect via <strong>Zoho Inventory WooCommerce Sync → Zoho Connection</strong>, or fill in the fallback credentials below.</p></div>';
            } else {
                $csv  = dsdf_upload_dir() . ( dsdf_get_opt( 'local_file' ) ?: 'ds-standard-datafeed.csv' );
                if ( ! file_exists( $csv ) ) {
                    echo '<div class="notice notice-error is-dismissible"><p>❌ CSV file not found. Click "Download CSV Now" first.</p></div>';
                } else {
                    wp_schedule_single_event( time() + 1, 'dsdf_background_sync' );
                    dsdf_set_sync_running( true, 0 );
                    update_option( 'dsdf_last_sync_stats', 'Sync queued — starting shortly...' );
                    dsdf_log( 'Sync manually triggered by admin user.' );
                    echo '<div class="notice notice-info is-dismissible"><p>⏳ Zoho sync queued — starting in a few seconds. Watch the progress bar below.</p></div>';
                }
            }
        }
    }

    // ---- Manual Zoho -> WooCommerce sync trigger ----
    if ( isset( $_POST['dsdf_sync_zoho_to_wc_now'] ) && check_admin_referer( 'dsdf_sync_zoho_to_wc_now', 'dsdf_sync_zoho_to_wc_nonce' ) ) {
        if ( dsdf_is_zoho_to_wc_sync_running() || wp_next_scheduled( 'dsdf_background_zoho_to_wc_sync' ) ) {
            echo '<div class="notice notice-warning is-dismissible"><p>⚠️ Zoho → WooCommerce sync is already running or queued.</p></div>';
        } elseif ( dsdf_is_sync_running() || file_exists( dsdf_sync_lock_file() ) ) {
            echo '<div class="notice notice-warning is-dismissible"><p>⚠️ Please wait until the current CSV → Zoho sync finishes, then run Zoho → WooCommerce sync.</p></div>';
        } elseif ( ! class_exists( '\ZohoInventorySync\Includes\Plugin' ) ) {
            echo '<div class="notice notice-error is-dismissible"><p>❌ Zoho → WooCommerce sync requires the <strong>Zoho Inventory WooCommerce Sync</strong> plugin.</p></div>';
        } else {
            $plugin = \ZohoInventorySync\Includes\Plugin::instance();
            if ( ! $plugin->oauth->is_connected() || '' === $plugin->oauth->get_organization_id() ) {
                echo '<div class="notice notice-error is-dismissible"><p>❌ Zoho Inventory WooCommerce Sync is not connected. Connect it first, then run Zoho → WooCommerce sync.</p></div>';
            } else {
                $test_filter = '';
                if ( isset( $_POST['dsdf_zoho_to_wc_test_filter'] ) ) {
                    $test_filter = sanitize_text_field( wp_unslash( $_POST['dsdf_zoho_to_wc_test_filter'] ) );
                }
                update_option( 'dsdf_zoho_to_wc_test_filter', $test_filter );
                wp_schedule_single_event( time() + 3, 'dsdf_background_zoho_to_wc_sync' );
                update_option( 'dsdf_zoho_to_wc_queued', true );
                update_option( 'dsdf_last_zoho_to_wc_sync_stats', 'Zoho → WooCommerce sync queued — starting shortly...' );
                dsdf_log( 'Zoho -> WooCommerce sync manually triggered by admin user.' );
                echo '<div class="notice notice-info is-dismissible"><p>⏳ Zoho → WooCommerce sync queued — starting in a few seconds.</p></div>';
            }
        }
    }

    // ---- Page data ----
    $local_file   = dsdf_upload_dir() . ( dsdf_get_opt( 'local_file' ) ?: 'ds-standard-datafeed.csv' );
    $file_exists  = file_exists( $local_file );
    $file_date    = $file_exists ? date( 'Y-m-d H:i:s', filemtime( $local_file ) ) : 'Never';
    $file_size    = $file_exists ? size_format( filesize( $local_file ) ) : '—';
    $today_csv    = $file_exists && ( date( 'Y-m-d', filemtime( $local_file ) ) === date( 'Y-m-d' ) );
    $next_cron    = wp_next_scheduled( 'dsdf_daily_download' );
    $sync_running = dsdf_is_sync_running();
    $zoho_to_wc_running = dsdf_is_zoho_to_wc_sync_running();
    $zoho_to_wc_queued  = (bool) get_option( 'dsdf_zoho_to_wc_queued', false ) || (bool) wp_next_scheduled( 'dsdf_background_zoho_to_wc_sync' );
    $sync_done    = (int) get_option( 'dsdf_sync_progress', 0 );
    $sync_total   = (int) get_option( 'dsdf_sync_total', 0 );
    $sync_pct     = $sync_total > 0 ? round( $sync_done / $sync_total * 100 ) : 0;
    $last_sync    = get_option( 'dsdf_last_sync_stats', '' );
    $last_zoho_to_wc_sync = (string) get_option( 'dsdf_last_zoho_to_wc_sync_stats', '' );
    $zoho_to_wc_test_filter = (string) get_option( 'dsdf_zoho_to_wc_test_filter', '' );
    $hash_cache   = dsdf_load_hash_cache();
    $hash_synced  = count( $hash_cache );
    $daily_budget  = (int) get_option( 'dsdf_daily_api_budget', DSDF_DEFAULT_BUDGET );
    $api_used      = dsdf_api_usage_today();
    $api_remaining = dsdf_api_budget_remaining();
    $zoho_blocked  = dsdf_is_zoho_daily_blocked();
    $csv_rows      = max( $sync_total, $hash_synced );
    $overall_pct  = $csv_rows > 0 ? min( 100, round( $hash_synced / $csv_rows * 100 ) ) : 0;

    $error_file    = dsdf_upload_dir() . 'dsdf-errors.json';
    $error_entries = [];
    if ( file_exists( $error_file ) ) {
        $raw = json_decode( file_get_contents( $error_file ), true );
        if ( is_array( $raw ) ) {
            $error_entries = array_slice( array_reverse( $raw ), 0, 100 );
        }
    }

    $zoho_info       = dsdf_zoho_connection_info();
    $zoho_configured = $zoho_info['configured'];
    $zoho_wc_sync    = $zoho_info['source'] === 'wc_sync';
    ?>
    <div class="wrap dsdf-wrap">

        <!-- Header -->
        <div class="dsdf-header">
            <img src="https://www.dynamicsupplies.com.au/wp-content/uploads/2021/03/Dynamic-Supplies-Logo.png"
                 onerror="this.style.display='none'" alt="Dynamic Supplies">
            <div>
                <h1>Dynamic Supplies → Zoho Inventory</h1>
                <p>CSV is downloaded automatically once per day. Zoho sync runs <strong>only</strong> when you click the button below.</p>
            </div>
        </div>

        <!-- Status cards -->
        <div class="dsdf-cards">
            <div class="dsdf-card">
                <div class="dsdf-card-icon">💾</div>
                <div class="dsdf-card-label">CSV File</div>
                <div class="dsdf-card-value">
                    <?php if ( $today_csv ) : ?>
                        <span class="dsdf-badge green">✔ Today's file ready</span>
                    <?php elseif ( $file_exists ) : ?>
                        <span class="dsdf-badge orange">⚠ Older file (<?php echo esc_html( date('Y-m-d', filemtime($local_file)) ); ?>)</span>
                    <?php else : ?>
                        <span class="dsdf-badge red">✘ Not downloaded yet</span>
                    <?php endif; ?>
                    <br><small style="color:#646970"><?php echo esc_html($file_size); ?> · <?php echo esc_html($file_date); ?></small>
                </div>
            </div>
            <div class="dsdf-card">
                <div class="dsdf-card-icon">🔗</div>
                <div class="dsdf-card-label">Zoho API</div>
                <div class="dsdf-card-value">
                    <?php echo $zoho_configured
                        ? '<span class="dsdf-badge green">✔ Connected</span>'
                        : '<span class="dsdf-badge red">✘ Not connected</span>'; ?>
                    <?php if ( $zoho_configured ) : ?>
                    <br><small style="color:#646970">
                        <?php echo esc_html( $zoho_info['label'] ); ?>
                        · Org: <?php echo esc_html( $zoho_info['org_id'] ?: '?' ); ?>
                        · DC: <?php echo esc_html( $zoho_info['dc'] ); ?>
                    </small>
                    <?php endif; ?>
                </div>
            </div>
            <div class="dsdf-card">
                <div class="dsdf-card-icon">🔄</div>
                <div class="dsdf-card-label">Sync Status</div>
                <div class="dsdf-card-value">
                    <?php if ( $sync_running ) : ?>
                        <span class="dsdf-badge blue">⏳ Running <?php echo esc_html($sync_done); ?>/<?php echo esc_html($sync_total); ?></span>
                    <?php elseif ( $last_sync ) : ?>
                        <span class="dsdf-badge green">✔ Last sync done</span>
                    <?php else : ?>
                        <span class="dsdf-badge grey">Never synced</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="dsdf-card">
                <div class="dsdf-card-icon">✅</div>
                <div class="dsdf-card-label">Products in Zoho</div>
                <div class="dsdf-card-value">
                    <strong><?php echo esc_html($hash_synced); ?></strong>
                    <?php if ( $csv_rows > 0 ) : ?>
                    / <?php echo esc_html($csv_rows); ?>
                    <div style="background:#f0f0f0;border-radius:3px;height:5px;margin-top:4px;overflow:hidden">
                        <div style="background:#00a32a;height:5px;width:<?php echo esc_attr($overall_pct); ?>%"></div>
                    </div>
                    <small style="color:#646970"><?php echo esc_html($overall_pct); ?>%</small>
                    <?php endif; ?>
                </div>
            </div>
            <div class="dsdf-card">
                <div class="dsdf-card-icon">📅</div>
                <div class="dsdf-card-label">Next Auto-Download</div>
                <div class="dsdf-card-value">
                    <?php echo $next_cron
                        ? esc_html( date('Y-m-d H:i', $next_cron) . ' UTC' )
                        : '<span class="dsdf-badge grey">Not scheduled</span>'; ?>
                </div>
            </div>
            <div class="dsdf-card">
                <div class="dsdf-card-icon">❌</div>
                <div class="dsdf-card-label">Sync Errors</div>
                <div class="dsdf-card-value">
                    <?php $err_count = count($error_entries);
                    echo $err_count > 0
                        ? '<span class="dsdf-badge red">' . esc_html($err_count) . ' error(s)</span>'
                        : '<span class="dsdf-badge green">None</span>'; ?>
                </div>
            </div>
            <div class="dsdf-card">
                <div class="dsdf-card-icon">📊</div>
                <div class="dsdf-card-label">API Calls Today</div>
                <div class="dsdf-card-value">
                    <?php if ( $zoho_blocked ) : ?>
                        <span class="dsdf-badge red">Zoho limit hit</span>
                    <?php else : ?>
                        <strong><?php echo esc_html( $api_used ); ?></strong> / <?php echo esc_html( $daily_budget ); ?>
                    <?php endif; ?>
                    <br><small style="color:#646970"><?php echo esc_html( $api_remaining ); ?> remaining · Zoho org max <?php echo esc_html( DSDF_ZOHO_ORG_DAILY_CAP ); ?>/day</small>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="dsdf-section">
            <h2>⚡ Actions</h2>
            <div class="dsdf-actions-row">

                <!-- Download CSV now -->
                <form method="post" style="display:inline">
                    <?php wp_nonce_field( 'dsdf_download_now', 'dsdf_dl_nonce' ); ?>
                    <?php submit_button( '⬇ Download CSV Now', 'secondary', 'dsdf_download_now', false ); ?>
                </form>

                <!-- Sync to Zoho — manual only -->
                <form method="post" style="display:inline" id="dsdf-sync-form">
                    <?php wp_nonce_field( 'dsdf_sync_now', 'dsdf_sync_nonce' ); ?>
                    <?php submit_button(
                        $sync_running ? '⏳ Sync Running...' : '🔄 Sync Products to Zoho',
                        'primary', 'dsdf_sync_now', false,
                        $sync_running ? ['disabled' => 'disabled'] : []
                    ); ?>
                </form>

                <!-- Sync Zoho -> WooCommerce -->
                <form method="post" style="display:inline">
                    <?php wp_nonce_field( 'dsdf_sync_zoho_to_wc_now', 'dsdf_sync_zoho_to_wc_nonce' ); ?>
                    <input type="text"
                           name="dsdf_zoho_to_wc_test_filter"
                           value="<?php echo esc_attr( $zoho_to_wc_test_filter ); ?>"
                           placeholder="Test only: SKU or item_id (comma separated)"
                           style="min-width:320px;margin-right:8px;vertical-align:middle">
                    <?php submit_button(
                        $zoho_to_wc_running ? '⏳ Zoho → WooCommerce Sync Running...' : ( $zoho_to_wc_queued ? '⏳ Zoho → WooCommerce Sync Queued...' : '⬅️ Start Zoho → WooCommerce Sync' ),
                        'secondary',
                        'dsdf_sync_zoho_to_wc_now',
                        false,
                        ( $zoho_to_wc_running || $zoho_to_wc_queued || $sync_running ) ? [ 'disabled' => 'disabled' ] : []
                    ); ?>
                </form>

                <button id="dsdf-stop-zoho-wc-btn" class="button button-link-delete<?php echo ( $zoho_to_wc_running || $zoho_to_wc_queued ) ? ' active' : ''; ?>">
                    🛑 Stop Zoho → WooCommerce
                </button>

                <!-- Stop -->
                <button id="dsdf-stop-btn" class="button button-link-delete<?php echo $sync_running ? ' active' : ''; ?>">
                    🛑 Stop Sync
                </button>

                <!-- Force full re-sync -->
                <button id="dsdf-clear-cache-btn" class="button button-secondary">
                    🔁 Force Full Re-sync
                </button>

                <!-- Reset stuck flag -->
                <button id="dsdf-reset-btn" class="button" style="border-color:#d63638;color:#d63638">
                    ⚠ Reset Stuck Sync
                </button>

                <?php if ( $zoho_blocked ) : ?>
                <button id="dsdf-reset-api-limit-btn" class="button" style="border-color:#d63638;color:#d63638">
                    🔓 Clear API Limit Block
                </button>
                <?php endif; ?>

            </div>

            <!-- Progress bar -->
            <div class="dsdf-progress-wrap<?php echo $sync_running ? ' visible' : ''; ?>">
                <div class="dsdf-progress-bar-bg">
                    <div class="dsdf-progress-bar" style="width:<?php echo esc_attr($sync_pct); ?>%"></div>
                </div>
                <div class="dsdf-progress-label">
                    <?php if ( $sync_running ) : ?>
                        Syncing... <?php echo esc_html($sync_done); ?> / <?php echo esc_html($sync_total); ?> rows (<?php echo esc_html($sync_pct); ?>%)
                    <?php elseif ( $last_sync ) : ?>
                        <?php echo esc_html($last_sync); ?>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ( $last_zoho_to_wc_sync ) : ?>
            <p style="margin-top:8px;color:#1d2327;font-size:.84rem">
                <strong>Zoho → WooCommerce:</strong> <?php echo esc_html( $last_zoho_to_wc_sync ); ?>
            </p>
            <?php endif; ?>
        </div>

        <!-- Zoho API Credentials -->
        <div class="dsdf-section">
            <h2>🔑 Zoho Connection</h2>
            <?php if ( $zoho_wc_sync ) : ?>
            <p style="color:#065f46;font-size:.85rem;margin-top:0;background:#d1fae5;padding:10px 14px;border-radius:6px">
                ✔ Using your existing <strong>Zoho Inventory WooCommerce Sync</strong> connection
                (Org ID <code><?php echo esc_html( $zoho_info['org_id'] ); ?></code>).
                Manage credentials in
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=zoho-inventory-sync&tab=oauth' ) ); ?>">Zoho Inventory Sync → Zoho Connection</a>.
            </p>
            <?php else : ?>
            <p style="color:#646970;font-size:.85rem;margin-top:0">
                <strong>Recommended:</strong> install and connect
                <em>Zoho Inventory WooCommerce Sync</em> — this plugin will reuse that OAuth connection automatically.
                Or enter fallback credentials below (Self Client from
                <a href="https://api-console.zoho.com" target="_blank">api-console.zoho.com</a>).
            </p>
            <?php endif; ?>
            <form method="post">
                <?php wp_nonce_field( 'dsdf_save_zoho', 'dsdf_zoho_nonce' ); ?>
                <table class="dsdf-cred-table">
                    <tr>
                        <td><label for="dsdf_zoho_client_id">Client ID</label></td>
                        <td>
                            <input type="text" id="dsdf_zoho_client_id" name="dsdf_zoho_client_id"
                                   value="<?php echo esc_attr( get_option('dsdf_zoho_client_id','') ); ?>" autocomplete="off">
                        </td>
                    </tr>
                    <tr>
                        <td><label for="dsdf_zoho_client_secret">Client Secret</label></td>
                        <td>
                            <input type="password" id="dsdf_zoho_client_secret" name="dsdf_zoho_client_secret"
                                   value="<?php echo esc_attr( get_option('dsdf_zoho_client_secret','') ); ?>" autocomplete="new-password">
                        </td>
                    </tr>
                    <tr>
                        <td><label for="dsdf_zoho_refresh_token">Refresh Token</label></td>
                        <td>
                            <input type="password" id="dsdf_zoho_refresh_token" name="dsdf_zoho_refresh_token"
                                   value="<?php echo esc_attr( get_option('dsdf_zoho_refresh_token','') ); ?>" autocomplete="new-password">
                        </td>
                    </tr>
                    <tr>
                        <td><label for="dsdf_zoho_org_id">Organisation ID</label></td>
                        <td>
                            <input type="text" id="dsdf_zoho_org_id" name="dsdf_zoho_org_id"
                                   value="<?php echo esc_attr( get_option('dsdf_zoho_org_id','') ); ?>">
                            <small>Zoho Inventory → Settings → Organisation Profile</small>
                        </td>
                    </tr>
                    <tr>
                        <td><label for="dsdf_inventory_account_name">Inventory Account Name</label></td>
                        <td>
                            <input type="text" id="dsdf_inventory_account_name" name="dsdf_inventory_account_name"
                                   value="<?php echo esc_attr( dsdf_get_inventory_account_name() ); ?>">
                            <small>Account name used for inventory account lookup (example: Dynamic Supply or Cost of Goods Sold).</small>
                        </td>
                    </tr>
                    <tr>
                        <td><label for="dsdf_zoho_dc">Data Centre</label></td>
                        <td>
                            <select id="dsdf_zoho_dc" name="dsdf_zoho_dc">
                                <?php foreach ( [
                                    'com' => 'Global (com)',
                                    'eu'  => 'Europe (eu)',
                                    'in'  => 'India (in)',
                                    'au'  => 'Australia (au)',
                                    'jp'  => 'Japan (jp)',
                                    'ca'  => 'Canada (ca)',
                                ] as $val => $lbl ) : ?>
                                <option value="<?php echo esc_attr($val); ?>"
                                    <?php selected( get_option('dsdf_zoho_dc','com'), $val ); ?>>
                                    <?php echo esc_html($lbl); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td><label for="dsdf_api_throttle_ms">API Throttle (ms)</label></td>
                        <td>
                            <input type="number" id="dsdf_api_throttle_ms" name="dsdf_api_throttle_ms"
                                   value="<?php echo esc_attr( (int) get_option('dsdf_api_throttle_ms', 800) ); ?>"
                                   min="0" max="5000" style="max-width:110px">
                            <small>Delay between each Zoho API call. 800 ms recommended (~75/min). Zoho org limit = 7,500/day total.</small>
                        </td>
                    </tr>
                    <tr>
                        <td><label for="dsdf_daily_api_budget">Daily API Budget</label></td>
                        <td>
                            <input type="number" id="dsdf_daily_api_budget" name="dsdf_daily_api_budget"
                                   value="<?php echo esc_attr( (int) get_option('dsdf_daily_api_budget', DSDF_DEFAULT_BUDGET) ); ?>"
                                   min="100" max="7400" style="max-width:110px">
                            <small>Max API calls this plugin may use per day. Zoho org limit = 7,500/day (shared with WooCommerce Zoho plugin). Recommended: 3,000.</small>
                        </td>
                    </tr>
                </table>
                <br><?php submit_button( 'Save Zoho Credentials', 'primary', 'dsdf_save_zoho', false ); ?>
            </form>
        </div>

        <!-- SFTP Settings -->
        <div class="dsdf-section">
            <h2>🖧 SFTP Settings</h2>
            <form method="post">
                <?php wp_nonce_field( 'dsdf_save_sftp', 'dsdf_sftp_nonce' ); ?>
                <table class="dsdf-cred-table">
                    <?php foreach ( [
                        'host'        => [ 'Host',           'text',     '' ],
                        'port'        => [ 'Port',           'number',   '' ],
                        'username'    => [ 'Username',       'text',     '' ],
                        'password'    => [ 'Password',       'password', '' ],
                        'remote_path' => [ 'Remote Path',    'text',     'e.g. /AUSWID/ds-standard-datafeed.csv' ],
                        'local_file'  => [ 'Local Filename', 'text',     'Saved in wp-content/uploads/dynamic-supplies/' ],
                    ] as $key => [ $label, $type, $hint ] ) : ?>
                    <tr>
                        <td><label for="dsdf_<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></label></td>
                        <td>
                            <input type="<?php echo esc_attr($type); ?>"
                                   id="dsdf_<?php echo esc_attr($key); ?>"
                                   name="dsdf_<?php echo esc_attr($key); ?>"
                                   value="<?php echo esc_attr( dsdf_get_opt($key) ); ?>"
                                   <?php if ( $key === 'port' ) echo 'style="max-width:90px"'; ?>>
                            <?php if ( $hint ) echo '<small>' . esc_html($hint) . '</small>'; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
                <br><?php submit_button( 'Save SFTP Settings', 'secondary', 'dsdf_save_sftp', false ); ?>
            </form>
        </div>

        <!-- Activity Log -->
        <?php
        $log_file = dsdf_upload_dir() . 'dsdf.log';
        if ( file_exists( $log_file ) ) :
            $lines = array_slice( file( $log_file ), -80 );
        ?>
        <div class="dsdf-section">
            <h2>📋 Activity Log <span style="font-size:.74rem;font-weight:400;color:#646970">(last 80 lines)</span></h2>
            <pre class="dsdf-log-box"><?php echo esc_html( implode( '', $lines ) ); ?></pre>
        </div>
        <?php endif; ?>

        <!-- Error Log -->
        <?php if ( ! empty( $error_entries ) ) : ?>
        <div class="dsdf-section" id="dsdf-error-section">
            <h2>❌ Sync Error Log
                <span style="font-size:.74rem;font-weight:400;color:#646970">(last 100 · historical, not live)</span>
                <button id="dsdf-clear-errors-btn" class="button button-small" style="margin-left:auto;border-color:#d63638;color:#d63638">
                    🗑 Clear Errors
                </button>
            </h2>
            <table class="dsdf-error-table">
                <thead>
                    <tr>
                        <th style="width:140px">Time</th>
                        <th style="width:100px">SKU</th>
                        <th style="width:50px">Row</th>
                        <th style="width:60px">Action</th>
                        <th>Error</th>
                        <th style="width:80px">Payload</th>
                        <th style="width:80px">Response</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ( $error_entries as $e ) : ?>
                    <tr>
                        <td><?php echo esc_html( $e['time'] ?? '—' ); ?></td>
                        <td><code><?php echo esc_html( $e['sku'] ?? '—' ); ?></code></td>
                        <td><?php echo esc_html( $e['row'] ?? '—' ); ?></td>
                        <td><span class="dsdf-badge <?php echo ($e['action']??'') === 'create' ? 'blue' : 'orange'; ?>">
                            <?php echo esc_html( $e['action'] ?? '—' ); ?></span></td>
                        <td style="color:#991b1b"><?php echo esc_html( $e['error'] ?? '' ); ?></td>
                        <td><?php if ( ! empty($e['payload']) ) : ?>
                            <span class="dsdf-json-toggle">Show</span>
                            <div class="dsdf-json-block"><?php echo esc_html( json_encode($e['payload'], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) ); ?></div>
                        <?php endif; ?></td>
                        <td><?php if ( ! empty($e['zoho_response']) ) : ?>
                            <span class="dsdf-json-toggle">Show</span>
                            <div class="dsdf-json-block"><?php echo esc_html( json_encode($e['zoho_response'], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) ); ?></div>
                        <?php endif; ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

    </div>
    <?php
}

// ---------------------------------------------------------------------------
// SFTP Download
// ---------------------------------------------------------------------------
function dsdf_run_download() {
    dsdf_log( 'Starting SFTP download...' );

    $host   = dsdf_get_opt( 'host' );
    $port   = (int) dsdf_get_opt( 'port', '22' );
    $user   = dsdf_get_opt( 'username' );
    $pass   = dsdf_get_opt( 'password' );
    $remote = dsdf_get_opt( 'remote_path' );
    $fname  = dsdf_get_opt( 'local_file', 'ds-standard-datafeed.csv' );
    if ( empty( trim( $fname ) ) ) $fname = 'ds-standard-datafeed.csv';

    $dir = dsdf_upload_dir();
    if ( ! file_exists( $dir ) ) wp_mkdir_p( $dir );
    $local = $dir . $fname;

    // TCP reachability check
    $sock = @fsockopen( $host, $port, $errno, $errstr, 10 );
    if ( ! $sock ) {
        $msg = "Cannot reach {$host}:{$port} — {$errno}: {$errstr}";
        dsdf_log( 'ERROR: ' . $msg );
        return new WP_Error( 'dsdf_unreachable', $msg );
    }
    fclose( $sock );
    dsdf_log( "TCP OK → {$host}:{$port}" );

    $vendor = DSDF_PLUGIN_DIR . 'vendor/autoload.php';
    if ( file_exists( $vendor ) ) {
        require_once $vendor;
        return dsdf_download_phpseclib( $host, $port, $user, $pass, $remote, $local );
    }
    if ( function_exists( 'ssh2_connect' ) ) {
        return dsdf_download_ssh2( $host, $port, $user, $pass, $remote, $local );
    }
    $msg = 'No SFTP library available. Run: composer install';
    dsdf_log( 'ERROR: ' . $msg );
    return new WP_Error( 'dsdf_no_sftp', $msg );
}

function dsdf_download_phpseclib( $host, $port, $user, $pass, $remote, $local ) {
    try {
        $sftp = new \phpseclib3\Net\SFTP( $host, $port, 30 );
        if ( ! $sftp->login( $user, $pass ) ) {
            $msg = "SFTP login failed for [{$user}].";
            dsdf_log( 'ERROR: ' . $msg );
            return new WP_Error( 'dsdf_login_failed', $msg );
        }
        dsdf_log( 'SFTP logged in.' );
        foreach ( [ $remote, ltrim( $remote, '/' ), basename( $remote ) ] as $try ) {
            if ( $sftp->get( $try, $local ) ) {
                dsdf_log( "Downloaded [{$try}] → {$local}" );
                return $local;
            }
        }
        $msg = 'All remote path attempts failed.';
        dsdf_log( 'ERROR: ' . $msg );
        return new WP_Error( 'dsdf_download_failed', $msg );
    } catch ( \Exception $e ) {
        dsdf_log( 'SFTP EXCEPTION: ' . $e->getMessage() );
        return new WP_Error( 'dsdf_exception', $e->getMessage() );
    }
}

function dsdf_download_ssh2( $host, $port, $user, $pass, $remote, $local ) {
    $conn = @ssh2_connect( $host, $port );
    if ( ! $conn ) {
        dsdf_log( 'ERROR: ssh2_connect failed' );
        return new WP_Error( 'dsdf_connect', 'ssh2_connect failed' );
    }
    if ( ! @ssh2_auth_password( $conn, $user, $pass ) ) {
        dsdf_log( 'ERROR: ssh2 auth failed' );
        return new WP_Error( 'dsdf_auth', 'SSH2 auth failed' );
    }
    $sftp   = ssh2_sftp( $conn );
    $stream = @fopen( 'ssh2.sftp://' . intval($sftp) . $remote, 'r' );
    if ( ! $stream ) {
        dsdf_log( 'ERROR: cannot open remote file via ssh2' );
        return new WP_Error( 'dsdf_open', 'Cannot open remote file' );
    }
    $contents = stream_get_contents( $stream );
    fclose( $stream );
    if ( false === file_put_contents( $local, $contents ) ) {
        dsdf_log( 'ERROR: cannot write local file' );
        return new WP_Error( 'dsdf_write', 'Cannot write local file' );
    }
    dsdf_log( 'Downloaded (ssh2): ' . $local );
    return $local;
}

// ---------------------------------------------------------------------------
// CSV field mapping + Zoho import defaults
// ---------------------------------------------------------------------------

/** Defaults for fields not present in the CSV (overridable via Zoho Import Settings). */
function dsdf_get_zoho_import_defaults(): array {
    $built_in = [
        'unit'          => 'qty',
        'initial_stock' => 0,
        'upc'           => '',
        'ean'           => '',
        'isbn'          => '',
        'part_number'   => '',
        'product_type'  => 'goods',
        'item_type'     => 'sales_and_purchases',
    ];

    $wc_import = get_option( 'zoho_inventory_sync_import_settings', [] );
    if ( ! is_array( $wc_import ) ) {
        $wc_import = [];
    }

    $dsdf = get_option( 'dsdf_zoho_import_defaults', [] );
    if ( ! is_array( $dsdf ) ) {
        $dsdf = [];
    }

    return wp_parse_args( $dsdf, wp_parse_args( $wc_import, $built_in ) );
}

/** Resolve Zoho inventory_account_id for configured account name (cached). */
function dsdf_resolve_inventory_account_id( $zoho, string $name = '' ): string {
    if ( '' === $name ) {
        $name = dsdf_get_inventory_account_name();
    }

    $cached = (string) get_option( 'dsdf_inventory_account_id', '' );
    if ( '' !== $cached ) {
        return $cached;
    }

    if ( ! method_exists( $zoho, 'find_account_id_by_name' ) ) {
        return '';
    }

    $id = $zoho->find_account_id_by_name( $name );
    if ( '' !== $id ) {
        update_option( 'dsdf_inventory_account_id', $id );
        dsdf_log( "Inventory account resolved: \"{$name}\" → {$id}" );
    } else {
        dsdf_log( "WARNING: Could not resolve inventory account \"{$name}\" — items may fail if item_type requires it." );
    }

    return $id;
}

/** Extract product image URL from a CSV row (Main Image URL column). */
function dsdf_normalize_csv_column_name( string $name ): string {
    $name = strtolower( trim( $name ) );
    return preg_replace( '/\s+/', ' ', $name );
}

function dsdf_csv_value_by_column( array $data, string $column_name ): string {
    if ( isset( $data[ $column_name ] ) ) {
        return trim( (string) $data[ $column_name ] );
    }

    $wanted = dsdf_normalize_csv_column_name( $column_name );
    foreach ( $data as $name => $value ) {
        if ( dsdf_normalize_csv_column_name( (string) $name ) === $wanted ) {
            return trim( (string) $value );
        }
    }

    return '';
}

function dsdf_csv_value_by_any_column( array $data, array $column_names ): string {
    foreach ( $column_names as $column_name ) {
        $value = dsdf_csv_value_by_column( $data, (string) $column_name );
        if ( '' !== $value ) {
            return $value;
        }
    }
    return '';
}

function dsdf_csv_image_url( array $data ): string {
    $url = dsdf_csv_value_by_column( $data, DSDF_CSV_MAIN_IMAGE_COLUMN );
    if ( '' === $url ) {
        foreach ( $data as $col => $val ) {
            if ( stripos( $col, 'Main Image' ) !== false || stripos( $col, 'Image URL' ) !== false ) {
                $url = trim( (string) $val );
                if ( '' !== $url ) {
                    break;
                }
            }
        }
    }

    if ( '' === $url || ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
        return '';
    }

    return esc_url_raw( $url );
}

/** Download a remote image to a temp file; returns local path or WP_Error. */
function dsdf_download_image_to_temp( string $url ) {
    if ( dsdf_sync_stop_requested() ) {
        return new WP_Error( 'dsdf_sync_stopped', 'Sync stopped by user.' );
    }

    $response = wp_remote_get( $url, [
        'timeout'  => 45,
        'redirection' => 3,
    ] );

    if ( is_wp_error( $response ) ) {
        return $response;
    }

    $code = wp_remote_retrieve_response_code( $response );
    if ( $code < 200 || $code >= 300 ) {
        return new WP_Error( 'dsdf_image_http', "Image download failed (HTTP {$code}): {$url}" );
    }

    $body = wp_remote_retrieve_body( $response );
    if ( '' === $body ) {
        return new WP_Error( 'dsdf_image_empty', "Empty image response: {$url}" );
    }

    $content_type = (string) wp_remote_retrieve_header( $response, 'content-type' );
    $ext          = 'jpg';
    if ( str_contains( $content_type, 'png' ) ) {
        $ext = 'png';
    } elseif ( str_contains( $content_type, 'gif' ) ) {
        $ext = 'gif';
    } elseif ( str_contains( $content_type, 'webp' ) ) {
        $ext = 'webp';
    } else {
        $path_ext = strtolower( pathinfo( wp_parse_url( $url, PHP_URL_PATH ) ?? '', PATHINFO_EXTENSION ) );
        if ( in_array( $path_ext, [ 'jpg', 'jpeg', 'png', 'gif', 'webp' ], true ) ) {
            $ext = 'jpeg' === $path_ext ? 'jpg' : $path_ext;
        }
    }

    $dir = dsdf_upload_dir() . 'tmp/';
    if ( ! file_exists( $dir ) ) {
        wp_mkdir_p( $dir );
    }

    $file = $dir . 'img-' . md5( $url ) . '.' . $ext;
    if ( false === file_put_contents( $file, $body, LOCK_EX ) ) {
        return new WP_Error( 'dsdf_image_write', 'Could not write temp image file.' );
    }

    return $file;
}

/** Upload CSV image URL to Zoho item after create/update. */
function dsdf_zoho_upload_item_image( $zoho, string $item_id, string $image_url ) {
    if ( '' === $image_url || '' === $item_id || ! method_exists( $zoho, 'upload_item_image' ) ) {
        return true;
    }

    $tmp = dsdf_download_image_to_temp( $image_url );
    if ( is_wp_error( $tmp ) ) {
        dsdf_log( '  ✘ IMAGE download failed: ' . $tmp->get_error_message() );
        return $tmp;
    }

    try {
        $res = dsdf_zoho_call( fn() => $zoho->upload_item_image( $item_id, $tmp ), "image:{$item_id}" );
        if ( is_wp_error( $res ) ) {
            dsdf_log( '  ✘ IMAGE upload failed: ' . $res->get_error_message() );
            return $res;
        }
        dsdf_log( "  ✔ IMAGE uploaded for item_id: {$item_id}" );
        return true;
    } finally {
        @unlink( $tmp );
    }
}

/** Force image re-upload on Zoho (Zoho replaces the existing item image). */
function dsdf_zoho_sync_item_image( $zoho, string $item_id, string $image_url, string $sku = '' ): bool {
    if ( '' === $image_url || '' === $item_id ) {
        return true;
    }

    $label = '' !== $sku ? "image:{$sku}" : "image:{$item_id}";
    dsdf_log( "  → Uploading image {$label}" );
    $res = dsdf_zoho_upload_item_image( $zoho, $item_id, $image_url );
    return ! is_wp_error( $res );
}

function dsdf_is_duplicate_sku_error( $res ): bool {
    $msg = strtolower( dsdf_zoho_error_message( $res ) );
    return str_contains( $msg, 'sku' )
        && ( str_contains( $msg, 'already' ) || str_contains( $msg, 'exist' ) || str_contains( $msg, 'duplicate' ) );
}

/** Look up existing Zoho item_id by SKU (handles both API client types). */
function dsdf_zoho_find_item_id_by_sku( $zoho, string $sku ): ?string {
    if ( ! method_exists( $zoho, 'find_by_sku' ) ) {
        return null;
    }

    $found = $zoho->find_by_sku( $sku );
    if ( is_wp_error( $found ) || empty( $found ) ) {
        return null;
    }

    $id = $found['item_id'] ?? '';
    return '' !== $id ? (string) $id : null;
}

// ---------------------------------------------------------------------------
// CSV row → Zoho item payload
// ---------------------------------------------------------------------------
function dsdf_csv_row_to_zoho( array $data ): array {
    $get_exact = function( string $col ) use ( $data ): string {
        return isset( $data[ $col ] ) ? trim( (string) $data[ $col ] ) : '';
    };

    $clean = function( string $str ): string {
        $str = wp_strip_all_tags( $str );
        $str = html_entity_decode( $str, ENT_QUOTES, 'UTF-8' );
        $str = preg_replace( '/[^\x20-\x7E]/', ' ', $str );
        $str = preg_replace( '/[<>"&\\\\]/', '', $str );
        return trim( substr( preg_replace( '/\s+/', ' ', $str ), 0, 500 ) );
    };

    $parse_price = function( string $raw ): float {
        return round( (float) str_replace( ',', '', $raw ), 2 );
    };
    $parse_int = function( string $raw ): ?int {
        $raw = trim( $raw );
        if ( '' === $raw ) {
            return null;
        }
        $raw = preg_replace( '/[^0-9\-]/', '', $raw );
        if ( '' === $raw || '-' === $raw ) {
            return null;
        }
        return (int) $raw;
    };

    // Required: Name → name
    $name = $clean( $get_exact( 'Name' ) );
    if ( '' === $name ) {
        return [];
    }

    // Duplicate matching (preferred): Dynamic Supplies SKU → sku
    $sku = $get_exact( 'Dynamic Supplies SKU' );
    if ( '' === $sku ) {
        foreach ( $data as $col => $val ) {
            if ( stripos( $col, 'SKU' ) !== false ) {
                $sku = trim( (string) $val );
                break;
            }
        }
    }
    if ( '' === $sku ) {
        return [];
    }

    // Reseller Price Ex GST → rate
    $rate = $parse_price( $get_exact( 'Reseller Price Ex GST' ) ?: $get_exact( 'Reseller Price' ) );

    // RRP (inc GST) → purchase_rate
    $purchase_rate = $parse_price( $get_exact( 'RRP (inc GST)' ) );

    // Alternative Product Title → description + purchase_description
    $alt_title = $clean( $get_exact( 'Alternative Product Title' ) );

    // Additional CSV details that users expect to appear in Zoho item details.
    $supplier_source_csv = $clean( dsdf_csv_value_by_any_column( $data, [ 'Supplier Source', 'Supplier', 'Supplier Name', 'Source', 'Vendor', 'Manufacturer', 'Brand' ] ) );
    $supplier_source = '' !== $supplier_source_csv ? $supplier_source_csv : DSDF_SUPPLIER_SOURCE_DEFAULT;
    $available_qty_csv = dsdf_csv_value_by_any_column( $data, [ 'Available Qty', 'Available Quantity', 'Quantity', 'Qty', 'Stock Qty', 'Stock' ] );
    $available_qty = $parse_int( $available_qty_csv );
    if ( null === $available_qty ) {
        $available_qty = (int) DSDF_AVAILABLE_QTY_DEFAULT;
    }
    $printer_compat  = $clean( dsdf_csv_value_by_any_column( $data, [ 'Printer Compatibility', 'Printer Compatit', 'Printer Compatitility' ] ) );
    $cartridge_yield = $clean( dsdf_csv_value_by_any_column( $data, [ 'Cartridge Yield', 'Cartridge Yie', 'Cartridge Yielded' ] ) );
    $brochure_url    = dsdf_csv_value_by_any_column( $data, [ 'Product Brochure', 'Product Brochure URL' ] );
    $msds_url        = dsdf_csv_value_by_any_column( $data, [ 'MSDS', 'MSDS URL' ] );
    $range_guide     = dsdf_csv_value_by_any_column( $data, [ 'Range Guide', 'Range Guide URL' ] );

    $extra_bits = [];
    if ( '' !== $supplier_source ) {
        $extra_bits[] = 'Supplier Source: ' . $supplier_source;
    }
    $extra_bits[] = 'Available Qty: ' . $available_qty;
    if ( '' !== $printer_compat ) {
        $extra_bits[] = 'Printer Compatibility: ' . $printer_compat;
    }
    if ( '' !== $cartridge_yield ) {
        $extra_bits[] = 'Cartridge Yield: ' . $cartridge_yield;
    }
    if ( '' !== $brochure_url ) {
        $extra_bits[] = 'Product Brochure: ' . esc_url_raw( $brochure_url );
    }
    if ( '' !== $msds_url ) {
        $extra_bits[] = 'MSDS: ' . esc_url_raw( $msds_url );
    }
    if ( '' !== $range_guide ) {
        $extra_bits[] = 'Range Guide: ' . esc_url_raw( $range_guide );
    }

    $description_base = '' !== $alt_title ? $alt_title : $name;
    $description_full = $description_base;
    if ( ! empty( $extra_bits ) ) {
        $description_full = $clean( $description_base . ' | ' . implode( ' | ', $extra_bits ) );
    }

    $defaults = dsdf_get_zoho_import_defaults();
    $initial_stock = max( 0, (int) ( $defaults['initial_stock'] ?? 0 ) );

    // Prefer real values from CSV; fallback to import defaults if missing.
    $upc_from_csv = $clean( dsdf_csv_value_by_any_column( $data, [ 'UPC', 'Upc' ] ) );
    $ean_from_csv = $clean( dsdf_csv_value_by_any_column( $data, [ 'EAN' ] ) );
    $isbn_from_csv = $clean( dsdf_csv_value_by_any_column( $data, [ 'ISBN' ] ) );
    $part_number_from_csv = $clean( dsdf_csv_value_by_any_column( $data, [ 'Part Number', 'MPN', 'Manufacturer Part Number' ] ) );

    $payload = [
        'name'                 => $name,
        'sku'                  => $sku,
        'rate'                 => $rate,
        'purchase_rate'        => $purchase_rate > 0 ? $purchase_rate : null,
        'description'          => $description_full,
        'purchase_description' => $description_base,
        'unit'                 => $defaults['unit'] ?? 'qty',
        'item_type'            => $defaults['item_type'] ?? 'sales_and_purchases',
        'product_type'         => $defaults['product_type'] ?? 'goods',
        'initial_stock'        => $initial_stock,
    ];

    $identity_fields = [
        'upc'         => '' !== $upc_from_csv ? $upc_from_csv : trim( (string) ( $defaults['upc'] ?? '' ) ),
        'ean'         => '' !== $ean_from_csv ? $ean_from_csv : trim( (string) ( $defaults['ean'] ?? '' ) ),
        'isbn'        => '' !== $isbn_from_csv ? $isbn_from_csv : trim( (string) ( $defaults['isbn'] ?? '' ) ),
        'part_number' => '' !== $part_number_from_csv ? $part_number_from_csv : trim( (string) ( $defaults['part_number'] ?? '' ) ),
    ];
    foreach ( $identity_fields as $field => $val ) {
        if ( '' !== $val ) {
            $payload[ $field ] = $val;
        }
    }

    if ( $initial_stock > 0 && $rate > 0 ) {
        $payload['initial_stock_rate'] = $rate;
    }

    $custom_fields = [];
    if ( '' !== DSDF_ZOHO_CF_SUPPLIER_SOURCE_ID ) {
        $custom_fields[] = [
            'customfield_id' => DSDF_ZOHO_CF_SUPPLIER_SOURCE_ID,
            'value'          => "Dynamic",
        ];
    }
    if ( '' !== DSDF_ZOHO_CF_AVAILABLE_QTY_ID ) {
        $custom_fields[] = [
            'customfield_id' => DSDF_ZOHO_CF_AVAILABLE_QTY_ID,
            'value'          => "10",
        ];
    }
    if ( ! empty( $custom_fields ) ) {
        $payload['custom_fields'] = $custom_fields;
    }

    return array_filter( $payload, fn( $v ) => $v !== '' && $v !== null );
}

/** Push one product to Zoho; retries without optional fields on validation errors. */
function dsdf_zoho_push_item( $zoho, string $action, ?string $existing_id, array $payload, ?string &$actual_action = null ) {
    $actual_action = $action;

    if ( dsdf_sync_stop_requested() ) {
        return new WP_Error( 'dsdf_sync_stopped', 'Sync stopped by user.' );
    }

    $sku = $payload['sku'] ?? '';

    if ( 'update' === $action && $existing_id ) {
        $update_payload = $payload;
        unset( $update_payload['initial_stock'], $update_payload['initial_stock_rate'] );
        return dsdf_zoho_call( fn() => $zoho->update_item( $existing_id, $update_payload ), "update:{$sku}" );
    }

    $res = dsdf_zoho_call( fn() => $zoho->create_item( $payload ), "create:{$sku}" );
    if ( ! is_wp_error( $res ) && ! empty( $res['item'] ) ) {
        return $res;
    }

    $err = dsdf_zoho_error_message( $res );
    if ( stripos( $err, 'description' ) !== false ) {
        $retry = $payload;
        unset( $retry['description'], $retry['purchase_description'] );
        $res = dsdf_zoho_call( fn() => $zoho->create_item( $retry ), "create-no-desc:{$sku}" );
        if ( ! is_wp_error( $res ) && ! empty( $res['item'] ) ) {
            return $res;
        }
    }

    // Item may exist in Zoho but missing from cached SKU map — fall back to update.
    if ( dsdf_is_duplicate_sku_error( $res ) ) {
        $found_id = dsdf_zoho_find_item_id_by_sku( $zoho, $sku );
        if ( $found_id ) {
            dsdf_log( "  ↪ SKU {$sku} already exists (item_id {$found_id}) — switching to UPDATE." );
            $actual_action = 'update';
            $update_payload = $payload;
            unset( $update_payload['initial_stock'], $update_payload['initial_stock_rate'] );
            return dsdf_zoho_call( fn() => $zoho->update_item( $found_id, $update_payload ), "update-fallback:{$sku}" );
        }
    }

    return $res;
}

// ---------------------------------------------------------------------------
// Main sync function — reads CSV, creates or updates each item in Zoho
// ---------------------------------------------------------------------------
function dsdf_sync_to_zoho( string $csv_path ): array {
    dsdf_log( str_repeat( '=', 55 ) );
    dsdf_log( 'SYNC START: ' . $csv_path );
    dsdf_log( str_repeat( '=', 55 ) );

    $stats = [ 'created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => 0, 'api_calls' => 0 ];

    // ---- Validate CSV ----
    if ( ! file_exists( $csv_path ) ) {
        dsdf_log( 'ERROR: CSV not found at ' . $csv_path );
        return $stats;
    }

    // ---- Get Zoho API client ----
    $zoho = dsdf_get_zoho_client();
    if ( ! $zoho->is_configured() ) {
        dsdf_log( 'ERROR: Zoho not connected. Connect via Zoho Inventory WooCommerce Sync or DSDF credentials.' );
        update_option( 'dsdf_last_sync_stats', 'ERROR: Zoho not connected.' );
        return $stats;
    }
    $conn = dsdf_zoho_connection_info();
    dsdf_log( 'Zoho source: ' . $conn['label'] . ' | DC: ' . $conn['dc'] . ' | Org: ' . ( $conn['org_id'] ?: '?' ) );

    // ---- Open CSV ----
    $fh = fopen( $csv_path, 'r' );
    if ( ! $fh ) {
        dsdf_log( 'ERROR: Cannot open CSV.' );
        return $stats;
    }

    $header = fgetcsv( $fh );
    if ( ! $header ) {
        dsdf_log( 'ERROR: CSV is empty.' );
        fclose( $fh );
        return $stats;
    }
    $header = array_map( 'trim', $header );
    dsdf_log( 'CSV columns (' . count($header) . '): ' . implode( ', ', $header ) );
    $has_main_image_column = in_array(
        dsdf_normalize_csv_column_name( DSDF_CSV_MAIN_IMAGE_COLUMN ),
        array_map( 'dsdf_normalize_csv_column_name', $header ),
        true
    );
    if ( $has_main_image_column ) {
        dsdf_log( 'Image column detected: "' . DSDF_CSV_MAIN_IMAGE_COLUMN . '"' );
    } else {
        dsdf_log( 'WARNING: CSV column "' . DSDF_CSV_MAIN_IMAGE_COLUMN . '" not found. Image sync will use fallback image column matching.' );
    }

    // ---- PASS 1: read all rows, filter unchanged via hash cache ----
    $hash_cache    = dsdf_load_hash_cache();
    $image_cache   = dsdf_load_image_hash_cache();
    $new_hashes    = $hash_cache;
    $new_img_hashes = $image_cache;
    $rows_to_sync  = [];
    $total_rows    = 0;
    $skipped_hash  = 0;

    while ( ( $row = fgetcsv( $fh ) ) !== false ) {
        $total_rows++;

        if ( dsdf_sync_stop_requested() ) {
            dsdf_log( "STOP requested during CSV read at row {$total_rows}." );
            break;
        }

        if ( count( $row ) < 4 ) {
            $stats['skipped']++;
            continue;
        }

        $data    = array_combine( $header, array_pad( $row, count($header), '' ) );
        $product = dsdf_csv_row_to_zoho( $data );
        $sku     = $product['sku'] ?? '';

        if ( empty( $sku ) ) {
            $stats['skipped']++;
            dsdf_log( "Row {$total_rows}: skipped (missing Name or SKU)." );
            continue;
        }

        $image_url = dsdf_csv_image_url( $data );

        // Product hash (fields only — image tracked separately).
        $hash_data = $product;
        unset( $hash_data['initial_stock'], $hash_data['initial_stock_rate'] );
        $product_hash = md5( wp_json_encode( $hash_data ) );
        $image_hash   = '' !== $image_url ? md5( $image_url ) : '';

        $product_unchanged = isset( $hash_cache[ $sku ] ) && $hash_cache[ $sku ] === $product_hash;
        $image_unchanged   = '' === $image_url
            || ( isset( $image_cache[ $sku ] ) && $image_cache[ $sku ] === $image_hash );

        if ( $product_unchanged && $image_unchanged ) {
            $stats['skipped']++;
            $skipped_hash++;
            continue;
        }

        $rows_to_sync[] = [
            'row'          => $total_rows,
            'product'      => $product,
            'image_url'    => $image_url,
            'image_hash'   => $image_hash,
            'product_hash' => $product_hash,
            'sync_product' => ! $product_unchanged,
            'sync_image'   => ! $image_unchanged && '' !== $image_url,
        ];
    }
    fclose( $fh );

    $need = count( $rows_to_sync );
    dsdf_log( "PASS 1 done | CSV rows: {$total_rows} | Unchanged: {$skipped_hash} | To sync: {$need}" );

    dsdf_set_sync_running( true, $need );

    if ( $need === 0 ) {
        $summary = "All {$total_rows} products already up to date — nothing to sync.";
        dsdf_log( $summary );
        update_option( 'dsdf_last_sync_stats', $summary );
        dsdf_set_sync_running( false );
        return $stats;
    }

    if ( dsdf_is_zoho_daily_blocked() ) {
        $summary = 'Zoho daily API limit (7,500/day) reached — sync again tomorrow.';
        dsdf_log( 'ERROR: ' . $summary );
        update_option( 'dsdf_last_sync_stats', $summary );
        dsdf_set_sync_running( false );
        return $stats;
    }

    $budget_remaining = dsdf_api_budget_remaining();
    if ( $budget_remaining <= 0 ) {
        $summary = 'Plugin daily API budget used up — sync again tomorrow or raise the budget.';
        dsdf_log( 'ERROR: ' . $summary );
        update_option( 'dsdf_last_sync_stats', $summary );
        dsdf_set_sync_running( false );
        return $stats;
    }

    // ---- Build SKU → item_id map (cached 6 h to save API calls) ----
    if ( dsdf_sync_stop_requested() ) {
        dsdf_log( 'STOP requested before SKU map fetch.' );
        dsdf_set_sync_running( false );
        update_option( 'dsdf_last_sync_stats', 'Stopped by user before Zoho sync started.' );
        return $stats;
    }

    $sku_map = dsdf_load_sku_map_cache();
    if ( ! empty( $sku_map ) ) {
        dsdf_log( 'Using cached Zoho SKU map (' . count( $sku_map ) . ' items).' );
    } else {
        dsdf_log( 'Fetching Zoho SKU map (one-time bulk lookup)...' );
        $sku_map   = $zoho->build_sku_map();
        $map_calls = max( 1, (int) ceil( count( $sku_map ) / 200 ) );
        dsdf_track_api_call( $map_calls );
        $stats['api_calls'] += $map_calls;
        dsdf_log( "SKU map fetch used ~{$map_calls} API call(s)." );
        if ( ! empty( $sku_map ) ) {
            dsdf_save_sku_map_cache( $sku_map );
        }
    }

    // Static inventory account: Dynamic Supply
    $inventory_account_id = dsdf_resolve_inventory_account_id( $zoho );
    if ( '' !== $inventory_account_id ) {
        foreach ( $rows_to_sync as &$row_item ) {
            $row_item['product']['inventory_account_id'] = $inventory_account_id;
        }
        unset( $row_item );
    }

    // ---- Settings ----
    $throttle_ms  = (int) get_option( 'dsdf_api_throttle_ms', 800 );
    $daily_budget = (int) get_option( 'dsdf_daily_api_budget', DSDF_DEFAULT_BUDGET );
    $api_calls    = 0;
    $budget_hit   = false;
    $daily_limit  = false;
    $sync_index   = 0;

    dsdf_log( "Settings | Throttle: {$throttle_ms}ms | Budget remaining: {$budget_remaining}/{$daily_budget} | Already used today: " . dsdf_api_usage_today() );

    // ---- Error log ----
    $error_file    = dsdf_upload_dir() . 'dsdf-errors.json';
    $error_entries = [];
    if ( file_exists( $error_file ) ) {
        $prev = json_decode( file_get_contents( $error_file ), true );
        if ( is_array( $prev ) ) $error_entries = $prev;
    }

    // ---- PASS 2: push to Zoho ----
    dsdf_log( str_repeat( '-', 55 ) );
    dsdf_log( 'PASS 2: sending to Zoho...' );
    dsdf_log( str_repeat( '-', 55 ) );

    foreach ( $rows_to_sync as $item ) {
        $sync_index++;

        if ( dsdf_sync_stop_requested() ) {
            dsdf_log( "STOPPED by user at {$sync_index}/{$need} (CSV row {$item['row']})." );
            break;
        }

        if ( dsdf_is_zoho_daily_blocked() || dsdf_api_budget_remaining() <= 0 ) {
            if ( ! $budget_hit ) {
                $budget_hit = true;
                dsdf_log( "Daily API budget reached — {$sync_index}/{$need} processed. Continue tomorrow." );
            }
            break;
        }

        if ( $throttle_ms > 0 && $api_calls > 0 ) {
            usleep( $throttle_ms * 1000 );
            if ( dsdf_sync_stop_requested() ) {
                dsdf_log( "STOPPED by user at {$sync_index}/{$need} (after throttle)." );
                break;
            }
        }

        $product      = $item['product'];
        $sku          = $product['sku'];
        $image_url    = $item['image_url'] ?? '';
        $sync_product = ! empty( $item['sync_product'] );
        $sync_image   = ! empty( $item['sync_image'] );
        $existing_id  = $sku_map[ $sku ] ?? null;
        $action       = ( $sync_product && $existing_id ) ? 'update' : ( $sync_product ? 'create' : 'image-only' );
        $payload      = $product;
        $item_id      = $existing_id ? (string) $existing_id : '';
        $actual_action = $action;
        $res          = null;
        $product_ok   = ! $sync_product;

        dsdf_log( "Row {$item['row']} | {$sync_index}/{$need} | " . strtoupper( $action ) . " | SKU: {$sku}" );

        if ( $sync_product ) {
            $res           = dsdf_zoho_push_item( $zoho, $existing_id ? 'update' : 'create', $existing_id, $payload, $actual_action );
            $api_calls++;
            $stats['api_calls']++;
            dsdf_track_api_call( 1 );

            $raw_err = dsdf_zoho_error_message( $res );

            if ( 'dsdf_sync_stopped' === ( is_wp_error( $res ) ? $res->get_error_code() : '' ) ) {
                dsdf_log( "STOPPED by user at {$sync_index}/{$need} (CSV row {$item['row']})." );
                break;
            }

            if ( dsdf_is_zoho_daily_limit_error( $raw_err ) ) {
                $daily_limit = true;
                dsdf_set_zoho_daily_blocked();
                $stats['errors']++;
                dsdf_log( "  ✘ DAILY LIMIT: {$raw_err}" );
                break;
            }

            if ( dsdf_zoho_api_succeeded( $res ) ) {
                $product_ok = true;
                $item_id    = (string) ( $res['item']['item_id'] ?? $existing_id ?? '' );
                if ( $item_id ) {
                    $sku_map[ $sku ] = $item_id;
                }
                $error_entries = array_values( array_filter( $error_entries, fn( $e ) => ( $e['sku'] ?? '' ) !== $sku ) );

                if ( 'update' === $actual_action ) {
                    $stats['updated']++;
                    dsdf_log( "  ✔ UPDATED: {$sku}" );
                } else {
                    $stats['created']++;
                    dsdf_log( "  ✔ CREATED: {$sku} → item_id: {$item_id}" );
                }
            } else {
                $stats['errors']++;
                $full_res = is_wp_error( $res ) ? [ 'wp_error' => $res->get_error_message() ] : $res;
                dsdf_log( "  ✘ ERROR [{$actual_action}] SKU {$sku}: {$raw_err}" );

                $error_entries = array_values( array_filter( $error_entries, fn( $e ) => ( $e['sku'] ?? '' ) !== $sku ) );
                $error_entries[] = [
                    'time'          => date( 'Y-m-d H:i:s' ),
                    'row'           => $item['row'],
                    'sku'           => $sku,
                    'action'        => $actual_action,
                    'error'         => $raw_err,
                    'zoho_response' => $full_res,
                    'payload'       => $payload,
                ];
                file_put_contents( $error_file, json_encode( $error_entries, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ), LOCK_EX );
            }
        }

        // Always upload/replace image when product is created or updated and URL is present.
        $should_upload_image = $product_ok && '' !== $image_url && ( $sync_image || $sync_product );

        if ( $should_upload_image && '' !== $image_url && '' !== $item_id && $product_ok ) {
            $img_ok = dsdf_zoho_sync_item_image( $zoho, $item_id, $image_url, $sku );
            $api_calls++;
            $stats['api_calls']++;
            dsdf_track_api_call( 1 );

            if ( $img_ok && '' !== $item['image_hash'] ) {
                $new_img_hashes[ $sku ] = $item['image_hash'];
            }
        } elseif ( $product_ok && '' === $image_url ) {
            unset( $new_img_hashes[ $sku ] );
        }

        if ( $product_ok && $sync_product ) {
            $new_hashes[ $sku ] = $item['product_hash'];
        } elseif ( $product_ok && ! $sync_product && $sync_image ) {
            $stats['updated']++;
            dsdf_log( "  ✔ IMAGE UPDATED: {$sku}" );
        }

        if ( ! $sync_product && ! $product_ok ) {
            // image-only row but item not found in Zoho.
            if ( '' === $item_id ) {
                $stats['errors']++;
                dsdf_log( "  ✘ ERROR [image-only] SKU {$sku}: item not found in Zoho." );
            }
        }

        dsdf_update_sync_progress( $sync_index );

        if ( $api_calls % 200 === 0 ) {
            dsdf_save_hash_cache( $new_hashes );
            dsdf_save_image_hash_cache( $new_img_hashes );
            dsdf_save_sku_map_cache( $sku_map );
            dsdf_log( "CHECKPOINT | {$sync_index}/{$need} | API today: " . dsdf_api_usage_today() . " | Created: {$stats['created']} | Updated: {$stats['updated']} | Errors: {$stats['errors']}" );
        }
    }

    // ---- Final save ----
    dsdf_save_hash_cache( $new_hashes );
    dsdf_save_image_hash_cache( $new_img_hashes );
    if ( ! empty( $error_entries ) ) {
        file_put_contents( $error_file, json_encode( $error_entries, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ), LOCK_EX );
    }

    $total_in_zoho = count( $new_hashes );
    $pct           = $total_rows > 0 ? round( $total_in_zoho / $total_rows * 100 ) : 0;
    $stopped     = dsdf_sync_stop_requested() ? ' (stopped by user)' : '';
    $budget_note = $budget_hit ? ' | budget reached — continue tomorrow' : '';
    $limit_note  = $daily_limit ? ' | Zoho 7,500/day limit hit — continue tomorrow' : '';

    $summary = sprintf(
        'Sync done%s | CSV: %d | Queued: %d | API calls: %d | Created: %d | Updated: %d | Skipped: %d | Errors: %d | In Zoho: %d/%d (%d%%)%s%s',
        $stopped, $total_rows, $need, $api_calls,
        $stats['created'], $stats['updated'], $stats['skipped'], $stats['errors'],
        $total_in_zoho, $total_rows, $pct, $budget_note, $limit_note
    );

    dsdf_log( str_repeat( '=', 55 ) );
    dsdf_log( $summary );
    dsdf_log( str_repeat( '=', 55 ) );

    update_option( 'dsdf_last_sync_stats', $summary );
    dsdf_set_sync_running( false );

    return $stats;
}
