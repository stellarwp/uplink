<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Licensing;

use StellarWP\Uplink\Licensing\Contracts\Licensing_Client;
use StellarWP\Uplink\Licensing\Results\Product_Entry;
use StellarWP\Uplink\Licensing\Results\Validation_Result;
use WP_Error;

/**
 * HTTP-based licensing client that calls the v4 licensing API.
 *
 * @since 3.0.0
 */
final class Http_Client implements Licensing_Client {

	/**
	 * Base URL of the licensing API (no trailing slash).
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	private string $base_url;

	/**
	 * Constructor.
	 *
	 * @since 3.0.0
	 *
	 * @param string $base_url Base URL of the licensing API.
	 */
	public function __construct( string $base_url ) {
		$this->base_url = rtrim( $base_url, '/' );
	}

	/**
	 * @inheritDoc
	 */
	public function get_products( string $key, string $domain ) {
		$url = $this->base_url . '/stellarwp/v4/products';

		$response = wp_remote_get(
			add_query_arg(
				[
					'key'    => $key,
					'domain' => $domain,
				],
				$url
			),
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

		if ( $code === 404 || ( $code >= 400 && $code < 500 ) ) {
			return new WP_Error(
				Error_Code::INVALID_KEY,
				sprintf( 'License key not recognized (HTTP %d).', $code )
			);
		}

		if ( $code < 200 || $code >= 300 ) {
			return new WP_Error(
				Error_Code::INVALID_RESPONSE,
				sprintf( 'Licensing API returned HTTP %d.', $code )
			);
		}

		if ( ! is_array( $data ) || ! isset( $data['products'] ) || ! is_array( $data['products'] ) ) {
			return new WP_Error(
				Error_Code::INVALID_RESPONSE,
				'Licensing API response could not be decoded.'
			);
		}

		return array_map( [ Product_Entry::class, 'from_array' ], $data['products'] );
	}

	/**
	 * @inheritDoc
	 */
	public function validate( string $key, string $domain, string $product_slug ) {
		$url = $this->base_url . '/stellarwp/v4/licenses/validate';

		$response = wp_remote_post(
			$url,
			[
				'timeout' => 15,
				'headers' => [
					'Accept'       => 'application/json',
					'Content-Type' => 'application/json',
				],
				'body'    => wp_json_encode(
					[
						'key'          => $key,
						'domain'       => $domain,
						'product_slug' => $product_slug,
					]
				),
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
				sprintf( 'Validation API returned HTTP %d.', $code )
			);
		}

		if ( ! is_array( $data ) || ! isset( $data['status'] ) ) {
			return new WP_Error(
				Error_Code::INVALID_RESPONSE,
				'Validation API response could not be decoded.'
			);
		}

		return Validation_Result::from_array( $data );
	}
}
