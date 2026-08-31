<?php
/**
 * Error log viewer page.
 *
 * @package ZohoInventoryImport\Admin
 *
 * @var array<string,array<string,mixed>> $history   Import session history.
 * @var string                            $view_id   Session ID being viewed.
 * @var array<int,array{time:string,status:string,message:string}> $entries Parsed log entries.
 * @var Logger                            $logger    Logger instance.
 */

use ZohoInventoryImport\Includes\Logger;

defined( 'ABSPATH' ) || exit;

$import_url = admin_url( 'admin.php?page=' . \ZohoInventoryImport\Admin\Admin_UI::MENU_SLUG );
?>
<div class="wrap zoho-inv-import-wrap">
	<h1><?php esc_html_e( 'Zoho Import Error Log', 'zoho-inventory-import' ); ?></h1>

	<?php if ( ! empty( $_GET['deleted'] ) ) : ?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'Log deleted successfully.', 'zoho-inventory-import' ); ?></p>
		</div>
	<?php endif; ?>

	<p>
		<a href="<?php echo esc_url( $import_url ); ?>" class="button">
			<?php esc_html_e( '← Back to CSV Import', 'zoho-inventory-import' ); ?>
		</a>
	</p>

	<?php if ( $view_id && $entries ) : ?>
		<div class="zoho-inv-import-card">
			<h2>
				<?php
				printf(
					/* translators: %s: session id */
					esc_html__( 'Log Details — Session %s', 'zoho-inventory-import' ),
					esc_html( $view_id )
				);
				?>
			</h2>

			<?php
			$download_url = wp_nonce_url(
				admin_url( 'admin-post.php?action=zoho_inv_import_download_log&session_id=' . rawurlencode( $view_id ) ),
				'zoho_inv_import_download_log_' . $view_id
			);
			?>
			<p>
				<a href="<?php echo esc_url( $download_url ); ?>" class="button button-secondary">
					<?php esc_html_e( 'Download Full Log', 'zoho-inventory-import' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . \ZohoInventoryImport\Admin\Admin_UI::ERROR_LOG_SLUG ) ); ?>" class="button">
					<?php esc_html_e( 'Back to Log List', 'zoho-inventory-import' ); ?>
				</a>
			</p>

			<div class="zoho-inv-log-viewer">
				<table class="widefat striped zoho-inv-log-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Time (UTC)', 'zoho-inventory-import' ); ?></th>
							<th><?php esc_html_e( 'Level', 'zoho-inventory-import' ); ?></th>
							<th><?php esc_html_e( 'Message', 'zoho-inventory-import' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $entries as $entry ) : ?>
							<tr class="zoho-inv-log-row zoho-inv-log-<?php echo esc_attr( $entry['status'] ); ?>">
								<td><?php echo esc_html( $entry['time'] ); ?></td>
								<td>
									<span class="zoho-inv-log-badge zoho-inv-log-badge-<?php echo esc_attr( $entry['status'] ); ?>">
										<?php echo esc_html( strtoupper( $entry['status'] ) ); ?>
									</span>
								</td>
								<td><code class="zoho-inv-log-message"><?php echo esc_html( $entry['message'] ); ?></code></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
	<?php elseif ( $view_id ) : ?>
		<div class="notice notice-warning">
			<p><?php esc_html_e( 'Log file not found for this session.', 'zoho-inventory-import' ); ?></p>
		</div>
	<?php endif; ?>

	<div class="zoho-inv-import-card">
		<h2><?php esc_html_e( 'Import History', 'zoho-inventory-import' ); ?></h2>

		<?php if ( empty( $history ) ) : ?>
			<p class="description"><?php esc_html_e( 'No import logs yet. Run a CSV import to generate logs.', 'zoho-inventory-import' ); ?></p>
		<?php else : ?>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Date', 'zoho-inventory-import' ); ?></th>
						<th><?php esc_html_e( 'Status', 'zoho-inventory-import' ); ?></th>
						<th><?php esc_html_e( 'Processed', 'zoho-inventory-import' ); ?></th>
						<th><?php esc_html_e( 'Created', 'zoho-inventory-import' ); ?></th>
						<th><?php esc_html_e( 'Updated', 'zoho-inventory-import' ); ?></th>
						<th><?php esc_html_e( 'Failed', 'zoho-inventory-import' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'zoho-inventory-import' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $history as $session_id => $meta ) : ?>
						<?php
						$status      = (string) ( $meta['status'] ?? 'unknown' );
						$failed      = (int) ( $meta['failed'] ?? 0 );
						$started     = (int) ( $meta['started_at'] ?? 0 );
						$view_url    = admin_url( 'admin.php?page=' . \ZohoInventoryImport\Admin\Admin_UI::ERROR_LOG_SLUG . '&session_id=' . rawurlencode( $session_id ) );
						$delete_url  = wp_nonce_url(
							admin_url( 'admin-post.php?action=zoho_inv_import_delete_log&session_id=' . rawurlencode( $session_id ) ),
							'zoho_inv_import_delete_log_' . $session_id
						);
						$download_url = wp_nonce_url(
							admin_url( 'admin-post.php?action=zoho_inv_import_download_log&session_id=' . rawurlencode( $session_id ) ),
							'zoho_inv_import_download_log_' . $session_id
						);
						?>
						<tr>
							<td><?php echo esc_html( $started ? wp_date( 'Y-m-d H:i:s', $started ) : '—' ); ?></td>
							<td>
								<span class="zoho-inv-log-badge zoho-inv-log-badge-<?php echo esc_attr( $failed > 0 ? 'error' : 'success' ); ?>">
									<?php echo esc_html( str_replace( '_', ' ', $status ) ); ?>
								</span>
							</td>
							<td><?php echo esc_html( (string) ( $meta['processed'] ?? '—' ) ); ?></td>
							<td><?php echo esc_html( (string) ( $meta['created'] ?? '—' ) ); ?></td>
							<td><?php echo esc_html( (string) ( $meta['updated'] ?? '—' ) ); ?></td>
							<td>
								<strong class="<?php echo $failed > 0 ? 'zoho-inv-failed-count' : ''; ?>">
									<?php echo esc_html( (string) $failed ); ?>
								</strong>
							</td>
							<td>
								<a href="<?php echo esc_url( $view_url ); ?>" class="button button-small"><?php esc_html_e( 'View Log', 'zoho-inventory-import' ); ?></a>
								<a href="<?php echo esc_url( $download_url ); ?>" class="button button-small"><?php esc_html_e( 'Download', 'zoho-inventory-import' ); ?></a>
								<a href="<?php echo esc_url( $delete_url ); ?>" class="button button-small" onclick="return confirm('<?php echo esc_js( __( 'Delete this log?', 'zoho-inventory-import' ) ); ?>');">
									<?php esc_html_e( 'Delete', 'zoho-inventory-import' ); ?>
								</a>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	</div>
</div>
