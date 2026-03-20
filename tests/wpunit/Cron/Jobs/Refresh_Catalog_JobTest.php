<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Cron\Jobs;

use StellarWP\Uplink\Catalog\Catalog_Repository;
use StellarWP\Uplink\Cron\Jobs\Refresh_Catalog_Job;
use StellarWP\Uplink\Tests\UplinkTestCase;

final class Refresh_Catalog_JobTest extends UplinkTestCase {

	public function test_run_calls_catalog_refresh(): void {
		$called = false;

		$catalog = $this->makeEmpty(
			Catalog_Repository::class,
			[
				'refresh' => static function () use ( &$called ) {
					$called = true;
				},
			]
		);

		$job = new Refresh_Catalog_Job( $catalog );
		$job->run();

		$this->assertTrue( $called );
	}
}
