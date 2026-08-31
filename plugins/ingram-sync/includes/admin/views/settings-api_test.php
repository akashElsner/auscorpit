<?php
/**
 * API Test tab.
 *
 * @package IngramSync
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$config   = Ingram_Sync_Api::validate_customer_config();
$is_sandbox = false !== strpos( Ingram_Sync_Settings::get( 'catalog_api_url' ), '/sandbox/' );
$mismatch = Ingram_Sync_Api::get_customer_mismatch_info();
$token_info = Ingram_Sync_Oauth::get_token_customer_info();
?>
<h2><?php esc_html_e( 'API Test', 'ingram-sync' ); ?></h2>
<p class="description"><?php esc_html_e( 'Test each API endpoint independently.', 'ingram-sync' ); ?></p>

<div class="ingram-sync-section" style="margin-bottom:20px;">
	<h3><?php esc_html_e( 'Current Configuration', 'ingram-sync' ); ?></h3>
	<table class="widefat striped">
		<tr>
			<th><?php esc_html_e( 'Environment', 'ingram-sync' ); ?></th>
			<td><?php echo $is_sandbox ? esc_html__( 'Sandbox', 'ingram-sync' ) : esc_html__( 'Production', 'ingram-sync' ); ?></td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Customer Number', 'ingram-sync' ); ?></th>
			<td><?php echo esc_html( Ingram_Sync_Api::get_customer_number() ?: __( 'Not set', 'ingram-sync' ) ); ?></td>
		</tr>
		<?php if ( ! empty( $mismatch['token'] ) ) : ?>
		<tr>
			<th><?php esc_html_e( 'Token Customer Number', 'ingram-sync' ); ?></th>
			<td>
				<code><?php echo esc_html( $mismatch['token'] ); ?></code>
				<?php if ( ! $mismatch['matches'] ) : ?>
					<span class="ingram-log-level ingram-log-error"><?php esc_html_e( 'Mismatch', 'ingram-sync' ); ?></span>
				<?php else : ?>
					<span class="ingram-log-level ingram-log-success"><?php esc_html_e( 'Match', 'ingram-sync' ); ?></span>
				<?php endif; ?>
			</td>
		</tr>
		<?php endif; ?>
		<tr>
			<th><?php esc_html_e( 'Test Part Number', 'ingram-sync' ); ?></th>
			<td><?php echo esc_html( Ingram_Sync_Api::get_test_part_number() ); ?></td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Sender ID', 'ingram-sync' ); ?></th>
			<td><?php echo $settings['sender_id'] ? esc_html( $settings['sender_id'] ) : '<em>' . esc_html__( 'Not set', 'ingram-sync' ) . '</em>'; ?></td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Country', 'ingram-sync' ); ?></th>
			<td>
				<?php echo esc_html( Ingram_Sync_Api::get_country_code() ?: '—' ); ?>
				<?php if ( ! empty( $mismatch['country_token'] ) && strcasecmp( $mismatch['country_configured'], $mismatch['country_token'] ) !== 0 ) : ?>
					<span class="ingram-log-level ingram-log-error"><?php printf( esc_html__( 'Token expects: %s', 'ingram-sync' ), esc_html( $mismatch['country_token'] ) ); ?></span>
				<?php endif; ?>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Customer Headers', 'ingram-sync' ); ?></th>
			<td>
				<?php if ( $config['valid'] && empty( $config['warnings'] ) ) : ?>
					<span class="ingram-log-level ingram-log-success"><?php esc_html_e( 'Valid', 'ingram-sync' ); ?></span>
				<?php elseif ( $config['valid'] ) : ?>
					<span class="ingram-log-level ingram-log-warning"><?php esc_html_e( 'Warning', 'ingram-sync' ); ?></span>
					<p><?php echo esc_html( $config['message'] ); ?></p>
				<?php else : ?>
					<span class="ingram-log-level ingram-log-error"><?php esc_html_e( 'Invalid', 'ingram-sync' ); ?></span>
					<p><?php echo esc_html( $config['message'] ); ?></p>
				<?php endif; ?>
			</td>
		</tr>
	</table>
	<?php if ( ! $config['valid'] ) : ?>
		<p>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=ingram-sync-settings&tab=ingram' ) ); ?>" class="button button-secondary">
				<?php esc_html_e( 'Configure Ingram Settings', 'ingram-sync' ); ?>
			</a>
		</p>
	<?php endif; ?>
</div>

<?php if ( ! $mismatch['matches'] && ( $mismatch['token'] || $mismatch['country_token'] ) ) : ?>
	<div class="notice notice-error inline">
		<p>
			<strong><?php esc_html_e( 'Customer mismatch detected!', 'ingram-sync' ); ?></strong>
			<?php esc_html_e( 'Your configured Customer Number/Country does not match what your OAuth token is registered for. This causes 403 errors.', 'ingram-sync' ); ?>
		</p>
		<p>
			<button type="button" class="button button-primary ingram-ajax-btn" data-action="sync_from_token">
				<?php esc_html_e( 'Fix — Apply Customer Number from Token', 'ingram-sync' ); ?>
			</button>
		</p>
	</div>
<?php elseif ( $token_info && empty( $mismatch['token'] ) && empty( $mismatch['country_token'] ) ) : ?>
	<div class="notice notice-warning inline">
		<p>
			<strong><?php esc_html_e( 'Could not read customer number from token.', 'ingram-sync' ); ?></strong>
			<?php esc_html_e( 'Enter the exact Customer Number from developer.ingrammicro.com → My Apps → Sandbox app. It is NOT your invoice account number.', 'ingram-sync' ); ?>
		</p>
	</div>
<?php endif; ?>

<?php if ( $config['valid'] ) : ?>
	<div class="notice notice-info inline"><p>
		<?php esc_html_e( 'OAuth only validates your Client ID/Secret. Catalog, Price, and Details require the exact Customer Number from your Ingram Developer Portal sandbox app, plus the correct Country for your region.', 'ingram-sync' ); ?>
	</p></div>
	<?php if ( 'US' === strtoupper( $settings['country'] ?? '' ) ) : ?>
		<div class="notice notice-warning inline"><p>
			<strong><?php esc_html_e( '403 error?', 'ingram-sync' ); ?></strong>
			<?php esc_html_e( 'If you are an Australian/NZ reseller, change Market Region to Australia / New Zealand and Country to AU in Settings → Ingram. Also use Customer Number from developer.ingrammicro.com, not your invoice number.', 'ingram-sync' ); ?>
		</p></div>
	<?php endif; ?>
<?php endif; ?>

<table class="form-table ingram-api-test-table">
	<tr>
		<th><?php esc_html_e( 'OAuth', 'ingram-sync' ); ?></th>
		<td>
			<button type="button" class="button ingram-ajax-btn" data-action="test_oauth"><?php esc_html_e( 'Test', 'ingram-sync' ); ?></button>
			<span class="ingram-ajax-result" data-for="test_oauth"></span>
		</td>
	</tr>
	<tr>
		<th><?php esc_html_e( 'Catalog', 'ingram-sync' ); ?></th>
		<td>
			<button type="button" class="button ingram-ajax-btn" data-action="test_catalog" <?php disabled( ! $config['valid'] ); ?>><?php esc_html_e( 'Test', 'ingram-sync' ); ?></button>
			<span class="ingram-ajax-result" data-for="test_catalog"></span>
		</td>
	</tr>
	<tr>
		<th><?php esc_html_e( 'Price', 'ingram-sync' ); ?></th>
		<td>
			<button type="button" class="button ingram-ajax-btn" data-action="test_price" <?php disabled( ! $config['valid'] ); ?>><?php esc_html_e( 'Test', 'ingram-sync' ); ?></button>
			<span class="ingram-ajax-result" data-for="test_price"></span>
		</td>
	</tr>
	<tr>
		<th><?php esc_html_e( 'Details', 'ingram-sync' ); ?></th>
		<td>
			<button type="button" class="button ingram-ajax-btn" data-action="test_details" <?php disabled( ! $config['valid'] ); ?>><?php esc_html_e( 'Test', 'ingram-sync' ); ?></button>
			<span class="ingram-ajax-result" data-for="test_details"></span>
		</td>
	</tr>
</table>
