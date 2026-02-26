<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Features\Update;

use StellarWP\Uplink\Features\API\Feature_Client;
use StellarWP\Uplink\Features\API\Update_Client;
use StellarWP\Uplink\Features\Types\Zip;
use StellarWP\Uplink\Resources\Collection;
use StellarWP\Uplink\Site\Data;
use stdClass;

/**
 * Consolidated update handler for Zip features and Resources.
 *
 * Filters `plugins_api` and `pre_set_site_transient_update_plugins`
 * to provide update information from the consolidation server.
 *
 * @since 3.0.0
 */
class Handler {

	/**
	 * The update API client.
	 *
	 * @since 3.0.0
	 *
	 * @var Update_Client
	 */
	private Update_Client $update_client;

	/**
	 * The feature catalog client.
	 *
	 * @since 3.0.0
	 *
	 * @var Feature_Client
	 */
	private Feature_Client $feature_client;

	/**
	 * The resource collection for this Uplink instance.
	 *
	 * @since 3.0.0
	 *
	 * @var Collection
	 */
	private Collection $resource_collection;

	/**
	 * The site data provider.
	 *
	 * @since 3.0.0
	 *
	 * @var Data
	 */
	private Data $site_data;

	/**
	 * Constructor for the consolidated update handler.
	 *
	 * @since 3.0.0
	 *
	 * @param Update_Client  $update_client       The update API client.
	 * @param Feature_Client $feature_client      The feature catalog client.
	 * @param Collection     $resource_collection The resource collection.
	 * @param Data           $site_data           The site data provider.
	 *
	 * @return void
	 */
	public function __construct(
		Update_Client $update_client,
		Feature_Client $feature_client,
		Collection $resource_collection,
		Data $site_data
	) {
		$this->update_client       = $update_client;
		$this->feature_client      = $feature_client;
		$this->resource_collection = $resource_collection;
		$this->site_data           = $site_data;
	}

	/**
	 * Filters the plugins_api response for Zip features and Resources.
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

		// TODO: Check for a Unified License Key. If absent, return $result so the old pathway runs without modification.

		/** @var string $slug */
		$slug = $args->slug;

		$products = $this->collect_products();

		if ( empty( $products['versions'] ) ) {
			return $result;
		}

		$domain   = $this->site_data->get_domain();
		$response = $this->update_client->check_updates(
			'', // TODO: Pass the unified license key.
			$domain,
			$products['versions']
		);

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

		// TODO: Check for a Unified License Key. If absent, return $transient so the old pathway runs without modification.

		$products = $this->collect_products();

		if ( empty( $products['versions'] ) ) {
			return $transient;
		}

		$domain   = $this->site_data->get_domain();
		$response = $this->update_client->check_updates(
			'', // TODO: Pass the unified license key.
			$domain,
			$products['versions']
		);

		if ( is_wp_error( $response ) || ! is_array( $response ) ) {
			return $transient;
		}

		if ( ! isset( $transient->response ) ) { // @phpstan-ignore property.notFound
			$transient->response = []; // @phpstan-ignore property.notFound
		}

		if ( ! isset( $transient->no_update ) ) { // @phpstan-ignore property.notFound
			$transient->no_update = []; // @phpstan-ignore property.notFound
		}

		foreach ( $response as $slug => $update_data ) {
			if ( ! is_string( $slug ) || ! is_array( $update_data ) ) {
				continue;
			}

			$plugin_file = $products['plugin_files'][ $slug ] ?? '';

			if ( empty( $plugin_file ) ) {
				continue;
			}

			/** @var string $new_version */
			$new_version       = $update_data['new_version'] ?? '';
			$installed_version = $products['versions'][ $slug ] ?? '';

			$update_object = $this->to_update_object( $slug, $plugin_file, $update_data );

			if ( version_compare( $new_version, $installed_version, '>' ) ) {
				$transient->response[ $plugin_file ] = $update_object; // @phpstan-ignore property.notFound, offsetAccess.nonOffsetAccessible

				if ( isset( $transient->no_update[ $plugin_file ] ) ) { // @phpstan-ignore property.notFound, offsetAccess.nonOffsetAccessible
					unset( $transient->no_update[ $plugin_file ] ); // @phpstan-ignore property.notFound, offsetAccess.nonOffsetAccessible
				}
			} else {
				$transient->no_update[ $plugin_file ] = $update_object; // @phpstan-ignore property.notFound, offsetAccess.nonOffsetAccessible

				if ( isset( $transient->response[ $plugin_file ] ) ) { // @phpstan-ignore property.notFound, offsetAccess.nonOffsetAccessible
					unset( $transient->response[ $plugin_file ] ); // @phpstan-ignore property.notFound, offsetAccess.nonOffsetAccessible
				}
			}
		}

		return $transient;
	}

	/**
	 * Collects all installed Zip features and Resources with their versions and plugin files.
	 *
	 * @since 3.0.0
	 *
	 * @return array{versions: array<string, string>, plugin_files: array<string, string>}
	 */
	private function collect_products(): array {
		$versions     = [];
		$plugin_files = [];

		// Collect Zip features.
		$features = $this->feature_client->get_features();

		if ( ! is_wp_error( $features ) ) {
			$zip_features = $features->filter( null, null, null, 'zip' );

			foreach ( $zip_features as $feature ) {
				if ( ! $feature instanceof Zip || ! $feature->is_installed() ) {
					continue;
				}

				$slug                  = $feature->get_slug();
				$versions[ $slug ]     = $feature->get_installed_version() ?? '';
				$plugin_files[ $slug ] = $feature->get_plugin_file();
			}
		}

		// Collect Resources from this instance's collection.
		// TODO: Cross-instance gathering deferred to a follow-up PR.
		$plugins = $this->resource_collection->get_plugins();

		foreach ( $plugins as $plugin ) {
			$slug                  = $plugin->get_slug();
			$versions[ $slug ]     = $plugin->get_installed_version();
			$plugin_files[ $slug ] = $plugin->get_path();
		}

		return [
			'versions'     => $versions,
			'plugin_files' => $plugin_files,
		];
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
