<?php

namespace StellarWP\Uplink\Admin;

use StellarWP\ContainerContract\ContainerInterface;
use StellarWP\Uplink\Config;
use StellarWP\Uplink\Resources\Collection;
use StellarWP\Uplink\Utils;

class Ajax {

	/**
	 * @var ContainerInterface
	 */
	protected $container;

	public function __construct() {
		$this->container = Config::get_container();
	}

	/**
	 * @since 1.0.0
	 * @return void
	 */
	public function validate_license() {
		$submission = [
			'_wpnonce' => isset( $_POST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ) : '',
			'plugin'   => isset( $_POST['plugin'] ) ? sanitize_text_field( wp_unslash( $_POST['plugin'] ) ) : '',
			'key'      => isset( $_POST['key'] ) ? Utils\Sanitize::key( wp_unslash( $_POST['key'] ) ) : '',
		];

		if ( empty( $submission ) || empty( $submission['key'] ) || ! wp_verify_nonce( $submission['_wpnonce'], $this->container->get( License_Field::class )->get_group_name() ) ) {
			echo json_encode( [
				'status'  => 0,
				'message' => __( 'Invalid request: nonce field is expired. Please try again.', '%TEXTDOMAIN%' )
			] );
			wp_die();
		}

		$collection = $this->container->get( Collection::class );
		$plugin     = $collection->current();

		$results = $plugin->validate_license( $submission['key'] );
		$message = is_plugin_active_for_network( $submission['plugin'] ) ? $results->get_network_message()->get() : $results->get_message()->get();

		echo json_encode( [
			'status' => intval( $results->is_valid() ),
			'message' => $message,
		] );

		wp_die();
	}


}
