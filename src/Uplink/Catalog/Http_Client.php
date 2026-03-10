<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Catalog;

use StellarWP\Uplink\Catalog\Contracts\Catalog_Client;
use StellarWP\Uplink\Catalog\Results\Product_Catalog;
use WP_Error;

/**
 * HTTP-based catalog client that calls the Commerce Portal catalog API.
 *
 * @since 3.0.0
 */
final class Http_Client implements Catalog_Client {

	/**
	 * Base URL of the catalog API (no trailing slash).
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	private string $base_url;

	/**
	 * License key sent with catalog requests.
	 *
	 * @since 3.0.0
	 *
	 * @var string|null
	 */
	private ?string $license_key;

	/**
	 * Constructor.
	 *
	 * @since 3.0.0
	 *
	 * @param string      $base_url    Base URL of the catalog API.
	 * @param string|null $license_key License key for key-scoped catalog lookups.
	 */
	public function __construct( string $base_url, ?string $license_key = null ) {
		$this->base_url    = rtrim( $base_url, '/' );
		$this->license_key = $license_key;
	}

	/**
	 * @inheritDoc
	 */
	public function get_catalog() {
		$url = $this->base_url . '/stellarwp/v4/catalog';

		$query_args = [];
		if ( $this->license_key !== null ) {
			$query_args['key'] = $this->license_key;
		}

		if ( ! empty( $query_args ) ) {
			$url = add_query_arg( $query_args, $url );
		}

		$response = wp_remote_get(
			$url,
			[
				'timeout' => 15,
				'headers' => [ 'Accept' => 'application/json' ],
			]
		);

		if ( is_wp_error( $response ) ) {
			return new WP_Error(
				Error_Code::INVALID_RESPONSE,
				$response->get_error_message()
			);
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( $code < 200 || $code >= 300 ) {
			return new WP_Error(
				Error_Code::INVALID_RESPONSE,
				sprintf( 'Catalog API returned HTTP %d.', $code )
			);
		}

		if ( ! is_array( $data ) ) {
			return new WP_Error(
				Error_Code::INVALID_RESPONSE,
				'Catalog API response could not be decoded.'
			);
		}

		$catalogs = new Catalog_Collection();

		foreach ( $data as $item ) {
			if ( ! is_array( $item ) || ! isset( $item['product_slug'] ) ) {
				return new WP_Error(
					Error_Code::INVALID_RESPONSE,
					'Catalog entry missing product_slug.'
				);
			}

			/** @var array<string, mixed> $item */
			$catalogs->add( Product_Catalog::from_array( $item ) );
		}

		if ( $catalogs->count() === 0 ) {
			return new WP_Error(
				Error_Code::INVALID_RESPONSE,
				'Catalog API returned an empty catalog.'
			);
		}

		return $catalogs;
	}
}
