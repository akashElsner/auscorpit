<?php
/**
 * Ingram Micro API client.
 *
 * @package IngramSync
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Ingram_Sync_Api
 */
class Ingram_Sync_Api {

	/**
	 * Validate Ingram customer header configuration.
	 *
	 * @return array{valid: bool, message: string, errors: string[], warnings: string[]}
	 */
	public static function validate_customer_config() {
		$errors   = array();
		$warnings = array();

		$customer_number = trim( (string) Ingram_Sync_Settings::get( 'customer_number' ) );
		$sender_id       = trim( (string) Ingram_Sync_Settings::get( 'sender_id' ) );
		$country         = self::get_country_code();

		if ( '' === $customer_number ) {
			$errors[] = __( 'Customer Number is required (Settings → Ingram).', 'ingram-sync' );
		} elseif ( ! preg_match( '/^\d{2}-\d{4,6}$/', $customer_number ) ) {
			$warnings[] = __( 'Customer Number should use Ingram format XX-XXXXXX (e.g. 20-222222).', 'ingram-sync' );
		}

		if ( '' === $sender_id ) {
			$errors[] = __( 'Sender ID is required (Settings → Ingram).', 'ingram-sync' );
		}

		if ( '' === $country || ! preg_match( '/^[A-Z]{2}$/', $country ) ) {
			$errors[] = __( 'Country must be a 2-letter ISO code (e.g. US, AU).', 'ingram-sync' );
		}

		$message = empty( $errors )
			? ( empty( $warnings ) ? __( 'Customer configuration looks valid.', 'ingram-sync' ) : implode( ' ', $warnings ) )
			: implode( ' ', $errors );

		return array(
			'valid'    => empty( $errors ),
			'message'  => $message,
			'errors'   => $errors,
			'warnings' => $warnings,
		);
	}

	/**
	 * Get normalized customer number for API headers.
	 *
	 * @return string
	 */
	public static function get_customer_number() {
		$configured = trim( (string) Ingram_Sync_Settings::get( 'customer_number' ) );
		if ( '' !== $configured ) {
			return $configured;
		}

		$token_info = Ingram_Sync_Oauth::get_token_customer_info();
		if ( $token_info && ! empty( $token_info['customer_number'] ) ) {
			return $token_info['customer_number'];
		}

		return '';
	}

	/**
	 * Get country code for API headers (configured or from token).
	 *
	 * @return string
	 */
	public static function get_country_code() {
		$configured = strtoupper( trim( (string) Ingram_Sync_Settings::get( 'country', '' ) ) );
		if ( '' !== $configured ) {
			return $configured;
		}

		$token_info = Ingram_Sync_Oauth::get_token_customer_info();
		if ( $token_info && ! empty( $token_info['country'] ) ) {
			return strtoupper( $token_info['country'] );
		}

		return 'AU';
	}

	/**
	 * Check if configured customer matches token registration.
	 *
	 * @return array{matches: bool, configured: string, token: string, country_configured: string, country_token: string}
	 */
	public static function get_customer_mismatch_info() {
		$token_info = Ingram_Sync_Oauth::get_token_customer_info();
		$configured = trim( (string) Ingram_Sync_Settings::get( 'customer_number' ) );
		$token_cn     = $token_info['customer_number'] ?? '';
		$country_cfg  = strtoupper( trim( (string) Ingram_Sync_Settings::get( 'country', '' ) ) );
		$country_tok  = $token_info['country'] ?? '';

		$matches = true;
		if ( $token_cn && $configured && strcasecmp( $configured, $token_cn ) !== 0 ) {
			$matches = false;
		}
		if ( $country_tok && $country_cfg && strcasecmp( $country_cfg, $country_tok ) !== 0 ) {
			$matches = false;
		}

		return array(
			'matches'            => $matches,
			'configured'         => $configured,
			'token'              => $token_cn,
			'country_configured' => $country_cfg,
			'country_token'      => $country_tok,
			'has_token_claims'   => ! empty( $token_info['claims'] ),
		);
	}

	/**
	 * Ensure price API URL includes required query parameters.
	 *
	 * @param string $url Price API URL.
	 * @return string
	 */
	public static function build_price_url( $url = '' ) {
		$url = $url ?: Ingram_Sync_Settings::get( 'price_api_url' );
		$url = strtok( $url, '?' );

		return add_query_arg(
			array(
				'includeAvailability'       => 'true',
				'includePricing'            => 'true',
				'includeProductAttributes'  => 'true',
			),
			$url
		);
	}

	/**
	 * Get test part number for API tests.
	 *
	 * @return string
	 */
	public static function get_test_part_number() {
		$part = trim( (string) Ingram_Sync_Settings::get( 'test_part_number', 'CA75695' ) );
		return $part ?: 'CA75695';
	}

	/**
	 * Make authenticated API request.
	 *
	 * @param string               $url     API endpoint URL.
	 * @param string               $method  HTTP method.
	 * @param array<string, mixed> $args    Request arguments.
	 * @param string               $source  Log source name.
	 * @return array{success: bool, status: int, body: mixed, message: string}
	 */
	public function request( $url, $method = 'GET', $args = array(), $source = 'API' ) {
		$config = self::validate_customer_config();
		if ( ! $config['valid'] ) {
			Ingram_Sync_Logger::log( $source, $config['message'], Ingram_Sync_Logger::LEVEL_ERROR );
			return array(
				'success' => false,
				'status'  => 400,
				'body'    => null,
				'message' => $config['message'],
			);
		}

		$token_result = Ingram_Sync_Oauth::ensure_valid_token();
		if ( ! $token_result['success'] ) {
			return array(
				'success' => false,
				'status'  => 401,
				'body'    => null,
				'message' => $token_result['message'],
			);
		}

		$access_token = Ingram_Sync_Settings::get( 'access_token' );
		$timeout      = (int) Ingram_Sync_Settings::get( 'timeout', 60 );
		$pause        = (int) Ingram_Sync_Settings::get( 'pause_between_calls', 1 );
		$method       = strtoupper( $method );

		$headers = $this->build_headers( $access_token, $method );
		if ( ! empty( $args['headers'] ) ) {
			$headers = array_merge( $headers, $args['headers'] );
		}

		$request_args = array(
			'method'  => $method,
			'timeout' => $timeout,
			'headers' => $headers,
		);

		if ( ! empty( $args['body'] ) ) {
			$request_args['body'] = is_array( $args['body'] ) ? wp_json_encode( $args['body'] ) : $args['body'];
		}

		$max_retry = (int) Ingram_Sync_Settings::get( 'max_retry', 5 );
		$attempt   = 0;
		$response  = null;

		while ( $attempt < $max_retry ) {
			++$attempt;
			$response = wp_remote_request( $url, $request_args );

			if ( is_wp_error( $response ) ) {
				if ( $attempt >= $max_retry ) {
					$error = $response->get_error_message();
					Ingram_Sync_Logger::log( $source, 'Request failed: ' . $error, Ingram_Sync_Logger::LEVEL_ERROR );
					return array(
						'success' => false,
						'status'  => 0,
						'body'    => null,
						'message' => $error,
					);
				}
				sleep( min( $attempt * 2, 10 ) );
				continue;
			}

			$status = wp_remote_retrieve_response_code( $response );

			if ( 401 === $status && $attempt < $max_retry ) {
				$refresh = Ingram_Sync_Oauth::generate_token();
				if ( ! $refresh['success'] ) {
					// The token refresh itself failed — retrying with the same (still
					// stale) token would just burn another attempt and mask the real
					// OAuth error behind a generic "HTTP 401" message.
					Ingram_Sync_Logger::log( $source, 'Token refresh failed after 401: ' . $refresh['message'], Ingram_Sync_Logger::LEVEL_ERROR );
					return array(
						'success' => false,
						'status'  => 401,
						'body'    => null,
						'message' => $refresh['message'],
					);
				}
				$request_args['headers']['Authorization'] = 'Bearer ' . Ingram_Sync_Settings::get( 'access_token' );
				continue;
			}

			// Rate limited — wait then retry.
			if ( 429 === $status && $attempt < $max_retry ) {
				sleep( max( $pause, min( $attempt * 3, 30 ) ) );
				continue;
			}

			if ( $status >= 500 && $attempt < $max_retry ) {
				sleep( min( $attempt * 2, 10 ) );
				continue;
			}

			break;
		}

		// Pause after a successful call (not before) so batches are not doubled in wait time.
		if ( $pause > 0 && ! is_wp_error( $response ) ) {
			$status_for_pause = wp_remote_retrieve_response_code( $response );
			if ( $status_for_pause >= 200 && $status_for_pause < 300 ) {
				usleep( $pause * 250000 ); // 0.25s * setting (1 ≈ 250ms) — keep rate soft without timeouts.
			}
		}

		$status   = wp_remote_retrieve_response_code( $response );
		$body_raw = wp_remote_retrieve_body( $response );
		$body     = json_decode( $body_raw, true );
		$success  = $status >= 200 && $status < 300;

		$log_level = $success ? Ingram_Sync_Logger::LEVEL_SUCCESS : Ingram_Sync_Logger::LEVEL_ERROR;
		$log_msg   = $success ? 'Request successful' : 'Request failed with status ' . $status;

		Ingram_Sync_Logger::log( $source, $log_msg, $log_level, array( 'status_code' => $status, 'url' => $url ) );

		return array(
			'success' => $success,
			'status'  => $status,
			'body'    => $body,
			'message' => $success ? __( 'OK', 'ingram-sync' ) : self::parse_error_message( $status, $body ),
		);
	}

	/**
	 * Extract a human-readable error from an Ingram API response.
	 *
	 * @param int                  $status HTTP status code.
	 * @param array<string, mixed> $body   Decoded response body.
	 * @return string
	 */
	private static function parse_error_message( $status, $body ) {
		if ( ! empty( $body['errors'][0]['message'] ) ) {
			$message = $body['errors'][0]['message'];
		} elseif ( ! empty( $body['faultstring'] ) ) {
			$message = $body['faultstring'];
		} elseif ( ! empty( $body['message'] ) ) {
			$message = $body['message'];
		} else {
			$message = 'HTTP ' . $status;
		}

		if ( 403 === $status && false !== stripos( $message, 'customer' ) ) {
			$message .= ' ' . __(
				'Verify Customer Number, Country, and Sender ID match your Ingram API registration.',
				'ingram-sync'
			);
		}

		return $message;
	}

	/**
	 * Build standard Ingram API headers.
	 *
	 * @param string $access_token Bearer token.
	 * @param string $method       HTTP method.
	 * @return array<string, string>
	 */
	private function build_headers( $access_token, $method = 'GET' ) {
		$correlation = 'yes' === Ingram_Sync_Settings::get( 'correlation_id_auto', 'yes' )
			? Ingram_Sync_Security::generate_correlation_id()
			: Ingram_Sync_Settings::get( 'correlation_id', '' );

		$headers = array(
			'Authorization'     => 'Bearer ' . $access_token,
			'Accept'            => 'application/json',
			'IM-CustomerNumber' => self::get_customer_number(),
			'IM-CountryCode'    => self::get_country_code(),
			'IM-CorrelationID'  => $correlation,
			'IM-SenderID'       => trim( (string) Ingram_Sync_Settings::get( 'sender_id' ) ),
		);

		// Content-Type only on requests with a body (POST/PUT).
		if ( in_array( $method, array( 'POST', 'PUT', 'PATCH' ), true ) ) {
			$headers['Content-Type'] = 'application/json';
		}

		return $headers;
	}

	/**
	 * Format API test result for JSON response.
	 *
	 * @param array{success: bool, status: int, message: string} $result API result.
	 * @return array{success: bool, status: int, message: string}
	 */
	private function format_test_result( $result ) {
		$status_label = $result['success'] ? 'OK' : (string) $result['status'];
		return array(
			'success' => $result['success'],
			'status'  => $result['status'],
			'message' => $result['success']
				? $result['status'] . ' ' . $status_label
				: $result['status'] . ' ' . $result['message'],
		);
	}

	/**
	 * Test catalog API.
	 *
	 * @return array{success: bool, status: int, message: string}
	 */
	public function test_catalog() {
		$url = rtrim( Ingram_Sync_Settings::get( 'catalog_api_url' ), '/' );
		$url = add_query_arg(
			array(
				'pageSize'   => 1,
				'pageNumber' => 1,
				'keyword'    => self::get_test_part_number(),
			),
			$url
		);
		return $this->format_test_result( $this->request( $url, 'GET', array(), 'Catalog' ) );
	}

	/**
	 * Test price API.
	 *
	 * @return array{success: bool, status: int, message: string}
	 */
	public function test_price() {
		$part_number = self::get_test_part_number();
		$result      = $this->request(
			self::build_price_url(),
			'POST',
			array(
				'body' => array(
					'products' => array(
						array( 'ingramPartNumber' => $part_number ),
					),
				),
			),
			'Price'
		);

		return $this->format_test_result( $result );
	}

	/**
	 * Test details API.
	 *
	 * @return array{success: bool, status: int, message: string}
	 */
	public function test_details() {
		$part_number = self::get_test_part_number();
		$url         = rtrim( Ingram_Sync_Settings::get( 'details_api_url' ), '/' ) . '/' . rawurlencode( $part_number );
		return $this->format_test_result( $this->request( $url, 'GET', array(), 'Details' ) );
	}

	/**
	 * Download catalog page.
	 *
	 * @param int $page       Page number.
	 * @param int $page_size  Page size.
	 * @return array{success: bool, body: mixed, message: string}
	 */
	public function get_catalog( $page = 1, $page_size = null ) {
		$page_size = $page_size ?: (int) Ingram_Sync_Settings::get( 'batch_size', 50 );
		$url       = rtrim( Ingram_Sync_Settings::get( 'catalog_api_url' ), '/' );
		$url       = add_query_arg(
			array(
				'pageSize'   => $page_size,
				'pageNumber' => $page,
			),
			$url
		);

		return $this->request( $url, 'GET', array(), 'Catalog' );
	}

	/**
	 * Get price and availability for products.
	 *
	 * @param array<string> $part_numbers Ingram part numbers.
	 * @return array{success: bool, body: mixed, message: string}
	 */
	public function get_price_availability( $part_numbers ) {
		$products = array();
		foreach ( $part_numbers as $pn ) {
			$products[] = array( 'ingramPartNumber' => $pn );
		}

		return $this->request(
			self::build_price_url(),
			'POST',
			array( 'body' => array( 'products' => $products ) ),
			'Price'
		);
	}

	/**
	 * Get product details.
	 *
	 * @param string $part_number Ingram part number.
	 * @return array{success: bool, body: mixed, message: string}
	 */
	public function get_product_details( $part_number ) {
		$url = rtrim( Ingram_Sync_Settings::get( 'details_api_url' ), '/' ) . '/' . rawurlencode( $part_number );
		return $this->request( $url, 'GET', array(), 'Details' );
	}
}
