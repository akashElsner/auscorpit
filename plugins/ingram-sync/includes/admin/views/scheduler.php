<?php
/**
 * Scheduler view.
 *
 * @package IngramSync
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$next_run = Ingram_Sync_Scheduler::get_next_run();
?>
<h1><?php esc_html_e( 'Scheduler', 'ingram-sync' ); ?></h1>

<form method="post" action="">
	<?php wp_nonce_field( 'ingram_sync_save_scheduler', 'ingram_sync_nonce' ); ?>
	<input type="hidden" name="ingram_sync_action" value="scheduler" />

	<table class="form-table">
		<tr>
			<th><?php esc_html_e( 'Enable Automatic Sync', 'ingram-sync' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="scheduler_enabled" value="1" <?php checked( $settings['scheduler_enabled'], 'yes' ); ?> />
					<?php esc_html_e( 'Yes', 'ingram-sync' ); ?>
				</label>
			</td>
		</tr>
		<tr>
			<th><label for="scheduler_frequency"><?php esc_html_e( 'Frequency', 'ingram-sync' ); ?></label></th>
			<td>
				<select name="scheduler_frequency" id="scheduler_frequency">
					<?php
					$frequencies = array(
						'hourly'         => __( 'Hourly', 'ingram-sync' ),
						'every_6_hours'  => __( 'Every 6 Hours', 'ingram-sync' ),
						'every_12_hours' => __( 'Every 12 Hours', 'ingram-sync' ),
						'daily'          => __( 'Daily', 'ingram-sync' ),
						'weekly'         => __( 'Weekly', 'ingram-sync' ),
					);
					foreach ( $frequencies as $value => $label ) :
						?>
						<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $settings['scheduler_frequency'], $value ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			</td>
		</tr>
		<tr>
			<th><label for="scheduler_time"><?php esc_html_e( 'Time', 'ingram-sync' ); ?></label></th>
			<td>
				<input type="time" name="scheduler_time" id="scheduler_time" value="<?php echo esc_attr( $settings['scheduler_time'] ); ?>" />
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Email On Failure', 'ingram-sync' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="notify_on_failure" value="1" <?php checked( $settings['notify_on_failure'], 'yes' ); ?> />
					<?php
					printf(
						/* translators: %s: site admin email address */
						esc_html__( 'Email %s when a sync run fails.', 'ingram-sync' ),
						esc_html( get_option( 'admin_email' ) )
					);
					?>
				</label>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Status', 'ingram-sync' ); ?></th>
			<td>
				<?php echo 'yes' === $settings['scheduler_running'] ? esc_html__( 'Running', 'ingram-sync' ) : esc_html__( 'Stopped', 'ingram-sync' ); ?>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Next Scheduled Run', 'ingram-sync' ); ?></th>
			<td><?php echo esc_html( $next_run['formatted'] ); ?></td>
		</tr>
	</table>

	<?php submit_button( __( 'Save', 'ingram-sync' ) ); ?>
</form>

<form method="post" action="" style="display:inline-block;margin-right:8px;">
	<?php wp_nonce_field( 'ingram_sync_save_run_now', 'ingram_sync_nonce' ); ?>
	<input type="hidden" name="ingram_sync_action" value="run_now" />
	<?php submit_button( __( 'Run Now', 'ingram-sync' ), 'secondary', 'submit', false ); ?>
</form>

<form method="post" action="" style="display:inline-block;margin-right:8px;">
	<?php wp_nonce_field( 'ingram_sync_save_start_scheduler', 'ingram_sync_nonce' ); ?>
	<input type="hidden" name="ingram_sync_action" value="start_scheduler" />
	<?php submit_button( __( 'Enable Cron', 'ingram-sync' ), 'secondary', 'submit', false, 'yes' === $settings['scheduler_enabled'] ? array( 'disabled' => 'disabled' ) : array() ); ?>
</form>

<form method="post" action="" style="display:inline-block;">
	<?php wp_nonce_field( 'ingram_sync_save_stop_scheduler', 'ingram_sync_nonce' ); ?>
	<input type="hidden" name="ingram_sync_action" value="stop_scheduler" />
	<?php submit_button( __( 'Disable Cron', 'ingram-sync' ), 'secondary', 'submit', false, 'yes' !== $settings['scheduler_enabled'] ? array( 'disabled' => 'disabled' ) : array() ); ?>
</form>
