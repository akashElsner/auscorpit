<?php
/**
 * Dashboard view.
 *
 * @package IngramSync
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$last_token   = (int) ( $settings['last_token_generated'] ?? 0 );
$token_expiry = (int) ( $settings['token_expiry'] ?? 0 );
$completed    = (int) ( $settings['stats_completed'] ?? 0 );
$failed       = (int) ( $settings['stats_failed'] ?? 0 );
$running      = (int) ( $settings['stats_running'] ?? 0 );
$next_run     = Ingram_Sync_Scheduler::get_next_run();
$lock_status  = Ingram_Sync_Lock::status();
?>
<h1><?php esc_html_e( 'Ingram Sync Dashboard', 'ingram-sync' ); ?></h1>

<div class="ingram-sync-cards">
	<div class="ingram-sync-card">
		<h3><?php esc_html_e( 'Last Token Generated', 'ingram-sync' ); ?></h3>
		<p class="ingram-sync-stat"><?php echo $last_token ? esc_html( wp_date( 'd-M-Y', $last_token ) ) : esc_html__( 'Never', 'ingram-sync' ); ?></p>
	</div>

	<div class="ingram-sync-card">
		<h3><?php esc_html_e( 'Token Expiry', 'ingram-sync' ); ?></h3>
		<p class="ingram-sync-stat"><?php echo esc_html( Ingram_Sync_Admin_Pages::human_time_until( $token_expiry ) ); ?></p>
		<p class="ingram-sync-meta"><?php echo esc_html( Ingram_Sync_Admin_Pages::format_expiry( $token_expiry ) ); ?></p>
	</div>

	<div class="ingram-sync-card">
		<h3><?php esc_html_e( 'Completed', 'ingram-sync' ); ?></h3>
		<p class="ingram-sync-stat ingram-sync-success"><?php echo esc_html( number_format_i18n( $completed ) ); ?></p>
	</div>

	<div class="ingram-sync-card">
		<h3><?php esc_html_e( 'Failed', 'ingram-sync' ); ?></h3>
		<p class="ingram-sync-stat ingram-sync-error"><?php echo esc_html( number_format_i18n( $failed ) ); ?></p>
	</div>

	<div class="ingram-sync-card">
		<h3><?php esc_html_e( 'Running', 'ingram-sync' ); ?></h3>
		<p class="ingram-sync-stat ingram-sync-warning"><?php echo esc_html( number_format_i18n( $running ) ); ?></p>
	</div>
</div>

<div class="ingram-sync-section">
	<h2><?php esc_html_e( 'Sync Lock', 'ingram-sync' ); ?></h2>
	<?php if ( $lock_status['locked'] ) : ?>
		<p>
			<strong><?php esc_html_e( 'Status:', 'ingram-sync' ); ?></strong>
			<?php
			printf(
				/* translators: 1: what is running, 2: how long ago it started */
				esc_html__( '%1$s running, started %2$s ago.', 'ingram-sync' ),
				esc_html( $lock_status['context'] ?: __( 'unknown', 'ingram-sync' ) ),
				esc_html( human_time_diff( $lock_status['started_at'] ?: time() ) )
			);
			?>
			<?php if ( $lock_status['stale'] ) : ?>
				<span class="ingram-log-level ingram-log-warning"><?php esc_html_e( 'Stale — likely abandoned', 'ingram-sync' ); ?></span>
			<?php endif; ?>
		</p>
		<p class="description"><?php esc_html_e( 'If no sync is actually running, use "Clear Stuck Lock" on the Tools page.', 'ingram-sync' ); ?></p>
	<?php else : ?>
		<p><?php esc_html_e( 'No sync currently running.', 'ingram-sync' ); ?></p>
	<?php endif; ?>
</div>

<div class="ingram-sync-section">
	<h2><?php esc_html_e( 'Scheduler', 'ingram-sync' ); ?></h2>
	<p>
		<strong><?php esc_html_e( 'Status:', 'ingram-sync' ); ?></strong>
		<?php echo 'yes' === $settings['scheduler_running'] ? esc_html__( 'Active', 'ingram-sync' ) : esc_html__( 'Stopped', 'ingram-sync' ); ?>
	</p>
	<p>
		<strong><?php esc_html_e( 'Next Run:', 'ingram-sync' ); ?></strong>
		<?php echo esc_html( $next_run['formatted'] ); ?>
	</p>
</div>

<div class="ingram-sync-section">
	<h2><?php esc_html_e( 'Recent Logs', 'ingram-sync' ); ?></h2>
	<?php
	$logs = Ingram_Sync_Logger::get_logs( 10 );
	if ( $logs ) :
		?>
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
				<?php foreach ( $logs as $log ) : ?>
					<tr>
						<td><?php echo esc_html( wp_date( 'H:i', strtotime( $log->created_at ) ) ); ?></td>
						<td><?php echo esc_html( $log->source ); ?></td>
						<td><?php echo esc_html( $log->message ); ?></td>
						<td><span class="ingram-log-level ingram-log-<?php echo esc_attr( $log->level ); ?>"><?php echo esc_html( ucfirst( $log->level ) ); ?></span></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php else : ?>
		<p><?php esc_html_e( 'No logs yet.', 'ingram-sync' ); ?></p>
	<?php endif; ?>
</div>
