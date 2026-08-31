<?php
/**
 * Admin view: Zoho OAuth connection tab.
 *
 * @package ZohoInventorySync\Admin
 */

defined( 'ABSPATH' ) || exit;

$oauth          = \ZohoInventorySync\Includes\Plugin::instance()->oauth;
$is_connected   = $oauth->is_connected();
$has_creds      = $oauth->has_credentials();
$org_id         = $oauth->get_organization_id();
$current_dc     = $oauth->get_data_centre();
$current_cid    = $oauth->get_client_id();
$data_centres   = \ZohoInventorySync\Includes\OAuth_Manager::get_data_centres();

$oauth_success  = get_transient( 'zoho_inventory_sync_oauth_success' );
if ( $oauth_success ) {
	delete_transient( 'zoho_inventory_sync_oauth_success' );
}

$oauth_error = get_transient( 'zoho_inventory_sync_oauth_error' );
if ( $oauth_error ) {
	delete_transient( 'zoho_inventory_sync_oauth_error' );
}
?>

<!-- ================================================================
     Step 1 — API Credentials + Data Centre
     ================================================================ -->
<div class="zoho-card">
	<h2><?php esc_html_e( 'Step 1 — Zoho API Credentials', 'zoho-inventory-sync' ); ?></h2>

	<p class="description">
		<?php
		printf(
			/* translators: %s: link to Zoho API Console */
			wp_kses(
				__( 'Create a <strong>Server-based OAuth 2.0</strong> application in the %s. Copy the <em>Redirect URI</em> below into your Zoho application settings.', 'zoho-inventory-sync' ),
				[ 'strong' => [], 'em' => [] ]
			),
			'<a href="https://api-console.zoho.com/" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Zoho API Console', 'zoho-inventory-sync' ) . '</a>'
		);
		?>
	</p>

	<!-- Redirect URI copy box -->
	<div class="zoho-redirect-uri-box" style="display:flex;align-items:center;gap:8px;margin:12px 0 18px;">
		<label style="white-space:nowrap;font-weight:600;"><?php esc_html_e( 'Redirect URI:', 'zoho-inventory-sync' ); ?></label>
		<code id="zoho-redirect-uri" style="flex:1;padding:6px 10px;background:#f6f7f7;border:1px solid #c3c4c7;border-radius:3px;word-break:break-all;">
			<?php echo esc_url( $oauth->get_redirect_uri() ); ?>
		</code>
		<button type="button" class="button button-secondary zoho-copy-btn" data-target="zoho-redirect-uri">
			<?php esc_html_e( 'Copy', 'zoho-inventory-sync' ); ?>
		</button>
	</div>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="zoho_inventory_sync_save_credentials">
		<?php wp_nonce_field( 'zoho_inv_save_credentials' ); ?>

		<table class="form-table" role="presentation">

			<!-- Data Centre -->
			<tr>
				<th scope="row">
					<label for="data_centre"><?php esc_html_e( 'Data Centre / Region', 'zoho-inventory-sync' ); ?></label>
				</th>
				<td>
					<select id="data_centre" name="data_centre" class="regular-text">
						<?php foreach ( $data_centres as $dc_key => $dc ) : ?>
							<option value="<?php echo esc_attr( $dc_key ); ?>"
								<?php selected( $current_dc, $dc_key ); ?>>
								<?php echo esc_html( $dc['label'] ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<p class="description">
						<?php esc_html_e( 'Choose the Zoho data centre where your account is registered. Changing this after connecting will clear your tokens and require re-authentication.', 'zoho-inventory-sync' ); ?>
					</p>
					<?php if ( $has_creds ) : ?>
						<p class="description" style="margin-top:4px;">
							<?php
							printf(
								/* translators: %s: current API base URL */
								esc_html__( 'Current Inventory API endpoint: %s', 'zoho-inventory-sync' ),
								'<code>' . esc_html( $oauth->get_inventory_api_base_url() ) . '</code>'
							);
							?>
						</p>
					<?php endif; ?>
				</td>
			</tr>

			<!-- Client ID -->
			<tr>
				<th scope="row">
					<label for="client_id"><?php esc_html_e( 'Client ID', 'zoho-inventory-sync' ); ?></label>
				</th>
				<td>
					<input
						type="text"
						id="client_id"
						name="client_id"
						value="<?php echo esc_attr( $current_cid ); ?>"
						class="regular-text"
						autocomplete="off"
						placeholder="<?php esc_attr_e( 'e.g. 1000.XXXXXXXX', 'zoho-inventory-sync' ); ?>"
						<?php echo $has_creds ? '' : 'required'; ?>
					>
					<?php if ( $has_creds ) : ?>
						<p class="description">
							<?php esc_html_e( 'Leave unchanged to keep the current Client ID.', 'zoho-inventory-sync' ); ?>
						</p>
					<?php endif; ?>
				</td>
			</tr>

			<!-- Client Secret -->
			<tr>
				<th scope="row">
					<label for="client_secret"><?php esc_html_e( 'Client Secret', 'zoho-inventory-sync' ); ?></label>
				</th>
				<td>
					<input
						type="password"
						id="client_secret"
						name="client_secret"
						class="regular-text"
						autocomplete="new-password"
						placeholder="<?php echo $has_creds ? esc_attr__( '●●●●●●●● (leave blank to keep current)', 'zoho-inventory-sync' ) : esc_attr__( 'Paste your Client Secret', 'zoho-inventory-sync' ); ?>"
						<?php echo $has_creds ? '' : 'required'; ?>
					>
					<?php if ( $has_creds ) : ?>
						<p class="description">
							<?php esc_html_e( 'Leave blank to keep the stored secret. Enter a new value only if you have regenerated it in Zoho.', 'zoho-inventory-sync' ); ?>
						</p>
					<?php else : ?>
						<p class="description">
							<?php esc_html_e( 'Required on initial setup.', 'zoho-inventory-sync' ); ?>
						</p>
					<?php endif; ?>
				</td>
			</tr>

		</table>

		<?php
		submit_button(
			$has_creds
				? __( 'Update Credentials', 'zoho-inventory-sync' )
				: __( 'Save Credentials', 'zoho-inventory-sync' ),
			'secondary'
		);
		?>
	</form>
</div>

<!-- ================================================================
     Step 2 — Connect (OAuth authorization)
     ================================================================ -->
<div class="zoho-card">
	<h2><?php esc_html_e( 'Step 2 — Connect Your Zoho Account', 'zoho-inventory-sync' ); ?></h2>

	<?php if ( $oauth_success ) : ?>
		<div class="notice notice-success inline">
			<p><?php esc_html_e( 'Successfully connected to Zoho Inventory!', 'zoho-inventory-sync' ); ?></p>
		</div>
	<?php endif; ?>

	<?php if ( $oauth_error ) : ?>
		<div class="notice notice-error inline">
			<p><?php echo esc_html( $oauth_error ); ?></p>
		</div>
	<?php endif; ?>

	<?php if ( $is_connected ) : ?>

		<p class="zoho-status zoho-connected">
			<span class="dashicons dashicons-yes-alt"></span>
			<?php esc_html_e( 'Connected to Zoho Inventory.', 'zoho-inventory-sync' ); ?>
		</p>
		<button type="button" id="zoho-disconnect-btn" class="button button-secondary">
			<?php esc_html_e( 'Disconnect', 'zoho-inventory-sync' ); ?>
		</button>

	<?php elseif ( $has_creds ) : ?>

		<p class="zoho-status zoho-disconnected">
			<span class="dashicons dashicons-no"></span>
			<?php esc_html_e( 'Not connected.', 'zoho-inventory-sync' ); ?>
		</p>
		<p class="description" style="margin-bottom:12px;">
			<?php esc_html_e( 'Click the button below to be redirected to Zoho for authorization. You will be returned here automatically.', 'zoho-inventory-sync' ); ?>
		</p>
		<a href="<?php echo esc_url( $oauth->get_authorization_url() ); ?>" class="button button-primary">
			<?php esc_html_e( 'Connect to Zoho Inventory', 'zoho-inventory-sync' ); ?>
		</a>

	<?php else : ?>

		<p class="zoho-status zoho-disconnected">
			<span class="dashicons dashicons-no"></span>
			<?php esc_html_e( 'Not connected.', 'zoho-inventory-sync' ); ?>
		</p>
		<p class="description">
			<?php esc_html_e( 'Please complete Step 1 (save your Client ID and Secret) before connecting.', 'zoho-inventory-sync' ); ?>
		</p>

	<?php endif; ?>
</div>

<!-- ================================================================
     Step 3 — Select Organisation (only shown when connected)
     ================================================================ -->
<?php if ( $is_connected ) : ?>
<div class="zoho-card">
	<h2><?php esc_html_e( 'Step 3 — Select Organisation', 'zoho-inventory-sync' ); ?></h2>

	<?php if ( $org_id ) : ?>
		<p>
			<?php esc_html_e( 'Currently selected Organisation ID:', 'zoho-inventory-sync' ); ?>
			<strong><?php echo esc_html( $org_id ); ?></strong>
		</p>
	<?php else : ?>
		<p class="description" style="color:#c62828;">
			<?php esc_html_e( 'No organisation selected yet. Load and choose one below.', 'zoho-inventory-sync' ); ?>
		</p>
	<?php endif; ?>

	<button type="button" id="zoho-load-orgs-btn" class="button button-secondary">
		<?php esc_html_e( 'Load Organisations', 'zoho-inventory-sync' ); ?>
	</button>
	<span id="zoho-orgs-loading" style="display:none;margin-left:8px;">
		<span class="zoho-spinner"></span>
		<?php esc_html_e( 'Fetching…', 'zoho-inventory-sync' ); ?>
	</span>

	<div id="zoho-orgs-wrapper" style="margin-top:16px; display:none;">
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="zoho_inventory_sync_save_org">
			<?php wp_nonce_field( 'zoho_inv_save_org' ); ?>

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">
						<label for="zoho-org-select"><?php esc_html_e( 'Organisation', 'zoho-inventory-sync' ); ?></label>
					</th>
					<td>
						<select name="organization_id" id="zoho-org-select" class="regular-text" required>
							<option value=""><?php esc_html_e( '— select an organisation —', 'zoho-inventory-sync' ); ?></option>
						</select>
					</td>
				</tr>
			</table>

			<?php submit_button( __( 'Save Organisation', 'zoho-inventory-sync' ), 'primary', 'save-org', false ); ?>
		</form>
	</div>
</div>
<?php endif; ?>
