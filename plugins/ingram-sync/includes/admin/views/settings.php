<?php
/**
 * Settings view with tabs.
 *
 * @package IngramSync
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$tab = $tab ?? 'authentication';
Ingram_Sync_Admin_Pages::render_settings_tabs( $tab );
?>

<div class="ingram-sync-settings-content">
	<?php
	$tab_file = INGRAM_SYNC_PLUGIN_DIR . 'includes/admin/views/settings-' . $tab . '.php';
	if ( file_exists( $tab_file ) ) {
		include $tab_file;
	} else {
		echo '<p>' . esc_html__( 'Settings tab not found.', 'ingram-sync' ) . '</p>';
	}
	?>
</div>
