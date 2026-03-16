<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Licensing\Clients;

use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use StellarWP\Uplink\Licensing\Error_Code;
use StellarWP\Uplink\Licensing\Results\Product_Entry;
use StellarWP\Uplink\Licensing\Results\Validation_Result;
use StellarWP\Uplink\Traits\With_Debugging;
use WP_Error;

/**
 * PSR-18 HTTP implementation of the v4 licensing API client.
 *
 * @since 3.0.0
 */
final class Http_Client implements Licensing_Client {

	use With_Debugging;

	/**
	 * The PSR-18 HTTP client.
	 *
	 * @since 3.0.0
	 *
	 * @var ClientInterface
	 */
	protected ClientInterface $client;

	/**
	 * The PSR-17 request factory.
	 *
	 * @since 3.0.0
	 *
	 * @var RequestFactoryInterface
	 */
	protected RequestFactoryInterface $request_factory;

	/**
	 * The PSR-17 stream factory.
	 *
	 * @since 3.0.0
	 *
	 * @var StreamFactoryInterface
	 */
	protected StreamFactoryInterface $stream_factory;

	/**
	 * The API base URL (no trailing slash).
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	protected string $base_url;

	/**
	 * Constructor.
	 *
	 * @since 3.0.0
	 *
	 * @param ClientInterface         $client          PSR-18 HTTP client.
	 * @param RequestFactoryInterface $request_factory PSR-17 request factory.
	 * @param StreamFactoryInterface  $stream_factory  PSR-17 stream factory.
	 * @param string                  $base_url        API base URL (no trailing slash).
	 */
	public function __construct(
		ClientInterface $client,
		RequestFactoryInterface $request_factory,
		StreamFactoryInterface $stream_factory,
		string $base_url
	) {
		$this->client          = $client;
		$this->request_factory = $request_factory;
		$this->stream_factory  = $stream_factory;
		$this->base_url        = $base_url;
	}

	/**
	 * Fetch the products associated with a license and domain.
	 *
	 * @since 3.0.0
	 *
	 * @param string $key    License key.
	 * @param string $domain Site domain.
	 *
	 * @return Product_Entry[]|WP_Error
	 */
	public function get_products( string $key, string $domain ) {
		self::debug_log(
			sprintf(
				'Licensing HTTP request: GET products for domain "%s".',
				$domain
			)
		);

		$query = http_build_query(
			[
				'key'    => $key,
				'domain' => $domain,
			]
		);

		$request = $this->request_factory->createRequest(
			'GET',
			$this->base_url . '/stellarwp/v4/products?' . $query
		);

		try {
			$response = $this->client->sendRequest( $request );
		} catch ( ClientExceptionInterface $e ) {
			self::debug_log(
				sprintf(
					'Licensing HTTP exception (get_products): %s',
					$e->getMessage()
				)
			);

			return new WP_Error(
				Error_Code::INVALID_RESPONSE,
				$e->getMessage(),
				[ 'status' => Error_Code::http_status( Error_Code::INVALID_RESPONSE ) ]
			);
		}

		$status_code = $response->getStatusCode();

		self::debug_log(
			sprintf( 'Licensing HTTP response (get_products): %d', $status_code )
		);

		if ( $status_code < 200 || $status_code >= 300 ) {
			return $this->error_from_response(
				(string) $response->getBody(),
				$status_code
			);
		}

		$data = json_decode( (string) $response->getBody(), true );

		if ( ! is_array( $data ) || ! isset( $data['products'] ) || ! is_array( $data['products'] ) ) {
			self::debug_log( 'Licensing response body could not be decoded as JSON.' );

			return new WP_Error(
				Error_Code::INVALID_RESPONSE,
				'License response could not be decoded.',
				[ 'status' => Error_Code::http_status( Error_Code::INVALID_RESPONSE ) ]
			);
		}

		/** @var list<array<string, mixed>> $products */
		$products = $data['products'];

		return array_map(
			static function ( array $product ): Product_Entry {
				return Product_Entry::from_array( $product );
			},
			$products
		);
	}

	/**
	 * Validate a license for a specific product on a domain.
	 *
	 * @since 3.0.0
	 *
	 * @param string $key          License key.
	 * @param string $domain       Site domain.
	 * @param string $product_slug Product identifier.
	 *
	 * @return Validation_Result|WP_Error
	 */
	public function validate( string $key, string $domain, string $product_slug ) {
		self::debug_log(
			sprintf(
				'Licensing HTTP request: POST validate for product "%s" on domain "%s".',
				$product_slug,
				$domain
			)
		);

		$request = $this->request_factory->createRequest(
			'POST',
			$this->base_url . '/stellarwp/v4/licenses/validate'
		);

		$json = wp_json_encode(
			[
				'key'          => $key,
				'domain'       => $domain,
				'product_slug' => $product_slug,
			]
		);

		$body = $this->stream_factory->createStream( (string) $json );

		$request = $request
			->withHeader( 'Content-Type', 'application/json' )
			->withBody( $body );

		try {
			$response = $this->client->sendRequest( $request );
		} catch ( ClientExceptionInterface $e ) {
			self::debug_log(
				sprintf(
					'Licensing HTTP exception (validate): %s',
					$e->getMessage()
				)
			);

			return new WP_Error(
				Error_Code::INVALID_RESPONSE,
				$e->getMessage(),
				[ 'status' => Error_Code::http_status( Error_Code::INVALID_RESPONSE ) ]
			);
		}

		$status_code = $response->getStatusCode();

		self::debug_log(
			sprintf( 'Licensing HTTP response (validate): %d', $status_code )
		);

		if ( $status_code < 200 || $status_code >= 300 ) {
			return $this->error_from_response(
				(string) $response->getBody(),
				$status_code
			);
		}

		$data = json_decode( (string) $response->getBody(), true );

		if ( ! is_array( $data ) || ! isset( $data['status'] ) ) {
			self::debug_log( 'Validation response body could not be decoded as JSON.' );

			return new WP_Error(
				Error_Code::INVALID_RESPONSE,
				'Validation response could not be decoded.',
				[ 'status' => Error_Code::http_status( Error_Code::INVALID_RESPONSE ) ]
			);
		}

		/** @var array<string, mixed> $data */
		return Validation_Result::from_array( $data );
	}

	/**
	 * Build a WP_Error from an error HTTP response.
	 *
	 * @since 3.0.0
	 *
	 * @param string $body        The response body.
	 * @param int    $status_code The HTTP status code.
	 *
	 * @return WP_Error
	 */
	protected function error_from_response( string $body, int $status_code ): WP_Error {
		$data = json_decode( $body, true );

		if ( is_array( $data ) && isset( $data['code'] ) && is_string( $data['code'] ) ) {
			$code    = $data['code'];
			$message = isset( $data['message'] ) && is_string( $data['message'] ) ? $data['message'] : $code;

			return new WP_Error( $code, $message, [ 'status' => $status_code ] );
		}

		if ( $status_code === 404 ) {
			return new WP_Error(
				Error_Code::INVALID_KEY,
				'License key not recognized.',
				[ 'status' => $status_code ]
			);
		}

		return new WP_Error(
			Error_Code::UNKNOWN_ERROR,
			sprintf( 'Unexpected HTTP %d response.', $status_code ),
			[ 'status' => $status_code ]
		);
	}
}
