<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\License\LicenseKeyFetcher;

use RuntimeException;
use StellarWP\Uplink\Auth\License\License_Manager;
use StellarWP\Uplink\Config;
use StellarWP\Uplink\Enums\License_Strategy;
use StellarWP\Uplink\License\License_Key_Fetcher;
use StellarWP\Uplink\License\Storage\License_Single_Site_Storage;
use StellarWP\Uplink\Register;
use StellarWP\Uplink\Resources\Resource;
use StellarWP\Uplink\Tests\Sample_Plugin;
use StellarWP\Uplink\Tests\Sample_Plugin_Helper;
use StellarWP\Uplink\Tests\UplinkTestCase;

/**
 * Test both "isolated" and "global" strategies function in single site mode.
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
	 * @var License_Key_Fetcher
	 */
	private $fetcher;

	/**
	 * Directly access single site license storage.
	 *
	 * @var License_Single_Site_Storage
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

		$this->container->get( License_Manager::class )->disable_cache();

		$this->fetcher         = $this->container->get( License_Key_Fetcher::class );
		$this->single_storage  = $this->container->get( License_Single_Site_Storage::class );
	}

	/**
	 * Data Providers must return something, but we are just using them to switch
	 * the config for each test.
	 *
	 * WARNING: Due to the way data Providers run, ensure "global" or whatever the default is
	 * in Config::$license_strategy is the last item, otherwise it doesn't get reset back.
	 *
	 * @see Config::$license_strategy
	 *
	 * @return array<string[]>
	 */
	public function configDataProvider(): array {
		$isolated = static function (): string {
			Config::set_license_key_strategy( License_Strategy::ISOLATED );

			return License_Strategy::ISOLATED;
		};

		$global = static function (): string {
			Config::set_license_key_strategy( License_Strategy::GLOBAL );

			return License_Strategy::GLOBAL;
		};

		return [
			[
				$isolated(),
			],
			[
				$global(),
			],
		];
	}

	/**
	 * @dataProvider configDataProvider
	 */
	public function test_it_throws_exception_with_invalid_license_key_strategy( string $config ): void {
		// Ensure the data provider is working just once.
		$this->assertThat( $config, $this->logicalOr(
			$this->equalTo( License_Strategy::ISOLATED ),
			$this->equalTo( License_Strategy::GLOBAL )
		) );

		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'Invalid config license strategy provided.' );

		Config::set_license_key_strategy( 'invalid' );

		$this->fetcher->get_key( $this->slug );
	}

	/**
	 * @dataProvider configDataProvider
	 */
	public function test_it_returns_null_with_unknown_resource(): void {
		$this->assertNull( $this->fetcher->get_key( 'unknown-resource' ) );
	}

	/**
	 * @dataProvider configDataProvider
	 */
	public function test_it_gets_single_site_license_key(): void {
		$this->assertNull( $this->fetcher->get_key( $this->slug ) );

		$this->single_storage->store( $this->resource, 'abcdef' );

		$this->assertSame( 'abcdef', $this->fetcher->get_key( $this->slug ) );
	}

	/**
	 * @dataProvider configDataProvider
	 */
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
		$this->assertSame( 'file-based-license-key', $this->fetcher->get_key( $slug ) );
	}

}
