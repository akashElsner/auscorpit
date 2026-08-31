<?php
/**
 * Sync settings tab.
 *
 * @package IngramSync
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<h2><?php esc_html_e( 'Sync Settings', 'ingram-sync' ); ?></h2>

<form method="post" action="">
	<?php wp_nonce_field( 'ingram_sync_save_sync', 'ingram_sync_nonce' ); ?>
	<input type="hidden" name="ingram_sync_action" value="sync" />

	<table class="form-table">
		<tr>
			<th><label for="batch_size"><?php esc_html_e( 'Batch Size', 'ingram-sync' ); ?></label></th>
			<td><input type="number" name="batch_size" id="batch_size" class="small-text" value="<?php echo esc_attr( $settings['batch_size'] ); ?>" min="1" max="500" /></td>
		</tr>
		<tr>
			<th><label for="pause_between_calls"><?php esc_html_e( 'Pause Between API Calls', 'ingram-sync' ); ?></label></th>
			<td>
				<input type="number" name="pause_between_calls" id="pause_between_calls" class="small-text" value="<?php echo esc_attr( $settings['pause_between_calls'] ); ?>" min="0" max="60" />
				<?php esc_html_e( 'Seconds', 'ingram-sync' ); ?>
			</td>
		</tr>
		<tr>
			<th><label for="max_retry"><?php esc_html_e( 'Maximum Retry', 'ingram-sync' ); ?></label></th>
			<td><input type="number" name="max_retry" id="max_retry" class="small-text" value="<?php echo esc_attr( $settings['max_retry'] ); ?>" min="1" max="20" /></td>
		</tr>
		<tr>
			<th><label for="timeout"><?php esc_html_e( 'Timeout', 'ingram-sync' ); ?></label></th>
			<td>
				<input type="number" name="timeout" id="timeout" class="small-text" value="<?php echo esc_attr( $settings['timeout'] ); ?>" min="10" max="300" />
				<?php esc_html_e( 'Seconds', 'ingram-sync' ); ?>
			</td>
		</tr>
		<tr>
			<th><label for="min_sync_interval_hours"><?php esc_html_e( 'Minimum Time Between Full Syncs', 'ingram-sync' ); ?></label></th>
			<td>
				<input type="number" name="min_sync_interval_hours" id="min_sync_interval_hours" class="small-text" value="<?php echo esc_attr( $settings['min_sync_interval_hours'] ); ?>" min="0" max="168" />
				<?php esc_html_e( 'Hours', 'ingram-sync' ); ?>
				<p class="description"><?php esc_html_e( 'The scheduled (cron) sync is skipped if a full sync completed more recently than this. Set to 0 to disable. "Run Now" and the Tools page buttons always run regardless of this setting.', 'ingram-sync' ); ?></p>
			</td>
		</tr>
	</table>

	<?php submit_button( __( 'Save Settings', 'ingram-sync' ) ); ?>
</form>
