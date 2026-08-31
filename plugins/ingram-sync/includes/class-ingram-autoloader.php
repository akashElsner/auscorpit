<?php
/**
 * PSR-4 style autoloader for Ingram Sync classes.
 *
 * @package IngramSync
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Ingram_Sync_Autoloader
 */
class Ingram_Sync_Autoloader {

	/**
	 * Register the autoloader.
	 */
	public static function register() {
		spl_autoload_register( array( __CLASS__, 'autoload' ) );
	}

	/**
	 * Autoload class files.
	 *
	 * @param string $class Class name.
	 */
	public static function autoload( $class ) {
		if ( 'Ingram_Sync' === $class ) {
			require_once INGRAM_SYNC_PLUGIN_DIR . 'includes/class-ingram-sync.php';
			return;
		}

		if ( 0 !== strpos( $class, 'Ingram_Sync_' ) ) {
			return;
		}

		$relative = strtolower( str_replace( array( 'Ingram_Sync_', '_' ), array( '', '-' ), $class ) );
		$paths    = array(
			INGRAM_SYNC_PLUGIN_DIR . 'includes/class-' . $relative . '.php',
			INGRAM_SYNC_PLUGIN_DIR . 'includes/core/class-' . $relative . '.php',
			INGRAM_SYNC_PLUGIN_DIR . 'includes/api/class-' . $relative . '.php',
			INGRAM_SYNC_PLUGIN_DIR . 'includes/admin/class-' . $relative . '.php',
			INGRAM_SYNC_PLUGIN_DIR . 'includes/sync/class-' . $relative . '.php',
			INGRAM_SYNC_PLUGIN_DIR . 'includes/ajax/class-' . $relative . '.php',
		);

		foreach ( $paths as $path ) {
			if ( file_exists( $path ) ) {
				require_once $path;
				return;
			}
		}
	}
}
