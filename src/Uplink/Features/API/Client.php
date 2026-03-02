<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Features\API;

use StellarWP\Uplink\Features\Feature_Collection;
use StellarWP\Uplink\Features\Types\Feature;
use StellarWP\Uplink\Utils\Cast;
use WP_Error;

/**
 * Fetches the feature catalog from the Commerce Portal API and
 * caches the result as a WordPress transient.
 *
 * @since 3.0.0
 */
class Client {

	/**
	 * Transient key for the cached feature catalog.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	private const TRANSIENT_KEY = 'stellarwp_uplink_feature_catalog';

	/**
	 * Default cache duration in seconds (12 hours).
	 *
	 * @since 3.0.0
	 *
	 * @var int
	 */
	private const DEFAULT_CACHE_DURATION = 43200;

	/**
	 * Map of feature type strings to Feature subclass names.
	 *
	 * @since 3.0.0
	 *
	 * @var array<string, class-string<Feature>>
	 */
	private array $type_map = [];

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
	 * Gets the feature collection, using the transient cache when available.
	 *
	 * @since 3.0.0
	 *
	 * @return Feature_Collection|WP_Error
	 */
	public function get_features() {
		$cached = get_transient( self::TRANSIENT_KEY );

		if ( $cached instanceof Feature_Collection || is_wp_error( $cached ) ) {
			return $cached;
		}

		return $this->fetch_features();
	}

	/**
	 * Deletes the transient cache and re-fetches from the API.
	 *
	 * @since 3.0.0
	 *
	 * @return Feature_Collection|WP_Error
	 */
	public function refresh() {
		delete_transient( self::TRANSIENT_KEY );

		return $this->fetch_features();
	}

	/**
	 * Fetches features from the Commerce Portal API and caches the result.
	 *
	 * @since 3.0.0
	 *
	 * @return Feature_Collection|WP_Error
	 */
	private function fetch_features() {
		$response = $this->request();

		if ( is_wp_error( $response ) ) {
			set_transient( self::TRANSIENT_KEY, $response, self::DEFAULT_CACHE_DURATION );

			return $response;
		}

		$collection = $this->hydrate( $response );

		set_transient( self::TRANSIENT_KEY, $collection, self::DEFAULT_CACHE_DURATION );

		return $collection;
	}

	/**
	 * Performs the HTTP request to the Commerce Portal API.
	 *
	 * @since 3.0.0
	 *
	 * @return array<int, array<string, mixed>>|WP_Error The decoded response entries or an error.
	 *
	 * @phpstan-ignore-next-line return.unusedType -- Remove once the API request is implemented.
	 */
	private function request() {
		// TODO: Replace this mock data with the actual API request to Commerce Portal.
		// Should send site domain + license keys and return the feature catalog.
		// The mock entries below allow end-to-end testing of the Manager → Strategy stack.
		return [
			[
				'slug'              => 'built-in-feature',
				'group'             => 'Default',
				'tier'              => 'Tier 1',
				'name'              => 'Built-In Feature',
				'description'       => 'A feature built in to the plugin, toggled via a DB flag.',
				'type'              => 'built_in',
				'is_available'      => true,
				'documentation_url' => '',
			],
			[
				'slug'              => 'valid-zip-feature',
				'group'             => 'Default',
				'tier'              => 'Tier 1',
				'name'              => 'Valid Zip Feature',
				'description'       => 'A feature delivered as a standalone plugin ZIP.',
				'type'              => 'zip',
				'plugin_file'       => 'valid-zip-feature/valid-zip-feature.php',
				'is_available'      => true,
				'documentation_url' => '',
				'authors'           => [ 'StellarWP' ],
			],
			[
				'slug'              => 'wrong-author-zip-feature',
				'group'             => 'Default',
				'tier'              => 'Tier 1',
				'name'              => 'Wrong Author Zip Feature',
				'description'       => 'Tests PLUGIN_OWNERSHIP_MISMATCH — plugin Author is "Foreign Developer" but feature expects "StellarWP".',
				'type'              => 'zip',
				'plugin_file'       => 'wrong-author-zip-feature/wrong-author-zip-feature.php',
				'is_available'      => true,
				'documentation_url' => '',
				'authors'           => [ 'StellarWP' ],
			],
			[
				'slug'              => 'fatal-zip-feature',
				'group'             => 'Default',
				'tier'              => 'Tier 1',
				'name'              => 'Fatal Zip Feature',
				'description'       => 'Tests ACTIVATION_FATAL — plugin throws RuntimeException on include.',
				'type'              => 'zip',
				'plugin_file'       => 'fatal-zip-feature/fatal-zip-feature.php',
				'is_available'      => true,
				'documentation_url' => '',
				'authors'           => [ 'StellarWP' ],
			],
			[
				'slug'              => 'high-php-req-zip-feature',
				'group'             => 'Default',
				'tier'              => 'Tier 1',
				'name'              => 'High PHP Requirement Zip Feature',
				'description'       => 'Tests PREFLIGHT_REQUIREMENTS_NOT_MET — requires PHP 99.0.',
				'type'              => 'zip',
				'plugin_file'       => 'high-php-req-zip-feature/high-php-req-zip-feature.php',
				'is_available'      => true,
				'documentation_url' => '',
				'authors'           => [ 'StellarWP' ],
			],
			[
				'slug'              => 'high-wp-req-zip-feature',
				'group'             => 'Default',
				'tier'              => 'Tier 1',
				'name'              => 'High WP Requirement Zip Feature',
				'description'       => 'Tests PREFLIGHT_REQUIREMENTS_NOT_MET — requires WordPress 99.0.',
				'type'              => 'zip',
				'plugin_file'       => 'high-wp-req-zip-feature/high-wp-req-zip-feature.php',
				'is_available'      => true,
				'documentation_url' => '',
				'authors'           => [ 'StellarWP' ],
			],
			[
				'slug'              => 'deactivation-fatal-zip-feature',
				'group'             => 'Default',
				'tier'              => 'Tier 1',
				'name'              => 'Deactivation Fatal Zip Feature',
				'description'       => 'Tests DEACTIVATION_FAILED — plugin re-activates itself on deactivation.',
				'type'              => 'zip',
				'plugin_file'       => 'deactivation-fatal-zip-feature/deactivation-fatal-zip-feature.php',
				'is_available'      => true,
				'documentation_url' => '',
				'authors'           => [ 'StellarWP' ],
			],
			[
				'slug'              => 'mismatched-dir-zip-feature',
				'group'             => 'Default',
				'tier'              => 'Tier 1',
				'name'              => 'Mismatched Directory Zip Feature',
				'description'       => 'Tests PLUGIN_NOT_FOUND_AFTER_INSTALL — ZIP extracts to a different directory than plugin_file expects.',
				'type'              => 'zip',
				'plugin_file'       => 'mismatched-dir-zip-feature/mismatched-dir-zip-feature.php',
				'is_available'      => true,
				'documentation_url' => '',
				'authors'           => [ 'StellarWP' ],
			],
			[
				'slug'              => 'syntax-error-zip-feature',
				'group'             => 'Default',
				'tier'              => 'Tier 1',
				'name'              => 'Syntax Error Zip Feature',
				'description'       => 'Tests ACTIVATION_FATAL — plugin contains a PHP ParseError.',
				'type'              => 'zip',
				'plugin_file'       => 'syntax-error-zip-feature/syntax-error-zip-feature.php',
				'is_available'      => true,
				'documentation_url' => '',
				'authors'           => [ 'StellarWP' ],
			],
			[
				'slug'              => 'die-on-include-zip-feature',
				'group'             => 'Default',
				'tier'              => 'Tier 1',
				'name'              => 'Die On Include Zip Feature',
				'description'       => 'Tests uncatchable fatal — plugin calls die() on include.',
				'type'              => 'zip',
				'plugin_file'       => 'die-on-include-zip-feature/die-on-include-zip-feature.php',
				'is_available'      => true,
				'documentation_url' => '',
				'authors'           => [ 'StellarWP' ],
			],
			[
				'slug'              => 'activation-fatal-zip-feature',
				'group'             => 'Default',
				'tier'              => 'Tier 1',
				'name'              => 'Activation Fatal Zip Feature',
				'description'       => 'Tests ACTIVATION_FATAL — activation hook throws RuntimeException.',
				'type'              => 'zip',
				'plugin_file'       => 'activation-fatal-zip-feature/activation-fatal-zip-feature.php',
				'is_available'      => true,
				'documentation_url' => '',
				'authors'           => [ 'StellarWP' ],
			],
			[
				'slug'              => 'no-header-zip-feature',
				'group'             => 'Default',
				'tier'              => 'Tier 1',
				'name'              => 'No Header Zip Feature',
				'description'       => 'Tests PREFLIGHT_INVALID_PLUGIN — plugin file has no Plugin Name header.',
				'type'              => 'zip',
				'plugin_file'       => 'no-header-zip-feature/no-header-zip-feature.php',
				'is_available'      => true,
				'documentation_url' => '',
				'authors'           => [ 'StellarWP' ],
			],
			[
				'slug'              => 'no-source-zip-feature',
				'group'             => 'Default',
				'tier'              => 'Tier 1',
				'name'              => 'No Source Zip Feature',
				'description'       => 'Tests PLUGINS_API_FAILED — feature exists in catalog but has no ZIP source directory.',
				'type'              => 'zip',
				'plugin_file'       => 'no-source-zip-feature/no-source-zip-feature.php',
				'is_available'      => true,
				'documentation_url' => '',
				'authors'           => [ 'StellarWP' ],
			],
			[
				'slug'              => 'unavailable-zip-feature',
				'group'             => 'Default',
				'tier'              => 'Tier 1',
				'name'              => 'Unavailable Zip Feature',
				'description'       => 'Tests REST validation — is_available is false, rejected before reaching the strategy.',
				'type'              => 'zip',
				'plugin_file'       => 'unavailable-zip-feature/unavailable-zip-feature.php',
				'is_available'      => false,
				'documentation_url' => '',
				'authors'           => [ 'StellarWP' ],
			],
		];
	}

	/**
	 * Hydrates Feature objects from the API response using a type-to-class map.
	 *
	 * @since 3.0.0
	 *
	 * @param array<int, array<string, mixed>> $response The API response entries.
	 *
	 * @return Feature_Collection
	 */
	private function hydrate( array $response ): Feature_Collection {
		$collection = new Feature_Collection();

		foreach ( $response as $entry ) {
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
