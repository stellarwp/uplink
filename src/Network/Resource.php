<?php

namespace StellarWP\Network;

class Resource {
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
	 * License key.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $license_key;

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
	 * @param string $name Resource name.
	 * @param string $slug Resource slug.
	 * @param string $path Resource path to bootstrap file.
	 * @param string $class Resource class.
	 * @param string $version Resource version.
	 * @param Container|null $container Container instance.
	 */
	public function __construct( $name, $slug, $path, $class, $version, \tad_DI52_Container $container = null ) {
		$this->name      = $name;
		$this->slug      = $slug;
		$this->path      = $path;
		$this->class     = $class;
		$this->version   = $version;
		$this->container = $container ?: Container::init();
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
	 * Gets the resource license key.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_license_key() {
		return $this->license_key;
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
		return $this->slug;
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
	 * Register a resource and add it to the collection.
	 *
	 * @since 1.0.0
	 *
	 * @param string $name Resource name.
	 * @param string $slug Resource slug.
	 * @param string $path Resource path to bootstrap file.
	 * @param string $class Resource class.
	 * @param string $version Resource version.
	 *
	 * @return Resource
	 */
	public static function register( $name, $slug, $path, $class, $version ) {
		$resource   = new static( $name, $slug, $path, $class, $version );
		$collection = Container::init()->make( Resource\Collection::class );

		/**
		 * Filters the registered plugin before adding to the collection.
		 *
		 * @since 1.0.0
		 *
		 * @param Resource $resource Resource instance.
		 */
		$resource = apply_filters( 'stellar_network_resource_register_before_collection', $resource );

		$collection->add( $resource );

		/**
		 * Filters the registered resource.
		 *
		 * @since 1.0.0
		 *
		 * @param Resource $resource Resource instance.
		 */
		$resource = apply_filters( 'stellar_network_resource_register', $resource );

		return $resource;
	}
}