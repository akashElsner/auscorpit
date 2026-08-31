<?php
/**
 * Plugin settings management.
 *
 * @package IngramSync
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Ingram_Sync_Settings
 */
class Ingram_Sync_Settings {

	const OPTION_PREFIX = 'ingram_';

	/**
	 * Sensitive options stored encrypted.
	 *
	 * @var string[]
	 */
	private static $encrypted_keys = array(
		'client_secret',
		'access_token',
		'zoho_client_secret',
		'zoho_refresh_token',
		'zoho_access_token',
	);

	/**
	 * Default option values.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_defaults() {
		return array(
			// Authentication.
			'grant_type'     => 'client_credentials',
			'client_id'      => '',
			'client_secret'  => '',
			'access_token'   => '',
			'token_expiry'   => 0,

			// API URLs.
			'oauth_url'              => 'https://api.ingrammicro.com/oauth/oauth20/token',
			'catalog_api_url'        => 'https://api.ingrammicro.com/sandbox/resellers/v6/catalog',
			'price_api_url'          => 'https://api.ingrammicro.com/sandbox/resellers/v6/catalog/priceandavailability?includeAvailability=true&includePricing=true&includeProductAttributes=true',
			'details_api_url'        => 'https://api.ingrammicro.com/sandbox/resellers/v6/catalog/details/',

			// Ingram.
			'customer_number'          => '',
			'sender_id'                => '',
			'market_region'            => 'au',
			'country'                  => 'AU',
			'correlation_id_auto'      => 'yes',
			'test_part_number'         => '1603467',

			// Sync.
			'batch_size'               => 50,
			'pause_between_calls'      => 1,
			'max_retry'                => 5,
			'timeout'                  => 60,
			'min_sync_interval_hours'  => 4,
			'last_full_sync_completed' => 0,

			// WooCommerce.
			'wc_sync_enabled'          => 'yes',
			'wc_products_per_batch'    => 100,
			'wc_update_existing'       => 'yes',
			'wc_delete_missing'        => 'no',

			// Zoho (prefer Zoho Inventory WooCommerce Sync OAuth; these are fallback only).
			'zoho_sync_enabled'        => 'yes',
			'zoho_organization_id'     => '',
			'zoho_client_id'           => '',
			'zoho_client_secret'       => '',
			'zoho_refresh_token'       => '',
			'zoho_access_token'        => '',
			'zoho_token_expiry'        => 0,
			'zoho_dc'                  => 'com',

			// Scheduler.
			'scheduler_enabled'        => 'yes',
			'scheduler_frequency'      => 'daily',
			'scheduler_time'           => '02:00',
			'scheduler_running'        => 'no',
			'notify_on_failure'        => 'no',
			'last_sync_failure'        => array(),

			// Stats (runtime).
			'stats_completed'          => 0,
			'stats_failed'             => 0,
			'stats_running'            => 0,
			'last_token_generated'     => 0,
		);
	}

	/**
	 * Set default options on activation.
	 */
	public static function set_defaults() {
		foreach ( self::get_defaults() as $key => $value ) {
			$option = self::OPTION_PREFIX . $key;
			if ( false === get_option( $option ) ) {
				add_option( $option, $value );
			}
		}
	}

	/**
	 * Get a setting value.
	 *
	 * @param string $key     Setting key without prefix.
	 * @param mixed  $default Default value.
	 * @return mixed
	 */
	public static function get( $key, $default = '' ) {
		$value = get_option( self::OPTION_PREFIX . $key, null );

		if ( null === $value ) {
			$defaults = self::get_defaults();
			$value    = isset( $defaults[ $key ] ) ? $defaults[ $key ] : $default;
		}

		if ( in_array( $key, self::$encrypted_keys, true ) && ! empty( $value ) ) {
			$decrypted = Ingram_Sync_Security::decrypt( $value );
			if ( false === $decrypted ) {
				// Never fall back to the raw (still-encrypted) value — that would
				// silently feed garbage into API calls as if it were the real secret.
				Ingram_Sync_Logger::log(
					'Settings',
					sprintf( 'Failed to decrypt "%s" — value may be corrupted or WordPress salts changed. Please re-enter this credential.', $key ),
					Ingram_Sync_Logger::LEVEL_ERROR
				);
				return '';
			}
			return $decrypted;
		}

		return $value;
	}

	/**
	 * Update a setting value.
	 *
	 * @param string $key   Setting key without prefix.
	 * @param mixed  $value Value to store.
	 * @return bool
	 */
	public static function update( $key, $value ) {
		if ( in_array( $key, self::$encrypted_keys, true ) && ! empty( $value ) ) {
			$encrypted = Ingram_Sync_Security::encrypt( $value );
			if ( false !== $encrypted ) {
				$value = $encrypted;
			}
		}

		return update_option( self::OPTION_PREFIX . $key, $value );
	}

	/**
	 * Delete a setting.
	 *
	 * @param string $key Setting key without prefix.
	 * @return bool
	 */
	public static function delete( $key ) {
		return delete_option( self::OPTION_PREFIX . $key );
	}

	/**
	 * Get all settings for admin display (masks secrets).
	 *
	 * @param bool $mask_secrets Whether to mask sensitive values.
	 * @return array<string, mixed>
	 */
	public static function get_all( $mask_secrets = true ) {
		$settings = array();
		foreach ( array_keys( self::get_defaults() ) as $key ) {
			$value = self::get( $key );
			if ( $mask_secrets && in_array( $key, self::$encrypted_keys, true ) && ! empty( $value ) ) {
				$value = str_repeat( '*', min( 12, strlen( $value ) ) );
			}
			$settings[ $key ] = $value;
		}
		return $settings;
	}

	/**
	 * Check if access token is expired.
	 *
	 * @return bool
	 */
	public static function is_token_expired() {
		$expiry = (int) self::get( 'token_expiry', 0 );
		if ( $expiry <= 0 ) {
			return true;
		}
		// Refresh 5 minutes before expiry.
		return time() >= ( $expiry - 300 );
	}

	/**
	 * Check if Zoho access token is expired.
	 *
	 * @return bool
	 */
	public static function is_zoho_token_expired() {
		$expiry = (int) self::get( 'zoho_token_expiry', 0 );
		if ( $expiry <= 0 ) {
			return true;
		}
		return time() >= ( $expiry - 300 );
	}

	/**
	 * Sanitize and save settings from POST array.
	 *
	 * @param array<string, mixed> $post Posted settings.
	 * @param string               $section Settings section.
	 * @return array<string, string> Errors keyed by field.
	 */
	public static function save_from_post( $post, $section ) {
		$errors = array();

		switch ( $section ) {
			case 'authentication':
				self::update( 'grant_type', sanitize_text_field( $post['grant_type'] ?? 'client_credentials' ) );
				self::update( 'client_id', sanitize_text_field( $post['client_id'] ?? '' ) );
				if ( ! empty( $post['client_secret'] ) ) {
					self::update( 'client_secret', sanitize_text_field( $post['client_secret'] ) );
				}
				break;

			case 'api_urls':
				self::update( 'oauth_url', esc_url_raw( $post['oauth_url'] ?? '' ) );
				self::update( 'catalog_api_url', esc_url_raw( $post['catalog_api_url'] ?? '' ) );
				self::update( 'price_api_url', esc_url_raw( $post['price_api_url'] ?? '' ) );
				self::update( 'details_api_url', esc_url_raw( $post['details_api_url'] ?? '' ) );
				break;

			case 'ingram':
				self::update( 'customer_number', sanitize_text_field( trim( $post['customer_number'] ?? '' ) ) );
				self::update( 'sender_id', sanitize_text_field( trim( $post['sender_id'] ?? '' ) ) );
				self::update( 'market_region', sanitize_text_field( $post['market_region'] ?? 'custom' ) );
				self::update( 'correlation_id_auto', isset( $post['correlation_id_auto'] ) ? 'yes' : 'no' );
				self::update( 'test_part_number', sanitize_text_field( trim( $post['test_part_number'] ?? '1603467' ) ) );

				$region = self::get_region_presets();
				$market = sanitize_text_field( $post['market_region'] ?? 'custom' );
				if ( isset( $region[ $market ] ) && 'custom' !== $market ) {
					self::update( 'country', $region[ $market ]['country'] );
					if ( empty( trim( $post['test_part_number'] ?? '' ) ) ) {
						self::update( 'test_part_number', $region[ $market ]['test_part_number'] );
					}
				} else {
					self::update( 'country', strtoupper( sanitize_text_field( trim( $post['country'] ?? 'AU' ) ) ) );
				}
				break;

			case 'sync':
				self::update( 'batch_size', absint( $post['batch_size'] ?? 50 ) );
				self::update( 'pause_between_calls', absint( $post['pause_between_calls'] ?? 1 ) );
				// Floor of 1 — 0 would make the retry loop's while() condition never
				// execute, silently reporting a fake generic failure instead of ever
				// attempting the request.
				self::update( 'max_retry', max( 1, absint( $post['max_retry'] ?? 5 ) ) );
				self::update( 'timeout', absint( $post['timeout'] ?? 60 ) );
				self::update( 'min_sync_interval_hours', absint( $post['min_sync_interval_hours'] ?? 4 ) );
				break;

			case 'woocommerce':
				self::update( 'wc_sync_enabled', isset( $post['wc_sync_enabled'] ) ? 'yes' : 'no' );
				self::update( 'wc_products_per_batch', absint( $post['wc_products_per_batch'] ?? 100 ) );
				self::update( 'wc_update_existing', isset( $post['wc_update_existing'] ) ? 'yes' : 'no' );
				self::update( 'wc_delete_missing', isset( $post['wc_delete_missing'] ) ? 'yes' : 'no' );
				break;

			case 'zoho':
				self::update( 'zoho_sync_enabled', isset( $post['zoho_sync_enabled'] ) ? 'yes' : 'no' );
				// When WC Sync OAuth is connected, org/client/tokens are managed there —
				// only persist fallback fields if they were submitted (non–WC-sync form).
				if ( isset( $post['zoho_organization_id'] ) ) {
					self::update( 'zoho_organization_id', sanitize_text_field( $post['zoho_organization_id'] ) );
				}
				if ( isset( $post['zoho_client_id'] ) ) {
					self::update( 'zoho_client_id', sanitize_text_field( $post['zoho_client_id'] ) );
				}
				if ( ! empty( $post['zoho_client_secret'] ) ) {
					self::update( 'zoho_client_secret', sanitize_text_field( $post['zoho_client_secret'] ) );
				}
				if ( ! empty( $post['zoho_refresh_token'] ) ) {
					self::update( 'zoho_refresh_token', sanitize_text_field( $post['zoho_refresh_token'] ) );
				}
				if ( isset( $post['zoho_dc'] ) ) {
					$dc = sanitize_key( $post['zoho_dc'] );
					self::update( 'zoho_dc', in_array( $dc, array( 'com', 'eu', 'in', 'au', 'jp', 'ca' ), true ) ? $dc : 'com' );
				}
				break;

			case 'scheduler':
				self::update( 'scheduler_enabled', isset( $post['scheduler_enabled'] ) ? 'yes' : 'no' );
				self::update( 'scheduler_frequency', sanitize_text_field( $post['scheduler_frequency'] ?? 'daily' ) );
				self::update( 'scheduler_time', sanitize_text_field( $post['scheduler_time'] ?? '02:00' ) );
				self::update( 'notify_on_failure', isset( $post['notify_on_failure'] ) ? 'yes' : 'no' );

				if ( 'yes' === self::get( 'scheduler_enabled' ) ) {
					Ingram_Sync_Scheduler::schedule(
						self::get( 'scheduler_frequency' ),
						self::get( 'scheduler_time' )
					);
				} else {
					Ingram_Sync_Scheduler::unschedule();
				}
				break;
		}

		return $errors;
	}

	/**
	 * Market region presets (country + sandbox test SKU).
	 *
	 * @return array<string, array{label: string, country: string, test_part_number: string}>
	 */
	public static function get_region_presets() {
		return array(
			'au' => array(
				'label'            => __( 'Australia / New Zealand', 'ingram-sync' ),
				'country'          => 'AU',
				'test_part_number' => '1603467',
			),
			'us' => array(
				'label'            => __( 'United States', 'ingram-sync' ),
				'country'          => 'US',
				'test_part_number' => 'TSXML3',
			),
			'uk' => array(
				'label'            => __( 'United Kingdom', 'ingram-sync' ),
				'country'          => 'GB',
				'test_part_number' => 'S26381-K521-L154',
			),
			'custom' => array(
				'label'            => __( 'Custom', 'ingram-sync' ),
				'country'          => '',
				'test_part_number' => '',
			),
		);
	}

	/**
	 * Normalize customer number to Ingram XX-XXXXXX format when possible.
	 *
	 * @param string $number Raw customer number.
	 * @return string
	 */
	public static function normalize_customer_number( $number ) {
		$number = trim( $number );
		if ( '' === $number ) {
			return '';
		}

		if ( preg_match( '/^\d{2}-\d{4,6}$/', $number ) ) {
			return $number;
		}

		$digits = preg_replace( '/\D/', '', $number );
		if ( preg_match( '/^\d{5,7}$/', $digits ) ) {
			return substr( $digits, 0, 2 ) . '-' . substr( $digits, 2 );
		}

		return $number;
	}

	/**
	 * Reset all plugin settings and data.
	 */
	public static function reset_plugin() {
		foreach ( array_keys( self::get_defaults() ) as $key ) {
			self::delete( $key );
		}
		self::set_defaults();
		Ingram_Sync_Logger::clear();
		Ingram_Sync_Database::truncate_products();
		Ingram_Sync_Scheduler::unschedule();
	}
}
