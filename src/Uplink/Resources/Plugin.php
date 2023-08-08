<?php

namespace StellarWP\Uplink\Resources;

use StellarWP\Uplink\Admin\Notice;
use StellarWP\Uplink\API\Validation_Response;

class Plugin extends Resource {
	/**
	 * Plugin update status.
	 *
	 * @since 1.0.0
	 *
	 * @var \stdClass
	 */
	protected $update_status;

	/**
	 * @inheritDoc
	 */
	protected $type = 'plugin';

	/**
	 * Update status for the resource.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public static $update_status_option_prefix = 'stellarwp_uplink_update_status_';

	/**
	 * Check for plugin updates.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $transient The pre-saved value of the `update_plugins` site transient.
	 * @param bool $force_fetch Force fetching the update status.
	 *
	 * @return mixed
	 */
	public function check_for_updates( $transient, $force_fetch = false ) {
		if ( ! is_object( $transient ) ) {
			return $transient;
		}

		$status                  = $this->get_update_status( $force_fetch );
		$status->last_check      = time();
		$status->checked_version = $this->get_installed_version();

		// Save before actually doing the checking just in case something goes wrong. We don't want to continually recheck.
		$this->set_update_status( $status );

		$results        = $this->validate_license();
		$status->update = $results->get_raw_response();

		if ( null !== $status->update ) {
			if ( version_compare( $this->get_version_from_response( $results ), $this->get_installed_version(), '>' ) ) {
				/** @var \stdClass $transient */
				if ( ! isset( $transient->response ) ) {
					$transient->response = [];
				}

				$transient->response[ $this->get_path() ] = $results->get_update_details();

				if ( 'expired' === $results->get_result() ) {
					$this->container->get( Notice::class )->add_notice( Notice::EXPIRED_KEY, $this->get_slug() );
				}
			}

			// In order to show relevant issues on plugins page parse response data and add it to transient
			if ( version_compare( $this->get_version_from_response( $results ), $this->get_installed_version(), '>=' ) && in_array( $results->get_result(), [ 'expired', 'invalid' ] ) ) {
				/** @var \stdClass $transient */
				if ( ! isset( $transient->response ) ) {
					$transient->response = [];
				}
				$transient->response[ $this->get_path() ] = $results->handle_api_errors();
			}

		}

		$this->set_update_status( $status );

		return $transient;
	}

	/**
	 * Retrieve version from response
	 *
	 * @param Validation_Response $response
	 *
	 * @return string
	 */
	protected function get_version_from_response( $response ): string {
		if ( ! isset( $response->get_raw_response()->version ) ) {
			return '';
		}

		return $response->get_raw_response()->version;
	}

	/**
	 * Get the update status of the plugin.
	 *
	 * @since 1.0.0
	 *
	 * @param bool $force_fetch Force fetching the update status.
	 *
	 * @return mixed
	 */
	public function get_update_status( $force_fetch = false) {
		if ( ! $force_fetch ) {
			$this->update_status = get_option( $this->get_update_status_option_name(), null );
		}

		if ( ! is_object( $this->update_status ) ) {
			$this->update_status = (object) [
				'last_check'      => 0,
				'checked_version' => '',
				'update'          => null,
			];
		}

		return $this->update_status;
	}

	/**
	 * Gets the update status option name.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	protected function get_update_status_option_name(): string {
		return static::$update_status_option_prefix . $this->get_slug();
	}

	/**
	 * @inheritDoc
	 */
	public static function register( $slug, $name, $version, $path, $class, string $license_class = null ) {
		return parent::register_resource( static::class, $slug, $name, $version, $path, $class, $license_class );
	}

	/**
	 * Updates the update status value in options.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $status
	 *
	 * @return void
	 */
	protected function set_update_status( $status ) {
		update_option( $this->get_update_status_option_name(), $status );
	}

}
