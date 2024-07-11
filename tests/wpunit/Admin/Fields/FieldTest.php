<?php

namespace StellarWP\Uplink\Admin\Fields;

use InvalidArgumentException;
use StellarWP\Uplink\Admin\License_Field;
use StellarWP\Uplink\Config;
use StellarWP\Uplink\Register;
use StellarWP\Uplink\Resources\Collection;
use StellarWP\Uplink\Tests\UplinkTestCase;
use StellarWP\Uplink\Tests\TestUtils;

class FieldTest extends UplinkTestCase {

	use TestUtils;

	/**
	 * The root directory for plugin/services paths.
	 *
	 * @var string
	 */
	private string $root;

	/**
	 * @test
	 */
	public function it_should_fail_with_invalid_slug() {
		$slug = 'Invalid Slug';
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( sprintf( 'Resource with slug "%s" does not exist.', $slug ) );
		new Field( $slug );
	}

	public function resourceProvider() {
		$resources = $this->get_test_resources();

		foreach ( $resources as $resource ) {
			yield [ $resource ];
		}
	}

	/**
	 * @test
	 * @dataProvider resourceProvider
	 */
	public function it_should_get_fields_with_slug( $resource ) {
		$collection = Config::get_container()->get( Collection::class );

		Register::{$resource['type']}(
			$resource['slug'],
			$resource['name'],
			$resource['version'],
			$resource['path'],
			$resource['class'],
		);

		$current_resource = $collection->get( $resource['slug'] );
		$slug             = $current_resource->get_slug();
		$field            = new Field( $slug );
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
		$this->assertEquals( $license_key, $field->get_field_value(), 'Field value should be equal to the license key' );
		$this->assertStringContainsString( 'A valid license key is required for support and updates', $field->get_key_status_html() );
		$this->assertEquals( 'License key', $field->get_placeholder() );
		$this->assertEquals( 'stellarwp-uplink-license-key-field', $field->get_classes() );

	}

	/**
	 * @test
	 * @dataProvider resourceProvider
	 */
	public function it_should_set_and_get_field_id( $resource ) {
		$collection = Config::get_container()->get( Collection::class );
		Register::{$resource['type']}(
			$resource['slug'],
			$resource['name'],
			$resource['version'],
			$resource['path'],
			$resource['class'],
		);

		$current_resource = $collection->get( $resource['slug'] );
		$slug             = $current_resource->get_slug();
		$field            = new Field( $slug );

		$field_id = 'custom-field-id';
		$field->set_field_id( $field_id );

		$this->assertEquals( $field_id, $field->get_field_id() );
	}

	/**
	 * @test
	 * @dataProvider resourceProvider
	 */
	public function it_should_set_and_get_field_label( $resource ) {
		$collection = Config::get_container()->get( Collection::class );
		Register::{$resource['type']}(
			$resource['slug'],
			$resource['name'],
			$resource['version'],
			$resource['path'],
			$resource['class'],
		);

		$current_resource = $collection->get( $resource['slug'] );
		$slug             = $current_resource->get_slug();
		$field            = new Field( $slug );

		$label = 'Custom Label';
		$field->set_label( $label );

		$this->assertEquals( $label, $field->get_label() );
	}

	/**
	 * @test
	 * @dataProvider resourceProvider
	 */
	public function it_should_get_placeholder( $resource ) {
		$collection = Config::get_container()->get( Collection::class );
		Register::{$resource['type']}(
			$resource['slug'],
			$resource['name'],
			$resource['version'],
			$resource['path'],
			$resource['class'],
		);

		$current_resource = $collection->get( $resource['slug'] );
		$slug             = $current_resource->get_slug();
		$field            = new Field( $slug );

		$this->assertEquals( 'License key', $field->get_placeholder() );

	}

	/**
	 * @test
	 * @dataProvider resourceProvider
	 */
	public function it_should_get_nonce_action_and_field( $resource ) {
		$collection = Config::get_container()->get( Collection::class );
		Register::{$resource['type']}(
			$resource['slug'],
			$resource['name'],
			$resource['version'],
			$resource['path'],
			$resource['class'],
		);

		$current_resource = $collection->get( $resource['slug'] );
		$slug             = $current_resource->get_slug();
		$field            = new Field( $slug );

		$nonce_action = Config::get_container()->get( License_Field::class )->get_group_name();
		$nonce_field  = $field->get_nonce_field();

		// Extract the nonce value from the nonce field
		preg_match( '/value=["\']([^"\']+)["\']/', $nonce_field, $matches );
		$nonce_value = $matches[1];

		$this->assertNotEmpty( $nonce_action, 'Nonce action should not be empty.' );
		$this->assertStringContainsString( 'stellarwp-uplink-license-key-nonce__' . $slug, $nonce_field, 'Nonce field should contain the correct action slug.' );

		// Validate the nonce
		$is_valid_nonce = wp_verify_nonce( $nonce_value, $nonce_action );
		$this->assertContains( $is_valid_nonce, [ 1, 2 ], 'Nonce should be valid.' );

	}

	/**
	 * @test
	 * @dataProvider resourceProvider
	 */
	public function it_should_show_and_hide_label_and_heading( $resource ) {
		$collection = Config::get_container()->get( Collection::class );
		Register::{$resource['type']}(
			$resource['slug'],
			$resource['name'],
			$resource['version'],
			$resource['path'],
			$resource['class'],
		);

		$current_resource = $collection->get( $resource['slug'] );
		$slug             = $current_resource->get_slug();
		$field            = new Field( $slug );

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
	public function it_should_render_correct_html($resource) {
		$collection = Config::get_container()->get( Collection::class );
		Register::{$resource['type']}(
			$resource['slug'],
			$resource['name'],
			$resource['version'],
			$resource['path'],
			$resource['class'],
		);

		$current_resource = $collection->get( $resource['slug'] );
		$slug             = $current_resource->get_slug();
		$field            = new Field( $slug );

		$html = $field->render();
		$this->assertStringContainsString( 'stellarwp-uplink-license-key-field', $html );
		$this->assertStringContainsString( $slug, $html );
	}

	/**
	 * @test
	 * @dataProvider resourceProvider
	 */
	public function it_should_handle_empty_field_name( $resource ) {
		$collection = Config::get_container()->get( Collection::class );
		Register::{$resource['type']}(
			$resource['slug'],
			$resource['name'],
			$resource['version'],
			$resource['path'],
			$resource['class'],
		);

		$current_resource = $collection->get( $resource['slug'] );
		$slug             = $current_resource->get_slug();
		$field            = new Field( $slug );

		$this->assertEmpty( $field->get_field_name(), 'Field name should be empty by default' );

	}

	/**
	 * @test
	 * @dataProvider resourceProvider
	 */
	public function it_should_handle_empty_field_id( $resource ) {
		$collection = Config::get_container()->get( Collection::class );
		Register::{$resource['type']}(
			$resource['slug'],
			$resource['name'],
			$resource['version'],
			$resource['path'],
			$resource['class'],
		);

		$current_resource = $collection->get( $resource['slug'] );
		$slug             = $current_resource->get_slug();
		$field            = new Field( $slug );

		$this->assertNotEmpty( $field->get_field_id(), 'Field ID should not be empty even if not set' );
	}

	/**
	 * @test
	 * @dataProvider resourceProvider
	 */
	public function it_should_handle_empty_label( $resource ) {
		$collection = Config::get_container()->get( Collection::class );
		Register::{$resource['type']}(
			$resource['slug'],
			$resource['name'],
			$resource['version'],
			$resource['path'],
			$resource['class'],
		);

		$current_resource = $collection->get( $resource['slug'] );
		$slug             = $current_resource->get_slug();
		$field            = new Field( $slug );

		$this->assertEmpty( $field->get_label(), 'Label should be empty by default' );
	}
}
