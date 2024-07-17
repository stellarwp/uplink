<?php

namespace StellarWP\Uplink\Tests;

use StellarWP\Uplink\Uplink;

trait TestUtils {
	protected $base;

	/**
	 * Resources.
	 */
	public function get_test_resources(): array {
		$base = $this->get_base();

		return [
			[
				'slug'          => 'plugin-1',
				'name'          => 'Plugin 1',
				'path'          => $base . '/plugin.php',
				'class'         => Uplink::class,
				'license_class' => Uplink::class,
				'version'       => '1.0.0',
				'type'          => 'plugin',
			],
			[
				'slug'          => 'plugin-2',
				'name'          => 'Plugin 2',
				'path'          => $base . '/plugin.php',
				'class'         => Uplink::class,
				'license_class' => Uplink::class,
				'version'       => '2.0.0',
				'type'          => 'plugin',
			],
			[
				'slug'          => 'service-1',
				'name'          => 'Service 1',
				'path'          => $base . '/service1.php',
				'class'         => Uplink::class,
				'license_class' => Uplink::class,
				'version'       => '3.0.0',
				'type'          => 'service',
			],
			[
				'slug'          => 'service-2',
				'name'          => 'Service 2',
				'path'          => $base . '/service2.php',
				'class'         => Uplink::class,
				'license_class' => Uplink::class,
				'version'       => '4.0.0',
				'type'          => 'service',
			],
		];
	}

	/**
	 * @before
	 */
	public function get_base() {
		return dirname( __DIR__, 2 );
	}
}
