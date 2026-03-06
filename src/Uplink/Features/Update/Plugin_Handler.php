<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Features\Update;

use StellarWP\Uplink\Features\Feature_Repository;
use StellarWP\Uplink\Features\Types\Plugin;
use StellarWP\Uplink\Site\Data;
use StellarWP\Uplink\Utils\Cast;
use stdClass;

/**
 * Consolidated update handler for Plugin features.
 *
 * Filters `plugins_api`, `pre_set_site_transient_update_plugins`,
 * and `site_transient_update_plugins` to provide update information
 * from the consolidation server.
 *
 * @since 3.0.0
 */
class Plugin_Handler {

	/**
	 * The update repository.
	 *
	 * @since 3.0.0
	 *
	 * @var Update_Repository
	 */
	private Update_Repository $update_repository;

	/**
	 * The feature repository.
	 *
	 * @since 3.0.0
	 *
	 * @var Feature_Repository
	 */
	private Feature_Repository $feature_repository;

	/**
	 * The site data provider.
	 *
	 * @since 3.0.0
	 *
	 * @var Data
	 */
	private Data $site_data;

	/**
	 * The license key.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	private string $key;

	/**
	 * Constructor for the consolidated update handler.
	 *
	 * @since 3.0.0
	 *
	 * @param Update_Repository  $update_repository  The update repository.
	 * @param Feature_Repository $feature_repository The feature repository.
	 * @param Data               $site_data          The site data provider.
	 * @param string             $key                The license key.
	 *
	 * @return void
	 */
	public function __construct(
		Update_Repository $update_repository,
		Feature_Repository $feature_repository,
		Data $site_data,
		string $key
	) {
		$this->update_repository  = $update_repository;
		$this->feature_repository = $feature_repository;
		$this->site_data          = $site_data;
		$this->key                = $key;
	}

	/**
	 * Filters the plugins_api response for Plugin features.
	 *
	 * Calls check_updates() which returns from cache if available,
	 * or fetches fresh data from the consolidation server.
	 *
	 * @since 3.0.0
	 *
	 * @param mixed       $result The current result (false or object).
	 * @param string|null $action The API action.
	 * @param object|null $args   The API request args.
	 *
	 * @return mixed
	 */
	public function filter_plugins_api( $result, ?string $action = null, $args = null ) {
		if ( 'plugin_information' !== $action || ! is_object( $args ) || empty( $args->slug ) ) {
			return $result;
		}

		if ( empty( $this->key ) ) {
			return $result;
		}

		/** @var string $slug */
		$slug = $args->slug;

		// Check whether the requested slug belongs to a known Plugin feature.
		$features = $this->feature_repository->get( $this->key, $this->site_data->get_domain() );

		if ( is_wp_error( $features ) || $features->get( $slug ) === null ) {
			return $result;
		}

		$domain   = $this->site_data->get_domain();
		$response = $this->update_repository->get( $this->key, $domain );

		if ( is_wp_error( $response ) || empty( $response[ $slug ] ) ) {
			return $result;
		}

		return $this->to_wp_format( $slug, $response[ $slug ] );
	}

	/**
	 * Filters the update_plugins transient to inject consolidated updates.
	 *
	 * @since 3.0.0
	 *
	 * @param mixed $transient The update_plugins transient value.
	 *
	 * @return mixed
	 */
	public function filter_update_check( $transient ) {
		if ( ! is_object( $transient ) ) {
			return $transient;
		}

		if ( empty( $this->key ) ) {
			return $transient;
		}

		$domain   = $this->site_data->get_domain();
		$response = $this->update_repository->get( $this->key, $domain );

		if ( is_wp_error( $response ) || ! is_array( $response ) ) {
			return $transient;
		}

		/**
		 * WordPress stores update data in two arrays on the transient object:
		 * - `response`: plugins that have a newer version available.
		 * - `no_update`: plugins that are up-to-date (checked, but no update).
		 *
		 * Both are keyed by plugin file path and contain stdClass objects.
		 */
		/** @var stdClass $transient */
		if ( ! property_exists( $transient, 'response' ) ) {
			$transient->response = [];
		}

		if ( ! property_exists( $transient, 'no_update' ) ) {
			$transient->no_update = [];
		}

		/** @var array<string, stdClass> $wp_response */
		$wp_response = $transient->response;
		/** @var array<string, stdClass> $wp_no_update */
		$wp_no_update = $transient->no_update;

		$features = $this->feature_repository->get( $this->key, $domain );

		foreach ( $response as $slug => $update_data ) {
			if ( ! is_string( $slug ) || ! is_array( $update_data ) ) {
				continue;
			}

			$plugin_file = Cast::to_string( $update_data['plugin_file'] ?? '' );

			if ( empty( $plugin_file ) ) {
				continue;
			}

			/** @var string $new_version */
			$new_version       = $update_data['new_version'] ?? '';
			$installed_version = '';

			if ( ! is_wp_error( $features ) ) {
				$feature = $features->get( $slug );

				if ( $feature instanceof Plugin ) {
					$installed_version = $feature->get_installed_version() ?? '';
				}
			}

			$update_object = $this->to_update_object( $slug, $plugin_file, $update_data );

			/**
			 * Place the update object in `response` if a newer version is available, otherwise in `no_update`.
			 * WordPress uses this distinction to show (or hide) the plugin on the Updates page.
			 *
			 * Each plugin file should only appear in one array, so we remove it from the other when placing it.
			 */
			if ( version_compare( $new_version, $installed_version, '>' ) ) {
				$wp_response[ $plugin_file ] = $update_object;
				unset( $wp_no_update[ $plugin_file ] );
			} else {
				$wp_no_update[ $plugin_file ] = $update_object;
				unset( $wp_response[ $plugin_file ] );
			}
		}

		$transient->response  = $wp_response;
		$transient->no_update = $wp_no_update;

		return $transient;
	}

	/**
	 * Builds a WordPress-format plugin info object for plugins_api responses.
	 *
	 * @since 3.0.0
	 *
	 * @param string               $slug        The plugin slug.
	 * @param array<string, mixed> $update_data The update data from the consolidation server.
	 *
	 * @return stdClass
	 */
	private function to_wp_format( string $slug, array $update_data ): stdClass {
		$info = new stdClass();

		$info->name          = $update_data['name'] ?? '';
		$info->slug          = $slug;
		$info->version       = $update_data['new_version'] ?? '';
		$info->requires      = $update_data['requires'] ?? '';
		$info->tested        = $update_data['tested'] ?? '';
		$info->download_link = $update_data['package'] ?? '';
		$info->author        = $update_data['author'] ?? '';
		$info->homepage      = $update_data['url'] ?? '';
		$info->last_updated  = $update_data['last_updated'] ?? '';
		$info->sections      = $update_data['sections'] ?? [ 'description' => '' ];

		return $info;
	}

	/**
	 * Builds an update object for the update_plugins transient.
	 *
	 * @since 3.0.0
	 *
	 * @param string               $slug        The plugin slug.
	 * @param string               $plugin_file The plugin file path.
	 * @param array<string, mixed> $update_data The update data from the consolidation server.
	 *
	 * @return stdClass
	 */
	private function to_update_object( string $slug, string $plugin_file, array $update_data ): stdClass {
		$update = new stdClass();

		$update->id          = $update_data['id'] ?? sprintf( 'stellarwp/plugins/%s', $slug );
		$update->plugin      = $plugin_file;
		$update->slug        = $slug;
		$update->new_version = $update_data['new_version'] ?? '';
		$update->url         = $update_data['url'] ?? '';
		$update->package     = $update_data['package'] ?? '';
		$update->tested      = $update_data['tested'] ?? '';
		$update->requires    = $update_data['requires'] ?? '';

		return $update;
	}
}
