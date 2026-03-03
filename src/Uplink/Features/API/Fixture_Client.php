<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Features\API;

use StellarWP\Uplink\Features\Contracts\Feature_Client;
use StellarWP\Uplink\Features\Error_Code;
use StellarWP\Uplink\Features\Feature_Collection;
use StellarWP\Uplink\Features\Types\Feature;
use StellarWP\Uplink\Utils\Cast;
use WP_Error;

/**
 * Fixture-based feature client that reads from a local JSON file.
 *
 * When the Commerce Portal API is available, this class will be
 * replaced with a real HTTP client and the DI binding updated.
 *
 * @since 3.0.0
 */
final class Fixture_Client implements Feature_Client {

	/**
	 * The path to the fixture JSON file.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	protected string $fixture_file;

	/**
	 * Map of feature type strings to Feature subclass names.
	 *
	 * @since 3.0.0
	 *
	 * @var array<string, class-string<Feature>>
	 */
	private array $type_map = [];

	/**
	 * In-memory cache of the parsed feature collection.
	 *
	 * @since 3.0.0
	 *
	 * @var Feature_Collection|WP_Error|null
	 */
	protected $cache;

	/**
	 * Constructor.
	 *
	 * @since 3.0.0
	 *
	 * @param string $fixture_file Path to the fixture JSON file.
	 */
	public function __construct( string $fixture_file ) { // phpcs:ignore Squiz.Commenting.FunctionComment.SpacingAfterParamType
		$this->fixture_file = $fixture_file;
	}

	/**
	 * Registers a Feature subclass for a given type string.
	 *
	 * @since 3.0.0
	 *
	 * @param string                $type          The feature type identifier (e.g. 'zip', 'built_in').
	 * @param class-string<Feature> $feature_class The Feature subclass FQCN.
	 *
	 * @return void
	 */
	public function register_type( string $type, string $feature_class ): void { // phpcs:ignore Squiz.Commenting.FunctionComment.IncorrectTypeHint -- class-string<Feature> is a PHPStan type narrowing.
		$this->type_map[ $type ] = $feature_class;
	}

	/**
	 * Fetch the full feature catalog.
	 *
	 * @since 3.0.0
	 *
	 * @return Feature_Collection|WP_Error
	 */
	public function get_features() {
		if ( $this->cache !== null ) {
			return $this->cache;
		}

		$json = @file_get_contents( $this->fixture_file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

		if ( $json === false ) {
			$this->cache = new WP_Error(
				Error_Code::INVALID_RESPONSE,
				'Feature fixture file could not be read.'
			);

			return $this->cache;
		}

		$data = json_decode( $json, true );

		if ( ! is_array( $data ) ) {
			$this->cache = new WP_Error(
				Error_Code::INVALID_RESPONSE,
				'Feature fixture file could not be decoded.'
			);

			return $this->cache;
		}

		$this->cache = $this->hydrate( $data );

		return $this->cache;
	}

	/**
	 * Hydrates Feature objects from the fixture data using a type-to-class map.
	 *
	 * @since 3.0.0
	 *
	 * @param array<int, array<string, mixed>> $data The fixture data entries.
	 *
	 * @return Feature_Collection
	 */
	private function hydrate( array $data ): Feature_Collection {
		$collection = new Feature_Collection();

		foreach ( $data as $entry ) {
			$type  = isset( $entry['type'] ) && is_string( $entry['type'] ) ? $entry['type'] : null;
			$class = $type !== null ? ( $this->type_map[ $type ] ?? null ) : null;

			if ( $class === null ) {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Intentionally logging.
					error_log(
						sprintf(
							"Uplink: Unknown feature type '%s' for slug '%s'",
							$type ?? '(null)',
							Cast::to_string( $entry['slug'] ?? '(unknown)' )
						)
					);
				}
				continue;
			}

			$collection->add( $class::from_array( $entry ) );
		}

		return $collection;
	}
}
