<?php
/**
 * Ingram OAuth token management.
 *
 * @package IngramSync
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Ingram_Sync_Oauth
 */
class Ingram_Sync_Oauth {

	/**
	 * Generate or refresh access token.
	 *
	 * @return array{success: bool, message: string, token?: string}
	 */
	public static function generate_token() {
		$client_id     = Ingram_Sync_Settings::get( 'client_id' );
		$client_secret = Ingram_Sync_Settings::get( 'client_secret' );
		$grant_type    = Ingram_Sync_Settings::get( 'grant_type', 'client_credentials' );
		$oauth_url     = Ingram_Sync_Settings::get( 'oauth_url' );

		if ( empty( $client_id ) || empty( $client_secret ) ) {
			Ingram_Sync_Logger::log( 'OAuth', 'Missing client credentials', Ingram_Sync_Logger::LEVEL_ERROR );
			return array(
				'success' => false,
				'message' => __( 'Client ID and Client Secret are required.', 'ingram-sync' ),
			);
		}

		if ( empty( $oauth_url ) ) {
			return array(
				'success' => false,
				'message' => __( 'OAuth URL is not configured.', 'ingram-sync' ),
			);
		}

		$timeout = (int) Ingram_Sync_Settings::get( 'timeout', 60 );

		$response = wp_remote_post(
			$oauth_url,
			array(
				'timeout' => $timeout,
				'headers' => array(
					'Content-Type' => 'application/x-www-form-urlencoded',
					'Accept'       => 'application/json',
				),
				'body'    => array(
					'grant_type'    => $grant_type,
					'client_id'     => $client_id,
					'client_secret' => $client_secret,
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			$error = $response->get_error_message();
			Ingram_Sync_Logger::log( 'OAuth', 'Request failed: ' . $error, Ingram_Sync_Logger::LEVEL_ERROR );
			return array(
				'success' => false,
				'message' => $error,
			);
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( 200 !== $code || empty( $body['access_token'] ) ) {
			$message = $body['error_description'] ?? $body['error'] ?? __( 'Failed to obtain access token.', 'ingram-sync' );
			Ingram_Sync_Logger::log(
				'OAuth',
				'Token request failed: ' . $message,
				Ingram_Sync_Logger::LEVEL_ERROR,
				array( 'status_code' => $code )
			);
			return array(
				'success' => false,
				'message' => $message,
				'status'  => $code,
			);
		}

		$expires_in = isset( $body['expires_in'] ) ? (int) $body['expires_in'] : 3600;
		$expiry     = time() + $expires_in;

		Ingram_Sync_Settings::update( 'access_token', $body['access_token'] );
		Ingram_Sync_Settings::update( 'token_expiry', $expiry );
		Ingram_Sync_Settings::update( 'last_token_generated', time() );

		Ingram_Sync_Logger::log( 'OAuth', 'Access token generated successfully', Ingram_Sync_Logger::LEVEL_SUCCESS );

		return array(
			'success' => true,
			'message' => __( 'Access token generated successfully.', 'ingram-sync' ),
			'token'   => $body['access_token'],
			'expiry'  => $expiry,
		);
	}

	/**
	 * Ensure valid token exists, refresh if expired.
	 *
	 * @return array{success: bool, message: string}
	 */
	public static function ensure_valid_token() {
		if ( ! Ingram_Sync_Settings::is_token_expired() ) {
			return array(
				'success' => true,
				'message' => __( 'Token is valid.', 'ingram-sync' ),
			);
		}

		return self::generate_token();
	}

	/**
	 * Test OAuth connection.
	 *
	 * @return array{success: bool, message: string, status?: int}
	 */
	public static function test_connection() {
		$result = self::generate_token();
		if ( $result['success'] ) {
			$result['status'] = 200;
		} else {
			$result['status'] = $result['status'] ?? 401;
		}
		return $result;
	}

	/**
	 * Decode JWT access token payload (non-verified, for reading registered claims).
	 *
	 * @param string|null $token Access token or null to use stored token.
	 * @return array<string, mixed>|null
	 */
	public static function decode_token_payload( $token = null ) {
		$token = $token ?: Ingram_Sync_Settings::get( 'access_token' );
		if ( empty( $token ) || ! is_string( $token ) ) {
			return null;
		}

		$parts = explode( '.', $token );
		if ( count( $parts ) < 2 ) {
			return null;
		}

		$payload = $parts[1];
		$payload = strtr( $payload, '-_', '+/' );
		$padding = strlen( $payload ) % 4;
		if ( $padding > 0 ) {
			$payload .= str_repeat( '=', 4 - $padding );
		}

		$decoded = base64_decode( $payload, true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		if ( false === $decoded ) {
			return null;
		}

		$data = json_decode( $decoded, true );
		return is_array( $data ) ? $data : null;
	}

	/**
	 * Extract customer number and country from token claims.
	 *
	 * @return array{customer_number: string, country: string, claims: array<string, mixed>}|null
	 */
	public static function get_token_customer_info() {
		$payload = self::decode_token_payload();
		if ( ! $payload ) {
			return null;
		}

		$customer_keys = array(
			'customerNumber',
			'customer_number',
			'customernumber',
			'IM-CustomerNumber',
			'im_customer_number',
			'cust_no',
			'accountNumber',
			'account_number',
			'resellerId',
			'reseller_id',
		);

		$country_keys = array(
			'countryCode',
			'country_code',
			'isoCountryCode',
			'iso_country_code',
			'IM-CountryCode',
			'im_country_code',
			'country',
		);

		$customer_number = '';
		$country         = '';

		foreach ( $customer_keys as $key ) {
			if ( ! empty( $payload[ $key ] ) ) {
				$customer_number = (string) $payload[ $key ];
				break;
			}
		}

		foreach ( $country_keys as $key ) {
			if ( ! empty( $payload[ $key ] ) ) {
				$country = strtoupper( (string) $payload[ $key ] );
				break;
			}
		}

		// Deep search nested claims (some tokens nest under "user" or "context").
		if ( '' === $customer_number || '' === $country ) {
			foreach ( $payload as $value ) {
				if ( ! is_array( $value ) ) {
					continue;
				}
				if ( '' === $customer_number ) {
					foreach ( $customer_keys as $key ) {
						if ( ! empty( $value[ $key ] ) ) {
							$customer_number = (string) $value[ $key ];
							break;
						}
					}
				}
				if ( '' === $country ) {
					foreach ( $country_keys as $key ) {
						if ( ! empty( $value[ $key ] ) ) {
							$country = strtoupper( (string) $value[ $key ] );
							break;
						}
					}
				}
			}
		}

		if ( '' === $customer_number && '' === $country ) {
			return array(
				'customer_number' => '',
				'country'         => '',
				'claims'          => $payload,
			);
		}

		return array(
			'customer_number' => $customer_number,
			'country'         => $country,
			'claims'          => $payload,
		);
	}

	/**
	 * Apply customer number and country from token to settings.
	 *
	 * @return array{success: bool, message: string, updated?: array<string, string>}
	 */
	public static function sync_customer_from_token() {
		$result = self::generate_token();
		if ( ! $result['success'] ) {
			return $result;
		}

		$info = self::get_token_customer_info();
		if ( ! $info ) {
			return array(
				'success' => false,
				'message' => __( 'Could not decode access token. Generate a token first.', 'ingram-sync' ),
			);
		}

		$updated = array();

		if ( ! empty( $info['customer_number'] ) ) {
			Ingram_Sync_Settings::update( 'customer_number', sanitize_text_field( $info['customer_number'] ) );
			$updated['customer_number'] = $info['customer_number'];
		}

		if ( ! empty( $info['country'] ) ) {
			Ingram_Sync_Settings::update( 'country', strtoupper( sanitize_text_field( $info['country'] ) ) );
			$updated['country'] = $info['country'];
		}

		if ( empty( $updated ) ) {
			return array(
				'success' => false,
				'message' => __( 'Token does not contain customer number or country. Enter the Customer Number from developer.ingrammicro.com → My Apps → your Sandbox app.', 'ingram-sync' ),
				'claims'  => array_keys( $info['claims'] ),
			);
		}

		Ingram_Sync_Logger::log(
			'OAuth',
			'Synced customer settings from token: ' . wp_json_encode( $updated ),
			Ingram_Sync_Logger::LEVEL_SUCCESS
		);

		return array(
			'success' => true,
			'message' => sprintf(
				/* translators: 1: customer number, 2: country */
				__( 'Applied from token — Customer: %1$s, Country: %2$s', 'ingram-sync' ),
				$updated['customer_number'] ?? '—',
				$updated['country'] ?? '—'
			),
			'updated' => $updated,
		);
	}
}
