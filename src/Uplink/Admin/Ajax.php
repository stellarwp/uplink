<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Admin;

use StellarWP\Uplink\Auth\License\License_Manager;
use StellarWP\Uplink\Resources\Collection;
use StellarWP\Uplink\Utils;

class Ajax {

	/**
	 * @var Collection
	 */
	protected $resources;

	/**
	 * @var License_Field
	 */
	protected $field;

	/**
	 * @var License_Manager
	 */
	protected $license_manager;

	/**
	 * Constructor.
	 *
	 * @param  Collection       $resources        The plugin/services collection.
	 * @param  License_Field    $field            The license field.
	 * @param  License_Manager  $license_manager  The license manager.
	 */
	public function __construct(
		Collection $resources,
		License_Field $field,
		License_Manager $license_manager
	) {
		$this->resources       = $resources;
		$this->field           = $field;
		$this->license_manager = $license_manager;
	}

	/**
	 * @since 1.0.0
	 * @return void
	 */
	public function validate_license(): void {
		$submission = [
			'_wpnonce' => sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ?? '' ) ),
			'slug'     => sanitize_text_field( wp_unslash( $_POST['slug'] ?? '' ) ),
			'key'      => Utils\Sanitize::key( wp_unslash( $_POST['key'] ?? '' ) ),
		];

		if ( empty( $submission['key'] ) || ! wp_verify_nonce( $submission['_wpnonce'], $this->field->get_group_name() ) ) {
			wp_send_json_error( [
				'status'  => 0,
				'message' => __( 'Invalid request: nonce field is expired. Please try again.', '%TEXTDOMAIN%' ),
			] );
		}

		$plugin = $this->resources->offsetGet( $submission['slug'] );

		if ( ! $plugin ) {
			wp_send_json_error( [
				'message'    => sprintf(
					__( 'Error: The plugin with slug "%s" was not found. It is impossible to validate the license key, please contact the plugin author.', '%TEXTDOMAIN%' ),
					$submission['slug']
				),
				'submission' => $submission,
			] );
		}

		$network_validate = $this->license_manager->allows_multisite_license( $plugin );
		$results          = $plugin->validate_license( $submission['key'], $network_validate );
		$message          = $network_validate ? $results->get_network_message()->get() : $results->get_message()->get();

		wp_send_json( [
			'status'  => absint( $results->is_valid() ),
			'message' => $message,
		] );
	}

}
