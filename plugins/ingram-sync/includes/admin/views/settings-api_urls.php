<?php
/**
 * API URLs settings tab.
 *
 * @package IngramSync
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<h2><?php esc_html_e( 'API URLs', 'ingram-sync' ); ?></h2>
<p class="description"><?php esc_html_e( 'Configure API endpoints. Switch between Sandbox and Production by updating these URLs.', 'ingram-sync' ); ?></p>

<form method="post" action="">
	<?php wp_nonce_field( 'ingram_sync_save_api_urls', 'ingram_sync_nonce' ); ?>
	<input type="hidden" name="ingram_sync_action" value="api_urls" />

	<table class="form-table">
		<tr>
			<th><label for="oauth_url"><?php esc_html_e( 'OAuth URL', 'ingram-sync' ); ?></label></th>
			<td><input type="url" name="oauth_url" id="oauth_url" class="large-text" value="<?php echo esc_url( $settings['oauth_url'] ); ?>" /></td>
		</tr>
		<tr>
			<th><label for="catalog_api_url"><?php esc_html_e( 'Catalog API', 'ingram-sync' ); ?></label></th>
			<td><input type="url" name="catalog_api_url" id="catalog_api_url" class="large-text" value="<?php echo esc_url( $settings['catalog_api_url'] ); ?>" /></td>
		</tr>
		<tr>
			<th><label for="price_api_url"><?php esc_html_e( 'Price & Availability API', 'ingram-sync' ); ?></label></th>
			<td><input type="url" name="price_api_url" id="price_api_url" class="large-text" value="<?php echo esc_url( $settings['price_api_url'] ); ?>" /></td>
		</tr>
		<tr>
			<th><label for="details_api_url"><?php esc_html_e( 'Product Details API', 'ingram-sync' ); ?></label></th>
			<td><input type="url" name="details_api_url" id="details_api_url" class="large-text" value="<?php echo esc_url( $settings['details_api_url'] ); ?>" /></td>
		</tr>
	</table>

	<?php submit_button( __( 'Save Settings', 'ingram-sync' ) ); ?>
</form>
