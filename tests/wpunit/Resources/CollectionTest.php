<?php

namespace StellarWP\Uplink\Tests\Resources;

use StellarWP\Uplink\Container;
use StellarWP\Uplink\Uplink;
use StellarWP\Uplink\Register;
use StellarWP\Uplink\Resources as Uplink_Resources;

class CollectionTest extends \StellarWP\Uplink\Tests\UplinkTestCase {
	public $collection;
	public $container;

	public function setUp() {
		parent::setUp();

		$this->container  = Container::init();
		$this->collection = $this->container->make( Uplink_Resources\Collection::class );

		$resources = $this->get_resources();
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
	public function get_resources() {
		$root = dirname( dirname( dirname( __DIR__ ) ) );

		return [
			'plugin-1' => [
				'slug'          => 'plugin-1',
				'name'          => 'Plugin 1',
				'path'          => $root . '/plugin.php',
				'class'         => Uplink::class,
				'license_class' => Uplink::class,
				'version'       => '1.0.0',
				'type'          => 'plugin',
			],
			'plugin-2' => [
				'slug'          => 'plugin-2',
				'name'          => 'Plugin 2',
				'path'          => $root . '/plugin.php',
				'class'         => Uplink::class,
				'license_class' => Uplink::class,
				'version'       => '2.0.0',
				'type'          => 'plugin',
			],
			'service-1' => [
				'slug'          => 'service-1',
				'name'          => 'Service 1',
				'path'          => $root . '/plugin.php',
				'class'         => Uplink::class,
				'license_class' => Uplink::class,
				'version'       => '3.0.0',
				'type'          => 'service',
			],
			'service-2' => [
				'slug'          => 'service-2',
				'name'          => 'Service 2',
				'path'          => $root . '/plugin.php',
				'class'         => Uplink::class,
				'license_class' => Uplink::class,
				'version'       => '4.0.0',
				'type'          => 'service',
			],
		];
	}

	/**
	 * It should register a plugin into the collection.
	 *
	 * @test
	 */
	public function it_should_register_resources() {

		$this->assertInstanceOf( Uplink_Resources\Plugin::class, $this->collection['plugin-1'] );
		$this->assertInstanceOf( Uplink_Resources\Plugin::class, $this->collection['plugin-2'] );
		$this->assertInstanceOf( Uplink_Resources\Service::class, $this->collection['service-1'] );
		$this->assertInstanceOf( Uplink_Resources\Service::class, $this->collection['service-2'] );

		$this->assertEquals( 'plugin-1', $this->collection['plugin-1']->get_slug() );
		$this->assertEquals( 'Plugin 1', $this->collection['plugin-1']->get_name() );
		$this->assertEquals( '1.0.0', $this->collection['plugin-1']->get_version() );

		$this->assertEquals( 'plugin-2', $this->collection['plugin-2']->get_slug() );
		$this->assertEquals( 'Plugin 2', $this->collection['plugin-2']->get_name() );
		$this->assertEquals( '2.0.0', $this->collection['plugin-2']->get_version() );
	}

	/**
	 * It should loop over resources.
	 *
	 * @test
	 */
	public function it_should_loop_over_resources() {
		foreach ( $this->collection as $resource ) {
			$this->assertInstanceOf( Uplink_Resources\Resource::class, $resource );
		}
	}
}
