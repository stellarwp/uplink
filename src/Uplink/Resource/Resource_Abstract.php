<?php

namespace StellarWP\Uplink\Resource;

use StellarWP\Uplink\API\Client;
use StellarWP\Uplink\Container;
use StellarWP\Uplink\Exceptions;
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
	 * Validates the resource's license key.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key License key.
	 * @param bool $do_network_validate Validate the key as a network key.
	 *
	 * @return array<mixed>
	 */
	public function validate_license( $key, $do_network_validate = false ): array {
		/** @var Client */
		$api = $this->container->make( Client::class );

		if ( empty( $key ) ) {
			$key = $this->get_license_object()->get_key();
		}

		$results            = $api->validate_license( $this, $key );
		$expiration         = isset( $results->expiration ) ? $results->expiration : __( 'unknown date', 'stellar-uplink-client' );
		$response           = [];
		$response['status'] = 0;

		if ( empty( $results ) ) {
			$response['message'] = __( 'Sorry, key validation server is not available.', 'stellar-uplink-client' );
		} elseif ( isset( $results->api_expired ) && 1 === (int) $results->api_expired ) {
			$response['message'] = $this->get_license_expired_message();
			$response['api_expired'] = true;
		} elseif ( isset( $results->api_upgrade ) && 1 === (int) $results->api_upgrade ) {
			$response['message'] = $this->get_api_message( $results );
			$response['api_upgrade'] = true;
		} elseif ( isset( $results->api_invalid ) && 1 === (int) $results->api_invalid ) {
			$response['message'] = $this->get_api_message( $results );
			$response['api_invalid'] = true;
		} else {
			$key_type = 'local';

			if ( $do_network_validate ) {
				$key_type = 'network';
			}

			$current_key = $this->get_license_object()->get_key( $key_type );

			if ( $current_key && $current_key === $key ) {
				$default_success_msg = esc_html( sprintf( __( 'Valid Key! Expires on %s', 'stellar-uplink-client' ), $expiration ) );
			} else {
				$this->get_license_object()->set_key( $key, $key_type );

				$default_success_msg = esc_html( sprintf( __( 'Thanks for setting up a valid key. It will expire on %s', 'stellar-uplink-client' ), $expiration ) );
			}

			//$pue_notices->clear_notices( $this->get_name() );

			$response['status']     = isset( $results->api_message ) ? 2 : 1;
			$response['message']    = isset( $results->api_message ) ? $results->api_message : $default_success_msg;
			$response['expiration'] = esc_html( $expiration );

			if ( isset( $results->daily_limit ) ) {
				$response['daily_limit'] = intval( $results->daily_limit );
			}
		}

		$response['message'] = wp_kses( $response['message'], 'data' );

		$this->get_license_object()->set_key_status( $response['status'] );

		return $response;
	}

	public function get_license_expired_message() {
		return '<a href="https://evnt.is/195y" target="_blank" class="button button-primary">' .
			__( 'Renew Your License Now', 'stellar-uplink-client' ) .
			'<span class="screen-reader-text">' .
			__( ' (opens in a new window)', 'stellar-uplink-client' ) .
			'</span></a>';
	}

		/**
	 * Processes variable substitutions for server-side API message.
	 *
	 * @param Tribe__PUE__Plugin_Info $info
	 *
	 * @return string
	 */
	private function get_api_message( $info ) {
		// this default message should never show, but is here as a fallback just in case.
		$message = sprintf(
			esc_html__( 'There is an update for %s. You\'ll need to %scheck your license%s to have access to updates, downloads, and support.', 'stellar-uplink-client' ),
			$this->get_name(),
			'<a href="https://theeventscalendar.com/license-keys/">',
			'</a>'
		);

		if ( ! empty( $info->api_inline_invalid_message ) ) {
			$message = wp_kses( $info->api_inline_invalid_message, 'post' );
		}

		$message = str_replace( '%plugin_name%', $this->get_name(), $message );
		$message = str_replace( '%plugin_slug%', $this->get_slug(), $message );
		$message = str_replace( '%update_url%', $this->get_pue_update_url() . '/', $message );
		$message = str_replace( '%version%', $info->version, $message );
		$message = str_replace( '%changelog%', '<a class="thickbox" title="' . $this->get_name() . '" href="plugin-install.php?tab=plugin-information&plugin=' . $this->get_slug() . '&TB_iframe=true&width=640&height=808">what\'s new</a>', $message );

		return $message;
	}
}
