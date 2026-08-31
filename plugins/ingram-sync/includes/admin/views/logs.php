<?php
/**
 * Logs view.
 *
 * @package IngramSync
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$page     = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$per_page = 100;
$source   = isset( $_GET['source'] ) ? sanitize_text_field( wp_unslash( $_GET['source'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

global $wpdb;
$table = Ingram_Sync_Database::logs_table();

if ( $source ) {
	$total = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE source = %s", $source ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$offset = ( $page - 1 ) * $per_page;
	$logs = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT * FROM {$table} WHERE source = %s ORDER BY id DESC LIMIT %d OFFSET %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$source,
			$per_page,
			$offset
		)
	);
} else {
	$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$logs = Ingram_Sync_Logger::get_logs( $per_page );
}

$sources = $wpdb->get_col( "SELECT DISTINCT source FROM {$table} ORDER BY source" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
$total_pages = ceil( $total / $per_page );
?>
<h1><?php esc_html_e( 'Logs', 'ingram-sync' ); ?></h1>

<p class="ingram-queue-filters">
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=ingram-sync-logs' ) ); ?>" class="<?php echo '' === $source ? 'current' : ''; ?>"><?php esc_html_e( 'All', 'ingram-sync' ); ?></a>
	<?php foreach ( $sources as $src ) : ?>
		| <a href="<?php echo esc_url( admin_url( 'admin.php?page=ingram-sync-logs&source=' . rawurlencode( $src ) ) ); ?>" class="<?php echo $source === $src ? 'current' : ''; ?>"><?php echo esc_html( $src ); ?></a>
	<?php endforeach; ?>
</p>

<table class="widefat striped">
	<thead>
		<tr>
			<th><?php esc_html_e( 'Time', 'ingram-sync' ); ?></th>
			<th><?php esc_html_e( 'Source', 'ingram-sync' ); ?></th>
			<th><?php esc_html_e( 'Message', 'ingram-sync' ); ?></th>
			<th><?php esc_html_e( 'Level', 'ingram-sync' ); ?></th>
		</tr>
	</thead>
	<tbody>
		<?php if ( $logs ) : ?>
			<?php foreach ( $logs as $log ) : ?>
				<tr>
					<td><?php echo esc_html( wp_date( 'H:i', strtotime( $log->created_at ) ) ); ?></td>
					<td><?php echo esc_html( $log->source ); ?></td>
					<td><?php echo esc_html( $log->message ); ?></td>
					<td><span class="ingram-log-level ingram-log-<?php echo esc_attr( $log->level ); ?>"><?php echo esc_html( ucfirst( $log->level ) ); ?></span></td>
				</tr>
			<?php endforeach; ?>
		<?php else : ?>
			<tr><td colspan="4"><?php esc_html_e( 'No logs found.', 'ingram-sync' ); ?></td></tr>
		<?php endif; ?>
	</tbody>
</table>

<p>
	<button type="button" class="button ingram-ajax-btn" data-action="clear_logs"><?php esc_html_e( 'Clear Logs', 'ingram-sync' ); ?></button>
</p>
