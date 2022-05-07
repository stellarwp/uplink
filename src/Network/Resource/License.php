<?php

namespace StellarWP\Network\Resource;

use StellarWP\Network\Container;

class License {
	/**
	 * Container instance.
	 *
	 * @since 1.0.0
	 *
	 * @var Container
	 */
	protected $container;

	/**
	 * License key.
	 *
	 * @since 1.0.0
	 *
	 * @var string|null
	 */
	protected $key;

	/**
	 * Option prefix.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public static $option_prefix = 'stellar_network_license_key_';

	/**
	 * Resource instance.
	 *
	 * @since 1.0.0
	 *
	 * @var Resource_Abstract
	 */
	protected $resource;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param Resource_Abstract $resource The resource instance.
	 * @param Container|null $container Container instance.
	 */
	public function __construct( Resource_Abstract $resource, Container $container = null ) {
		$this->resource  = $resource;
		$this->container = $container ?: Container::init();
	}

	/**
	 * Get the license key.
	 *
	 * @since 1.0.0
	 *
	 * @return string|null
	 */
	public function get_key() {
		if ( ! empty( $this->key ) ) {
			return $this->key;
		}

		$this->key = $this->get_key_from_option();

		if ( empty( $this->key ) ) {
			$this->key = $this->get_key_from_license_file();
		}

		return $this->key;
	}

	/**
	 * Get the license key from a class that holds the license key.
	 *
	 * @since 1.0.0
	 *
	 * @return string|null
	 */
	protected function get_key_from_license_file() {
		$license_class = $this->resource->get_license_class();

		if (
			empty( $license_class )
			|| ! defined( $license_class . '::KEY' )
		) {
			return null;
		}

		return $license_class::KEY;
	}

	/**
	 * Get the license key from an option.
	 *
	 * @since 1.0.0
	 *
	 * @return string|null
	 */
	protected function get_key_from_option() {
		/** @var string|null */
		return get_site_option( $this->get_key_option_name(), null );
	}

	/**
	 * Get the option name for the license key.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_key_option_name(): string {
		return static::$option_prefix . $this->resource->get_slug();
	}

	/**
	 * Sets the key in site options.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key License key.
	 *
	 * @return bool
	 */
	public function set_key( $key ): bool {
		$this->key = $key;

		return update_site_option( $this->get_key_option_name(), $key );
	}

	/**
	 * Get the key as a string.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function __toString(): string {
		return $this->get_key() ?: '';
	}
}
