<?php
/**
 * Tools view.
 *
 * @package IngramSync
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<h1><?php esc_html_e( 'Tools', 'ingram-sync' ); ?></h1>

<div class="ingram-sync-tools">
	<div class="ingram-sync-tool-group">
		<h2><?php esc_html_e( 'Authentication', 'ingram-sync' ); ?></h2>
		<button type="button" class="button button-primary ingram-ajax-btn" data-action="generate_token"><?php esc_html_e( 'Generate Token', 'ingram-sync' ); ?></button>
	</div>

	<div class="ingram-sync-tool-group">
		<h2><?php esc_html_e( 'Download', 'ingram-sync' ); ?></h2>
		<button type="button" class="button ingram-ajax-btn" data-action="download_catalog"><?php esc_html_e( 'Download Catalog', 'ingram-sync' ); ?></button>
		<button type="button" class="button ingram-ajax-btn" data-action="download_price"><?php esc_html_e( 'Download Price', 'ingram-sync' ); ?></button>
		<button type="button" class="button ingram-ajax-btn" data-action="download_details"><?php esc_html_e( 'Download Details', 'ingram-sync' ); ?></button>
	</div>

	<div class="ingram-sync-tool-group">
		<h2><?php esc_html_e( 'Sync', 'ingram-sync' ); ?></h2>
		<button type="button" class="button ingram-ajax-btn" data-action="sync_woocommerce"><?php esc_html_e( 'Sync WooCommerce', 'ingram-sync' ); ?></button>
		<button type="button" class="button ingram-ajax-btn" data-action="sync_zoho"><?php esc_html_e( 'Sync Zoho', 'ingram-sync' ); ?></button>
		<button type="button" class="button button-primary ingram-ajax-btn" data-action="run_complete_sync"><?php esc_html_e( 'Run Complete Sync', 'ingram-sync' ); ?></button>
	</div>

	<div class="ingram-sync-tool-group">
		<h2><?php esc_html_e( 'Maintenance', 'ingram-sync' ); ?></h2>
		<button type="button" class="button ingram-ajax-btn ingram-confirm" data-action="clear_lock" data-confirm="<?php esc_attr_e( 'Only clear the lock if you are sure no sync is actually still running — this will let a new sync start immediately.', 'ingram-sync' ); ?>"><?php esc_html_e( 'Clear Stuck Lock', 'ingram-sync' ); ?></button>
		<button type="button" class="button ingram-ajax-btn" data-action="clear_logs"><?php esc_html_e( 'Clear Logs', 'ingram-sync' ); ?></button>
		<button type="button" class="button ingram-ajax-btn ingram-confirm" data-action="delete_products" data-confirm="<?php esc_attr_e( 'Delete all synced WooCommerce products?', 'ingram-sync' ); ?>"><?php esc_html_e( 'Delete Products', 'ingram-sync' ); ?></button>
		<button type="button" class="button button-link-delete ingram-ajax-btn ingram-confirm" data-action="reset_plugin" data-confirm="<?php esc_attr_e( 'Reset all plugin settings and data?', 'ingram-sync' ); ?>"><?php esc_html_e( 'Reset Plugin', 'ingram-sync' ); ?></button>
	</div>
</div>

<div id="ingram-tools-result" class="ingram-tools-result"></div>

<div class="ingram-sync-section ingram-system-info">
	<h2><?php esc_html_e( 'System Information', 'ingram-sync' ); ?></h2>
	<table class="widefat">
		<tr><th><?php esc_html_e( 'Plugin Version', 'ingram-sync' ); ?></th><td><?php echo esc_html( INGRAM_SYNC_VERSION ); ?></td></tr>
		<tr><th><?php esc_html_e( 'WordPress Version', 'ingram-sync' ); ?></th><td><?php echo esc_html( get_bloginfo( 'version' ) ); ?></td></tr>
		<tr><th><?php esc_html_e( 'PHP Version', 'ingram-sync' ); ?></th><td><?php echo esc_html( PHP_VERSION ); ?></td></tr>
		<tr><th><?php esc_html_e( 'WooCommerce', 'ingram-sync' ); ?></th><td><?php echo class_exists( 'WooCommerce' ) ? esc_html( WC()->version ) : esc_html__( 'Not active', 'ingram-sync' ); ?></td></tr>
		<tr><th><?php esc_html_e( 'OpenSSL', 'ingram-sync' ); ?></th><td><?php echo function_exists( 'openssl_encrypt' ) ? esc_html__( 'Available', 'ingram-sync' ) : esc_html__( 'Not available', 'ingram-sync' ); ?></td></tr>
		<tr><th><?php esc_html_e( 'WP Cron', 'ingram-sync' ); ?></th><td><?php echo defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ? esc_html__( 'Disabled', 'ingram-sync' ) : esc_html__( 'Enabled', 'ingram-sync' ); ?></td></tr>
	</table>
</div>
