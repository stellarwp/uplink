<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Features;

use StellarWP\Uplink\Features\API\Client;
use StellarWP\Uplink\Features\Strategy\Resolver;
use StellarWP\Uplink\Features\Types\Feature;
use WP_Error;

/**
 * Central orchestrator for enabling, disabling, and querying features.
 *
 * Delegates the actual mechanism to the appropriate strategy and fires
 * global + slug-specific WordPress actions around each operation.
 *
 * @since 3.0.0
 */
class Manager {

	/**
	 * The client for fetching available features.
	 *
	 * @since 3.0.0
	 *
	 * @var Client
	 */
	private $client;

	/**
	 * The strategy resolver for determining how to toggle features.
	 *
	 * @since 3.0.0
	 *
	 * @var Resolver
	 */
	private $resolver;

	/**
	 * Constructor for the central feature orchestrator.
	 *
	 * @since 3.0.0
	 *
	 * @param Client   $client   The client for fetching available features.
	 * @param Resolver $resolver The strategy resolver.
	 *
	 * @return void
	 */
	public function __construct( Client $client, Resolver $resolver ) {
		$this->client   = $client;
		$this->resolver = $resolver;
	}

	/**
	 * Enables a feature by slug.
	 *
	 * Fires 'stellarwp/uplink/feature_enabling' and 'stellarwp/uplink/{slug}/feature_enabling'
	 * before the operation, and the corresponding 'feature_enabled' actions after success.
	 *
	 * @since 3.0.0
	 *
	 * @param string $slug The feature slug.
	 *
	 * @return true|WP_Error True on success, WP_Error on failure.
	 */
	public function enable( string $slug ) {
		$feature = $this->get_feature( $slug );

		if ( ! $feature ) {
			return new WP_Error(
				'feature_not_found',
				sprintf( 'Feature "%s" not found in the catalog.', $slug )
			);
		}

		$strategy = $this->resolver->resolve( $feature );

		/**
		 * Fires before a feature is enabled.
		 *
		 * @since 3.0.0
		 *
		 * @param Feature $feature The feature being enabled.
		 *
		 * @return void
		 */
		do_action( 'stellarwp/uplink/feature_enabling', $feature );

		/**
		 * Fires before a specific feature is enabled.
		 *
		 * @since 3.0.0
		 *
		 * @param Feature $feature The feature being enabled.
		 *
		 * @return void
		 */
		do_action( "stellarwp/uplink/{$slug}/feature_enabling", $feature );

		$result = $strategy->enable( $feature );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		/**
		 * Fires after a feature has been successfully enabled.
		 *
		 * @since 3.0.0
		 *
		 * @param Feature $feature The feature that was enabled.
		 *
		 * @return void
		 */
		do_action( 'stellarwp/uplink/feature_enabled', $feature );

		/**
		 * Fires after a specific feature has been successfully enabled.
		 *
		 * @since 3.0.0
		 *
		 * @param Feature $feature The feature that was enabled.
		 *
		 * @return void
		 */
		do_action( "stellarwp/uplink/{$slug}/feature_enabled", $feature );

		return true;
	}

	/**
	 * Disables a feature by slug.
	 *
	 * Fires 'stellarwp/uplink/feature_disabling' and 'stellarwp/uplink/{slug}/feature_disabling'
	 * before the operation, and the corresponding 'feature_disabled' actions after success.
	 *
	 * @since 3.0.0
	 *
	 * @param string $slug The feature slug.
	 *
	 * @return true|WP_Error True on success, WP_Error on failure.
	 */
	public function disable( string $slug ) {
		$feature = $this->get_feature( $slug );

		if ( ! $feature ) {
			return new WP_Error(
				'feature_not_found',
				sprintf( 'Feature "%s" not found in the catalog.', $slug )
			);
		}

		$strategy = $this->resolver->resolve( $feature );

		/**
		 * Fires before a feature is disabled.
		 *
		 * @since 3.0.0
		 *
		 * @param Feature $feature The feature being disabled.
		 *
		 * @return void
		 */
		do_action( 'stellarwp/uplink/feature_disabling', $feature );

		/**
		 * Fires before a specific feature is disabled.
		 *
		 * @since 3.0.0
		 *
		 * @param Feature $feature The feature being disabled.
		 *
		 * @return void
		 */
		do_action( "stellarwp/uplink/{$slug}/feature_disabling", $feature );

		$result = $strategy->disable( $feature );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		/**
		 * Fires after a feature has been successfully disabled.
		 *
		 * @since 3.0.0
		 *
		 * @param Feature $feature The feature that was disabled.
		 *
		 * @return void
		 */
		do_action( 'stellarwp/uplink/feature_disabled', $feature );

		/**
		 * Fires after a specific feature has been successfully disabled.
		 *
		 * @since 3.0.0
		 *
		 * @param Feature $feature The feature that was disabled.
		 *
		 * @return void
		 */
		do_action( "stellarwp/uplink/{$slug}/feature_disabled", $feature );

		return true;
	}

	/**
	 * Checks whether a feature is in the catalog AND currently enabled/active.
	 *
	 * Returns false if the feature is not in the catalog.
	 *
	 * @since 3.0.0
	 *
	 * @param string $slug The feature slug.
	 *
	 * @return bool
	 */
	public function is_enabled( string $slug ): bool {
		$feature = $this->get_feature( $slug );

		if ( ! $feature ) {
			return false;
		}

		$strategy = $this->resolver->resolve( $feature );

		return $strategy->is_active( $feature );
	}

	/**
	 * Checks whether a feature exists in the cached catalog.
	 *
	 * @since 3.0.0
	 *
	 * @param string $slug The feature slug.
	 *
	 * @return bool
	 */
	public function is_available( string $slug ): bool {
		return $this->get_feature( $slug ) !== null;
	}

	/**
	 * Gets the feature collection from the catalog.
	 *
	 * @since 3.0.0
	 *
	 * @return Feature_Collection|WP_Error
	 */
	public function get_features() {
		return $this->client->get_features();
	}

	/**
	 * Looks up a feature by slug from the cached catalog.
	 *
	 * Returns null when the feature is not found or when the API
	 * request fails, since the catalog is unavailable.
	 *
	 * @since 3.0.0
	 *
	 * @param string $slug The feature slug.
	 *
	 * @return Feature|null
	 */
	private function get_feature( string $slug ): ?Feature {
		$features = $this->client->get_features();

		if ( is_wp_error( $features ) ) {
			return null;
		}

		return $features->get( $slug );
	}
}
