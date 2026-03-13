<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Features\Dependency\Clients;

use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use StellarWP\Uplink\Features\Dependency\Dependency;
use StellarWP\Uplink\Features\Dependency\Dependency_Collection;
use StellarWP\Uplink\Utils\Cast;
use WP_Error;

/**
 * PSR-18 HTTP implementation of the dependency API client.
 *
 * @since 3.0.0
 */
final class Http_Client implements Dependency_Client {

	/**
	 * Error code for invalid or unreadable API responses.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public const INVALID_RESPONSE = 'stellarwp_uplink_invalid_dependency_response';

	/**
	 * The PSR-18 HTTP client.
	 *
	 * @since 3.0.0
	 *
	 * @var ClientInterface
	 */
	private ClientInterface $client;

	/**
	 * The PSR-17 request factory.
	 *
	 * @since 3.0.0
	 *
	 * @var RequestFactoryInterface
	 */
	private RequestFactoryInterface $request_factory;

	/**
	 * The API base URL (no trailing slash).
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
	public function get_dependencies() {
		$request = $this->request_factory->createRequest(
			'GET',
			$this->base_url . '/stellarwp/v4/dependencies'
		);

		try {
			$response = $this->client->sendRequest( $request );
		} catch ( ClientExceptionInterface $e ) {
			return new WP_Error(
				self::INVALID_RESPONSE,
				$e->getMessage()
			);
		}

		$status_code = $response->getStatusCode();

		if ( $status_code < 200 || $status_code >= 300 ) {
			return new WP_Error(
				self::INVALID_RESPONSE,
				sprintf( 'Dependencies API returned HTTP %d.', $status_code ),
				[ 'status' => $status_code ]
			);
		}

		$data = json_decode( (string) $response->getBody(), true );

		if ( ! is_array( $data ) ) {
			return new WP_Error(
				self::INVALID_RESPONSE,
				'Dependencies response could not be decoded.'
			);
		}

		$collection = new Dependency_Collection();

		foreach ( $data as $entry ) {
			if ( ! is_array( $entry ) || empty( $entry['feature_slug'] ) ) {
				continue;
			}

			$slug     = Cast::to_string( $entry['feature_slug'] );
			$versions = isset( $entry['versions'] ) && is_array( $entry['versions'] )
				? $entry['versions']
				: [];

			foreach ( $versions as $version => $dep_list ) {
				$deps = [];

				foreach ( (array) $dep_list as $dep_data ) {
					if ( ! is_array( $dep_data ) ) {
						continue;
					}

					/** @var array<string, mixed> $dep_data */
					$deps[] = Dependency::from_array( $dep_data );
				}

				$collection->add( $slug, (string) $version, $deps );
			}
		}

		return $collection;
	}
}
