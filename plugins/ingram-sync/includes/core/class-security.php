<?php
/**
 * Encryption and security utilities.
 *
 * @package IngramSync
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Ingram_Sync_Security
 */
class Ingram_Sync_Security {

	const CIPHER = 'AES-256-CBC';

	/**
	 * Get encryption key derived from WordPress salts.
	 *
	 * @return string
	 */
	private static function get_key() {
		return hash( 'sha256', wp_salt( 'auth' ) . wp_salt( 'secure_auth' ), true );
	}

	/**
	 * Legacy (pre-1.0.4) initialization vector — static per install, which made
	 * encryption deterministic. Kept only to decrypt values saved by older
	 * versions of the plugin; new values always use a random IV.
	 *
	 * @return string
	 */
	private static function get_legacy_iv() {
		return substr( hash( 'sha256', wp_salt( 'logged_in' ) ), 0, 16 );
	}

	/**
	 * Encrypt a value using a fresh random IV, prepended to the ciphertext.
	 *
	 * @param string $value Plain text.
	 * @return string|false Base64 encoded ciphertext or false on failure.
	 */
	public static function encrypt( $value ) {
		if ( ! function_exists( 'openssl_encrypt' ) ) {
			return base64_encode( $value ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		}

		$iv        = openssl_random_pseudo_bytes( openssl_cipher_iv_length( self::CIPHER ) );
		$encrypted = openssl_encrypt( $value, self::CIPHER, self::get_key(), 0, $iv );
		return false !== $encrypted ? base64_encode( $iv . $encrypted ) : false; // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
	}

	/**
	 * Decrypt a value. Tries the current random-IV format first, then falls
	 * back to the legacy static-IV format for values saved by older versions.
	 *
	 * @param string $value Encrypted value.
	 * @return string|false Plain text or false on failure.
	 */
	public static function decrypt( $value ) {
		if ( empty( $value ) ) {
			return '';
		}

		if ( ! function_exists( 'openssl_decrypt' ) ) {
			return base64_decode( $value, true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		}

		$decoded = base64_decode( $value, true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		if ( false === $decoded ) {
			return false;
		}

		$iv_length = openssl_cipher_iv_length( self::CIPHER );
		if ( strlen( $decoded ) > $iv_length ) {
			$iv         = substr( $decoded, 0, $iv_length );
			$ciphertext = substr( $decoded, $iv_length );
			$plain      = openssl_decrypt( $ciphertext, self::CIPHER, self::get_key(), 0, $iv );
			if ( false !== $plain ) {
				return $plain;
			}
		}

		// Legacy static-IV format.
		return openssl_decrypt( $decoded, self::CIPHER, self::get_key(), 0, self::get_legacy_iv() );
	}

	/**
	 * Verify admin capability.
	 *
	 * @return bool
	 */
	public static function current_user_can_manage() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Verify nonce for admin actions.
	 *
	 * @param string $action Nonce action.
	 * @param string $field  Nonce field name.
	 * @return bool
	 */
	public static function verify_nonce( $action, $field = 'ingram_sync_nonce' ) {
		if ( ! isset( $_REQUEST[ $field ] ) ) {
			return false;
		}
		return (bool) wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST[ $field ] ) ), $action );
	}

	/**
	 * Generate a correlation ID (max 32 chars per Ingram API).
	 *
	 * @return string
	 */
	public static function generate_correlation_id() {
		$id = '';
		if ( function_exists( 'wp_generate_uuid4' ) ) {
			$id = wp_generate_uuid4();
		} else {
			$data    = random_bytes( 16 );
			$data[6] = chr( ord( $data[6] ) & 0x0f | 0x40 );
			$data[8] = chr( ord( $data[8] ) & 0x3f | 0x80 );
			$id      = vsprintf( '%s%s-%s-%s-%s-%s%s%s', str_split( bin2hex( $data ), 4 ) );
		}

		// Ingram API allows max 32 characters for IM-CorrelationID.
		return substr( str_replace( '-', '', $id ), 0, 32 );
	}

	/**
	 * Mask a secret for display.
	 *
	 * @param string $value Secret value.
	 * @return string
	 */
	public static function mask_secret( $value ) {
		if ( empty( $value ) ) {
			return '';
		}
		$len = strlen( $value );
		if ( $len <= 4 ) {
			return str_repeat( '*', $len );
		}
		return substr( $value, 0, 2 ) . str_repeat( '*', min( 10, $len - 4 ) ) . substr( $value, -2 );
	}
}
