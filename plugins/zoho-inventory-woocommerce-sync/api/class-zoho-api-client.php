<?php
/**
 * Base Zoho Inventory REST API client.
 *
 * Handles authentication headers, JSON encoding/decoding,
 * HTTP error normalisation, and basic rate-limit back-off.
 *
 * @package ZohoInventorySync\Api
 */

namespace ZohoInventorySync\Api;

use ZohoInventorySync\Includes\OAuth_Manager;

defined( 'ABSPATH' ) || exit;

/**
 * Class Zoho_API_Client
 */
class Zoho_API_Client {

	/** HTTP timeout in seconds. */
	const TIMEOUT = 30;

	/** @var OAuth_Manager */
	private OAuth_Manager $oauth;

	/** @var string Cached organization ID. */
	private string $org_id = '';

	/**
	 * Data-centre-aware Inventory API base URL (resolved at construction time).
	 * Example: "https://www.zohoapis.eu/inventory/v1"
	 *
	 * @var string
	 */
	private string $api_base = '';

	/** Track last request time for rate limiting (requests per second). */
	private static float $last_request_time = 0.0;

	/** Minimum seconds between requests. */
	private static float $min_interval = 0.25; // 4 req/s

	public function __construct( OAuth_Manager $oauth ) {
		$this->oauth    = $oauth;
		$this->org_id   = $oauth->get_organization_id();
		$this->api_base = $oauth->get_inventory_api_base_url(); // e.g. https://www.zohoapis.eu/inventory/v1
	}

	/**
	 * Perform a GET request.
	 *
	 * @param  string $endpoint  API endpoint path (relative to API_BASE).
	 * @param  array  $params    Query parameters.
	 * @return array|\WP_Error   Decoded response body or WP_Error.
	 */
	public function get( string $endpoint, array $params = [] ) {
		if ( $this->org_id ) {
			$params['organization_id'] = $this->org_id;
		}

		$url = $this->api_base . '/' . ltrim( $endpoint, '/' );
		if ( $params ) {
			$url .= '?' . http_build_query( $params );
		}

		return $this->request( 'GET', $url );
	}

	/**
	 * Perform a POST request.
	 *
	 * @param  string $endpoint  API endpoint path.
	 * @param  array  $body      Request body (will be JSON-encoded).
	 * @param  array  $params    Additional query parameters.
	 * @return array|\WP_Error
	 */
	public function post( string $endpoint, ?array $body = null, array $params = [] ) {
		$params['organization_id'] = $this->org_id;

		$url = $this->api_base . '/' . ltrim( $endpoint, '/' ) . '?' . http_build_query( $params );
		return $this->request( 'POST', $url, $body );
	}

	/**
	 * Perform a PUT request.
	 *
	 * @param  string $endpoint
	 * @param  array  $body
	 * @param  array  $params
	 * @return array|\WP_Error
	 */
	public function put( string $endpoint, array $body = [], array $params = [] ) {
		$params['organization_id'] = $this->org_id;

		$url = $this->api_base . '/' . ltrim( $endpoint, '/' ) . '?' . http_build_query( $params );
		return $this->request( 'PUT', $url, $body );
	}

	/**
	 * Perform a DELETE request.
	 *
	 * @param  string $endpoint
	 * @param  array  $params
	 * @return array|\WP_Error
	 */
	public function delete( string $endpoint, array $params = [] ) {
		$params['organization_id'] = $this->org_id;

		$url = $this->api_base . '/' . ltrim( $endpoint, '/' ) . '?' . http_build_query( $params );
		return $this->request( 'DELETE', $url );
	}

	/**
	 * Upload a file to a Zoho Inventory endpoint using multipart/form-data.
	 *
	 * Used for item image uploads: POST /items/{id}/image
	 *
	 * @param  string $endpoint   API endpoint (relative).
	 * @param  string $file_path  Absolute path to the local file.
	 * @param  string $field_name Form field name expected by Zoho (default "image").
	 * @return true|\WP_Error
	 */
	public function upload_file( string $endpoint, string $file_path, string $field_name = 'image' ) {
		if ( ! file_exists( $file_path ) || ! is_readable( $file_path ) ) {
			return new \WP_Error( 'file_not_found', "Image file not found: {$file_path}" );
		}

		$token = $this->oauth->get_access_token();
		if ( is_wp_error( $token ) ) {
			return $token;
		}

		$this->throttle();

		$boundary  = '----ZohoInventorySync' . md5( microtime() );
		$file_data = file_get_contents( $file_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$mime_type = mime_content_type( $file_path ) ?: 'image/jpeg';
		$filename  = basename( $file_path );

		$body  = "--{$boundary}\r\n";
		$body .= "Content-Disposition: form-data; name=\"{$field_name}\"; filename=\"{$filename}\"\r\n";
		$body .= "Content-Type: {$mime_type}\r\n\r\n";
		$body .= $file_data . "\r\n";
		$body .= "--{$boundary}--\r\n";

		$params = [ 'organization_id' => $this->org_id ];
		$url    = $this->api_base . '/' . ltrim( $endpoint, '/' ) . '?' . http_build_query( $params );

		$response = wp_remote_post(
			$url,
			[
				'timeout' => 60,
				'headers' => [
					'Authorization' => 'Zoho-oauthtoken ' . $token,
					'Content-Type'  => 'multipart/form-data; boundary=' . $boundary,
				],
				'body'    => $body,
			]
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$http_code = wp_remote_retrieve_response_code( $response );
		if ( $http_code < 200 || $http_code >= 300 ) {
			$data    = json_decode( wp_remote_retrieve_body( $response ), true );
			$message = $data['message'] ?? "HTTP {$http_code}";
			return new \WP_Error( 'zoho_upload_error', $message );
		}

		return true;
	}

	/**
	 * Download a binary file from Zoho (e.g. item image).
	 *
	 * Returns an array with 'data' (raw bytes) and 'content_type' keys,
	 * or a WP_Error on failure.
	 *
	 * @param  string $endpoint  API endpoint relative to api_base.
	 * @return array|\WP_Error
	 */
	public function download_file( string $endpoint ) {
		$token = $this->oauth->get_access_token();
		if ( is_wp_error( $token ) ) {
			return $token;
		}

		$this->throttle();

		$params = [ 'organization_id' => $this->org_id ];
		$url    = $this->api_base . '/' . ltrim( $endpoint, '/' ) . '?' . http_build_query( $params );

		$response = wp_remote_get( $url, [
			'timeout' => 30,
			'headers' => [
				'Authorization' => 'Zoho-oauthtoken ' . $token,
			],
		] );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$http_code    = wp_remote_retrieve_response_code( $response );
		$content_type = wp_remote_retrieve_header( $response, 'content-type' );
		$body         = wp_remote_retrieve_body( $response );

		if ( $http_code < 200 || $http_code >= 300 ) {
			return new \WP_Error( 'zoho_download_error', "HTTP {$http_code}" );
		}

		// If Zoho returned JSON it's an error response, not an image.
		if ( str_contains( (string) $content_type, 'application/json' ) ) {
			$data = json_decode( $body, true );
			return new \WP_Error( 'zoho_download_error', $data['message'] ?? 'No image found.' );
		}

		return [
			'data'         => $body,
			'content_type' => $content_type,
		];
	}

	/**
	 * Core HTTP request method.
	 *
	 * @param  string     $method  HTTP verb.
	 * @param  string     $url     Full URL.
	 * @param  array|null $body    Request body for POST/PUT.
	 * @param  int        $retry   Current retry attempt number.
	 * @return array|\WP_Error
	 */
	private function request( string $method, string $url, ?array $body = null, int $retry = 0 ) {
		// Throttle outgoing requests.
		$this->throttle();

		$token = $this->oauth->get_access_token();
		if ( is_wp_error( $token ) ) {
			return $token;
		}

		$args = [
			'method'  => $method,
			'timeout' => self::TIMEOUT,
			'headers' => [
				'Authorization' => 'Zoho-oauthtoken ' . $token,
				'Content-Type'  => 'application/json;charset=UTF-8',
			],
		];

		if ( $body !== null ) {
			$args['body'] = wp_json_encode( $body );
		}

		$response = wp_remote_request( $url, $args );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$http_code = wp_remote_retrieve_response_code( $response );
		$raw_body  = wp_remote_retrieve_body( $response );
		$data      = json_decode( $raw_body, true );

		// Handle rate limiting (429).
		if ( $http_code === 429 && $retry < 3 ) {
			$retry_after = (int) ( wp_remote_retrieve_header( $response, 'retry-after' ) ?: 10 );
			sleep( $retry_after );
			return $this->request( $method, $url, $body, $retry + 1 );
		}

		// Handle expired token (401) — try refreshing once.
		if ( $http_code === 401 && $retry === 0 ) {
			$refreshed = $this->oauth->refresh_token();
			if ( ! is_wp_error( $refreshed ) ) {
				return $this->request( $method, $url, $body, 1 );
			}
		}

		if ( $http_code < 200 || $http_code >= 300 ) {
			$message = isset( $data['message'] ) ? $data['message'] : "HTTP {$http_code}";
			return new \WP_Error( 'zoho_api_error', $message, [ 'status' => $http_code, 'body' => $raw_body ] );
		}

		if ( isset( $data['code'] ) && (int) $data['code'] !== 0 ) {
			return new \WP_Error( 'zoho_api_error', $data['message'] ?? 'Zoho API error', [ 'code' => $data['code'] ] );
		}

		return $data ?? [];
	}

	/**
	 * Enforce a minimum interval between successive API calls.
	 */
	private function throttle(): void {
		$now     = microtime( true );
		$elapsed = $now - self::$last_request_time;

		if ( $elapsed < self::$min_interval ) {
			usleep( (int) ( ( self::$min_interval - $elapsed ) * 1_000_000 ) );
		}

		self::$last_request_time = microtime( true );
	}

	/**
	 * Retrieve all pages of a paginated endpoint.
	 *
	 * @param  string $endpoint   API endpoint.
	 * @param  array  $params     Query params (page/per_page are managed internally).
	 * @param  string $data_key   Key in the response that holds the array of items.
	 * @return array              Flat array of all items.
	 */
	public function get_all_pages( string $endpoint, array $params = [], string $data_key = '' ): array {
		$all   = [];
		$page  = 1;

		do {
			$params['page']     = $page;
			$params['per_page'] = 200;

			$response = $this->get( $endpoint, $params );

			if ( is_wp_error( $response ) ) {
				break;
			}

			// Auto-detect data key.
			if ( ! $data_key ) {
				foreach ( $response as $k => $v ) {
					if ( is_array( $v ) && $k !== 'page_context' ) {
						$data_key = $k;
						break;
					}
				}
			}

			$items = $data_key && isset( $response[ $data_key ] ) ? $response[ $data_key ] : [];
			$all   = array_merge( $all, $items );

			$has_more = ! empty( $response['page_context']['has_more_page'] );
			$page++;
		} while ( $has_more );

		return $all;
	}
}
