<?php

namespace StellarWP\Uplink\Admin;

use StellarWP\Uplink\Container;
use StellarWP\Uplink\Resources\Collection;

class Ajax {

	public function validate_license(): void {
		$submission = filter_var_array( $_POST, [
			'_wpnonce' => FILTER_SANITIZE_STRING,
			'key'      => FILTER_SANITIZE_STRING,
			'plugin'   => FILTER_SANITIZE_STRING,
		] );

		if ( empty( $submission ) || empty( $submission['key'] ) || ! wp_verify_nonce( $submission['_wpnonce'], License_Field::get_group_name() ) ) {
			echo json_encode( [
				'status'  => 0,
				'message' => __( 'Invalid request: nonce field is expired. Please try again.', 'stellar_uplink' )
			] );
			wp_die();
		}

		$collection = Container::init()->make( Collection::class );
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
