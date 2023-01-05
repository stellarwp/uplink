<?php

namespace muwpunit;

use StellarWP\Uplink\Tests\Licensing\Service_Mock;
use StellarWP\Uplink\API\Validation_Response;
use StellarWP\Uplink\Register;
use StellarWP\Uplink\Resources\Plugin;
use StellarWP\Uplink\Tests\UplinkTestCase;
use StellarWP\Uplink\Tests\Traits\With_Uopz;
use StellarWP\Uplink\Uplink;

class Replacement_Key_Test extends UplinkTestCase {
	use With_Uopz;

	/**
	 * @var Service_Mock
	 */
	private $service_mock;

	/**
	 * @var Plugin
	 */
	private $resource;

	public function setUp() {
		parent::setUp();

		$this->resource = Register::plugin(
			'sample',
			'Lib Sample',
			'1.0.10',
			'uplink/index.php',
			\StellarWP\Uplink\Tests\Sample_Plugin::class
		);
	}

	/**
	 * @before
	 */
	public function set_up_service_mock(): void {
		$this->service_mock = new Service_Mock();
	}

	/**
	 * @before
	 */
	public function set_plugin_active_for_network(): void {
		$this->set_fn_return( 'is_plugin_active_for_network', function ( string $plugin ): bool {
			return $plugin === 'uplink/index.php' || is_plugin_active_for_network( $plugin );
		}, true );
	}

	/**
	 * It should not update license key if replacement key not provided
	 *
	 * @test
	 */
	public function should_not_update_license_key_if_replacement_key_not_provided(): void {
		// Ensure there is no key set.
		$this->resource->delete_license_key();
		$validated_key = md5( microtime() );
		$body = $this->service_mock->get_validate_key_success_body();
		$mock_response = $this->service_mock->make_response( 200, $body, 'application/json' );
		$this->service_mock->will_reply_to_request( 'POST', '/plugins/v2/license/validate', $mock_response );

		$result = $this->resource->validate_license( $validated_key );

		$this->assertTrue( $result->is_valid() );
		$this->assertEquals( $validated_key, $this->resource->get_license_key() );
	}

	/**
	 * It should not update license key if replacement key is empty
	 *
	 * @test
	 */
	public function should_not_update_license_key_if_replacement_key_is_empty(): void {
		// Ensure there is no key set.
		$this->resource->delete_license_key();
		$validated_key = md5( microtime() );
		$body = $this->service_mock->get_validate_key_success_body();
		// Add an empty replacement key to the response body.
		$body['results'][0]['replacement_key'] = '';
		$mock_response = $this->service_mock->make_response( 200, $body, 'application/json' );
		$this->service_mock->will_reply_to_request( 'POST', '/plugins/v2/license/validate', $mock_response );

		$result = $this->resource->validate_license( $validated_key );

		$this->assertEquals( $validated_key, $this->resource->get_license_key() );
	}

	/**
	 * It should update license key if replacement key provided and key not previously set
	 *
	 * @test
	 */
	public function should_update_license_key_if_replacement_key_provided_and_key_not_previously_set(): void {
		$validated_key = md5( microtime() );
		// Ensure there is no key set.
		$this->resource->delete_license_key();
		// Set the response mock to provide a replacement key.
		$replacement_key = '2222222222222222222222222222222222222222';
		$body = $this->service_mock->get_validate_key_success_body();
		// Add a replacement key to the response body.
		$body['results'][0]['replacement_key'] = $replacement_key;
		$mock_response = $this->service_mock->make_response( 200, $body, 'application/json' );
		$this->service_mock->will_reply_to_request( 'POST', '/plugins/v2/license/validate', $mock_response );

		$result = $this->resource->validate_license( $validated_key );

		$this->assertEquals( $replacement_key, $this->resource->get_license_key() );
	}

	/**
	 * It should update license key if replacement key provided and key previously set
	 *
	 * @test
	 */
	public function should_update_license_key_if_replacement_key_provided_and_key_previously_set(): void {
		$original_key = md5( microtime() );
		// Set the current license key for the plugin.
		$this->resource->set_license_key( $original_key );
		// Set the response mock to provide a replacement key.
		$replacement_key = '2222222222222222222222222222222222222222';
		$body = $this->service_mock->get_validate_key_success_body();
		// Add a replacement key to the response body.
		$body['results'][0]['replacement_key'] = $replacement_key;
		$mock_response = $this->service_mock->make_response( 200, $body, 'application/json' );
		$this->service_mock->will_reply_to_request( 'POST', '/plugins/v2/license/validate', $mock_response );

		$result = $this->resource->validate_license( $original_key );

		$this->assertEquals( $replacement_key, $this->resource->get_license_key() );
	}

	/**
	 * It should set not previosly set network key to validated key when replacement key not provided
	 *
	 * @test
	 */
	public function should_set_network_key_to_validated_key_when_not_previously_set_and_replacement_not_provided(): void {
		$validated_key = md5( microtime() );
		// Ensure there is no license key locally or network wide.
		$this->resource->delete_license_key();
		$this->resource->delete_license_key( 'network' );
		$body = $this->service_mock->get_validate_key_success_body();
		$mock_response = $this->service_mock->make_response( 200, $body, 'application/json' );
		$this->service_mock->will_reply_to_request( 'POST', '/plugins/v2/license/validate', $mock_response );

		$result = $this->resource->validate_license( $validated_key, true );

		$this->assertEquals( $validated_key, $this->resource->get_license_key() );
	}

	/**
	 * @test
	 */
	public function should_set_network_key_to_validated_key_when_not_previously_set_and_replacement_key_empty(): void {
		$validated_key = md5( microtime() );
		// Ensure there is no license key locally or network wide.
		$this->resource->delete_license_key();
		$this->resource->delete_license_key( 'network' );
		$body = $this->service_mock->get_validate_key_success_body();
		// Add a replacement key to the response body.
		$body['results'][0]['replacement_key'] = '';
		$mock_response = $this->service_mock->make_response( 200, $body, 'application/json' );
		$this->service_mock->will_reply_to_request( 'POST', '/plugins/v2/license/validate', $mock_response );

		$result = $this->resource->validate_license( $validated_key, true );

		$this->assertEquals( $validated_key, $this->resource->get_license_key() );
	}

	/**
	 * @test
	 */
	public function should_set_network_key_to_provided_replacement_key_when_not_previously_set(): void {
		$validated_key = md5( microtime() );
		// Ensure there is no license key locally or network wide.
		$this->resource->delete_license_key();
		$this->resource->delete_license_key( 'network' );
		$body = $this->service_mock->get_validate_key_success_body();
		// Add a replacement key to the response body.
		$replacement_key = '2222222222222222222222222222222222222222';
		$body['results'][0]['replacement_key'] = $replacement_key;
		$mock_response = $this->service_mock->make_response( 200, $body, 'application/json' );
		$this->service_mock->will_reply_to_request( 'POST', '/plugins/v2/license/validate', $mock_response );

		$result = $this->resource->validate_license( $validated_key, true );

		$this->assertEquals( $replacement_key, $this->resource->get_license_key() );
	}


	/**
	 * It should set previously set network key to replacement key if provided
	 *
	 * @test
	 */
	public function should_set_previously_set_network_key_to_replacement_key_if_provided() {
		$validated_key = md5( microtime() );
		// Ensure there is no license key locally or network wide.
		$this->resource->delete_license_key();
		$this->resource->delete_license_key( 'network' );
		$body = $this->service_mock->get_validate_key_success_body();
		// Add a replacement key to the response body.
		$replacement_key = '2222222222222222222222222222222222222222';
		$body['results'][0]['replacement_key'] = $replacement_key;
		$mock_response = $this->service_mock->make_response( 200, $body, 'application/json' );
		$this->service_mock->will_reply_to_request( 'POST', '/plugins/v2/license/validate', $mock_response );

		$result = $this->resource->validate_license( $validated_key, true );

		$this->assertEquals( $replacement_key, $this->resource->get_license_key() );
	}
}
