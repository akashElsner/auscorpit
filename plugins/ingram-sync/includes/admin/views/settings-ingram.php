<?php
/**
 * Ingram settings tab.
 *
 * @package IngramSync
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$config  = Ingram_Sync_Api::validate_customer_config();
$regions = Ingram_Sync_Settings::get_region_presets();
$market  = $settings['market_region'] ?? 'au';
$mismatch = Ingram_Sync_Api::get_customer_mismatch_info();
?>
<h2><?php esc_html_e( 'Ingram Settings', 'ingram-sync' ); ?></h2>

<div class="notice notice-info inline">
	<p>
		<strong><?php esc_html_e( 'Important:', 'ingram-sync' ); ?></strong>
		<?php esc_html_e( 'Your Customer Number must be the exact value registered in the Ingram Developer Portal when you created your sandbox app — not your invoice account number.', 'ingram-sync' ); ?>
	</p>
	<p>
		<?php
		echo wp_kses_post(
			sprintf(
				/* translators: %s: developer portal link */
				__( 'Find it at %s → Sign In → My Apps → click your Sandbox app → view Customer Number / Account details.', 'ingram-sync' ),
				'<a href="https://developer.ingrammicro.com" target="_blank" rel="noopener noreferrer">developer.ingrammicro.com</a>'
			)
		);
		?>
	</p>
</div>

<?php if ( ! $config['valid'] ) : ?>
	<div class="notice notice-warning inline"><p><?php echo esc_html( $config['message'] ); ?></p></div>
<?php elseif ( ! empty( $config['warnings'] ) ) : ?>
	<div class="notice notice-warning inline"><p><?php echo esc_html( $config['message'] ); ?></p></div>
<?php endif; ?>

<?php if ( ! $mismatch['matches'] && ( $mismatch['token'] || $mismatch['country_token'] ) ) : ?>
	<div class="notice notice-error inline">
		<p>
			<strong><?php esc_html_e( '403 fix:', 'ingram-sync' ); ?></strong>
			<?php
			printf(
				esc_html__( 'Your token is registered for Customer %1$s / Country %2$s but you configured %3$s / %4$s.', 'ingram-sync' ),
				esc_html( $mismatch['token'] ?: '—' ),
				esc_html( $mismatch['country_token'] ?: '—' ),
				esc_html( $mismatch['configured'] ?: '—' ),
				esc_html( $mismatch['country_configured'] ?: '—' )
			);
			?>
		</p>
		<p>
			<button type="button" class="button button-primary ingram-ajax-btn" data-action="sync_from_token">
				<?php esc_html_e( 'Apply Customer Number from Token', 'ingram-sync' ); ?>
			</button>
		</p>
	</div>
<?php endif; ?>

<form method="post" action="">
	<?php wp_nonce_field( 'ingram_sync_save_ingram', 'ingram_sync_nonce' ); ?>
	<input type="hidden" name="ingram_sync_action" value="ingram" />

	<table class="form-table">
		<tr>
			<th><label for="market_region"><?php esc_html_e( 'Market Region', 'ingram-sync' ); ?></label></th>
			<td>
				<select name="market_region" id="market_region">
					<?php foreach ( $regions as $key => $region ) : ?>
						<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $market, $key ); ?>>
							<?php echo esc_html( $region['label'] ); ?>
						</option>
					<?php endforeach; ?>
				</select>
				<p class="description"><?php esc_html_e( 'Sets the default country code and sandbox test SKU for your region.', 'ingram-sync' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><label for="customer_number"><?php esc_html_e( 'Customer Number', 'ingram-sync' ); ?></label></th>
			<td>
				<input type="text" name="customer_number" id="customer_number" class="regular-text" value="<?php echo esc_attr( $settings['customer_number'] ); ?>" placeholder="20-222222" required />
				<p class="description">
					<?php esc_html_e( 'Exact value from Ingram Developer Portal (sandbox app). US format is often XX-XXXXXX. Do not guess — copy from the portal.', 'ingram-sync' ); ?>
				</p>
			</td>
		</tr>
		<tr>
			<th><label for="sender_id"><?php esc_html_e( 'Sender ID', 'ingram-sync' ); ?></label></th>
			<td>
				<input type="text" name="sender_id" id="sender_id" class="regular-text" value="<?php echo esc_attr( $settings['sender_id'] ); ?>" placeholder="MyCompany" required />
				<p class="description"><?php esc_html_e( 'Free-text identifier for your integration (e.g. your company name).', 'ingram-sync' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><label for="country"><?php esc_html_e( 'Country', 'ingram-sync' ); ?></label></th>
			<td>
				<input type="text" name="country" id="country" class="small-text" value="<?php echo esc_attr( $settings['country'] ); ?>" maxlength="2" placeholder="AU" required />
				<p class="description"><?php esc_html_e( '2-letter ISO country code (AU for Australia, US for United States). Must match your Ingram account region.', 'ingram-sync' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><label for="test_part_number"><?php esc_html_e( 'Test Part Number', 'ingram-sync' ); ?></label></th>
			<td>
				<input type="text" name="test_part_number" id="test_part_number" class="regular-text" value="<?php echo esc_attr( $settings['test_part_number'] ?? '1603467' ); ?>" />
				<p class="description">
					<?php esc_html_e( 'Sandbox test SKU. Australia: 1603467 (PCIEX68ADAP). US: TSXML3. See Ingram sandbox docs for your region.', 'ingram-sync' ); ?>
				</p>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Correlation ID', 'ingram-sync' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="correlation_id_auto" value="1" <?php checked( $settings['correlation_id_auto'], 'yes' ); ?> />
					<?php esc_html_e( 'Generate Automatically (new UUID per API request)', 'ingram-sync' ); ?>
				</label>
			</td>
		</tr>
	</table>

	<?php submit_button( __( 'Save Settings', 'ingram-sync' ) ); ?>
</form>

<script>
(function () {
	var presets = <?php echo wp_json_encode( $regions ); ?>;
	var select = document.getElementById('market_region');
	var country = document.getElementById('country');
	var testPart = document.getElementById('test_part_number');

	if (!select || !country || !testPart) {
		return;
	}

	select.addEventListener('change', function () {
		var preset = presets[select.value];
		if (preset && preset.country) {
			country.value = preset.country;
		}
		if (preset && preset.test_part_number) {
			testPart.value = preset.test_part_number;
		}
	});
})();
</script>
