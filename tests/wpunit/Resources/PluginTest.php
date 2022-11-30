<?php

namespace wpunit\Resources;

use StellarWP\Uplink\API\Client;
use StellarWP\Uplink\API\Validation_Response;
use StellarWP\Uplink\Container;
use StellarWP\Uplink\Resources\Collection;
use StellarWP\Uplink\Resources\Plugin;
use StellarWP\Uplink\Tests\UplinkTestCase;
use StellarWP\Uplink\Uplink;

class PluginTest extends UplinkTestCase {

	public $expected_empty;
	/**
	 * @var \StellarWP\Uplink\Resources\Resource
	 */
	public $resource;

	public function setUp() {
		parent::setUp();
		$mock = $this->getMockBuilder( Plugin::class )->disableOriginalConstructor()->getMock();
		$mock->method( 'get_installed_version' )->will($this->returnValue( '1.0.3'));
		$mock->expects( $this->once() )->method( 'register_resource' )->withAnyParameters();
		$mock->expects( $this->once() )->method( 'register' )->with(
			'sample',
			'Lib Sample',
			'sample/index.php',
			Uplink::class,
			'1.0.10',
			Uplink::class
		);


		$this->validate_license_mock();

		$this->expected_empty = (object) [
			'last_check'      => 0,
			'checked_version' => '',
			'update'          => null,
		];
	}

	public function test_check_for_updates_with_same_results() {
		$this->assertSame(
			[],
			$this->get_plugin()->check_for_updates( [] ),
			'Return same transient if it is not an object'
		);

		$result = $this->get_plugin()->check_for_updates( new \stdClass() );
		$this->assertEquals( new \stdClass(), $result, 'Transient should remain same since we do not make an api call' );

		$update_from_option = get_option( 'stellar_uplink_update_status_' . $this->get_plugin()->get_slug() );
		$this->assertSame( $this->expected_empty->checked_version,  $update_from_option->checked_version );
		$this->assertSame( $this->expected_empty->update, $update_from_option->update );
	}

	public function test_get_update_status() {
		$this->assertEquals( $this->expected_empty, $this->get_plugin()->get_update_status() );
		$update = new \stdClass();
		$time   = time();

		$update->checked_version = '1.0';
		$update->last_check      = $time;
		$update->update			 = null;

		update_option( 'stellar_uplink_update_status_' . $this->get_plugin()->get_slug(), $update );

		$this->assertEquals( $update,  $this->get_plugin()->get_update_status() );
	}

	public function test_check_for_updates_with_fake_invalid_response() {
		$result = $this->get_plugin()->check_for_updates( new \stdClass() );

		$this->assertEquals( '1.0.10', $result->new_version );
		$this->assertEquals( true, $result->api_invalid );
		$this->assertEquals( 'invalid_license', $result->package );
	}

	private function validate_license_mock() {
		$class = $this->createMock( Client::class );
		$class->method( 'validate_license' )
			->will( $this->returnCallback( [ $this, 'get_validation_response' ] ) );

		return $class;
	}

	/**
	 * @return Validation_Response
	 */
	private function get_validation_response(): Validation_Response {
		return new Validation_Response( 'aaa11', 'local', $this->get_dummy_api_invalid_response(), $this->get_plugin() );
	}

	public function get_dummy_api_invalid_response(): \stdClass {
		return json_decode( '{"results":[{"api_invalid":true,"version":"1.0.10","api_invalid_message":"<p>You are using %plugin_name% but your license key is invalid. Visit the Events Calendar website to check your <a href=\"https:\/\/theeventscalendar.com\/license-keys\/?utm_medium=pue&utm_campaign=in-app\">licenses<\/a>.","api_inline_invalid_message":"<p>There is a new version of %plugin_name% available but your license key is invalid. View %changelog% with version %version%. Visit the Events Calendar website to check your <a href=\"https:\/\/theeventscalendar.com\/license-keys\/?utm_medium=pue&utm_campaign=in-app\">licenses<\/a>.","sections":{"changelog":"Changelog data"}}]}' );
	}

	private function get_plugin() {
		return Container::init()->make( Collection::class )->current();
	}

}
