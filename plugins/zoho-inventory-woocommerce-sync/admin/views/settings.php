<?php
/**
 * Admin view: Sync settings tab.
 *
 * @package ZohoInventorySync\Admin
 */

defined( 'ABSPATH' ) || exit;

$settings       = \ZohoInventorySync\Includes\Plugin::instance()->get_settings();
$webhook_secret = get_option( 'zoho_inventory_sync_webhook_secret', '' );
$webhook_url    = rest_url( 'zoho-inventory-sync/v1/webhook' );
?>

<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
	<input type="hidden" name="action" value="zoho_inventory_sync_save_settings">
	<?php wp_nonce_field( 'zoho_inv_save_settings' ); ?>

	<!-- Sync Toggles -->
	<div class="zoho-card">
		<h2><?php esc_html_e( 'Sync Toggles', 'zoho-inventory-sync' ); ?></h2>
		<table class="form-table" role="presentation">
			<?php
			$toggles = [
				'sync_customers'            => __( 'Sync Customers',                                                                   'zoho-inventory-sync' ),
				'sync_products'             => __( 'Sync Products / Items (WooCommerce → Zoho)',                                       'zoho-inventory-sync' ),
				'create_products_from_zoho' => __( 'Create WC Products from new Zoho Items (Zoho → WooCommerce)',                      'zoho-inventory-sync' ),
				'sync_orders'               => __( 'Sync Orders',                                                                      'zoho-inventory-sync' ),
				'sync_stock'                => __( 'Sync Stock / Inventory (WooCommerce → Zoho)',                                      'zoho-inventory-sync' ),
				'deduct_stock_from_zoho_so' => __( 'Deduct WC Stock when Sales Order Created in Zoho (Zoho → WooCommerce)',            'zoho-inventory-sync' ),
				'sync_purchase_orders'      => __( 'Sync Purchase Orders (WC stock receipts → Zoho Inventory Purchase Orders)',        'zoho-inventory-sync' ),
				'debug_mode'                => __( 'Debug Mode (log to file)',                                                         'zoho-inventory-sync' ),
			];
			foreach ( $toggles as $key => $label ) :
				$checked = ! empty( $settings[ $key ] ) ? 'checked' : '';
				?>
				<tr>
					<th scope="row"><?php echo esc_html( $label ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="zoho_settings[<?php echo esc_attr( $key ); ?>]" value="1" <?php echo esc_attr( $checked ); ?>>
							<?php echo esc_html( $label ); ?>
						</label>
					</td>
				</tr>
			<?php endforeach; ?>
		</table>
	</div>

	<!-- Invoice Settings -->
	<div class="zoho-card">
		<h2><?php esc_html_e( 'Invoice Settings', 'zoho-inventory-sync' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Auto-Create Invoice', 'zoho-inventory-sync' ); ?></th>
				<td>
					<label>
						<input type="checkbox" id="zoho_auto_invoice" name="zoho_settings[auto_create_invoice]" value="1"
							<?php checked( ! empty( $settings['auto_create_invoice'] ) ); ?>>
						<?php esc_html_e( 'Automatically create a Zoho Inventory invoice when an order reaches the trigger status below.', 'zoho-inventory-sync' ); ?>
					</label>
				</td>
			</tr>
			<tr id="zoho_invoice_trigger_row" <?php echo empty( $settings['auto_create_invoice'] ) ? 'style="display:none"' : ''; ?>>
				<th scope="row">
					<label for="zoho_invoice_trigger_status"><?php esc_html_e( 'Create Invoice on Status', 'zoho-inventory-sync' ); ?></label>
				</th>
				<td>
					<?php
					$trigger = $settings['invoice_trigger_status'] ?? 'delivered';
					$statuses = [
						'confirmed' => __( 'Confirmed',  'zoho-inventory-sync' ),
						'packed'    => __( 'Packed',     'zoho-inventory-sync' ),
						'shipped'   => __( 'Shipped',    'zoho-inventory-sync' ),
						'delivered' => __( 'Delivered',  'zoho-inventory-sync' ),
					];
					?>
					<select id="zoho_invoice_trigger_status" name="zoho_settings[invoice_trigger_status]">
						<?php foreach ( $statuses as $value => $label ) : ?>
							<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $trigger, $value ); ?>>
								<?php echo esc_html( $label ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<p class="description"><?php esc_html_e( 'Choose which Zoho Inventory sales order status triggers invoice creation. The status flow is: Confirmed → Packed → Shipped → Delivered.', 'zoho-inventory-sync' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Cancelled / Refunded Orders', 'zoho-inventory-sync' ); ?></th>
				<td>
					<p class="description">
						<?php esc_html_e( 'When an order is cancelled or refunded, the linked Zoho Inventory invoice (if any) and sales order will be automatically voided.', 'zoho-inventory-sync' ); ?>
					</p>
				</td>
			</tr>
		</table>
	</div>

	<script>
	document.getElementById('zoho_auto_invoice').addEventListener('change', function () {
		document.getElementById('zoho_invoice_trigger_row').style.display = this.checked ? '' : 'none';
	});
	</script>

	<!-- Performance -->
	<div class="zoho-card">
		<h2><?php esc_html_e( 'Performance', 'zoho-inventory-sync' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row">
					<label for="batch_size"><?php esc_html_e( 'Batch Size', 'zoho-inventory-sync' ); ?></label>
				</th>
				<td>
					<input type="number" id="batch_size" name="zoho_settings[batch_size]"
						value="<?php echo esc_attr( $settings['batch_size'] ?? 50 ); ?>"
						min="1" max="200" class="small-text">
					<p class="description"><?php esc_html_e( 'Items processed per cron run (1–200).', 'zoho-inventory-sync' ); ?></p>
				</td>
			</tr>
		</table>
	</div>

	<!-- Webhooks -->
	<div class="zoho-card">
		<h2><?php esc_html_e( 'Zoho Inventory Webhooks', 'zoho-inventory-sync' ); ?></h2>
		<p class="description">
			<?php esc_html_e( 'Configure the URL below as the notification URL in your Zoho Inventory Webhooks settings. Add the secret token to both Zoho and this field.', 'zoho-inventory-sync' ); ?>
		</p>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Webhook URL', 'zoho-inventory-sync' ); ?></th>
				<td>
					<code id="zoho-webhook-url"><?php echo esc_url( $webhook_url ); ?></code>
					<button type="button" class="button button-secondary zoho-copy-btn" data-target="zoho-webhook-url">
						<?php esc_html_e( 'Copy', 'zoho-inventory-sync' ); ?>
					</button>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="webhook_secret"><?php esc_html_e( 'Webhook Secret Token', 'zoho-inventory-sync' ); ?></label>
				</th>
				<td>
					<input type="text" id="webhook_secret" name="webhook_secret"
						value="<?php echo esc_attr( $webhook_secret ); ?>"
						class="regular-text" autocomplete="off">
					<p class="description">
						<?php esc_html_e( 'Must match the token you set in Zoho. Leave blank to skip verification (not recommended).', 'zoho-inventory-sync' ); ?>
					</p>
				</td>
			</tr>
		</table>
	</div>

	<?php submit_button( __( 'Save Settings', 'zoho-inventory-sync' ) ); ?>
</form>
