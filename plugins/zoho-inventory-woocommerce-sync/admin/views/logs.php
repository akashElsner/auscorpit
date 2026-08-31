<?php
/**
 * Admin view: Sync Logs tab.
 *
 * @package ZohoInventorySync\Admin
 */

defined( 'ABSPATH' ) || exit;

$initial_logs = \ZohoInventorySync\Includes\Plugin::instance()->logger->get_logs( [ 'limit' => 50 ] );
?>

<div class="zoho-card">
	<h2><?php esc_html_e( 'Sync Log', 'zoho-inventory-sync' ); ?></h2>

	<!-- Filters -->
	<div class="zoho-log-filters" style="margin-bottom:12px; display:flex; gap:12px; flex-wrap:wrap; align-items:center;">
		<select id="zoho-log-entity-filter">
			<option value=""><?php esc_html_e( 'All entities', 'zoho-inventory-sync' ); ?></option>
			<option value="customer"><?php esc_html_e( 'Customers', 'zoho-inventory-sync' ); ?></option>
			<option value="product"><?php esc_html_e( 'Products', 'zoho-inventory-sync' ); ?></option>
			<option value="order"><?php esc_html_e( 'Orders', 'zoho-inventory-sync' ); ?></option>
			<option value="inventory"><?php esc_html_e( 'Inventory', 'zoho-inventory-sync' ); ?></option>
			<option value="system"><?php esc_html_e( 'System', 'zoho-inventory-sync' ); ?></option>
		</select>

		<select id="zoho-log-status-filter">
			<option value=""><?php esc_html_e( 'All statuses', 'zoho-inventory-sync' ); ?></option>
			<option value="success"><?php esc_html_e( 'Success', 'zoho-inventory-sync' ); ?></option>
			<option value="error"><?php esc_html_e( 'Error', 'zoho-inventory-sync' ); ?></option>
			<option value="info"><?php esc_html_e( 'Info', 'zoho-inventory-sync' ); ?></option>
			<option value="warning"><?php esc_html_e( 'Warning', 'zoho-inventory-sync' ); ?></option>
		</select>

		<button type="button" id="zoho-log-refresh-btn" class="button button-secondary">
			<?php esc_html_e( 'Refresh', 'zoho-inventory-sync' ); ?>
		</button>
	</div>

	<table class="widefat striped" id="zoho-log-table">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Date / Time', 'zoho-inventory-sync' ); ?></th>
				<th><?php esc_html_e( 'Entity', 'zoho-inventory-sync' ); ?></th>
				<th><?php esc_html_e( 'Action', 'zoho-inventory-sync' ); ?></th>
				<th><?php esc_html_e( 'Status', 'zoho-inventory-sync' ); ?></th>
				<th><?php esc_html_e( 'Message', 'zoho-inventory-sync' ); ?></th>
			</tr>
		</thead>
		<tbody id="zoho-log-tbody">
			<?php if ( empty( $initial_logs ) ) : ?>
				<tr><td colspan="5"><?php esc_html_e( 'No log entries yet.', 'zoho-inventory-sync' ); ?></td></tr>
			<?php else : ?>
				<?php foreach ( $initial_logs as $log ) :
					$status_color = match ( $log['status'] ) {
						'success' => 'color:green',
						'error'   => 'color:red',
						'warning' => 'color:orange',
						default   => '',
					};
					?>
					<tr>
						<td><?php echo esc_html( $log['created_at'] ); ?></td>
						<td><?php echo esc_html( $log['entity_type'] ); ?> #<?php echo esc_html( $log['entity_id'] ); ?></td>
						<td><?php echo esc_html( $log['action'] ); ?></td>
						<td style="<?php echo esc_attr( $status_color ); ?>"><?php echo esc_html( ucfirst( $log['status'] ) ); ?></td>
						<td><?php echo esc_html( $log['message'] ); ?></td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
</div>
