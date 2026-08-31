<?php
/**
 * Import settings page (defaults only — Zoho OAuth is in sync plugin).
 *
 * @package ZohoInventoryImport\Admin
 *
 * @var array{connected:bool,message:string,org_id:string} $connection Connection status.
 */

defined( 'ABSPATH' ) || exit;

$settings   = zoho_inventory_import()->settings();
$item_types = \ZohoInventoryImport\Includes\Import_Settings::get_item_type_options();
$sync_url   = zoho_inventory_import()->bridge()->get_sync_settings_url();
?>
<div class="wrap zoho-inv-import-wrap">
	<h1><?php esc_html_e( 'Zoho Import Settings', 'zoho-inventory-import' ); ?></h1>

	<?php settings_errors( 'zoho_inv_import_messages' ); ?>

	<div class="zoho-inv-import-card">
		<h2><?php esc_html_e( 'Zoho Connection', 'zoho-inventory-import' ); ?></h2>
		<?php if ( ! empty( $connection['connected'] ) ) : ?>
			<p class="description" style="color:#00a32a;">
				<span class="dashicons dashicons-yes-alt"></span>
				<?php echo esc_html( $connection['message'] ); ?>
				<?php if ( ! empty( $connection['org_id'] ) ) : ?>
					<br />
					<strong><?php esc_html_e( 'Organization ID:', 'zoho-inventory-import' ); ?></strong>
					<?php echo esc_html( $connection['org_id'] ); ?>
				<?php endif; ?>
			</p>
		<?php else : ?>
			<p class="description" style="color:#dba617;">
				<?php echo esc_html( $connection['message'] ); ?>
			</p>
		<?php endif; ?>
		<p>
			<a href="<?php echo esc_url( $sync_url ); ?>" class="button button-secondary">
				<?php esc_html_e( 'Manage Zoho Connection (WooCommerce → Zoho Inventory Sync)', 'zoho-inventory-import' ); ?>
			</a>
		</p>
		<p class="description">
			<?php esc_html_e( 'Client ID, Client Secret, Refresh Token, and Organization ID are managed by the Zoho Inventory WooCommerce Sync plugin.', 'zoho-inventory-import' ); ?>
		</p>
	</div>

	<form method="post" action="">
		<?php wp_nonce_field( 'zoho_inv_import_save_settings', 'zoho_inv_import_settings_nonce' ); ?>

		<h2><?php esc_html_e( 'Import Default & Placeholder Values', 'zoho-inventory-import' ); ?></h2>
		<p class="description">
			<?php esc_html_e( 'These values are applied to Zoho fields that are not present in your CSV.', 'zoho-inventory-import' ); ?>
		</p>

		<table class="form-table" role="presentation">
			<tr>
				<th scope="row">
					<label for="item_type_placeholder"><?php esc_html_e( 'Default Item Type', 'zoho-inventory-import' ); ?></label>
				</th>
				<td>
					<select id="item_type_placeholder" name="item_type_placeholder">
						<?php foreach ( $item_types as $value => $label ) : ?>
							<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $settings->get_item_type_placeholder(), $value ); ?>>
								<?php echo esc_html( $label ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<p class="description"><?php esc_html_e( 'Required by Zoho — not in CSV.', 'zoho-inventory-import' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="default_unit"><?php esc_html_e( 'Default Unit', 'zoho-inventory-import' ); ?></label></th>
				<td>
					<input type="text" class="regular-text" id="default_unit" name="default_unit" value="<?php echo esc_attr( $settings->get_default_unit() ); ?>" />
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="default_initial_stock"><?php esc_html_e( 'Default Initial Stock', 'zoho-inventory-import' ); ?></label></th>
				<td>
					<input type="number" min="0" step="1" class="small-text" id="default_initial_stock" name="default_initial_stock" value="<?php echo esc_attr( (string) $settings->get_default_initial_stock() ); ?>" />
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="default_product_type"><?php esc_html_e( 'Default Product Type', 'zoho-inventory-import' ); ?></label></th>
				<td>
					<select id="default_product_type" name="default_product_type">
						<option value="goods" <?php selected( $settings->get_default_product_type(), 'goods' ); ?>><?php esc_html_e( 'Goods', 'zoho-inventory-import' ); ?></option>
						<option value="service" <?php selected( $settings->get_default_product_type(), 'service' ); ?>><?php esc_html_e( 'Service', 'zoho-inventory-import' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="dummy_upc"><?php esc_html_e( 'Placeholder UPC', 'zoho-inventory-import' ); ?></label></th>
				<td><input type="text" class="regular-text" id="dummy_upc" name="dummy_upc" value="<?php echo esc_attr( $settings->get_dummy_upc() ); ?>" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="dummy_ean"><?php esc_html_e( 'Placeholder EAN', 'zoho-inventory-import' ); ?></label></th>
				<td><input type="text" class="regular-text" id="dummy_ean" name="dummy_ean" value="<?php echo esc_attr( $settings->get_dummy_ean() ); ?>" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="dummy_isbn"><?php esc_html_e( 'Placeholder ISBN', 'zoho-inventory-import' ); ?></label></th>
				<td><input type="text" class="regular-text" id="dummy_isbn" name="dummy_isbn" value="<?php echo esc_attr( $settings->get_dummy_isbn() ); ?>" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="dummy_part_number"><?php esc_html_e( 'Placeholder Part Number', 'zoho-inventory-import' ); ?></label></th>
				<td><input type="text" class="regular-text" id="dummy_part_number" name="dummy_part_number" value="<?php echo esc_attr( $settings->get_dummy_part_number() ); ?>" /></td>
			</tr>
		</table>

		<?php submit_button( __( 'Save Import Settings', 'zoho-inventory-import' ) ); ?>
	</form>
</div>
