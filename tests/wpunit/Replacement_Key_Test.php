<?php

namespace wpunit;

use StellarWP\Uplink\Tests\Licensing\Service_Mock;
use StellarWP\Uplink\API\Validation_Response;
use StellarWP\Uplink\Register;
use StellarWP\Uplink\Resources\Plugin;
use StellarWP\Uplink\Tests\UplinkTestCase;
use StellarWP\Uplink\Uplink;

class Replacement_Key_Test extends UplinkTestCase {
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

	public function test_replacemnt_key_update_in_multisite_context(): void {

	}
}
