<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Features;

use StellarWP\Uplink\Features\Strategy\Strategy_Factory;
use StellarWP\Uplink\Features\Types\Feature;
use StellarWP\Uplink\Features\Error_Code;
use Throwable;
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
	 * The repository for fetching available features.
	 *
	 * @since 3.0.0
	 *
	 * @var Feature_Repository
	 */
	private Feature_Repository $repository;

	/**
	 * The strategy factory for determining how to toggle features.
	 *
	 * @since 3.0.0
	 *
	 * @var Strategy_Factory
	 */
	private Strategy_Factory $strategy_factory;

	/**
	 * The license key.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	private string $key;

	/**
	 * The site domain.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	private string $domain;

	/**
	 * Constructor for the central feature orchestrator.
	 *
	 * @since 3.0.0
	 *
	 * @param Feature_Repository $repository The repository for fetching available features.
	 * @param Strategy_Factory   $strategy_factory The strategy factory.
	 * @param string             $key              The license key.
	 * @param string             $domain           The site domain.
	 *
	 * @return void
	 */
	public function __construct( Feature_Repository $repository, Strategy_Factory $strategy_factory, string $key, string $domain ) {
		$this->repository       = $repository;
		$this->strategy_factory = $strategy_factory;
		$this->key              = $key;
		$this->domain           = $domain;
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
	 * @return Feature|WP_Error The feature with updated is_enabled state, or WP_Error on failure.
	 */
	public function enable( string $slug ) {
		$features = $this->repository->get( $this->key, $this->domain );

		if ( is_wp_error( $features ) ) {
			return $features;
		}

		$feature = $features->get( $slug );

		if ( ! $feature ) {
			return new WP_Error(
				Error_Code::FEATURE_NOT_FOUND,
				sprintf( 'Feature "%s" not found in the catalog.', $slug )
			);
		}

		/**
		 * Fires before a feature is enabled.
		 *
		 * @since 3.0.0
		 *
		 * @param array<string, mixed> $feature The feature being enabled.
		 *
		 * @return void
		 */
		do_action( 'stellarwp/uplink/feature_enabling', $feature->to_array() );

		/**
		 * Fires before a specific feature is enabled.
		 *
		 * @since 3.0.0
		 *
		 * @param array<string, mixed> $feature The feature being enabled.
		 *
		 * @return void
		 */
		do_action( "stellarwp/uplink/{$slug}/feature_enabling", $feature->to_array() );

		try {
			$strategy = $this->strategy_factory->make( $feature );

			$result = $strategy->enable();
		} catch ( Throwable $e ) {
			return new WP_Error(
				Error_Code::FEATURE_ENABLE_FAILED,
				$e->getMessage()
			);
		}

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$feature = $this->get( $slug );

		if ( ! $feature ) {
			return new WP_Error(
				Error_Code::FEATURE_NOT_FOUND,
				sprintf( 'Feature "%s" not found after enabling.', $slug )
			);
		}

		/**
		 * Fires after a feature has been successfully enabled.
		 *
		 * @since 3.0.0
		 *
		 * @param array<string, mixed> $feature The feature that was enabled.
		 *
		 * @return void
		 */
		do_action( 'stellarwp/uplink/feature_enabled', $feature->to_array() );

		/**
		 * Fires after a specific feature has been successfully enabled.
		 *
		 * @since 3.0.0
		 *
		 * @param array<string, mixed> $feature The feature that was enabled.
		 *
		 * @return void
		 */
		do_action( "stellarwp/uplink/{$slug}/feature_enabled", $feature->to_array() );

		return $feature;
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
	 * @return Feature|WP_Error The feature with updated is_enabled state, or WP_Error on failure.
	 */
	public function disable( string $slug ) {
		$features = $this->repository->get( $this->key, $this->domain );

		if ( is_wp_error( $features ) ) {
			return $features;
		}

		$feature = $features->get( $slug );

		if ( ! $feature ) {
			return new WP_Error(
				Error_Code::FEATURE_NOT_FOUND,
				sprintf( 'Feature "%s" not found in the catalog.', $slug )
			);
		}

		/**
		 * Fires before a feature is disabled.
		 *
		 * @since 3.0.0
		 *
		 * @param array<string, mixed> $feature The feature being disabled.
		 *
		 * @return void
		 */
		do_action( 'stellarwp/uplink/feature_disabling', $feature->to_array() );

		/**
		 * Fires before a specific feature is disabled.
		 *
		 * @since 3.0.0
		 *
		 * @param array<string, mixed> $feature The feature being disabled.
		 *
		 * @return void
		 */
		do_action( "stellarwp/uplink/{$slug}/feature_disabling", $feature->to_array() );

		try {
			$strategy = $this->strategy_factory->make( $feature );

			$result = $strategy->disable();
		} catch ( Throwable $e ) {
			return new WP_Error(
				Error_Code::FEATURE_DISABLE_FAILED,
				$e->getMessage()
			);
		}

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$feature = $this->get( $slug );

		if ( ! $feature ) {
			return new WP_Error(
				Error_Code::FEATURE_NOT_FOUND,
				sprintf( 'Feature "%s" not found after disabling.', $slug )
			);
		}

		/**
		 * Fires after a feature has been successfully disabled.
		 *
		 * @since 3.0.0
		 *
		 * @param array<string, mixed> $feature The feature that was disabled.
		 *
		 * @return void
		 */
		do_action( 'stellarwp/uplink/feature_disabled', $feature->to_array() );

		/**
		 * Fires after a specific feature has been successfully disabled.
		 *
		 * @since 3.0.0
		 *
		 * @param array<string, mixed> $feature The feature that was disabled.
		 *
		 * @return void
		 */
		do_action( "stellarwp/uplink/{$slug}/feature_disabled", $feature->to_array() );

		return $feature;
	}

	/**
	 * Checks whether a feature is in the catalog AND currently enabled/active.
	 *
	 * @since 3.0.0
	 *
	 * @param string $slug The feature slug.
	 *
	 * @return bool|WP_Error True if enabled, false if disabled, WP_Error on failure.
	 */
	public function is_enabled( string $slug ) {
		$features = $this->get_all();

		if ( is_wp_error( $features ) ) {
			return $features;
		}

		$feature = $features->get( $slug );

		if ( ! $feature ) {
			return new WP_Error(
				Error_Code::FEATURE_NOT_FOUND,
				sprintf( 'Feature "%s" not found in the catalog.', $slug )
			);
		}

		return $feature->is_enabled();
	}

	/**
	 * Checks whether a feature is available for the current site's tier.
	 *
	 * @since 3.0.0
	 *
	 * @param string $slug The feature slug.
	 *
	 * @return bool|WP_Error True if available, false if not, WP_Error on failure.
	 */
	public function is_available( string $slug ) {
		$features = $this->get_all();

		if ( is_wp_error( $features ) ) {
			return $features;
		}

		$feature = $features->get( $slug );

		if ( ! $feature ) {
			return new WP_Error(
				Error_Code::FEATURE_NOT_FOUND,
				sprintf( 'Feature "%s" not found in the catalog.', $slug )
			);
		}

		return $feature->is_available();
	}

	/**
	 * Checks whether a feature exists in the catalog.
	 *
	 * @since 3.0.0
	 *
	 * @param string $slug The feature slug.
	 *
	 * @return bool|WP_Error
	 */
	public function exists( string $slug ) {
		$features = $this->repository->get( $this->key, $this->domain );

		if ( is_wp_error( $features ) ) {
			return $features;
		}

		return $features->get( $slug ) !== null;
	}

	/**
	 * Gets the feature collection from the catalog with live enabled state.
	 *
	 * @since 3.0.0
	 *
	 * @return Feature_Collection|WP_Error
	 */
	public function get_all() {
		$features = $this->repository->get( $this->key, $this->domain );

		if ( is_wp_error( $features ) ) {
			return $features;
		}

		$this->stamp_enabled_state( $features );

		return $features;
	}

	/**
	 * Looks up a feature by slug from the cached catalog with live enabled state.
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
	public function get( string $slug ): ?Feature {
		$features = $this->get_all();

		if ( is_wp_error( $features ) ) {
			return null;
		}

		return $features->get( $slug );
	}

	/**
	 * Stamps live enabled state onto every feature in the collection.
	 *
	 * The Feature_Collection may come from a transient cache where
	 * is_enabled values are stale. This method overwrites each
	 * feature's is_enabled with the current live state from its strategy.
	 *
	 * @since 3.0.0
	 *
	 * @param Feature_Collection $features The collection to stamp.
	 *
	 * @return void
	 */
	private function stamp_enabled_state( Feature_Collection $features ): void {
		foreach ( $features as $feature ) {
			$strategy           = $this->strategy_factory->make( $feature );
			$class              = get_class( $feature );
			$data               = $feature->to_array();
			$data['is_enabled'] = $strategy->is_active();

			$features->offsetSet( $feature->get_slug(), $class::from_array( $data ) );
		}
	}
}
