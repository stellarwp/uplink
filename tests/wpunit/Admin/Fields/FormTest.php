<?php

namespace StellarWP\Uplink\Admin\Fields;

use StellarWP\Uplink\Config;
use StellarWP\Uplink\Register;
use StellarWP\Uplink\Resources\Collection;
use StellarWP\Uplink\Tests\UplinkTestCase;
use StellarWP\Uplink\Tests\TestUtils;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use StellarWP\Uplink\Tests\Traits\With_Uopz;

class FormTest extends UplinkTestCase {

	use TestUtils;
	use SnapshotAssertions;
	use With_Uopz;

	public function setup_container_get_slug( $resource ) {
		$collection = Config::get_container()->get( Collection::class );

		Register::{$resource['type']}(
			$resource['slug'],
			$resource['name'],
			$resource['version'],
			$resource['path'],
			$resource['class']
		);

		return $collection->get( $resource['slug'] );
	}

	public function resourceProvider() {
		$resources = $this->get_test_resources();

		foreach ( $resources as $resource ) {
			yield $resource['slug'] => [ $resource ];
		}
	}

	/**
	 * @test
	 * @dataProvider resourceProvider
	 */
	public function it_should_add_field_to_form( $resource ) {
		$current_resource = $this->setup_container_get_slug( $resource );
		$slug             = $current_resource->get_slug();

		$field = new Field( $slug );
		$field->set_field_name( 'field-' . $slug );
		$field->show_label( true );

		$form = new Form();
		$form->add_field( $field );

		$fields = $form->get_fields();
		$this->assertArrayHasKey( $slug, $fields );
		$this->assertSame( $field, $fields[ $slug ] );
	}

	/**
	 * @test
	 * @dataProvider resourceProvider
	 */
	public function it_should_set_and_get_button_text( $resource ) {
		$button_text = 'Submit';
		$form        = new Form();
		$form->set_button_text( $button_text );

		$this->assertEquals( $button_text, $form->get_button_text() );
	}

	/**
	 * @test
	 * @dataProvider resourceProvider
	 */
	public function it_should_render_form( $resource ) {
		$current_resource = $this->setup_container_get_slug( $resource );
		$slug             = $current_resource->get_slug();

		$field = new Field( $slug );
		$field->set_field_name( 'field-' . $slug );

		$form = new Form();
		$form->add_field( $field );

		$license_key = 'license_key' . $slug;
		$option_name = $current_resource->get_license_object()->get_key_option_name();
		$this->set_fn_return( 'wp_create_nonce', '123456789', false );

		// Update the license key to a known value.
		update_option( $option_name, $license_key );
		$option_value = get_option( $option_name );
		$this->assertEquals( $option_value, $license_key );

		// Render the form and assert the HTML snapshot
		$form_html = $form->render();

		// Assert the HTML snapshot
		$this->assertMatchesHtmlSnapshot( $form_html );
	}

	/**
	 * @test
	 * @dataProvider resourceProvider
	 */
	public function it_should_get_button_text( $resource ) {
		$form = new Form();
		$this->assertEquals( 'Save Changes', $form->get_button_text() );
	}

	/**
	 * @test
	 * @dataProvider resourceProvider
	 */
	public function it_should_show_and_hide_button( $resource ) {
		$form = new Form();
		$this->assertTrue( $form->should_show_button() );

		$form->show_button( false );
		$this->assertFalse( $form->should_show_button() );
	}

	/**
	 * @test
	 */
	public function it_should_add_multiple_fields_to_form() {
		$resources            = $this->get_test_resources();
		$form                 = new Form();
		$expected_field_count = count( $resources );

		foreach ( $resources as $resource ) {
			$current_resource = $this->setup_container_get_slug( $resource );
			$slug             = $current_resource->get_slug();
			$field            = new Field( $slug );
			$field->set_field_name( 'field-' . $slug );
			$form->add_field( $field );

			// Assert the field is added to the form
			$fields = $form->get_fields();
			$this->assertArrayHasKey( $slug, $fields, "Form should have the field with slug '$slug'" );
			$this->assertEquals( 'field-' . $slug, $fields[ $slug ]->get_field_name(), "Field name should be 'field-$slug'" );
			$this->assertInstanceOf( Field::class, $fields[ $slug ], "Field should be an instance of Field class" );
		}

		$form_fields = $form->get_fields();

		// Assert the number of fields added to the form
		$this->assertCount( $expected_field_count, $form_fields, "Form should contain $expected_field_count fields" );
	}


	/**
	 * @test
	 */
	public function it_should_render_form_with_multiple_fields() {
		$resources            = $this->get_test_resources();
		$form                 = new Form();
		$expected_field_count = count( $resources );

		foreach ( $resources as $resource ) {
			$current_resource = $this->setup_container_get_slug( $resource );
			$slug             = $current_resource->get_slug();
			$field            = new Field( $slug );
			$field->set_field_name( 'field-' . $slug );
			$form->add_field( $field );

			// Assert the field is added to the form
			$fields = $form->get_fields();
			$this->assertArrayHasKey( $slug, $fields, "Form should have the field with slug '$slug'" );
			$this->assertEquals( 'field-' . $slug, $fields[ $slug ]->get_field_name(), "Field name should be 'field-$slug'" );
			$this->assertInstanceOf( Field::class, $fields[ $slug ], "Field should be an instance of Field class" );
		}

		// Mock the wp_create_nonce function
		$this->set_fn_return( 'wp_create_nonce', '123456789', false );

		// Render the form and assert the HTML snapshot
		$form_html = $form->render();

		// Assert the HTML snapshot
		$this->assertMatchesHtmlSnapshot( $form_html );
	}
}