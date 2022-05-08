<?php

namespace StellarWP\Uplink\Resource;

use StellarWP\Uplink\API;
use StellarWP\Uplink\Container;
use StellarWP\Uplink\Exceptions;
use StellarWP\Uplink\Messages;
use StellarWP\Uplink\Site\Data;
/**
 * The base resource class for StellarWP Uplink plugins and services.
 *
 * @property-read string    $class The class name.
 * @property-read Container $container Container instance.
 * @property-read string    $type The resource type.
 * @property-read string    $license_key License key.
 * @property-read string    $name The resource name.
 * @property-read string    $slug The resource slug.
 * @property-read string    $version The resource version.
 * @property-read string    $path The resource path.
 */
abstract class Resource_Abstract {
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
	 * @var Container
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
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug Resource slug.
	 * @param string $name Resource name.
	 * @param string $path Resource path to bootstrap file.
	 * @param string $class Resource class.
	 * @param string $version Resource version.
	 * @param string|null $license_class Class that holds the embedded license key.
	 * @param Container|null $container Container instance.
	 */
	public function __construct( $slug, $name, $version, $path, $class, string $license_class = null, Container $container = null ) {
		$this->name          = $name;
		$this->slug          = $slug;
		$this->path          = $path;
		$this->class         = $class;
		$this->license_class = $license_class;
		$this->version       = $version;
		$this->container     = $container ?: Container::init();
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
	public function get_download_args() {
		$args = [];

		/** @var Data */
		$data = $this->container->make( Data::class );

		$args['plugin']            = sanitize_text_field( $this->get_slug() );
		$args['installed_version'] = sanitize_text_field( $this->get_installed_version() ?: '' );
		$args['domain']            = sanitize_text_field( $data->get_domain() );

		// get general stats
		/** @var array<string,array<mixed>> */
		$stats = $data->get_stats();

		$args['multisite']         = $stats['network']['multisite'];
		$args['network_activated'] = (int) $this->is_network_activated();
		$args['active_sites']      = $stats['network']['active_sites'];
		$args['wp_version']        = $stats['versions']['wp'];

		// the following is for install key inclusion (will apply later with PUE addons.)
		$args['key'] = sanitize_text_field( $this->get_license_object()->get_key() ?: '' );
		$args['dk']  = sanitize_text_field( $this->get_license_object()->get_key( 'default' ) ?: '' );
		$args['o']   = sanitize_text_field( $this->get_license_object()->get_key_origin_code() );

		return $args;
	}

	/**
	 * Get the currently installed version of the plugin.
	 *
	 * @since 1.0.0
	 *
	 * @return string|null
	 */
	public function get_installed_version() {
		if ( ! function_exists( 'get_plugins' ) ) {
			return null;
		}

		$all_plugins = get_plugins();

		if (
			! array_key_exists( $this->get_path(), $all_plugins )
			|| ! array_key_exists( 'Version', $all_plugins[ $this->get_path() ] )
		) {
			return null;
		}

		return $all_plugins[ $this->get_path() ]['Version'];
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
	 * @param string $type The type of key to get (any, network, local, default).
	 *
	 * @return string
	 */
	public function get_license_key( $type = 'local' ): string {
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
		return apply_filters( 'stellar_uplink_resource_get_slug', $this->slug );
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
	public function get_validation_args() {
		$args = [];

		$args['key']            = sanitize_text_field( $this->get_license_object()->get_key() ?: '' );
		$args['default_key']    = sanitize_text_field( $this->get_license_object()->get_key( 'default' ) ?: '' );
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
		return apply_filters( 'stellar_uplink_resource_get_version', $this->version );
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

		return is_plugin_active_for_network( $this->get_path() );
	}

	/**
	 * Whether the plugin is network activated and licensed or not.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function is_network_licensed(): bool {
		return $this->get_license_object()->is_network_licensed();
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
	 * @return Resource_Abstract
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
	 * @return Resource_Abstract
	 */
	public static function register_resource( $resource_class, $slug, $name, $version, $path, $class, string $license_class = null ) {
		/** @var Resource_Abstract */
		$resource   = new $resource_class( $slug, $name, $version, $path, $class, $license_class );

		/** @var Collection */
		$collection = Container::init()->make( Collection::class );

		/**
		 * Filters the registered plugin before adding to the collection.
		 *
		 * @since 1.0.0
		 *
		 * @param Resource_Abstract $resource Resource instance.
		 */
		$resource = apply_filters( 'stellar_uplink_resource_register_before_collection', $resource );

		if ( ! empty( $collection[ $resource->get_slug() ] ) ) {
			throw new Exceptions\ResourceAlreadyRegisteredException( $resource->get_slug() );
		}

		$collection->add( $resource );

		/**
		 * Filters the registered resource.
		 *
		 * @since 1.0.0
		 *
		 * @param Resource_Abstract $resource Resource instance.
		 */
		$resource = apply_filters( 'stellar_uplink_resource_register', $resource );

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
	public function set_license_key( $key ): bool {
		return $this->get_license_object()->set_key( $key );
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
	 * @since 1.0.0
	 *
	 * @param string|null $key License key.
	 * @param bool $do_network_validate Validate the key as a network key.
	 *
	 * @return API\Validation_Response
	 */
	public function validate_license( $key = null, $do_network_validate = false ) {
		/** @var API\Client */
		$api = $this->container->make( API\Client::class );

		if ( empty( $key ) ) {
			$key = $this->get_license_key();
		}

		$results = $api->validate_license( $this, $key, $do_network_validate ? 'network' : 'local' );

		if ( 'new' === $results->get_result() ) {
			$this->get_license_object()->set_key( $results->get_key() );
		}

		$this->get_license_object()->set_key_status( $results->is_valid() );

		return $results;
	}
}
