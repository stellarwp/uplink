<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Resources\License;

use StellarWP\Uplink\License\Manager\License_Handler;
use StellarWP\Uplink\License\Storage\Local_Storage;
use StellarWP\Uplink\Register;
use StellarWP\Uplink\Resources\Resource;
use StellarWP\Uplink\Tests\Sample_Plugin;
use StellarWP\Uplink\Tests\Sample_Plugin_Helper;
use StellarWP\Uplink\Tests\UplinkTestCase;

/**
 * Test license key fetching in single site mode.
 */
final class LicenseKeySingleSiteFetcherTest extends UplinkTestCase {

	/**
	 * The resource slug.
	 *
	 * @var string
	 */
	private $slug = 'sample';

	/**
	 * @var Resource
	 */
	private $resource;

	/**
	 * Directly access single site license storage.
	 *
	 * @var Local_Storage
	 */
	private $single_storage;

	protected function setUp(): void {
		parent::setUp();

		// Register the sample plugin as a developer would in their plugin.
		$this->resource = Register::plugin(
			$this->slug,
			'Lib Sample',
			'1.0.10',
			'uplink/index.php',
			Sample_Plugin::class
		);

		$this->container->get( License_Handler::class )->disable_cache();

		$this->single_storage = $this->container->get( Local_Storage::class );
	}

	public function test_it_gets_single_site_license_key(): void {
		$this->assertEmpty( $this->resource->get_license_key() );

		$this->single_storage->store( $this->resource, 'abcdef' );

		$this->assertSame( 'abcdef', $this->resource->get_license_key() );
	}

	public function test_it_gets_single_site_fallback_file_license_key(): void {
		$slug = 'sample-with-license';

		// Register the sample plugin as a developer would in their plugin.
		$resource = Register::plugin(
			$slug,
			'Lib Sample With License',
			'1.2.0',
			'uplink/index.php',
			Sample_Plugin::class,
			Sample_Plugin_Helper::class
		);

		// No local key stored.
		$this->assertEmpty( $this->single_storage->get( $resource ) );

		// File based key returned.
		$this->assertSame( 'file-based-license-key', $resource->get_license_key() );
	}

}
