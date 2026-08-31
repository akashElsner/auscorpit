<?php
/**
 * Import page view.
 *
 * @package ZohoInventoryImport\Admin
 *
 * @var array{connected:bool,message:string,org_id:string} $connection Connection status.
 */

defined( 'ABSPATH' ) || exit;

$is_configured = ! empty( $connection['connected'] );
$sync_url      = zoho_inventory_import()->bridge()->get_sync_settings_url();
?>
<div class="wrap zoho-inv-import-wrap">
	<h1><?php esc_html_e( 'Zoho CSV Import', 'zoho-inventory-import' ); ?></h1>

	<?php if ( $is_configured ) : ?>
		<div class="notice notice-success inline">
			<p>
				<?php echo esc_html( $connection['message'] ); ?>
				<?php if ( ! empty( $connection['org_id'] ) ) : ?>
					<strong><? esc_html_e( 'Organization ID:', 'zoho-inventory-import' ); ?></strong>
					<?php echo esc_html( $connection['org_id'] ); ?>
				<?php endif; ?>
			</p>
		</div>
	<?php else : ?>
		<div class="notice notice-warning">
			<p>
				<?php echo esc_html( $connection['message'] ); ?>
				<a href="<?php echo esc_url( $sync_url ); ?>" class="button button-secondary" style="margin-left:8px;">
					<?php esc_html_e( 'Open Zoho Inventory Sync Settings', 'zoho-inventory-import' ); ?>
				</a>
			</p>
		</div>
	<?php endif; ?>

	<div id="zoho-inv-notice-area" style="display:none;" aria-live="polite"></div>

	<div class="zoho-inv-import-card">
		<h2><?php esc_html_e( 'Upload CSV File', 'zoho-inventory-import' ); ?></h2>
		<p class="description">
			<?php esc_html_e( 'Upload a CSV file containing product/item data. Only .csv files are accepted.', 'zoho-inventory-import' ); ?>
		</p>

		<form id="zoho-inv-import-form" enctype="multipart/form-data" method="post">
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">
						<label for="csv_file"><?php esc_html_e( 'CSV File', 'zoho-inventory-import' ); ?></label>
					</th>
					<td>
						<input
							type="file"
							id="csv_file"
							name="csv_file"
							accept=".csv,text/csv,application/vnd.ms-excel"
							required
							<?php disabled( ! $is_configured ); ?>
						/>
						<p class="description">
							<?php esc_html_e( 'See examples/example-import.csv in the plugin folder for the expected format.', 'zoho-inventory-import' ); ?>
						</p>
					</td>
				</tr>
			</table>

			<p class="submit">
				<button type="submit" class="button button-primary" id="zoho-inv-import-start" <?php disabled( ! $is_configured ); ?>>
					<?php esc_html_e( 'Start Import', 'zoho-inventory-import' ); ?>
				</button>
			</p>
		</form>
	</div>

	<div id="zoho-inv-import-progress" class="zoho-inv-import-card" style="display:none;">
		<h2><?php esc_html_e( 'Import Progress', 'zoho-inventory-import' ); ?></h2>
		<div class="zoho-inv-progress-bar-wrap">
			<div class="zoho-inv-progress-bar" id="zoho-inv-progress-bar" style="width:0%;"></div>
		</div>
		<p id="zoho-inv-progress-text">0%</p>
		<p id="zoho-inv-progress-status"></p>

		<div id="zoho-inv-live-log-wrap" style="display:none;">
			<h3><?php esc_html_e( 'Live Import Log', 'zoho-inventory-import' ); ?></h3>
			<div class="zoho-inv-live-log-scroll">
				<table class="widefat zoho-inv-live-log-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Time', 'zoho-inventory-import' ); ?></th>
							<th><?php esc_html_e( 'Level', 'zoho-inventory-import' ); ?></th>
							<th><?php esc_html_e( 'Message', 'zoho-inventory-import' ); ?></th>
						</tr>
					</thead>
					<tbody id="zoho-inv-live-log-body"></tbody>
				</table>
			</div>
		</div>
	</div>

	<div id="zoho-inv-import-summary" class="zoho-inv-import-card" style="display:none;">
		<h2><?php esc_html_e( 'Import Summary', 'zoho-inventory-import' ); ?></h2>
		<table class="widefat striped zoho-inv-summary-table">
			<tbody>
				<tr>
					<th><?php esc_html_e( 'Total Records Processed', 'zoho-inventory-import' ); ?></th>
					<td id="summary-processed">0</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'New Items Created', 'zoho-inventory-import' ); ?></th>
					<td id="summary-created">0</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Existing Items Updated', 'zoho-inventory-import' ); ?></th>
					<td id="summary-updated">0</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Failed Records', 'zoho-inventory-import' ); ?></th>
					<td id="summary-failed">0</td>
				</tr>
			</tbody>
		</table>

		<div id="zoho-inv-import-errors" style="display:none;">
			<h3>
				<span class="dashicons dashicons-warning" style="color:#d63638;"></span>
				<?php esc_html_e( 'Import Errors', 'zoho-inventory-import' ); ?>
			</h3>
			<ul id="zoho-inv-error-list"></ul>
		</div>

		<div class="zoho-inv-log-actions">
			<p id="zoho-inv-log-download-wrap" style="display:none;">
				<a href="#" class="button button-secondary" id="zoho-inv-log-download">
					<span class="dashicons dashicons-download" style="margin-top:3px;"></span>
					<?php esc_html_e( 'Download Error Log', 'zoho-inventory-import' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . \ZohoInventoryImport\Admin\Admin_UI::ERROR_LOG_SLUG ) ); ?>" class="button" id="zoho-inv-log-view" style="display:none;">
					<span class="dashicons dashicons-list-view" style="margin-top:3px;"></span>
					<?php esc_html_e( 'View Full Error Log', 'zoho-inventory-import' ); ?>
				</a>
			</p>
		</div>
	</div>

	<div class="zoho-inv-import-card">
		<h2><?php esc_html_e( 'CSV Field Mapping', 'zoho-inventory-import' ); ?></h2>
		<table class="widefat striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'CSV Column', 'zoho-inventory-import' ); ?></th>
					<th><?php esc_html_e( 'Zoho Inventory Field', 'zoho-inventory-import' ); ?></th>
					<th><?php esc_html_e( 'Notes', 'zoho-inventory-import' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<tr><td>Name</td><td>name</td><td><?php esc_html_e( 'Required', 'zoho-inventory-import' ); ?></td></tr>
				<tr><td>Dynamic Supplies SKU</td><td>sku</td><td><?php esc_html_e( 'Duplicate matching (preferred)', 'zoho-inventory-import' ); ?></td></tr>
				<tr><td>Reseller Price Ex GST</td><td>rate</td><td></td></tr>
				<tr><td>RRP (inc GST)</td><td>purchase_rate</td><td></td></tr>
				<tr><td>Alternative Product Title</td><td>description, purchase_description</td><td></td></tr>
				<tr><td><em><?php esc_html_e( '(not in CSV)', 'zoho-inventory-import' ); ?></em></td><td>unit, initial_stock, upc, ean, isbn, part_number, product_type, item_type</td><td><?php esc_html_e( 'Defaults in WooCommerce → Zoho Import Settings', 'zoho-inventory-import' ); ?></td></tr>
			</tbody>
		</table>
	</div>
</div>
