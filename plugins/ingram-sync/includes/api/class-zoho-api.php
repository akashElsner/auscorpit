<?php
/**
 * Zoho Inventory API client.
 *
 * Prefers the Zoho Inventory WooCommerce Sync OAuth connection when available.
 * Falls back to Ingram Sync Client ID / Secret / Refresh Token.
 *
 * @package IngramSync
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Ingram_Sync_Zoho_Api
 */
class Ingram_Sync_Zoho_Api {

	const LIST_PAGE_SIZE = 200;

	/**
	 * Refresh Zoho access token using direct (fallback) credentials.
	 *
	 * @return array{success: bool, message: string}
	 */
	public function refresh_token() {
		if ( Ingram_Sync_Zoho_Bridge::get_wc_sync_oauth() ) {
			return array(
				'success' => true,
				'message' => __( 'Using Zoho Inventory WooCommerce Sync connection — token managed there.', 'ingram-sync' ),
			);
		}

		$client_id     = Ingram_Sync_Settings::get( 'zoho_client_id' );
		$client_secret = Ingram_Sync_Settings::get( 'zoho_client_secret' );
		$refresh_token = Ingram_Sync_Settings::get( 'zoho_refresh_token' );

		if ( empty( $client_id ) || empty( $client_secret ) || empty( $refresh_token ) ) {
			return array(
				'success' => false,
				'message' => __( 'Zoho credentials are incomplete. Connect via Zoho Inventory WooCommerce Sync, or enter Client ID, Client Secret, and Refresh Token below.', 'ingram-sync' ),
			);
		}

		$dc       = $this->get_data_centre_config();
		$response = wp_remote_post(
			$dc['accounts'] . '/oauth/v2/token',
			array(
				'timeout' => (int) Ingram_Sync_Settings::get( 'timeout', 60 ),
				'body'    => array(
					'refresh_token' => $refresh_token,
					'client_id'     => $client_id,
					'client_secret' => $client_secret,
					'grant_type'    => 'refresh_token',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			Ingram_Sync_Logger::log( 'Zoho', 'Token refresh failed: ' . $response->get_error_message(), Ingram_Sync_Logger::LEVEL_ERROR );
			return array( 'success' => false, 'message' => $response->get_error_message() );
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( empty( $body['access_token'] ) ) {
			$message = $body['error'] ?? __( 'Failed to refresh Zoho token.', 'ingram-sync' );
			Ingram_Sync_Logger::log( 'Zoho', $message, Ingram_Sync_Logger::LEVEL_ERROR );
			return array( 'success' => false, 'message' => $message );
		}

		$expires_in = isset( $body['expires_in'] ) ? (int) $body['expires_in'] : 3600;
		Ingram_Sync_Settings::update( 'zoho_access_token', $body['access_token'] );
		Ingram_Sync_Settings::update( 'zoho_token_expiry', time() + $expires_in );

		Ingram_Sync_Logger::log( 'Zoho', 'Access token refreshed', Ingram_Sync_Logger::LEVEL_SUCCESS );

		return array( 'success' => true, 'message' => __( 'Zoho token generated.', 'ingram-sync' ) );
	}

	/**
	 * Ensure a valid Zoho access token is available.
	 *
	 * @return array{success: bool, message: string, token?: string}
	 */
	public function ensure_valid_token() {
		$oauth = Ingram_Sync_Zoho_Bridge::get_wc_sync_oauth();
		if ( $oauth ) {
			$token = $oauth->get_access_token();
			if ( is_wp_error( $token ) ) {
				return array(
					'success' => false,
					'message' => $token->get_error_message(),
				);
			}
			return array(
				'success' => true,
				'message' => 'OK',
				'token'   => $token,
			);
		}

		if ( ! Ingram_Sync_Settings::is_zoho_token_expired() ) {
			$token = Ingram_Sync_Settings::get( 'zoho_access_token' );
			if ( ! empty( $token ) ) {
				return array(
					'success' => true,
					'message' => 'OK',
					'token'   => $token,
				);
			}
		}

		$result = $this->refresh_token();
		if ( ! $result['success'] ) {
			return $result;
		}

		return array(
			'success' => true,
			'message' => 'OK',
			'token'   => Ingram_Sync_Settings::get( 'zoho_access_token' ),
		);
	}

	/**
	 * Make Zoho API request.
	 *
	 * @param string               $endpoint API path only (no query string), e.g. "items" or "items/123".
	 * @param string               $method   HTTP method.
	 * @param array<string, mixed> $body     Request body.
	 * @param bool                 $log      Whether to write failures to the plugin log.
	 * @param bool                 $throttle Whether to honour pause_between_calls.
	 * @param array<string, mixed> $query    Query string parameters (safely encoded).
	 * @return array{success: bool, status: int, body: mixed, message: string}
	 */
	public function request( $endpoint, $method = 'GET', $body = array(), $log = true, $throttle = true, $query = array() ) {
		$token_result = $this->ensure_valid_token();
		if ( ! $token_result['success'] ) {
			return array(
				'success' => false,
				'status'  => 401,
				'body'    => null,
				'message' => $token_result['message'],
			);
		}

		// Split path/query if a legacy caller passed "?foo=bar" in the endpoint.
		$path = (string) $endpoint;
		if ( false !== strpos( $path, '?' ) ) {
			$parts = wp_parse_url( 'https://dummy.local/' . ltrim( $path, '/' ) );
			$path  = isset( $parts['path'] ) ? ltrim( $parts['path'], '/' ) : ltrim( $path, '/' );
			if ( ! empty( $parts['query'] ) ) {
				parse_str( $parts['query'], $parsed );
				$query = array_merge( $parsed, $query );
			}
		}

		$token  = $token_result['token'] ?? '';
		$org_id = $this->get_organization_id();
		$query  = array_merge( array( 'organization_id' => $org_id ), $query );

		$url = rtrim( $this->get_api_base(), '/' ) . '/' . ltrim( $path, '/' );
		$url = $url . '?' . http_build_query( $query, '', '&', PHP_QUERY_RFC3986 );

		$args = array(
			'method'  => $method,
			'timeout' => (int) Ingram_Sync_Settings::get( 'timeout', 60 ),
			'headers' => array(
				'Authorization' => 'Zoho-oauthtoken ' . $token,
				'Content-Type'  => 'application/json',
			),
		);

		if ( ! empty( $body ) ) {
			$args['body'] = wp_json_encode( $body );
		}

		if ( $throttle ) {
			$pause = (int) Ingram_Sync_Settings::get( 'pause_between_calls', 1 );
			if ( $pause > 0 ) {
				usleep( min( $pause, 1 ) * 100000 ); // Max 0.1s between calls during Zoho sync.
			}
		}

		$max_retry = max( 1, (int) Ingram_Sync_Settings::get( 'max_retry', 5 ) );
		$attempt   = 0;
		$response  = null;

		// Retry transport errors and 429/5xx with backoff, mirroring Ingram_Sync_Api::request() —
		// without this, a single rate-limited Zoho call was marked failed outright and needed a
		// full second upsert attempt (extra find+create/update round trip) via a later manual retry.
		while ( $attempt < $max_retry ) {
			++$attempt;
			$response = wp_remote_request( $url, $args );

			if ( is_wp_error( $response ) ) {
				if ( $attempt >= $max_retry ) {
					if ( $log ) {
						Ingram_Sync_Logger::log(
							'Zoho',
							'HTTP error: ' . $response->get_error_message(),
							Ingram_Sync_Logger::LEVEL_ERROR,
							array( 'endpoint' => $path, 'method' => $method )
						);
					}
					return array(
						'success' => false,
						'status'  => 0,
						'body'    => null,
						'message' => $response->get_error_message(),
					);
				}
				sleep( min( $attempt * 2, 10 ) );
				continue;
			}

			$attempt_status = wp_remote_retrieve_response_code( $response );

			if ( ( 429 === $attempt_status || $attempt_status >= 500 ) && $attempt < $max_retry ) {
				sleep( min( $attempt * 3, 30 ) );
				continue;
			}

			break;
		}

		$status = wp_remote_retrieve_response_code( $response );
		$raw    = wp_remote_retrieve_body( $response );
		$data   = json_decode( $raw, true );
		$msg    = is_array( $data ) ? (string) ( $data['message'] ?? '' ) : '';

		// Zoho often returns HTTP 2xx with a non-zero business `code` (e.g. already exists).
		$biz_code = is_array( $data ) && isset( $data['code'] ) ? (int) $data['code'] : 0;
		$success  = ( $status >= 200 && $status < 300 && 0 === $biz_code );

		if ( ! $success ) {
			if ( '' === $msg ) {
				$msg = 0 !== $biz_code ? ( 'Zoho error code ' . $biz_code ) : ( 'HTTP ' . $status );
			}
			if ( 429 === $status ) {
				$msg = __( 'API Limit', 'ingram-sync' );
			}
			// Never log "already exists" here — upsert converts those into updates.
			if ( $log && ! $this->is_already_exists_error( $msg ) ) {
				Ingram_Sync_Logger::log(
					'Zoho',
					sprintf( 'API %s %s failed: %s', $method, $path, $msg ),
					Ingram_Sync_Logger::LEVEL_ERROR,
					array(
						'status_code' => $status,
						'zoho_code'   => $biz_code,
						'endpoint'    => $path,
					)
				);
			}
		}

		return array(
			'success' => $success,
			'status'  => $status,
			'body'    => $data,
			'message' => $success ? 'OK' : $msg,
		);
	}

	/**
	 * Test Zoho connection.
	 *
	 * @return array{success: bool, status: int, message: string}
	 */
	public function test_connection() {
		$result = $this->request( 'organizations' );
		return array(
			'success' => $result['success'],
			'status'  => $result['status'],
			'message' => $result['status'] . ' ' . $result['message'],
		);
	}

	/**
	 * Normalize a product name for map lookups.
	 *
	 * @param string $name Item name.
	 * @return string
	 */
	public static function normalize_name( $name ) {
		return strtolower( trim( preg_replace( '/\s+/', ' ', (string) $name ) ) );
	}

	/**
	 * Search for an item by SKU.
	 *
	 * @param string $sku Product SKU.
	 * @return array<string, mixed>|null
	 */
	public function find_by_sku( $sku ) {
		$sku = (string) $sku;
		if ( '' === $sku ) {
			return null;
		}

		$result = $this->request( 'items', 'GET', array(), false, false, array( 'sku' => $sku ) );
		if ( empty( $result['body']['items'] ) || ! is_array( $result['body']['items'] ) ) {
			return null;
		}

		foreach ( $result['body']['items'] as $item ) {
			if ( isset( $item['sku'] ) && (string) $item['sku'] === $sku ) {
				return $item;
			}
		}

		return $result['body']['items'][0] ?? null;
	}

	/**
	 * Search for an item by exact name.
	 *
	 * @param string $name Item name.
	 * @return array<string, mixed>|null
	 */
	public function find_by_name( $name ) {
		$name = trim( (string) $name );
		if ( '' === $name ) {
			return null;
		}

		$needle = self::normalize_name( $name );

		$query_sets = array(
			array( 'name' => $name, 'per_page' => 50 ),
			array( 'search_text' => $name, 'per_page' => 50 ),
		);

		$simple = trim( preg_replace( '/[^a-zA-Z0-9 ]+/', ' ', $name ) );
		$simple = trim( preg_replace( '/\s+/', ' ', (string) $simple ) );
		if ( '' !== $simple && self::normalize_name( $simple ) !== $needle ) {
			$query_sets[] = array( 'search_text' => $simple, 'per_page' => 50 );
		}

		foreach ( $query_sets as $query ) {
			$result = $this->request( 'items', 'GET', array(), false, false, $query );
			if ( empty( $result['body']['items'] ) || ! is_array( $result['body']['items'] ) ) {
				continue;
			}
			foreach ( $result['body']['items'] as $item ) {
				if ( isset( $item['name'] ) && self::normalize_name( $item['name'] ) === $needle ) {
					return $item;
				}
			}
		}

		return null;
	}

	/**
	 * Fetch one page of Zoho items (raw list, no map building) — used both by
	 * build_item_maps() below and by Ingram_Sync_Zoho_Sync::warm_maps_batch()
	 * for a chunked, AJAX-safe map warm-up.
	 *
	 * @param int $page     Page number (1-based).
	 * @param int $per_page Page size.
	 * @return array{success: bool, items: array<int,array>, has_more: bool, message: string}
	 */
	public function list_items_page( $page = 1, $per_page = self::LIST_PAGE_SIZE ) {
		$result = $this->request(
			'items',
			'GET',
			array(),
			false,
			false,
			array(
				'page'     => max( 1, (int) $page ),
				'per_page' => max( 1, (int) $per_page ),
			)
		);

		if ( ! $result['success'] ) {
			return array(
				'success'  => false,
				'items'    => array(),
				'has_more' => false,
				'message'  => $result['message'],
			);
		}

		return array(
			'success'  => true,
			'items'    => (array) ( $result['body']['items'] ?? array() ),
			'has_more' => (bool) ( $result['body']['page_context']['has_more_page'] ?? false ),
			'message'  => 'OK',
		);
	}

	/**
	 * Build SKU and name maps from Zoho.
	 *
	 * @return array{sku: array<string, string>, name: array<string, string>}
	 */
	public function build_item_maps() {
		$sku_map  = array();
		$name_map = array();
		$page     = 1;

		do {
			$page_result = $this->list_items_page( $page );

			if ( ! $page_result['success'] ) {
				Ingram_Sync_Logger::log( 'Zoho', 'Item map fetch failed on page ' . $page . ': ' . $page_result['message'], Ingram_Sync_Logger::LEVEL_ERROR );
				break;
			}

			foreach ( $page_result['items'] as $item ) {
				$id = isset( $item['item_id'] ) ? (string) $item['item_id'] : '';
				if ( '' === $id ) {
					continue;
				}
				if ( ! empty( $item['sku'] ) ) {
					$sku_map[ (string) $item['sku'] ] = $id;
				}
				if ( ! empty( $item['name'] ) ) {
					$name_map[ self::normalize_name( $item['name'] ) ] = $id;
				}
			}

			$has_more = $page_result['has_more'];
			++$page;
		} while ( $has_more );

		return array(
			'sku'  => $sku_map,
			'name' => $name_map,
		);
	}

	/**
	 * @deprecated Use build_item_maps().
	 * @return array<string, string>
	 */
	public function build_sku_map() {
		$maps = $this->build_item_maps();
		return $maps['sku'];
	}

	/**
	 * Create a new item.
	 *
	 * @param array<string, mixed> $item_data Item payload.
	 * @param bool                 $log       Whether to log failures.
	 * @return array{success: bool, status: int, body: mixed, message: string}
	 */
	public function create_item( $item_data, $log = true ) {
		return $this->request( 'items', 'POST', $item_data, $log, false );
	}

	/**
	 * Update an existing item.
	 *
	 * @param string               $item_id   Zoho item ID.
	 * @param array<string, mixed> $item_data Item payload.
	 * @param bool                 $log       Whether to log failures.
	 * @return array{success: bool, status: int, body: mixed, message: string}
	 */
	public function update_item( $item_id, $item_data, $log = true ) {
		return $this->request( 'items/' . rawurlencode( (string) $item_id ), 'PUT', $item_data, $log, false );
	}

	/**
	 * Resolve an existing Zoho item ID from maps / API.
	 *
	 * @param string|null          $item_id    Known ID.
	 * @param string               $sku        SKU.
	 * @param string               $name       Name.
	 * @param array<string,string> $sku_map    SKU map.
	 * @param array<string,string> $name_map   Name map.
	 * @param bool                 $api_lookup Whether to call Zoho find APIs (false = maps only, faster).
	 * @param bool                 $name_api   Whether to also call find_by_name (slower; use on duplicates).
	 * @return string
	 */
	public function resolve_item_id( $item_id, $sku, $name, $sku_map = array(), $name_map = array(), $api_lookup = true, $name_api = false ) {
		if ( ! empty( $item_id ) ) {
			return (string) $item_id;
		}

		$sku = (string) $sku;
		if ( '' !== $sku && isset( $sku_map[ $sku ] ) ) {
			return (string) $sku_map[ $sku ];
		}

		$norm = self::normalize_name( $name );
		if ( '' !== $norm && isset( $name_map[ $norm ] ) ) {
			return (string) $name_map[ $norm ];
		}

		if ( ! $api_lookup ) {
			return '';
		}

		if ( '' !== $sku ) {
			$found = $this->find_by_sku( $sku );
			if ( ! empty( $found['item_id'] ) ) {
				return (string) $found['item_id'];
			}
		}

		if ( $name_api && '' !== trim( (string) $name ) ) {
			$found = $this->find_by_name( $name );
			if ( ! empty( $found['item_id'] ) ) {
				return (string) $found['item_id'];
			}
		}

		return '';
	}

	/**
	 * Create item if missing, otherwise update.
	 *
	 * @param array<string, mixed> $item_data Item payload.
	 * @param string|null          $item_id   Known Zoho item ID.
	 * @param array<string,string> $sku_map   Optional SKU → ID map.
	 * @param array<string,string> $name_map  Optional name → ID map.
	 * @return array{success: bool, status: int, body: mixed, message: string, action?: string, item_id?: string}
	 */
	public function upsert_item( $item_data, $item_id = null, $sku_map = array(), $name_map = array() ) {
		$sku  = isset( $item_data['sku'] ) ? (string) $item_data['sku'] : '';
		$name = isset( $item_data['name'] ) ? (string) $item_data['name'] : '';

		// Fast path: maps + SKU lookup only (name API only on duplicate recovery).
		$item_id = $this->resolve_item_id( $item_id, $sku, $name, $sku_map, $name_map, true, false );

		if ( '' !== $item_id ) {
			$result = $this->do_update( $item_id, $item_data );
			if ( $result['success'] ) {
				Ingram_Sync_Logger::log(
					'Zoho',
					sprintf( 'UPDATED SKU=%s item_id=%s name="%s"', $sku ?: '?', $item_id, $name ),
					Ingram_Sync_Logger::LEVEL_SUCCESS,
					array( 'sku' => $sku, 'item_id' => $item_id, 'action' => 'update' )
				);
			}
			return $result;
		}

		// Create — silent; duplicates are recovered as updates.
		$result = $this->create_item( $item_data, false );
		if ( $result['success'] && ! empty( $result['body']['item']['item_id'] ) ) {
			$new_id            = (string) $result['body']['item']['item_id'];
			$result['action']  = 'create';
			$result['item_id'] = $new_id;
			Ingram_Sync_Logger::log(
				'Zoho',
				sprintf( 'CREATED SKU=%s item_id=%s name="%s"', $sku ?: '?', $new_id, $name ),
				Ingram_Sync_Logger::LEVEL_SUCCESS,
				array( 'sku' => $sku, 'item_id' => $new_id, 'action' => 'create' )
			);
			return $result;
		}

		$err_msg  = (string) ( $result['message'] ?? '' );
		$err_body = is_array( $result['body'] ) ? $result['body'] : array();
		$biz_code = isset( $err_body['code'] ) ? (int) $err_body['code'] : 0;

		if ( $this->is_already_exists_error( $err_msg ) || $this->is_already_exists_error( wp_json_encode( $err_body ) ) ) {
			$existing_id = $this->extract_existing_id_from_error( $err_msg, $err_body, $sku, $name, $sku_map, $name_map );

			if ( '' !== $existing_id ) {
				$result = $this->do_update( $existing_id, $item_data );
				if ( $result['success'] ) {
					Ingram_Sync_Logger::log(
						'Zoho',
						sprintf( 'DUP→UPDATED SKU=%s item_id=%s (was: %s)', $sku ?: '?', $existing_id, $err_msg ),
						Ingram_Sync_Logger::LEVEL_SUCCESS,
						array( 'sku' => $sku, 'item_id' => $existing_id, 'action' => 'update_after_duplicate', 'zoho_code' => $biz_code )
					);
				}
				return $result;
			}

			// Create with unique name to avoid name collision.
			if ( '' !== $name && '' !== $sku ) {
				$retry         = $item_data;
				$retry['name'] = $name . ' [' . $sku . ']';

				$unique_id = $this->resolve_item_id( null, $sku, $retry['name'], $sku_map, $name_map );
				if ( '' !== $unique_id ) {
					return $this->do_update( $unique_id, $retry );
				}

				$retry_result = $this->create_item( $retry, false );
				if ( $retry_result['success'] && ! empty( $retry_result['body']['item']['item_id'] ) ) {
					$new_id                  = (string) $retry_result['body']['item']['item_id'];
					$retry_result['action']  = 'create';
					$retry_result['item_id'] = $new_id;
					Ingram_Sync_Logger::log(
						'Zoho',
						sprintf( 'CREATED (unique name) SKU=%s item_id=%s name="%s"', $sku, $new_id, $retry['name'] ),
						Ingram_Sync_Logger::LEVEL_SUCCESS,
						array( 'sku' => $sku, 'item_id' => $new_id, 'action' => 'create_unique_name' )
					);
					return $retry_result;
				}

				if ( $this->is_already_exists_error( $retry_result['message'] ?? '' ) ) {
					$existing_id = $this->extract_existing_id_from_error(
						(string) ( $retry_result['message'] ?? '' ),
						is_array( $retry_result['body'] ) ? $retry_result['body'] : array(),
						$sku,
						$retry['name'],
						$sku_map,
						$name_map
					);
					if ( '' !== $existing_id ) {
						return $this->do_update( $existing_id, $retry );
					}
				}
				$result = $retry_result;
			}
		}

		Ingram_Sync_Logger::log(
			'Zoho',
			sprintf(
				'FAILED SKU=%s name="%s" | %s | zoho_code=%s | http=%s',
				$sku ?: '?',
				$name ?: '?',
				$result['message'] ?? 'unknown',
				(string) $biz_code,
				(string) ( $result['status'] ?? 0 )
			),
			Ingram_Sync_Logger::LEVEL_ERROR,
			array(
				'sku'       => $sku,
				'name'      => $name,
				'zoho_code' => $biz_code,
				'http'      => $result['status'] ?? 0,
				'body'      => $err_body,
			)
		);

		return $result;
	}

	/**
	 * Resolve an existing Zoho item ID from a duplicate-error response.
	 *
	 * @param string               $err_msg  Error message.
	 * @param array<string,mixed>  $err_body Response body.
	 * @param string               $sku      SKU.
	 * @param string               $name     Name.
	 * @param array<string,string> $sku_map  SKU map.
	 * @param array<string,string> $name_map Name map.
	 * @return string
	 */
	private function extract_existing_id_from_error( $err_msg, array $err_body, $sku, $name, $sku_map, $name_map ) {
		// Some Zoho responses include the existing item.
		foreach ( array( 'item_id', 'id' ) as $key ) {
			if ( ! empty( $err_body[ $key ] ) ) {
				return (string) $err_body[ $key ];
			}
			if ( ! empty( $err_body['item'][ $key ] ) ) {
				return (string) $err_body['item'][ $key ];
			}
		}
		if ( ! empty( $err_body['items'][0]['item_id'] ) ) {
			return (string) $err_body['items'][0]['item_id'];
		}

		$existing_id = $this->resolve_item_id( null, $sku, $name, $sku_map, $name_map, true, true );
		if ( '' !== $existing_id ) {
			return $existing_id;
		}

		if ( preg_match( '/Item\s+"([^"]+)"\s+already exists/i', $err_msg, $m ) ) {
			$dup_name    = html_entity_decode( $m[1], ENT_QUOTES, 'UTF-8' );
			$existing_id = $this->resolve_item_id( null, $sku, $dup_name, $sku_map, $name_map, true, true );
			if ( '' !== $existing_id ) {
				return $existing_id;
			}
			$found = $this->find_by_name( $dup_name );
			if ( ! empty( $found['item_id'] ) ) {
				return (string) $found['item_id'];
			}
		}

		$found = $this->find_by_sku( $sku );
		if ( ! empty( $found['item_id'] ) ) {
			return (string) $found['item_id'];
		}

		$found = $this->find_by_name( $name );
		if ( ! empty( $found['item_id'] ) ) {
			return (string) $found['item_id'];
		}

		return '';
	}

	/**
	 * Perform an update and normalize the response.
	 *
	 * @param string               $item_id   Zoho item ID.
	 * @param array<string, mixed> $item_data Payload.
	 * @return array{success: bool, status: int, body: mixed, message: string, action?: string, item_id?: string}
	 */
	private function do_update( $item_id, $item_data ) {
		$update_data = $item_data;
		// item_type/product_type are creation-time only — Zoho rejects any update that
		// tries to change them once an item has transactions against it ("Item type
		// cannot be changed for Items having transactions"), so never attempt it here.
		unset( $update_data['initial_stock'], $update_data['initial_stock_rate'], $update_data['item_type'], $update_data['product_type'] );

		$result = $this->update_item( $item_id, $update_data, false );
		if ( $result['success'] ) {
			$result['action']  = 'update';
			$result['item_id'] = (string) $item_id;
			if ( empty( $result['body']['item']['item_id'] ) ) {
				$result['body']['item']['item_id'] = $item_id;
			}
			return $result;
		}

		// If update failed because of name clash on another item, retry without changing name.
		if ( $this->is_already_exists_error( $result['message'] ?? '' ) ) {
			unset( $update_data['name'] );
			$result = $this->update_item( $item_id, $update_data, false );
			if ( $result['success'] ) {
				$result['action']  = 'update';
				$result['item_id'] = (string) $item_id;
				if ( empty( $result['body']['item']['item_id'] ) ) {
					$result['body']['item']['item_id'] = $item_id;
				}
				return $result;
			}
		}

		Ingram_Sync_Logger::log(
			'Zoho',
			sprintf( 'Update failed for item %s: %s', $item_id, $result['message'] ?? 'unknown' ),
			Ingram_Sync_Logger::LEVEL_ERROR
		);

		$result['item_id'] = (string) $item_id;
		return $result;
	}

	/**
	 * Whether a Zoho error indicates the item/SKU/name already exists.
	 *
	 * @param string $message Error message.
	 * @return bool
	 */
	public function is_already_exists_error( $message ) {
		$msg = strtolower( (string) $message );
		return ( false !== strpos( $msg, 'already' ) )
			|| ( false !== strpos( $msg, 'exist' ) )
			|| ( false !== strpos( $msg, 'duplicate' ) );
	}

	/**
	 * Organization ID from WC Sync or direct settings.
	 *
	 * @return string
	 */
	private function get_organization_id() {
		$oauth = Ingram_Sync_Zoho_Bridge::get_wc_sync_oauth();
		if ( $oauth ) {
			return $oauth->get_organization_id();
		}
		return (string) Ingram_Sync_Settings::get( 'zoho_organization_id' );
	}

	/**
	 * Inventory API base URL for the active connection.
	 *
	 * @return string
	 */
	private function get_api_base() {
		$oauth = Ingram_Sync_Zoho_Bridge::get_wc_sync_oauth();
		if ( $oauth ) {
			return rtrim( $oauth->get_inventory_api_base_url(), '/' );
		}
		$dc = $this->get_data_centre_config();
		return rtrim( $dc['api'], '/' );
	}

	/**
	 * Data centre config for direct credentials.
	 *
	 * @return array{accounts: string, api: string, label: string}
	 */
	private function get_data_centre_config() {
		$centres = Ingram_Sync_Zoho_Bridge::get_data_centres();
		$key     = (string) Ingram_Sync_Settings::get( 'zoho_dc', 'com' );
		return isset( $centres[ $key ] ) ? $centres[ $key ] : $centres['com'];
	}
}
