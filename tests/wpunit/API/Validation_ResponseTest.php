<?php

namespace wpunit\API;

use StellarWP\Uplink\API\Validation_Response;
use StellarWP\Uplink\Register;
use StellarWP\Uplink\Tests\UplinkTestCase;
use StellarWP\Uplink\Uplink;

class Validation_ResponseTest extends UplinkTestCase {

	public $resource;
	public $container;

	public function setUp() {
		parent::setUp();

		$root 		    = dirname( __DIR__, 3 );
		$this->resource = Register::plugin(
			'Lib Sample',
			'sample',
			$root . '/plugin.php',
			Uplink::class,
			'1.0.10',
			Uplink::class
		);
	}

	public function get_dummy_valid_response(): \stdClass {
		return json_decode( '{"results":[{"brand_id":"1","name":"Lib Sample","slug":"sample","file_prefix":"sample","homepage":"","download_url":"https:\/\/pue.lndo.site\/api\/plugins\/v2\/download?plugin=sample&version=1.0.10","zip_url":"https:\/\/uplink2.lndo.site\/sample.zip","icon_svg_url":"","version":"1.0.10","requires":"6.0.3","tested":"","release_date":"2022-10-01 00:00:00","upgrade_notice":"Test message update","last_updated":"2022-11-04 16:50:06","sections":{"description":"Short description","installation":"Some data for install","changelog":"Changelog data"},"expiration":"2023-10-11","daily_limit":null,"api_upgrade":false,"api_expired":false,"api_message":null}]}' );
	}

	public function get_dummy_api_invalid_response(): \stdClass {
		return json_decode( '{"results":[{"api_invalid":true,"version":"1.0.10","api_invalid_message":"<p>You are using %plugin_name% but your license key is invalid. Visit the Events Calendar website to check your <a href=\"https:\/\/theeventscalendar.com\/license-keys\/?utm_medium=pue&utm_campaign=in-app\">licenses<\/a>.","api_inline_invalid_message":"<p>There is a new version of %plugin_name% available but your license key is invalid. View %changelog% with version %version%. Visit the Events Calendar website to check your <a href=\"https:\/\/theeventscalendar.com\/license-keys\/?utm_medium=pue&utm_campaign=in-app\">licenses<\/a>.","sections":{"changelog":"Changelog data"}}]}' );
	}

	public function test_it_should_provide_valid_update_details(): void {
		$result = new Validation_Response( 'aaa11', 'local', $this->get_dummy_valid_response(), $this->resource );
		$update = $result->get_update_details();

		$this->assertEquals( '', $update->id );
		$this->assertEquals( '', $update->plugin );
		$this->assertEquals( 'sample', $update->slug );
		$this->assertEquals( '1.0.10', $update->new_version );
		$this->assertEquals( 'Test message update', $update->upgrade_notice );
	}

	public function test_it_should_provide_api_error_details_with_corresponding_message() {
		$result = new Validation_Response( 'aaa11', 'local', $this->get_dummy_api_invalid_response(), $this->resource );
		$update = $result->get_update_details();

		$this->assertEquals( '1.0.10', $update->new_version );
		$this->assertEquals( true, $update->api_invalid );
		$this->assertEquals( 'invalid_license', $update->package );
		$this->assertStringContainsString( '<p>There is a new version of Lib Sample available but your license key is invalid. View ', $update->license_error );
	}

}
