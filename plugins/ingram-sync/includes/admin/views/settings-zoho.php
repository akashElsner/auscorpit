<?php
/**
 * Zoho settings tab.
 *
 * Matches Dynamic Supplies Datafeed: prefer Zoho Inventory WooCommerce Sync OAuth.
 * Refresh / access tokens are not required when that connection is active.
 *
 * @package IngramSync
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$zoho_info       = Ingram_Sync_Zoho_Bridge::connection_info();
$zoho_configured = $zoho_info['configured'];
$zoho_wc_sync    = 'wc_sync' === $zoho_info['source'];
$data_centres    = Ingram_Sync_Zoho_Bridge::get_data_centres();
?>
<h2><?php esc_html_e( 'Zoho Connection', 'ingram-sync' ); ?></h2>

<p>
	<?php if ( $zoho_configured ) : ?>
		<span class="dashicons dashicons-yes-alt" style="color:#00a32a;"></span>
		<?php
		printf(
			/* translators: 1: connection label, 2: org id, 3: data centre */
			esc_html__( 'Connected via %1$s · Org: %2$s · DC: %3$s', 'ingram-sync' ),
			esc_html( $zoho_info['label'] ),
			esc_html( $zoho_info['org_id'] ?: '?' ),
			esc_html( $zoho_info['dc'] )
		);
		?>
	<?php else : ?>
		<span class="dashicons dashicons-warning" style="color:#d63638;"></span>
		<?php esc_html_e( 'Not connected.', 'ingram-sync' ); ?>
	<?php endif; ?>
</p>

<?php if ( $zoho_wc_sync ) : ?>
	<div class="notice notice-success inline" style="margin:12px 0;">
		<p>
			<?php
			printf(
				/* translators: %s: organisation ID */
				esc_html__( 'Using your existing Zoho Inventory WooCommerce Sync connection (Org ID %s). Refresh token and access token are not required here.', 'ingram-sync' ),
				'<code>' . esc_html( $zoho_info['org_id'] ) . '</code>'
			);
			?>
			<?php
			printf(
				/* translators: %s: settings link */
				esc_html__( 'Manage credentials in %s.', 'ingram-sync' ),
				'<a href="' . esc_url( Ingram_Sync_Zoho_Bridge::get_wc_sync_settings_url() ) . '">' . esc_html__( 'Zoho Inventory Sync → Zoho Connection', 'ingram-sync' ) . '</a>'
			);
			?>
		</p>
	</div>
<?php else : ?>
	<div class="notice notice-info inline" style="margin:12px 0;">
		<p>
			<?php if ( Ingram_Sync_Zoho_Bridge::is_wc_sync_available() ) : ?>
				<?php
				printf(
					/* translators: %s: settings link */
					esc_html__( 'Recommended: connect %s — this plugin will reuse that OAuth connection automatically (no refresh or access token needed here).', 'ingram-sync' ),
					'<a href="' . esc_url( Ingram_Sync_Zoho_Bridge::get_wc_sync_settings_url() ) . '"><em>' . esc_html__( 'Zoho Inventory WooCommerce Sync', 'ingram-sync' ) . '</em></a>'
				);
				?>
			<?php else : ?>
				<?php
				esc_html_e( 'Recommended: install and connect Zoho Inventory WooCommerce Sync — this plugin will reuse that OAuth connection automatically. Or enter fallback credentials below (Self Client from api-console.zoho.com). Access token is never required; it is generated automatically from the refresh token.', 'ingram-sync' );
				?>
			<?php endif; ?>
		</p>
	</div>
<?php endif; ?>

<form method="post" action="">
	<?php wp_nonce_field( 'ingram_sync_save_zoho', 'ingram_sync_nonce' ); ?>
	<input type="hidden" name="ingram_sync_action" value="zoho" />

	<table class="form-table">
		<tr>
			<th><?php esc_html_e( 'Enable Zoho Sync', 'ingram-sync' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="zoho_sync_enabled" value="1" <?php checked( $settings['zoho_sync_enabled'], 'yes' ); ?> />
					<?php esc_html_e( 'Yes', 'ingram-sync' ); ?>
				</label>
			</td>
		</tr>
	</table>

	<?php if ( ! $zoho_wc_sync ) : ?>
		<h3><?php esc_html_e( 'Fallback credentials (optional)', 'ingram-sync' ); ?></h3>
		<p class="description">
			<?php esc_html_e( 'Only needed if Zoho Inventory WooCommerce Sync is not connected. Do not enter an access token — it is obtained automatically.', 'ingram-sync' ); ?>
		</p>

		<table class="form-table">
			<tr>
				<th><label for="zoho_organization_id"><?php esc_html_e( 'Organisation ID', 'ingram-sync' ); ?></label></th>
				<td>
					<input type="text" name="zoho_organization_id" id="zoho_organization_id" class="regular-text" value="<?php echo esc_attr( $settings['zoho_organization_id'] ); ?>" />
					<p class="description"><?php esc_html_e( 'Zoho Inventory → Settings → Organisation Profile', 'ingram-sync' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="zoho_client_id"><?php esc_html_e( 'Client ID', 'ingram-sync' ); ?></label></th>
				<td><input type="text" name="zoho_client_id" id="zoho_client_id" class="regular-text" value="<?php echo esc_attr( $settings['zoho_client_id'] ); ?>" autocomplete="off" /></td>
			</tr>
			<tr>
				<th><label for="zoho_client_secret"><?php esc_html_e( 'Client Secret', 'ingram-sync' ); ?></label></th>
				<td>
					<input type="password" name="zoho_client_secret" id="zoho_client_secret" class="regular-text" value="" placeholder="<?php echo $settings['zoho_client_secret'] ? esc_attr( Ingram_Sync_Security::mask_secret( $settings['zoho_client_secret'] ) ) : ''; ?>" autocomplete="new-password" />
					<p class="description"><?php esc_html_e( 'Leave blank to keep existing secret.', 'ingram-sync' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="zoho_refresh_token"><?php esc_html_e( 'Refresh Token', 'ingram-sync' ); ?></label></th>
				<td>
					<input type="password" name="zoho_refresh_token" id="zoho_refresh_token" class="regular-text" value="" placeholder="<?php echo $settings['zoho_refresh_token'] ? esc_attr( Ingram_Sync_Security::mask_secret( $settings['zoho_refresh_token'] ) ) : ''; ?>" autocomplete="new-password" />
					<p class="description"><?php esc_html_e( 'Self Client refresh token only. Access token is not required and is generated automatically.', 'ingram-sync' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="zoho_dc"><?php esc_html_e( 'Data Centre', 'ingram-sync' ); ?></label></th>
				<td>
					<select name="zoho_dc" id="zoho_dc">
						<?php foreach ( $data_centres as $val => $dc ) : ?>
							<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $settings['zoho_dc'] ?? 'com', $val ); ?>>
								<?php echo esc_html( $dc['label'] ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
		</table>
	<?php else : ?>
		<input type="hidden" name="zoho_organization_id" value="<?php echo esc_attr( $settings['zoho_organization_id'] ); ?>" />
		<input type="hidden" name="zoho_client_id" value="<?php echo esc_attr( $settings['zoho_client_id'] ); ?>" />
		<input type="hidden" name="zoho_dc" value="<?php echo esc_attr( $settings['zoho_dc'] ?? 'com' ); ?>" />
	<?php endif; ?>

	<?php submit_button( __( 'Save Settings', 'ingram-sync' ) ); ?>
</form>

<?php if ( ! $zoho_wc_sync && Ingram_Sync_Zoho_Bridge::has_direct_credentials() ) : ?>
<form method="post" action="" style="display:inline-block;">
	<?php wp_nonce_field( 'ingram_sync_save_zoho_token', 'ingram_sync_nonce' ); ?>
	<input type="hidden" name="ingram_sync_action" value="zoho_token" />
	<?php submit_button( __( 'Test / Refresh Token', 'ingram-sync' ), 'secondary', 'submit', false ); ?>
</form>
<?php endif; ?>
