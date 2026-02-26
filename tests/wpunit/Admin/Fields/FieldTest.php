<?php

namespace StellarWP\Uplink\Admin\Fields;

use StellarWP\ContainerContract\ContainerInterface;
use StellarWP\Uplink\Admin\Group;
use StellarWP\Uplink\Auth\Token\Contracts\Token_Manager;
use StellarWP\Uplink\Auth\Token\Token_Manager as Concrete_Token_Manager;
use StellarWP\Uplink\Config;
use StellarWP\Uplink\Register;
use StellarWP\Uplink\Resources\Collection;
use StellarWP\Uplink\Resources\Resource;
use StellarWP\Uplink\Tests\TestUtils;
use StellarWP\Uplink\Tests\Traits\With_Uopz;
use StellarWP\Uplink\Tests\UplinkTestCase;
use StellarWP\Uplink\Uplink;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;

class FieldTest extends UplinkTestCase {

	use TestUtils;
	use SnapshotAssertions;
	use With_Uopz;

	/**
	 * @var ContainerInterface|null
	 */
	private $original_container = null;

	public function resourceProvider() {
		$resources = $this->get_test_resources();

		foreach ( $resources as $resource ) {
			yield $resource['slug'] => [ $resource ];
		}
	}

	public function setup_container_get_slug( $resource ) {
		$collection = Config::get_container()->get( Collection::class );

		Register::{$resource['type']}(
			$resource['slug'],
			$resource['name'],
			$resource['version'],
			$resource['path'],
			$resource['class'],
		);

		return $collection->get( $resource['slug'] );
	}

	public function setUp(): void {
		parent::setUp();

		// Clone the original container used in all tests to avoid polluting the global container with custom bindings.
		$this->original_container = $this->container;
		$this->container          = clone $this->container;
		// Bind the token manager to the cloned container.
		$this->container->singleton(
			Token_Manager::class,
			static function ( $c ) {
				return new Concrete_Token_Manager( 'something' );
			} 
		);
	}

	public function tearDown(): void {
		$this->container = $this->original_container;
		parent::tearDown();
	}

	/**
	 * @test
	 * @dataProvider resourceProvider
	 */
	public function it_should_get_fields_with_slug( $resource ) {
		$current_resource = $this->setup_container_get_slug( $resource );
		$slug             = $current_resource->get_slug();
		$field            = $this->container->get( Field::class )->set_resource( $current_resource );
		$license_key      = 'license_key' . $slug;
		$option_name      = $current_resource->get_license_object()->get_key_option_name();

		// Update the license key to a known value.
		update_option( $option_name, $license_key );
		$option_value = get_option( $option_name );
		$this->assertEquals( $option_value, $license_key );

		$field_name = 'field-' . $slug;
		$field->set_field_name( $field_name );

		// Add assertions to verify the field properties and methods
		$this->assertEquals( $slug, $field->get_slug() );
		$this->assertEquals( $current_resource->get_path(), $field->get_product() );
		$this->assertEquals( $field_name, $field->get_field_name() );
		$this->assertEquals( 'stellarwp_uplink_license_key_' . $slug, $field->get_field_id() );
		$this->assertEquals(
			$license_key,
			$field->get_field_value(),
			'Field value should be equal to the license key' 
		);
		$this->assertStringContainsString(
			'A valid license key is required for support and updates',
			$field->get_key_status_html() 
		);
		$this->assertEquals( 'License key', $field->get_placeholder() );
		$this->assertEquals( 'stellarwp-uplink-license-key-field', $field->get_classes() );
	}

	/**
	 * @test
	 * @dataProvider resourceProvider
	 */
	public function it_should_render_fields_correctly( $resource ) {
		$current_resource = $this->setup_container_get_slug( $resource );
		$slug             = $current_resource->get_slug();
		$field            = $this->container->get( Field::class )->set_resource( $current_resource );
		$license_key      = 'license_key' . $slug;
		$option_name      = $current_resource->get_license_object()->get_key_option_name();
		$this->set_fn_return( 'wp_create_nonce', '123456789', false );

		// Update the license key to a known value.
		update_option( $option_name, $license_key );
		$option_value = get_option( $option_name );
		$this->assertEquals( $option_value, $license_key );

		$field_name = 'field-' . $slug;
		$field->set_field_name( $field_name );

		$test = $field->get_render_html();
		$this->assertMatchesHtmlSnapshot( $test );
	}


	/**
	 * @test
	 * @dataProvider resourceProvider
	 */
	public function it_should_set_and_get_field_id( $resource ) {
		$field = $this->container->get( Field::class )->set_resource( $this->setup_container_get_slug( $resource ) );

		$field_id = 'custom-field-id';
		$field->set_field_id( $field_id );

		$this->assertEquals( $field_id, $field->get_field_id() );
	}

	/**
	 * @test
	 * @dataProvider resourceProvider
	 */
	public function it_should_set_and_get_field_label( $resource ) {
		$field = $this->container->get( Field::class )->set_resource( $this->setup_container_get_slug( $resource ) );

		$label = 'Custom Label';
		$field->set_label( $label );

		$this->assertEquals( $label, $field->get_label() );
	}

	/**
	 * @test
	 * @dataProvider resourceProvider
	 */
	public function it_should_get_placeholder( $resource ) {
		$field = $this->container->get( Field::class )->set_resource( $this->setup_container_get_slug( $resource ) );

		$this->assertEquals( 'License key', $field->get_placeholder() );
	}

	/**
	 * @test
	 * @dataProvider resourceProvider
	 */
	public function it_should_get_nonce_action_and_field( $resource ) {
		$field = $this->container->get( Field::class )->set_resource( $this->setup_container_get_slug( $resource ) );

		$nonce_action = $this->container->get( Group::class )->get_name();
		$nonce_field  = $field->get_nonce_field();

		// Extract the nonce value from the nonce field
		preg_match( '/value=["\']([^"\']+)["\']/', $nonce_field, $matches );
		$nonce_value = $matches[1];

		$this->assertNotEmpty( $nonce_action, 'Nonce action should not be empty.' );
		$this->assertStringContainsString(
			'stellarwp-uplink-license-key-nonce__' . $field->get_slug(),
			$nonce_field,
			'Nonce field should contain the correct action slug.' 
		);

		// Validate the nonce
		$is_valid_nonce = wp_verify_nonce( $nonce_value, $nonce_action );
		$this->assertContains( $is_valid_nonce, [ 1, 2 ], 'Nonce should be valid.' );
	}

	/**
	 * @test
	 * @dataProvider resourceProvider
	 */
	public function it_should_show_and_hide_label_and_heading( $resource ) {
		$field = $this->container->get( Field::class )->set_resource( $this->setup_container_get_slug( $resource ) );

		$field->show_label( true );
		$this->assertTrue( $field->should_show_label() );

		$field->show_label( false );
		$this->assertFalse( $field->should_show_label() );

		$field->show_heading( true );
		$this->assertTrue( $field->should_show_heading() );

		$field->show_heading( false );
		$this->assertFalse( $field->should_show_heading() );
	}

	/**
	 * @test
	 * @dataProvider resourceProvider
	 */
	public function it_should_render_correct_html( $resource ) {
		$field = $this->container->get( Field::class )->set_resource( $this->setup_container_get_slug( $resource ) );

		$html = $field->get_render_html();
		$this->assertStringContainsString( 'stellarwp-uplink-license-key-field', $html );
		$this->assertStringContainsString( $field->get_slug(), $html );
	}

	/**
	 * @test
	 * @dataProvider resourceProvider
	 */
	public function it_should_handle_empty_field_name( $resource ) {
		$field = $this->container->get( Field::class )->set_resource( $this->setup_container_get_slug( $resource ) );

		$this->assertEmpty( $field->get_field_name(), 'Field name should be empty by default' );
	}

	/**
	 * @test
	 * @dataProvider resourceProvider
	 */
	public function it_should_handle_empty_field_id( $resource ) {
		$field = $this->container->get( Field::class )->set_resource( $this->setup_container_get_slug( $resource ) );

		$this->assertNotEmpty( $field->get_field_id(), 'Field ID should not be empty even if not set' );
	}

	/**
	 * @test
	 * @dataProvider resourceProvider
	 */
	public function it_should_handle_empty_label( $resource ) {
		$field = $this->container->get( Field::class )->set_resource( $this->setup_container_get_slug( $resource ) );

		$this->assertEmpty( $field->get_label(), 'Label should be empty by default' );
	}

	/**
	 * @test
	 */
	public function it_should_render_correct_html_for_oauth_resource_with_license_key() {
		$this->set_fn_return( 'wp_create_nonce', '535281edcd' );
		$this->set_fn_return( 'wp_generate_password', '535281edcd535281ed' );
		$slug = 'service-oauth-with-license-key-field-1';
		Register::service(
			$slug,
			'Service OAuth With License Key Field 1',
			'1.0.0',
			$this->get_base() . '/service-oauth-with-license-key-field-1.php',
			Uplink::class,
			Uplink::class,
			Resource::OAUTH_REQUIRES_LICENSE_KEY
		);
		add_filter( 'stellarwp/uplink/test/auth/can_auth', '__return_true' );
		update_option(
			'test_storage',
			[
				'stellarwp_auth_url_service_oauth_with_license_key_field_1' => [
					'expiration' => 0,
					'value'      => 'https://licensing.stellarwp.com/account-auth',
				],
			] 
		);
		$collection = $this->container->get( Collection::class );

		$field = $this->container->get( Field::class )->set_resource( $collection->get( $slug ) );
		$html  = $field->get_render_html();

		$this->assertMatchesHtmlSnapshot( $html );
	}
}
