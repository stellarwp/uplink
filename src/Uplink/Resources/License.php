<?php

namespace StellarWP\Uplink\Resources;

use StellarWP\ContainerContract\ContainerInterface;
use StellarWP\Uplink\Config;
use StellarWP\Uplink\License\Storage\Storage_Handler;
use StellarWP\Uplink\Site\Data;
use StellarWP\Uplink\Utils;

class License {

	/**
	 * How often to check for updates (in hours).
	 *
	 * @var int
	 */
	protected $check_period = 12;

	/**
	 * Container instance.
	 *
	 * @since 1.0.0
	 *
	 * @var ContainerInterface
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
	 * License origin.
	 *
	 *     network_option
	 *     site_option
	 *     file
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $key_origin;

	/**
	 * License origin code.
	 *
	 *     m = manual
	 *     e = embedded
	 *     o = original
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $key_origin_code;

	/**
	 * Option prefix for the key status.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public static $key_status_option_prefix = 'stellarwp_uplink_license_key_status_';

	/**
	 * Resource instance.
	 *
	 * @since 1.0.0
	 *
	 * @var Resource
	 */
	protected $resource;

	/**
	 * The storage handler.
	 *
	 * @var Storage_Handler
	 */
	protected $storage;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param  Resource                 $resource   The resource instance.
	 * @param  ContainerInterface|null  $container  Container instance.
	 *
	 * @throws \RuntimeException
	 */
	public function __construct( Resource $resource, $container = null ) {
		$this->resource  = $resource;
		$this->container = $container ?: Config::get_container();
		$this->storage   = $this->container->get( Storage_Handler::class );
	}

	/**
	 * Deletes the license key from the appropriate storage location.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function delete_key(): bool {
		return $this->storage->delete( $this->resource );
	}

	/**
	 * Get the license key.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_key(): string {
		if ( empty( $this->key ) ) {
			$this->key = $this->storage->get( $this->resource );

			if ( $this->storage->is_original() ) {
				$this->key_origin = 'file';
			}
		}

		/**
		 * Filter the license key.
		 *
		 * @since 1.0.0
		 *
		 * @param string|null $key The license key.
		 */
		$key = apply_filters( 'stellarwp/uplink/' . Config::get_hook_prefix(). '/license_get_key', $this->key );

		return (string) $key;
	}

	/**
	 * Get the license key from a class that holds the license key (aka the original key).
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_default_key(): ?string {
		return (string) $this->storage->get_from_file( $this->resource );
	}

	/**
	 * Get the license key origin code.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_key_origin_code(): string {
		if ( ! empty( $this->key_origin_code ) ) {
			return $this->key_origin_code;
		}

		$key         = $this->get_key();
		$default_key = $this->get_default_key();

		if ( $key === $default_key ) {
			$this->key_origin_code = 'o';
		} elseif ( 'file' === $this->key_origin ) {
			$this->key_origin_code = 'e';
		} else {
			$this->key_origin_code = 'm';
		}

		return $this->key_origin_code;
	}

	/**
	 * Get the license key status from an option.
	 *
	 * @since 1.0.0
	 *
	 * @return string|null
	 */
	protected function get_key_status(): ?string {
		$network = $this->resource->uses_network_licensing();
		$func    = 'get_option';

		if ( $network ) {
			$func = 'get_site_option';
		}

		/** @var string|null */
		$status = $func( $this->get_key_status_option_name(), 'invalid' );
		$key    = $this->get_key();

		if ( null === $status && $key ) {
			$this->resource->validate_license( $key );
			$status = $func( $this->get_key_status_option_name(), 'invalid' );
		}

		return $status;
	}

	/**
	 * Get the option name for the license key status.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_key_status_option_name(): string {
		/** @var Data */
		$data = $this->container->get( Data::class );

		return static::$key_status_option_prefix . $this->resource->get_slug() . '_'. $data->get_site_domain();
	}

	/**
	 * Whether the plugin is network activated and licensed or not.
	 *
	 * @TODO remove this once override logic is complete.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function is_network_licensed() {
//		$is_network_licensed = false;
//
//		if ( ! is_network_admin() && $this->resource->is_network_activated() ) {
//			$network_key = $this->get_key( 'network' );
//			$local_key   = $this->get_key( 'local' );
//
//			// Check whether the network is licensed and NOT overridden by local license
//			// TODO: Need to account for this in the new system
//			if ( $network_key && ( empty( $local_key ) || $local_key === $network_key ) ) {
//				$is_network_licensed = true;
//			}
//		}
//
//		return $is_network_licensed;
	}

	/**
	 * Whether the plugin is validly licensed or not.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function is_valid(): bool {
		return 'valid' === $this->get_key_status();
	}

	/**
	 * Whether the plugin license is expired or not.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function is_expired(): bool {
		return 'expired' === $this->get_key_status();
	}

	/**
	 * Whether the validation has expired.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function is_validation_expired(): bool {
		$option_expiration = get_option( $this->get_key_status_option_name() . '_timeout', null );
		return is_null( $option_expiration ) || ( time() > $option_expiration );
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
	public function set_key( string $key ): bool {
		$key = Utils\Sanitize::key( $key );

		$this->key = $key;

		return $this->storage->store( $this->resource, $key );
	}

	/**
	 * Sets the key status based on the key validation check results.
	 *
	 * @since TBD
	 *
	 * @param int $valid 0 for invalid, 1 or 2 for valid.
	 *
	 * @return void
	 */
	public function set_key_status( $valid ): void {
		$status  = Utils\Checks::is_truthy( $valid ) ? 'valid' : 'invalid';
		$network = $this->resource->uses_network_licensing();
		$timeout = $this->check_period * HOUR_IN_SECONDS;
		$func    = 'update_option';

		if ( $network ) {
			$func = 'update_site_option';
		}

		$func( $this->get_key_status_option_name(), $status );
		$func( $this->get_key_status_option_name() . '_timeout', $timeout );
	}

	/**
	 * Get the key as a string.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function __toString(): string {
		return $this->get_key();
	}
}
