<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Features\Dependency\Clients;

use StellarWP\Uplink\Features\Dependency\Dependency;
use StellarWP\Uplink\Features\Dependency\Dependency_Collection;
use WP_Error;

/**
 * A fixture-based dependency client that reads from a JSON file.
 *
 * @since 3.0.0
 */
final class Fixture_Client implements Dependency_Client {

	/**
	 * Error code for invalid or unreadable fixture data.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public const INVALID_RESPONSE = 'stellarwp_uplink_invalid_dependency_response';

	/**
	 * The path to the fixture JSON file.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	private string $fixture_file;

	/**
	 * In-memory cache of the parsed collection.
	 *
	 * @since 3.0.0
	 *
	 * @var Dependency_Collection|WP_Error|null
	 */
	private $cache;

	/**
	 * Constructor.
	 *
	 * @since 3.0.0
	 *
	 * @param string $fixture_file Absolute path to the JSON fixture file.
	 */
	public function __construct( string $fixture_file ) {
		$this->fixture_file = $fixture_file;
	}

	/**
	 * @inheritDoc
	 */
	public function get_dependencies() {
		if ( $this->cache !== null ) {
			return $this->cache;
		}

		$json = @file_get_contents( $this->fixture_file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

		if ( $json === false ) {
			$this->cache = new WP_Error(
				self::INVALID_RESPONSE,
				'Dependency fixture file could not be read.'
			);

			return $this->cache;
		}

		$data = json_decode( $json, true );

		if ( ! is_array( $data ) ) {
			$this->cache = new WP_Error(
				self::INVALID_RESPONSE,
				'Dependency fixture file could not be decoded.'
			);

			return $this->cache;
		}

		$collection = new Dependency_Collection();

		foreach ( $data as $entry ) {
			if ( ! is_array( $entry ) || empty( $entry['feature_slug'] ) ) {
				continue;
			}

			$slug     = (string) $entry['feature_slug'];
			$versions = isset( $entry['versions'] ) && is_array( $entry['versions'] )
				? $entry['versions']
				: [];

			foreach ( $versions as $version => $dep_list ) {
				$deps = [];

				foreach ( (array) $dep_list as $dep_data ) {
					if ( ! is_array( $dep_data ) ) {
						continue;
					}

					$deps[] = Dependency::from_array( $dep_data );
				}

				$collection->add( $slug, (string) $version, $deps );
			}
		}

		$this->cache = $collection;

		return $this->cache;
	}
}
