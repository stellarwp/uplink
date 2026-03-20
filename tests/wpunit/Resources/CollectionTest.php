<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Resources;

use ArrayIterator;
use StellarWP\Uplink\Tests\UplinkTestCase;
use StellarWP\Uplink\Uplink;
use StellarWP\Uplink\Register;
use StellarWP\Uplink\Resources as Uplink_Resources;

final class CollectionTest extends UplinkTestCase {

	/**
	 * @var Uplink_Resources\Collection
	 */
	private $collection;

	/**
	 * The root directory for plugin/services paths.
	 *
	 * @var string
	 */
	private $root;

	protected function setUp(): void {
		parent::setUp();

		$this->collection = $this->container->get( Uplink_Resources\Collection::class );
		$this->root       = dirname( __DIR__, 3 );

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
	public function get_resources(): array {
		return [
			'plugin-1'  => [
				'slug'          => 'plugin-1',
				'name'          => 'Plugin 1',
				'path'          => $this->root . '/plugin.php',
				'class'         => Uplink::class,
				'license_class' => Uplink::class,
				'version'       => '1.0.0',
				'type'          => 'plugin',
			],
			'plugin-2'  => [
				'slug'          => 'plugin-2',
				'name'          => 'Plugin 2',
				'path'          => $this->root . '/plugin.php',
				'class'         => Uplink::class,
				'license_class' => Uplink::class,
				'version'       => '2.0.0',
				'type'          => 'plugin',
			],
			'service-1' => [
				'slug'          => 'service-1',
				'name'          => 'Service 1',
				'path'          => $this->root . '/service1.php',
				'class'         => Uplink::class,
				'license_class' => Uplink::class,
				'version'       => '3.0.0',
				'type'          => 'service',
			],
			'service-2' => [
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
	 * It should register a plugin into the collection.
	 */
	public function test_it_should_register_resources(): void {
		$this->assertSame( 4, $this->collection->count() );

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

	public function test_it_should_loop_over_resources(): void {
		foreach ( $this->collection as $resource ) {
			$this->assertInstanceOf( Uplink_Resources\Resource::class, $resource );
		}
	}

	public function test_it_gets_multiple_resources_by_path(): void {
		$resources = $this->collection->get_by_path( $this->root . '/plugin.php' );

		$this->assertSame( 2, $resources->count() );
		$this->assertInstanceOf( Uplink_Resources\Collection::class, $resources );

		// Assert we didn't modify the underlying collection.
		$this->assertSame( 4, $this->collection->count() );
		$this->assertCount( 4, $this->collection->getIterator() );

		foreach ( $resources as $resource ) {
			$this->assertThat(
				$resource->get_slug(),
				$this->logicalOr(
					$this->equalTo( 'plugin-1' ),
					$this->equalTo( 'plugin-2' )
				) 
			);
		}
	}

	public function test_it_gets_multiple_resources_by_multiple_paths(): void {
		$resources = $this->collection->get_by_paths(
			[
				$this->root . '/plugin.php',
				$this->root . '/service1.php',
			] 
		);

		$this->assertSame( 3, $resources->count() );
		$this->assertInstanceOf( Uplink_Resources\Collection::class, $resources );

		// Assert we didn't modify the underlying collection.
		$this->assertSame( 4, $this->collection->count() );
		$this->assertCount( 4, $this->collection->getIterator() );

		foreach ( $resources as $resource ) {
			$this->assertThat(
				$resource->get_slug(),
				$this->logicalOr(
					$this->equalTo( 'plugin-1' ),
					$this->equalTo( 'plugin-2' ),
					$this->equalTo( 'service-1' )
				) 
			);
		}
	}

	public function test_it_gets_plugins(): void {
		$resources = $this->collection->get_plugins();

		$this->assertSame( 2, $resources->count() );
		$this->assertInstanceOf( Uplink_Resources\Collection::class, $resources );

		// Assert we didn't modify the underlying collection.
		$this->assertSame( 4, $this->collection->count() );

		foreach ( $resources as $resource ) {
			$this->assertThat(
				$resource->get_slug(),
				$this->logicalOr(
					$this->equalTo( 'plugin-1' ),
					$this->equalTo( 'plugin-2' )
				) 
			);
		}
	}

	public function test_it_gets_services(): void {
		$resources = $this->collection->get_services();

		$this->assertSame( 2, $resources->count() );
		$this->assertInstanceOf( Uplink_Resources\Collection::class, $resources );

		// Assert we didn't modify the underlying collection.
		$this->assertSame( 4, $this->collection->count() );

		foreach ( $resources as $resource ) {
			$this->assertThat(
				$resource->get_slug(),
				$this->logicalOr(
					$this->equalTo( 'service-1' ),
					$this->equalTo( 'service-2' )
				) 
			);
		}
	}

	public function test_it_accepts_an_iterator(): void {
		$plugins   = array_slice( $this->get_resources(), 0, 2 );
		$resources = [];

		foreach ( $plugins as $slug => $plugin ) {
			$resources[ $slug ] = new Uplink_Resources\Plugin(
				$plugin['slug'],
				$plugin['name'],
				$plugin['version'],
				$plugin['path'],
				$plugin['class'] 
			);
		}

		$collection = new Uplink_Resources\Collection( new ArrayIterator( $resources ) );

		$this->assertSame( 2, $collection->count() );
		$this->assertCount( 2, $collection->getIterator() );

		foreach ( $collection as $resource ) {
			$this->assertThat(
				$resource->get_slug(),
				$this->logicalOr(
					$this->equalTo( 'plugin-1' ),
					$this->equalTo( 'plugin-2' )
				) 
			);
		}

		$services = $collection->get_services();

		$this->assertCount( 0, $services );

		// Assert the original underlying iterator was not changed.
		$this->assertSame( 2, $collection->count() );
		$this->assertCount( 2, $collection->getIterator() );
	}
}
