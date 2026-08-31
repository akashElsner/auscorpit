<?php
/**
 * Products view.
 *
 * @package IngramSync
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

$table   = Ingram_Sync_Database::products_table();
$page    = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$per_page = 50;
$offset  = ( $page - 1 ) * $per_page;

$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
$products = $wpdb->get_results(
	$wpdb->prepare(
		"SELECT * FROM {$table} ORDER BY id DESC LIMIT %d OFFSET %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$per_page,
		$offset
	)
);
$total_pages = ceil( $total / $per_page );
?>
<h1><?php esc_html_e( 'Products', 'ingram-sync' ); ?></h1>
<p><?php printf( esc_html__( 'Total cached products: %s', 'ingram-sync' ), '<strong>' . esc_html( number_format_i18n( $total ) ) . '</strong>' ); ?></p>

<table class="widefat striped">
	<thead>
		<tr>
			<th><?php esc_html_e( 'Ingram Part #', 'ingram-sync' ); ?></th>
			<th><?php esc_html_e( 'Vendor Part #', 'ingram-sync' ); ?></th>
			<th><?php esc_html_e( 'WC Product ID', 'ingram-sync' ); ?></th>
			<th><?php esc_html_e( 'Zoho Item ID', 'ingram-sync' ); ?></th>
			<th><?php esc_html_e( 'Status', 'ingram-sync' ); ?></th>
			<th><?php esc_html_e( 'Updated', 'ingram-sync' ); ?></th>
		</tr>
	</thead>
	<tbody>
		<?php if ( $products ) : ?>
			<?php foreach ( $products as $product ) : ?>
				<tr>
					<td><?php echo esc_html( $product->ingram_part_number ); ?></td>
					<td><?php echo esc_html( $product->vendor_part_number ); ?></td>
					<td>
						<?php if ( $product->wc_product_id ) : ?>
							<a href="<?php echo esc_url( get_edit_post_link( $product->wc_product_id ) ); ?>"><?php echo esc_html( $product->wc_product_id ); ?></a>
						<?php else : ?>
							&mdash;
						<?php endif; ?>
					</td>
					<td><?php echo $product->zoho_item_id ? esc_html( $product->zoho_item_id ) : '&mdash;'; ?></td>
					<td><span class="ingram-status ingram-status-<?php echo esc_attr( $product->sync_status ); ?>"><?php echo esc_html( ucfirst( $product->sync_status ) ); ?></span></td>
					<td><?php echo esc_html( wp_date( 'd-M-Y H:i', strtotime( $product->updated_at ) ) ); ?></td>
				</tr>
			<?php endforeach; ?>
		<?php else : ?>
			<tr><td colspan="6"><?php esc_html_e( 'No products cached yet. Run a catalog download from Tools.', 'ingram-sync' ); ?></td></tr>
		<?php endif; ?>
	</tbody>
</table>

<?php if ( $total_pages > 1 ) : ?>
	<div class="tablenav bottom">
		<div class="tablenav-pages">
			<?php
			echo wp_kses_post(
				paginate_links(
					array(
						'base'    => add_query_arg( 'paged', '%#%' ),
						'format'  => '',
						'current' => $page,
						'total'   => $total_pages,
					)
				)
			);
			?>
		</div>
	</div>
<?php endif; ?>
