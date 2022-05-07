<?php

namespace StellarWP\Network\Resource;

use StellarWP\Network\Container;

/**
 * The base resource class for StellarWP Network plugins and services.
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
	 * @return string|null
	 */
	public function get_license_key() {
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
		return apply_filters( 'stellar_network_resource_get_slug', $this->slug );
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
		return apply_filters( 'stellar_network_resource_get_version', $this->version );
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
		$resource = apply_filters( 'stellar_network_resource_register_before_collection', $resource );

		$collection->add( $resource );

		/**
		 * Filters the registered resource.
		 *
		 * @since 1.0.0
		 *
		 * @param Resource_Abstract $resource Resource instance.
		 */
		$resource = apply_filters( 'stellar_network_resource_register', $resource );

		return $resource;
	}

	public function validate(): bool {
		return true;
	}
}
