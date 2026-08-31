<?php
/**
 * Main plugin bootstrap class.
 *
 * @package ZohoInventoryImport\Includes
 */

namespace ZohoInventoryImport\Includes;

use ZohoInventoryImport\Admin\Admin_UI;
use ZohoInventoryImport\Api\Zoho_API_Handler;

defined( 'ABSPATH' ) || exit;

/**
 * Class Plugin
 */
final class Plugin {

	/** @var Plugin|null */
	private static ?Plugin $instance = null;

	/** @var Sync_Bridge */
	private Sync_Bridge $bridge;

	/** @var Import_Settings */
	private Import_Settings $settings;

	/** @var Logger */
	private Logger $logger;

	/** @var Zoho_API_Handler */
	private Zoho_API_Handler $api;

	/** @var Admin_UI|null */
	private ?Admin_UI $admin = null;

	public static function instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {
		$this->load_dependencies();
		$this->bridge   = new Sync_Bridge();
		$this->settings = new Import_Settings();
		$this->logger   = new Logger();
		$this->api      = new Zoho_API_Handler( $this->bridge, $this->settings );

		if ( is_admin() ) {
			$this->admin = new Admin_UI( $this->bridge, $this->settings, $this->api, $this->logger );
		}
	}

	private function load_dependencies(): void {
		require_once ZOHO_INV_IMPORT_PATH . 'includes/class-sync-bridge.php';
		require_once ZOHO_INV_IMPORT_PATH . 'includes/class-import-settings.php';
		require_once ZOHO_INV_IMPORT_PATH . 'includes/class-field-mapping.php';
		require_once ZOHO_INV_IMPORT_PATH . 'includes/class-logger.php';
		require_once ZOHO_INV_IMPORT_PATH . 'api/class-zoho-api-handler.php';
		require_once ZOHO_INV_IMPORT_PATH . 'import/class-csv-importer.php';
		require_once ZOHO_INV_IMPORT_PATH . 'admin/class-admin-ui.php';
	}

	public function bridge(): Sync_Bridge {
		return $this->bridge;
	}

	public function settings(): Import_Settings {
		return $this->settings;
	}

	public function logger(): Logger {
		return $this->logger;
	}

	public function api(): Zoho_API_Handler {
		return $this->api;
	}
}
