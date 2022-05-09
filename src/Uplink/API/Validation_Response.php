<?php

namespace StellarWP\Uplink\API;

use StellarWP\Uplink\Container;
use StellarWP\Uplink\Messages;
use StellarWP\Uplink\Resource\Resource_Abstract;

class Validation_Response {
	/**
	 * Validation response message.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $api_response_message;

	/**
	 * Container instance.
	 *
	 * @since 1.0.0
	 *
	 * @var Container
	 */
	protected $container;

	/**
	 * Current resource key.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $current_key;

	/**
	 * Daily limit from the validation.
	 *
	 * @since 1.0.0
	 *
	 * @var int
	 */
	protected $daily_limit;

	/**
	 * Expiration from the validation.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $expiration;

	/**
	 * Is response valid.
	 *
	 * @since 1.0.0
	 *
	 * @var bool
	 */
	protected $is_valid = true;

	/**
	 * License key.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $key;

	/**
	 * Resource instance.
	 *
	 * @since 1.0.0
	 *
	 * @var Resource_Abstract
	 */
	protected $resource;

	/**
	 * Validation response.
	 *
	 * @since 1.0.0
	 *
	 * @var \stdClass
	 */
	protected $response;

	/**
	 * Result of the validation.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $result = 'success';

	/**
	 * Validation type.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $validation_type;

	/**
	 * Version from validation response.
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
	 * @param string|null       $key             License key.
	 * @param string            $validation_type Validation type (local or network).
	 * @param \stdClass         $response        Validation response.
	 * @param Resource_Abstract $resource        Resource instance.
	 * @param Container|null    $container       Container instance.
	 */
	public function __construct( $key, string $validation_type, \stdClass $response, Resource_Abstract $resource, Container $container = null ) {
		$this->key             = $key ?: '';
		$this->validation_type = 'network' === $validation_type ? 'network' : 'local';
		$this->response        = $response;
		$this->resource        = $resource;
		$this->container       = $container ?: Container::init();

		$this->parse();
	}

	/**
	 * Gets the daily limit from the validation response.
	 *
	 * @since 1.0.0
	 *
	 * @return int
	 */
	public function get_daily_limit(): int {
		return $this->daily_limit;
	}

	/**
	 * Gets the validation response key.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_key(): string {
		return $this->key;
	}

	/**
	 * Gets the message from the validation response.
	 *
	 * @since 1.0.0
	 *
	 * @return Messages\Message_Abstract
	 */
	public function get_message() {
		switch ( $this->result ) {
			case 'unreachable':
				$message = new Messages\Unreachable();
				break;
			case 'expired':
				$message = new Messages\Expired_Key();
				break;
			case 'invalid':
			case 'upgrade':
				$message = new Messages\API( $this->api_response_message, $this->version, $this->resource );
				break;
			case 'success':
				$message = $this->get_success_message();
				break;
			default:
				$message = new Messages\Update_Available( $this->resource );
		}

		return $message;
	}

	/**
	 * Gets the network level message from the validation response.
	 *
	 * @since 1.0.0
	 *
	 * @return Messages\Message_Abstract
	 */
	public function get_network_message() {
		if ( $this->is_valid() ) {
			return new Messages\Network_Licensed();
		}

		if ( 'expired' === $this->result ) {
			return new Messages\Network_Expired();
		}

		return new Messages\Network_Unlicensed();
	}

	/**
	 * Gets the raw response from the validation request.
	 *
	 * @since 1.0.0
	 *
	 * @return \stdClass
	 */
	public function get_raw_response() {
		return $this->response;
	}

	/**
	 * Gets the validation response result.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_result(): string {
		return $this->result;
	}

	/**
	 * Gets the success message of the validation response.
	 *
	 * @since 1.0.0
	 *
	 * @return Messages\Message_Abstract
	 */
	private function get_success_message() {
		if ( ! empty( $this->api_response_message ) ) {
			return new Messages\API( $this->api_response_message, $this->version, $this->resource );
		}

		if ( 'new' !== $this->result ) {
			return new Messages\Valid_Key( $this->expiration );
		}

		return new Messages\Valid_Key_New( $this->expiration );
	}

	/**
	 * Get update details from the validation response.
	 *
	 * @since 1.0.0
	 *
	 * @return \stdClass
	 */
	public function get_update_details() {
		$update = new \stdClass;

		$update->id          = $this->id;
		$update->plugin      = $this->plugin;
		$update->slug        = $this->slug;
		$update->new_version = $this->version;
		$update->url         = $this->homepage;
		$update->package     = $this->download_url;

		if ( ! empty( $this->upgrade_notice ) ) {
			$update->upgrade_notice = $this->upgrade_notice;
		}

		// Support custom $update properties coming straight from PUE
		if ( ! empty( $this->custom_update ) ) {
			$custom_update = get_object_vars( $this->custom_update );

			foreach ( $custom_update as $field => $custom_value ) {
				if ( is_object( $custom_value ) ) {
					$custom_value = get_object_vars( $custom_value );
				}

				$update->$field = $custom_value;
			}
		}

		return $update;
	}

	/**
	 * Gets the version from the validation response.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_version(): string {
		return $this->version ?: '';
	}

	/**
	 * Returns where or not the license key was valid.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function is_valid(): bool {
		return $this->is_valid;
	}

	/**
	 * Parses the response from the API.
	 *
	 * @since 1.0.0
	 */
	private function parse(): void {
		$this->current_key = $this->resource->get_license_key( $this->validation_type );
		$this->expiration  = isset( $this->response->expiration ) ? $this->response->expiration : __( 'unknown date', 'stellar-uplink-client' );

		if ( ! empty( $this->response->api_inline_invalid_message ) ) {
			$this->api_response_message = wp_kses( $this->response->api_inline_invalid_message, 'post' );
		}

		if ( ! empty( $this->response->version ) ) {
			$this->version = sanitize_text_field( $this->response->version );
		}

		$this->version = $this->version ?: $this->resource->get_version();

		if ( null === $this->response ) {
			$this->result = 'unreachable';
		} elseif ( isset( $this->response->api_expired ) && 1 === (int) $this->response->api_expired ) {
			$this->result = 'expired';
			$this->is_valid = false;
		} elseif ( isset( $this->response->api_upgrade ) && 1 === (int) $this->response->api_upgrade ) {
			$this->result = 'upgrade';
			$this->is_valid = false;
		} elseif ( isset( $this->response->api_invalid ) && 1 === (int) $this->response->api_invalid ) {
			$this->result = 'invalid';
			$this->is_valid = false;
		} else {
			if ( isset( $this->response->api_message ) ) {
				$this->api_response_message = wp_kses( $this->response->api_message, 'data' );
			}

			if ( isset( $this->response->daily_limit ) ) {
				$this->daily_limit = intval( $this->response->daily_limit );
			}

			// If the license key is new or not the same as the one we have, mark it as a new key.
			if ( ! ( $this->current_key && $this->current_key === $this->key ) ) {
				$this->result = 'new';
			}
		}
	}

	/**
	 * Magic getter for the response properties.
	 *
	 * @param string $key Response value to fetch.
	 *
	 * @return mixed
	 */
	public function __get( $key ) {
		if ( ! isset( $this->response->$key ) ) {
			return null;
		}

		return $this->response->$key;
	}

	/**
	 * Magic isset for the response properties.
	 *
	 * @param string $key
	 *
	 * @return bool
	 */
	public function __isset( $key ) {
		return isset( $this->response->$key );
	}
}
