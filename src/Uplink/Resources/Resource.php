<?php

namespace StellarWP\Uplink\Resources;

use stdClass;
use StellarWP\ContainerContract\ContainerInterface;
use StellarWP\Uplink\API;
use StellarWP\Uplink\Config;
use StellarWP\Uplink\Exceptions;
use StellarWP\Uplink\License\Manager\License_Handler;
use StellarWP\Uplink\Site\Data;
use StellarWP\Uplink\Utils;

/**
 * The base resource class for StellarWP Uplink plugins and services.
 *
 * @property-read string    $class The class name.
 * @property-read ContainerInterface $container Container instance.
 * @property-read string    $type The resource type.
 * @property-read string    $license_key License key.
 * @property-read string    $name The resource name.
 * @property-read string    $slug The resource slug.
 * @property-read string    $version The resource version.
 * @property-read string    $path The resource path.
 */
abstract class Resource {

	/**
	 * Resource class.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $class;

	/**
	 * Container instance.
	 *
	 * @since 1.0.0
	 *
	 * @var ContainerInterface
	 */
	protected $container;

	/**
	 * Resource type.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $type = 'resource';

	/**
	 * License object.
	 *
	 * @since 1.0.0
	 *
	 * @var License
	 */
	protected $license;

	/**
	 * License class.
	 *
	 * @since 1.0.0
	 *
	 * @var string|null
	 */
	protected $license_class;

	/**
	 * Resource name.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * Resource path to bootstrap file.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $path;

	/**
	 * Resource slug.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $slug;

	/**
	 * Resource version.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $version;

	/**
	 * Resource home url
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $home_url;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug Resource slug.
	 * @param string $name Resource name.
	 * @param string $version Resource version.
	 * @param string $path Resource path to bootstrap file.
	 * @param string $class Resource class.
	 * @param string|null $license_class Class that holds the embedded license key.
	 */
	public function __construct( $slug, $name, $version, $path, $class, string $license_class = null ) {
		$this->name          = $name;
		$this->slug          = $slug;
		$this->path          = $path;
		$this->class         = $class;
		$this->license_class = $license_class;
		$this->version       = $version;
		$this->container     = Config::get_container();
	}

	/**
	 * Whether the current site, in the current configuration is using network licensing.
	 *
	 * @return bool
	 */
	public function uses_network_licensing(): bool {
		return $this->container->get( License_Handler::class )->current_site_allows_network_licensing( $this );
	}

	/**
	 * Deletes the resource license key.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function delete_license_key(): bool {
		return $this->get_license_object()->delete_key();
	}

	/**
	 * Gets the resource class.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_class() {
		return $this->class;
	}

	/**
	 * Get the arguments for generating the download URL.
	 *
	 * @since 1.0.0
	 *
	 * @return array<mixed>
	 */
	public function get_download_args(): array {
		$args = [];

		/** @var Data */
		$data = $this->container->get( Data::class );

		$args['plugin']            = sanitize_text_field( $this->get_slug() );
		$args['installed_version'] = sanitize_text_field( $this->get_installed_version() ?: '' );
		$args['domain']            = sanitize_text_field( $data->get_domain( true ) );

		// get general stats
		/** @var array<string,array<mixed>> */
		$stats = $data->get_stats();

		$args['multisite']         = $stats['network']['multisite'];
		$args['network_activated'] = (int) $this->is_network_activated();
		$args['active_sites']      = $stats['network']['active_sites'];
		$args['wp_version']        = $stats['versions']['wp'];

		// the following is for install key inclusion (will apply later with PUE addons.)
		$args['key'] = Utils\Sanitize::key( $this->get_license_object()->get_key() );
		$args['dk']  = Utils\Sanitize::key( $this->get_license_object()->get_default_key() );
		$args['o']   = sanitize_text_field( $this->get_license_object()->get_key_origin_code() );

		return $args;
	}

	/**
	 * Get the currently installed version of the plugin.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_installed_version(): string {
		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		// @phpstan-ignore-next-line
		$plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $this->get_path() );

		return $plugin_data['Version'] ?: '';
	}

	/**
	 * Get the license class.
	 *
	 * @since 1.0.0
	 *
	 * @return string|null
	 */
	public function get_license_class() {
		return $this->license_class;
	}

	/**
	 * Gets the resource license key.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_license_key(): string {
		return $this->get_license_object()->get_key();
	}

	/**
	 * Get the license object.
	 *
	 * @since 1.0.0
	 *
	 * @return License
	 */
	public function get_license_object() {
		if ( null === $this->license ) {
			$this->license = new License( $this );
		}

		return $this->license;
	}

	/**
	 * Gets the resource name.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_name() {
		return $this->name;
	}

	/**
	 * Gets the resource path.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_path() {
		return $this->path;
	}

	/**
	 * Gets the resource slug.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_slug() {
		/**
		 * Filter the resource slug.
		 *
		 * @since 1.0.0
		 *
		 * @param string $slug Resource slug.
		 */
		return apply_filters( 'stellarwp/uplink/' . Config::get_hook_prefix(). '/resource_get_slug', $this->slug );
	}

	/**
	 * Gets the resource type.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_type() {
		return $this->type;
	}

	/**
	 * Get the arguments for generating the validation URL.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string,mixed>
	 */
	public function get_validation_args(): array {
		$args = [];

		$args['key']            = Utils\Sanitize::key( $this->get_license_object()->get_key() );
		$args['default_key']    = Utils\Sanitize::key( $this->get_license_object()->get_default_key() );
		$args['license_origin'] = sanitize_text_field( $this->get_license_object()->get_key_origin_code() );
		$args['plugin']         = sanitize_text_field( $this->get_slug() );
		$args['version']        = sanitize_text_field( $this->get_installed_version() ?: '' );

		return $args;
	}

	/**
	 * Gets the resource version.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_version() {
		/**
		 * Filter the resource version.
		 *
		 * @since 1.0.0
		 *
		 * @param string $version Resource version.
		 */
		return apply_filters( 'stellarwp/uplink/' . Config::get_hook_prefix(). '/resource_get_version', $this->version );
	}

	public function get_home_url(): ?string {
		return $this->home_url;
	}

	/**
	 * Returns whether or not the license is listed as valid.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function has_valid_license(): bool {
		return $this->get_license_object()->is_valid();
	}

	/**
	 * Checks if the resource is installed at the network level.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function is_network_activated() {
		if ( ! is_multisite() ) {
			return false;
		}

		if( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return is_plugin_active_for_network( $this->get_path() );
	}

	/**
	 * Register a resource and add it to the collection.
	 *
	 * @since 1.0.0
	 *
	 * @param string $name Resource name.
	 * @param string $slug Resource slug.
	 * @param string $path Resource path to bootstrap file.
	 * @param string $version Resource version.
	 * @param string $class Resource class.
	 * @param string|null $license_class Class that holds the embedded license key.
	 *
	 * @return Resource
	 */
	abstract public static function register( $name, $slug, $path, $class, $version, string $license_class = null );

	/**
	 * Register a resource and add it to the collection.
	 *
	 * @since 1.0.0
	 *
	 * @param string $resource_class Resource class.
	 * @param string $slug Resource slug.
	 * @param string $name Resource name.
	 * @param string $version Resource version.
	 * @param string $path Resource path to bootstrap file.
	 * @param string $class Resource class.
	 * @param string|null $license_class Class that holds the embedded license key.
	 *
	 * @return Resource
	 */
	public static function register_resource( $resource_class, $slug, $name, $version, $path, $class, string $license_class = null ) {
		/** @var Resource */
		$resource   = new $resource_class( $slug, $name, $version, $path, $class, $license_class );

		/** @var Collection */
		$collection = Config::get_container()->get( Collection::class );

		/**
		 * Filters the registered plugin before adding to the collection.
		 *
		 * @since 1.0.0
		 *
		 * @param Resource $resource Resource instance.
		 */
		$resource = apply_filters( 'stellarwp/uplink/' . Config::get_hook_prefix(). '/resource_register_before_collection', $resource );

		if ( ! empty( $collection[ $resource->get_slug() ] ) ) {
			throw new Exceptions\ResourceAlreadyRegisteredException( $resource->get_slug() );
		}

		$collection->add( $resource );

		/**
		 * Filters the registered resource.
		 *
		 * @since 1.0.0
		 *
		 * @param Resource $resource Resource instance.
		 */
		$resource = apply_filters( 'stellarwp/uplink/' . Config::get_hook_prefix(). '/resource_register', $resource );

		return $resource;
	}

	/**
	 * Sets the license key for the resource.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key License key.
	 *
	 * @return bool
	 */
	public function set_license_key( string $key ): bool {
		return $this->get_license_object()->set_key( $key );
	}

	/**
	 * Set plugin home url
	 *
	 * @param string $url
	 *
	 * @return string
	 */
	public function set_home_url( $url = '' ): string {
		$this->home_url = $url;

		return $this->home_url;
	}

	/**
	 * Returns whether or not the license key is in need of validation.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function should_validate(): bool {
		return $this->get_license_object()->is_validation_expired();
	}

	/**
	 * Validates the resource's license key.
	 *
	 * @TODO add an action here so this can fire when the key is deleted or modified.
	 *
	 * @since 1.0.0
	 *
	 * @param string|null $key License key.
	 *
	 * @return API\Validation_Response
	 */
	public function validate_license( ?string $key = null ): API\Validation_Response {
		/** @var API\Client */
		$api = $this->container->get( API\Client::class );

		if ( empty( $key ) ) {
			$key = $this->get_license_key();
		}

		if ( empty( $key ) ) {
			$results = new API\Validation_Response( null, new stdClass(), $this );
			$results->set_is_valid( false );
			return $results;
		}

		$results             = $api->validate_license( $this, $key );
		$results_key         = $results->get_key();
		$result_type         = $results->get_result();
		$has_replacement_key = $results->has_replacement_key();

		if (
			$result_type === 'new'
			|| $has_replacement_key
		) {
			$this->set_license_key( $results_key );
		}

		$this->get_license_object()->set_key_status( $results->is_valid() );

		return $results;
	}
}
