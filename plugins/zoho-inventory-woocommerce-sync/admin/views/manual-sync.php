<?php
/**
 * Admin view: Manual Sync tab.
 *
 * @package ZohoInventorySync\Admin
 */

defined( 'ABSPATH' ) || exit;

$is_connected = \ZohoInventorySync\Includes\Plugin::instance()->oauth->is_connected();
?>

<div class="zoho-card">
	<h2><?php esc_html_e( 'Queue Statistics', 'zoho-inventory-sync' ); ?></h2>
	<div id="zoho-queue-stats">
		<p><em><?php esc_html_e( 'Loading…', 'zoho-inventory-sync' ); ?></em></p>
	</div>
	<button type="button" id="zoho-refresh-stats-btn" class="button button-secondary" style="margin-top:8px">
		<?php esc_html_e( 'Refresh', 'zoho-inventory-sync' ); ?>
	</button>
</div>

<?php if ( ! $is_connected ) : ?>
	<div class="notice notice-warning inline">
		<p><?php esc_html_e( 'You need to connect your Zoho account before syncing.', 'zoho-inventory-sync' ); ?></p>
	</div>
<?php else : ?>
<div class="zoho-card">
	<h2><?php esc_html_e( 'Manual Sync', 'zoho-inventory-sync' ); ?></h2>
	<p class="description">
		<?php esc_html_e( 'Queue all records of a specific entity for sync. Items are processed in the background — you can check the Sync Logs tab for progress.', 'zoho-inventory-sync' ); ?>
	</p>

	<table class="widefat zoho-sync-table">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Entity', 'zoho-inventory-sync' ); ?></th>
				<th><?php esc_html_e( 'Description', 'zoho-inventory-sync' ); ?></th>
				<th><?php esc_html_e( 'Action', 'zoho-inventory-sync' ); ?></th>
				<th><?php esc_html_e( 'Status', 'zoho-inventory-sync' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php
			$entities = [
				'customers' => [
					'label' => __( 'Customers', 'zoho-inventory-sync' ),
					'desc'  => __( 'Sync all WooCommerce customers to Zoho Inventory contacts.', 'zoho-inventory-sync' ),
				],
				'products'  => [
					'label' => __( 'Products', 'zoho-inventory-sync' ),
					'desc'  => __( 'Sync all published products to Zoho Inventory items.', 'zoho-inventory-sync' ),
				],
				'orders'    => [
					'label' => __( 'Orders', 'zoho-inventory-sync' ),
					'desc'  => __( 'Sync all orders to Zoho Inventory sales orders.', 'zoho-inventory-sync' ),
				],
				'inventory' => [
					'label' => __( 'Inventory', 'zoho-inventory-sync' ),
					'desc'  => __( 'Push current stock levels to Zoho Inventory.', 'zoho-inventory-sync' ),
				],
			];
			foreach ( $entities as $key => $info ) :
				?>
				<tr>
					<td><strong><?php echo esc_html( $info['label'] ); ?></strong></td>
					<td><?php echo esc_html( $info['desc'] ); ?></td>
					<td>
						<button type="button"
							class="button button-primary zoho-sync-btn"
							data-entity="<?php echo esc_attr( $key ); ?>">
							<?php
							printf(
								/* translators: %s: entity label */
								esc_html__( 'Sync All %s', 'zoho-inventory-sync' ),
								esc_html( $info['label'] )
							);
							?>
						</button>
					</td>
					<td class="zoho-sync-status" id="zoho-sync-status-<?php echo esc_attr( $key ); ?>"></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>
<?php endif; ?>
