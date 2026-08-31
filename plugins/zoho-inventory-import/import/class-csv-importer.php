<?php
/**
 * CSV file reader and batch processor for Zoho Inventory import.
 *
 * @package ZohoInventoryImport\Import
 */

namespace ZohoInventoryImport\Import;

use ZohoInventoryImport\Api\Zoho_API_Handler;
use ZohoInventoryImport\Includes\Field_Mapping;
use ZohoInventoryImport\Includes\Logger;

defined( 'ABSPATH' ) || exit;

/**
 * Class CSV_Importer
 */
class CSV_Importer {

	const BATCH_SIZE = 10;

	/** @var Zoho_API_Handler */
	private Zoho_API_Handler $api;

	/** @var Logger */
	private Logger $logger;

	/**
	 * @param Zoho_API_Handler $api    Zoho API handler.
	 * @param Logger           $logger Logger instance.
	 */
	public function __construct( Zoho_API_Handler $api, Logger $logger ) {
		$this->api    = $api;
		$this->logger = $logger;
	}

	/**
	 * Validate uploaded file is a readable CSV.
	 *
	 * @param string $file_path Absolute file path.
	 * @return true|\WP_Error
	 */
	public function validate_csv_file( string $file_path ) {
		if ( ! file_exists( $file_path ) || ! is_readable( $file_path ) ) {
			return new \WP_Error( 'file_not_readable', __( 'CSV file could not be read.', 'zoho-inventory-import' ) );
		}

		$extension = strtolower( pathinfo( $file_path, PATHINFO_EXTENSION ) );
		if ( 'csv' !== $extension ) {
			return new \WP_Error( 'invalid_extension', __( 'Only CSV files are allowed.', 'zoho-inventory-import' ) );
		}

		// MIME type varies by browser/OS — trust .csv extension when type is empty or common.
		$mime      = wp_check_filetype( $file_path );
		$allowed   = [ 'text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel', 'application/octet-stream' ];
		$mime_type = $mime['type'] ?? '';

		if ( '' !== $mime_type && ! in_array( $mime_type, $allowed, true ) ) {
			return new \WP_Error( 'invalid_mime', __( 'Invalid file type. Please upload a CSV file.', 'zoho-inventory-import' ) );
		}

		$handle = fopen( $file_path, 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		if ( false === $handle ) {
			return new \WP_Error( 'file_open_failed', __( 'Unable to open CSV file.', 'zoho-inventory-import' ) );
		}

		$header = fgetcsv( $handle );
		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose

		if ( empty( $header ) || ! is_array( $header ) ) {
			return new \WP_Error( 'empty_csv', __( 'CSV file appears to be empty.', 'zoho-inventory-import' ) );
		}

		return true;
	}

	/**
	 * Count data rows in a CSV (excluding header).
	 *
	 * @param string $file_path File path.
	 * @return int
	 */
	public function count_rows( string $file_path ): int {
		$handle = fopen( $file_path, 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		if ( false === $handle ) {
			return 0;
		}

		$count = 0;
		fgetcsv( $handle ); // Skip header.

		while ( false !== fgetcsv( $handle ) ) {
			++$count;
		}

		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		return $count;
	}

	/**
	 * Process a batch of CSV rows starting at offset.
	 *
	 * @param string $file_path Absolute CSV path.
	 * @param int    $offset    Zero-based data row offset (after header).
	 * @param int    $limit     Number of rows to process.
	 * @return array{
	 *     processed:int,
	 *     created:int,
	 *     updated:int,
	 *     failed:int,
	 *     errors:array<int,string>,
	 *     has_more:bool
	 * }
	 */
	public function process_batch( string $file_path, int $offset, int $limit = self::BATCH_SIZE ): array {
		$result = [
			'processed' => 0,
			'created'   => 0,
			'updated'   => 0,
			'failed'    => 0,
			'errors'    => [],
			'has_more'  => false,
		];

		$handle = fopen( $file_path, 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		if ( false === $handle ) {
			$result['failed']++;
			$result['errors'][] = __( 'Unable to open CSV file for processing.', 'zoho-inventory-import' );
			return $result;
		}

		$header = fgetcsv( $handle );
		if ( empty( $header ) ) {
			fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
			$result['failed']++;
			$result['errors'][] = __( 'CSV header row is missing.', 'zoho-inventory-import' );
			return $result;
		}

		$header = array_map(
			static function ( $column ) {
				return strtolower( trim( (string) $column ) );
			},
			$header
		);

		$current_row = 0;
		$processed   = 0;

		while ( ( $row = fgetcsv( $handle ) ) !== false ) {
			if ( $this->is_empty_row( $row ) ) {
				continue;
			}

			if ( $current_row < $offset ) {
				++$current_row;
				continue;
			}

			if ( $processed >= $limit ) {
				$result['has_more'] = true;
				break;
			}

			$row_number = $current_row + 2; // Header is row 1.
			$assoc_row  = $this->combine_row( $header, $row );
			$outcome    = $this->process_row( $assoc_row, $row_number );

			++$result['processed'];
			if ( 'created' === $outcome['status'] ) {
				++$result['created'];
			} elseif ( 'updated' === $outcome['status'] ) {
				++$result['updated'];
			} else {
				++$result['failed'];
				$result['errors'][] = $outcome['message'];
			}

			++$processed;
			++$current_row;
		}

		if ( false === $result['has_more'] ) {
			while ( ( $next = fgetcsv( $handle ) ) !== false ) {
				if ( ! $this->is_empty_row( $next ) ) {
					$result['has_more'] = true;
					break;
				}
			}
		}

		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		return $result;
	}

	/**
	 * Process a single CSV row.
	 *
	 * @param array $row        Associative row data.
	 * @param int   $row_number Human-readable row number.
	 * @return array{status:string,message:string}
	 */
	private function process_row( array $row, int $row_number ): array {
		$sku  = $this->get_mapped_value( $row, Field_Mapping::SKU_COLUMNS );
		$name = $this->get_mapped_value( $row, Field_Mapping::NAME_COLUMNS );

		$existing = $this->api->find_existing_item( $sku, $name );
		$is_new   = null === $existing;

		$payload = $this->api->map_row_to_zoho( $row, $is_new );
		if ( is_wp_error( $payload ) ) {
			$message = $this->format_error_message( $payload );
			$this->logger->log_error( $row_number, $message, [ 'sku' => $sku, 'name' => $name ] );
			return [
				'status'  => 'failed',
				'message' => sprintf(
					/* translators: 1: row number, 2: error message */
					__( 'Row %1$d failed: %2$s', 'zoho-inventory-import' ),
					$row_number,
					$message
				),
			];
		}

		if ( $is_new ) {
			$response = $this->api->create_item( $payload );
			if ( is_wp_error( $response ) ) {
				$message = $this->format_error_message( $response );
				$this->logger->log_error( $row_number, $message, $payload );
				return [
					'status'  => 'failed',
					'message' => sprintf(
						/* translators: 1: row number, 2: error message */
						__( 'Row %1$d failed: %2$s', 'zoho-inventory-import' ),
						$row_number,
						$message
					),
				];
			}

			$item_id = $response['item']['item_id'] ?? '';
			$this->logger->log_success( $row_number, sprintf( 'Created item %s.', $item_id ) );
			return [ 'status' => 'created', 'message' => '' ];
		}

		$item_id  = (string) ( $existing['item_id'] ?? '' );
		$response = $this->api->update_item( $item_id, $payload );
		if ( is_wp_error( $response ) ) {
			$message = $this->format_error_message( $response );
			$this->logger->log_error( $row_number, $message, $payload );
			return [
				'status'  => 'failed',
				'message' => sprintf(
					/* translators: 1: row number, 2: error message */
					__( 'Row %1$d failed: %2$s', 'zoho-inventory-import' ),
					$row_number,
					$message
				),
			];
		}

		$this->logger->log_success( $row_number, sprintf( 'Updated item %s.', $item_id ) );
		return [ 'status' => 'updated', 'message' => '' ];
	}

	/**
	 * @param array $header CSV header columns.
	 * @param array $row    CSV data row.
	 * @return array
	 */
	private function combine_row( array $header, array $row ): array {
		$assoc = [];
		foreach ( $header as $index => $column ) {
			$assoc[ $column ] = isset( $row[ $index ] ) ? trim( (string) $row[ $index ] ) : '';
		}
		return $assoc;
	}

	/**
	 * Build a detailed error string from WP_Error.
	 *
	 * @param \WP_Error $error Error object.
	 * @return string
	 */
	private function format_error_message( \WP_Error $error ): string {
		$message = $error->get_error_message();
		$code    = $error->get_error_code();
		$data    = $error->get_error_data();

		$parts = [];
		if ( $code ) {
			$parts[] = '[' . $code . ']';
		}
		$parts[] = $message;

		if ( is_array( $data ) && ! empty( $data['status'] ) ) {
			$parts[] = '(HTTP ' . (int) $data['status'] . ')';
		}

		return implode( ' ', $parts );
	}

	/**
	 * @param array $row     Row data.
	 * @param array $aliases Column aliases.
	 * @return string
	 */
	private function get_mapped_value( array $row, array $aliases ): string {
		foreach ( $aliases as $alias ) {
			$key = strtolower( $alias );
			if ( isset( $row[ $key ] ) && '' !== trim( (string) $row[ $key ] ) ) {
				return trim( (string) $row[ $key ] );
			}
		}
		return '';
	}

	/**
	 * @param array $row CSV row.
	 * @return bool
	 */
	private function is_empty_row( array $row ): bool {
		foreach ( $row as $value ) {
			if ( '' !== trim( (string) $value ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Store uploaded CSV in the imports directory.
	 *
	 * @param array $file $_FILES entry.
	 * @return string|\WP_Error Stored file path.
	 */
	public function store_uploaded_file( array $file ) {
		if ( empty( $file['tmp_name'] ) || ! is_uploaded_file( $file['tmp_name'] ) ) {
			return new \WP_Error( 'no_upload', __( 'No file was uploaded.', 'zoho-inventory-import' ) );
		}

		if ( UPLOAD_ERR_OK !== (int) $file['error'] ) {
			return new \WP_Error( 'upload_error', __( 'File upload failed.', 'zoho-inventory-import' ) );
		}

		$upload_dir  = wp_upload_dir();
		$import_dir  = trailingslashit( $upload_dir['basedir'] ) . 'zoho-inventory-import';
		wp_mkdir_p( $import_dir );

		$filename = 'import-' . gmdate( 'Ymd-His' ) . '-' . wp_generate_password( 8, false ) . '.csv';
		$dest     = trailingslashit( $import_dir ) . $filename;

		if ( ! move_uploaded_file( $file['tmp_name'], $dest ) ) {
			return new \WP_Error( 'move_failed', __( 'Failed to store uploaded CSV file.', 'zoho-inventory-import' ) );
		}

		return $dest;
	}
}
