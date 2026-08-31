<?php
/**
 * Authentication settings tab.
 *
 * @package IngramSync
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<h2><?php esc_html_e( 'Authentication', 'ingram-sync' ); ?></h2>

<form method="post" action="">
	<?php wp_nonce_field( 'ingram_sync_save_authentication', 'ingram_sync_nonce' ); ?>
	<input type="hidden" name="ingram_sync_action" value="authentication" />

	<table class="form-table">
		<tr>
			<th><label for="grant_type"><?php esc_html_e( 'Grant Type', 'ingram-sync' ); ?></label></th>
			<td>
				<select name="grant_type" id="grant_type">
					<option value="client_credentials" <?php selected( $settings['grant_type'], 'client_credentials' ); ?>><?php esc_html_e( 'client_credentials', 'ingram-sync' ); ?></option>
				</select>
			</td>
		</tr>
		<tr>
			<th><label for="client_id"><?php esc_html_e( 'Client ID', 'ingram-sync' ); ?></label></th>
			<td><input type="text" name="client_id" id="client_id" class="regular-text" value="<?php echo esc_attr( $settings['client_id'] ); ?>" autocomplete="off" /></td>
		</tr>
		<tr>
			<th><label for="client_secret"><?php esc_html_e( 'Client Secret', 'ingram-sync' ); ?></label></th>
			<td>
				<input type="password" name="client_secret" id="client_secret" class="regular-text" value="" placeholder="<?php echo $settings['client_secret'] ? esc_attr( Ingram_Sync_Security::mask_secret( $settings['client_secret'] ) ) : ''; ?>" autocomplete="new-password" />
				<p class="description"><?php esc_html_e( 'Leave blank to keep existing secret.', 'ingram-sync' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Access Token', 'ingram-sync' ); ?></th>
			<td>
				<input type="text" class="regular-text" readonly value="<?php echo $settings['access_token'] ? esc_attr( Ingram_Sync_Security::mask_secret( $settings['access_token'] ) ) : ''; ?>" />
				<p class="description"><?php esc_html_e( 'Generated automatically. Do not enter manually.', 'ingram-sync' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Token Expiry', 'ingram-sync' ); ?></th>
			<td><?php echo esc_html( Ingram_Sync_Admin_Pages::format_expiry( (int) $settings['token_expiry'] ) ); ?></td>
		</tr>
	</table>

	<?php submit_button( __( 'Save Settings', 'ingram-sync' ) ); ?>
</form>

<?php
$token_info = Ingram_Sync_Oauth::get_token_customer_info();
if ( $token_info && ! empty( $token_info['claims'] ) ) :
	$safe_claims = array();
	foreach ( $token_info['claims'] as $key => $value ) {
		if ( is_scalar( $value ) && ! preg_match( '/secret|password|token/i', (string) $key ) ) {
			$safe_claims[ $key ] = $value;
		}
	}
	if ( $safe_claims ) :
		?>
		<div class="ingram-sync-section">
			<h3><?php esc_html_e( 'Token Claims (for troubleshooting)', 'ingram-sync' ); ?></h3>
			<table class="widefat striped">
				<?php foreach ( $safe_claims as $key => $value ) : ?>
					<tr><th><?php echo esc_html( $key ); ?></th><td><code><?php echo esc_html( (string) $value ); ?></code></td></tr>
				<?php endforeach; ?>
			</table>
		</div>
		<?php
	endif;
endif;
?>

<form method="post" action="" style="display:inline-block;margin-right:8px;">
	<?php wp_nonce_field( 'ingram_sync_save_refresh_token', 'ingram_sync_nonce' ); ?>
	<input type="hidden" name="ingram_sync_action" value="refresh_token" />
	<?php submit_button( __( 'Refresh Token', 'ingram-sync' ), 'secondary', 'submit', false ); ?>
</form>
<button type="button" class="button ingram-ajax-btn" data-action="sync_from_token"><?php esc_html_e( 'Apply Customer from Token', 'ingram-sync' ); ?></button>
<button type="button" class="button ingram-ajax-btn" data-action="test_oauth"><?php esc_html_e( 'Test Connection', 'ingram-sync' ); ?></button>
<div class="ingram-ajax-result" id="ingram-test-oauth"></div>
