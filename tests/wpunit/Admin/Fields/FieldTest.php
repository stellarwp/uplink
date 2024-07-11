<?php

namespace StellarWP\Uplink\Admin\Fields;

use ArrayIterator;
use InvalidArgumentException;
use StellarWP\Uplink\Register;
use StellarWP\Uplink\Tests\UplinkTestCase;
use StellarWP\Uplink\Uplink;
use StellarWP\Uplink\Resources as Uplink_Resources;

class FieldTest extends UplinkTestCase {

	/**
	 * The root directory for plugin/services paths.
	 *
	 * @var string
	 */
	private string $root;

	protected function setUp(): void {
		parent::setUp();
		$this->collection = $this->container->get( Uplink_Resources\Collection::class );
		$this->root       = dirname( __DIR__, 3 );
		$resources        = $this->get_resources();
		foreach ( $resources as $resource ) {
			call_user_func(
				Register::class . '::' . $resource['type'],
				$resource['slug'],
				$resource['name'],
				$resource['version'],
				$resource['path'],
				$resource['class']
			);
		}
	}

	/**
	 * Resources.
	 */
	public function get_resources(): array {
		return [
			[
				'slug'          => 'plugin-1',
				'name'          => 'Plugin 1',
				'path'          => $this->root . '/plugin.php',
				'class'         => Uplink::class,
				'license_class' => Uplink::class,
				'version'       => '1.0.0',
				'type'          => 'plugin',
			],
			[
				'slug'          => 'plugin-2',
				'name'          => 'Plugin 2',
				'path'          => $this->root . '/plugin.php',
				'class'         => Uplink::class,
				'license_class' => Uplink::class,
				'version'       => '2.0.0',
				'type'          => 'plugin',
			],
			[
				'slug'          => 'service-1',
				'name'          => 'Service 1',
				'path'          => $this->root . '/service1.php',
				'class'         => Uplink::class,
				'license_class' => Uplink::class,
				'version'       => '3.0.0',
				'type'          => 'service',
			],
			[
				'slug'          => 'service-2',
				'name'          => 'Service 2',
				'path'          => $this->root . '/service2.php',
				'class'         => Uplink::class,
				'license_class' => Uplink::class,
				'version'       => '4.0.0',
				'type'          => 'service',
			],
		];
	}

	/**
	 * @test
	 */
	public function it_should_fail_with_invalid_slug() {
		$slug = 'Invalid Slug';
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( sprintf( 'Resource with slug "%s" does not exist.', $slug ) );
		new Field( $slug );
	}

	/**
	 * @test
	 */
	public function it_should_get_fields_with_slug() {
		$plugins   = array_slice( $this->get_resources(), 0, 2 );
		$resources = [];

		foreach ( $plugins as $slug => $plugin ) {
			$resources[ $slug ] = new Uplink_Resources\Plugin( $plugin['slug'],
															   $plugin['name'],
															   $plugin['version'],
															   $plugin['path'],
															   $plugin['class'] );
		}

		$collection = new Uplink_Resources\Collection( new ArrayIterator( $resources ) );

		foreach ( $collection as $resource ) {
			$slug  = $resource->get_slug();
			$field = new Field( $slug );
			$license_key = 'license_key'.$slug;
			$option_name = $resource->get_license_object()->get_key_option_name();

			// Update the license key to a known value.
			update_option( $option_name, $license_key );

			$field_name = 'field-' . $slug;
			$field->set_field_name( $field_name );

			// Add assertions to verify the field properties and methods
			$this->assertEquals( $slug, $field->get_slug() );
			$this->assertEquals( $resource->get_path(), $field->get_product() );
			$this->assertEquals( $field_name, $field->get_field_name() );
			$this->assertEquals( 'stellarwp_uplink_license_key_' . $slug, $field->get_field_id() );
			$this->assertEquals($license_key, $field->get_field_value(), 'Field value should be equal to the license key' );
			$this->assertStringContainsString( 'A valid license key is required for support and updates', $field->get_key_status_html() );
			$this->assertEquals( 'License key', $field->get_placeholder() );
			$this->assertEquals( 'stellarwp-uplink-license-key-field', $field->get_classes() );

			}
	}

	/**
	 * @test
	 */
	public function it_should_set_and_get_field_id() {
		foreach ( $this->get_resources() as $resource ) {
			$slug  = $resource['slug'];
			$field = new Field( $slug );

			$field_id = 'custom-field-id';
			$field->set_field_id( $field_id );

			$this->assertEquals( $field_id, $field->get_field_id() );
		}
	}

	/**
	 * @test
	 */
	public function it_should_set_and_get_field_label() {
		foreach ( $this->get_resources() as $resource ) {
			$slug  = $resource['slug'];
			$field = new Field( $slug );

			$label = 'Custom Label';
			$field->set_label( $label );

			$this->assertEquals( $label, $field->get_label() );
		}
	}

	/**
	 * @test
	 */
	public function it_should_get_placeholder() {
		foreach ( $this->get_resources() as $resource ) {
			$slug  = $resource['slug'];
			$field = new Field( $slug );

			$this->assertEquals( 'License key', $field->get_placeholder() );
		}
	}

	/**
	 * @test
	 */
	public function it_should_get_nonce_action_and_field() {
		foreach ( $this->get_resources() as $resource ) {
			$slug  = $resource['slug'];
			$field = new Field( $slug );

			$nonce_action = $field->get_nonce_action();
			$nonce_field  = $field->get_nonce_field();

			$this->assertNotEmpty( $nonce_action, 'Nonce action should not be empty' );
			$this->assertStringContainsString( 'stellarwp-uplink-license-key-nonce__' . $slug, $nonce_field );
		}
	}

	/**
	 * @test
	 */
	public function it_should_show_and_hide_label_and_heading() {
		foreach ( $this->get_resources() as $resource ) {
			$slug  = $resource['slug'];
			$field = new Field( $slug );

			$field->show_label( true );
			$this->assertTrue( $field->should_show_label() );

			$field->show_label( false );
			$this->assertFalse( $field->should_show_label() );

			$field->show_heading( true );
			$this->assertTrue( $field->should_show_heading() );

			$field->show_heading( false );
			$this->assertFalse( $field->should_show_heading() );
		}
	}

	/**
	 * @test
	 */
	public function it_should_render_correct_html() {
		foreach ( $this->get_resources() as $resource ) {
			$slug  = $resource['slug'];
			$field = new Field( $slug );

			$html = $field->render();
			$this->assertStringContainsString( 'stellarwp-uplink-license-key-field', $html );
			$this->assertStringContainsString( $slug, $html );
		}
	}

	/**
	 * @test
	 */
	public function it_should_handle_empty_field_name() {
		foreach ( $this->get_resources() as $resource ) {
			$slug  = $resource['slug'];
			$field = new Field( $slug );

			$this->assertEmpty( $field->get_field_name(), 'Field name should be empty by default' );
		}
	}

	/**
	 * @test
	 */
	public function it_should_handle_empty_field_id() {
		foreach ( $this->get_resources() as $resource ) {
			$slug  = $resource['slug'];
			$field = new Field( $slug );

			$this->assertNotEmpty( $field->get_field_id(), 'Field ID should not be empty even if not set' );
		}
	}

	/**
	 * @test
	 */
	public function it_should_handle_empty_label() {
		foreach ( $this->get_resources() as $resource ) {
			$slug  = $resource['slug'];
			$field = new Field( $slug );

			$this->assertEmpty( $field->get_label(), 'Label should be empty by default' );
		}
	}
}
