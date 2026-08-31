<?php
/**
 * WooCommerce settings tab.
 *
 * @package IngramSync
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<h2><?php esc_html_e( 'WooCommerce Settings', 'ingram-sync' ); ?></h2>

<form method="post" action="">
	<?php wp_nonce_field( 'ingram_sync_save_woocommerce', 'ingram_sync_nonce' ); ?>
	<input type="hidden" name="ingram_sync_action" value="woocommerce" />

	<table class="form-table">
		<tr>
			<th><?php esc_html_e( 'Enable WooCommerce Sync', 'ingram-sync' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="wc_sync_enabled" value="1" <?php checked( $settings['wc_sync_enabled'], 'yes' ); ?> />
					<?php esc_html_e( 'Yes', 'ingram-sync' ); ?>
				</label>
			</td>
		</tr>
		<tr>
			<th><label for="wc_products_per_batch"><?php esc_html_e( 'Products Per Batch', 'ingram-sync' ); ?></label></th>
			<td><input type="number" name="wc_products_per_batch" id="wc_products_per_batch" class="small-text" value="<?php echo esc_attr( $settings['wc_products_per_batch'] ); ?>" min="1" max="500" /></td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Update Existing Products', 'ingram-sync' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="wc_update_existing" value="1" <?php checked( $settings['wc_update_existing'], 'yes' ); ?> />
					<?php esc_html_e( 'Yes', 'ingram-sync' ); ?>
				</label>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Delete Missing Products', 'ingram-sync' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="wc_delete_missing" value="1" <?php checked( $settings['wc_delete_missing'], 'yes' ); ?> />
					<?php esc_html_e( 'Yes — remove WooCommerce products no longer in catalog', 'ingram-sync' ); ?>
				</label>
			</td>
		</tr>
	</table>

	<?php submit_button( __( 'Save Settings', 'ingram-sync' ) ); ?>
</form>
