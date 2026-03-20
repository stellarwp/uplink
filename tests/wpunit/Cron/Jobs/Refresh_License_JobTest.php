<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Cron\Jobs;

use StellarWP\Uplink\Cron\Jobs\Refresh_License_Job;
use StellarWP\Uplink\Licensing\License_Manager;
use StellarWP\Uplink\Site\Data;
use StellarWP\Uplink\Tests\UplinkTestCase;

final class Refresh_License_JobTest extends UplinkTestCase {

	public function test_run_skips_when_no_key_stored(): void {
		$refresh_called = false;

		$license_manager = $this->makeEmpty(
			License_Manager::class,
			[
				'key_exists'       => false,
				'refresh_products' => static function () use ( &$refresh_called ) {
					$refresh_called = true;
				},
			]
		);

		$site_data = $this->makeEmpty( Data::class );

		$job = new Refresh_License_Job( $license_manager, $site_data );
		$job->run();

		$this->assertFalse( $refresh_called );
	}

	public function test_run_refreshes_products_with_site_domain(): void {
		$domain          = 'example.com';
		$refreshed_with  = null;

		$license_manager = $this->makeEmpty(
			License_Manager::class,
			[
				'key_exists'       => true,
				'refresh_products' => static function ( string $d ) use ( &$refreshed_with ) {
					$refreshed_with = $d;
				},
			]
		);

		$site_data = $this->makeEmpty(
			Data::class,
			[ 'get_domain' => $domain ]
		);

		$job = new Refresh_License_Job( $license_manager, $site_data );
		$job->run();

		$this->assertSame( $domain, $refreshed_with );
	}
}
