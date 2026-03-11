<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Catalog\Clients;

use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use StellarWP\Uplink\Catalog\Catalog_Collection;
use StellarWP\Uplink\Catalog\Error_Code;
use StellarWP\Uplink\Catalog\Results\Product_Catalog;
use WP_Error;

/**
 * PSR-18 HTTP implementation of the catalog API client.
 *
 * @since 3.0.0
 */
final class Http_Client implements Catalog_Client {

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
	 * @param string                  $base_url        API base URL (no trailing slash).
	 */
	public function __construct(
		ClientInterface $client,
		RequestFactoryInterface $request_factory,
		string $base_url
	) {
		$this->client          = $client;
		$this->request_factory = $request_factory;
		$this->base_url        = $base_url;
	}

	/**
	 * @inheritDoc
	 */
	public function get_catalog() {
		$request = $this->request_factory->createRequest(
			'GET',
			$this->base_url . '/stellarwp/v4/catalog'
		);

		try {
			$response = $this->client->sendRequest( $request );
		} catch ( ClientExceptionInterface $e ) {
			return new WP_Error(
				Error_Code::INVALID_RESPONSE,
				$e->getMessage()
			);
		}

		$status_code = $response->getStatusCode();

		if ( $status_code < 200 || $status_code >= 300 ) {
			return new WP_Error(
				Error_Code::INVALID_RESPONSE,
				sprintf( 'Catalog API returned HTTP %d.', $status_code ),
				[ 'status' => $status_code ]
			);
		}

		$data = json_decode( (string) $response->getBody(), true );

		if ( ! is_array( $data ) ) {
			return new WP_Error(
				Error_Code::INVALID_RESPONSE,
				'Catalog response could not be decoded.'
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
				'Catalog response is empty.'
			);
		}

		return $catalogs;
	}
}
