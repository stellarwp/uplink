<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Legacy;

use StellarWP\Uplink\Legacy\License_Repository;
use StellarWP\Uplink\Legacy\Legacy_License;
use StellarWP\Uplink\Tests\UplinkTestCase;

/**
 * @since 3.0.0
 */
final class License_RepositoryTest extends UplinkTestCase {

	/**
	 * @var License_Repository
	 */
	private $repository;

	protected function setUp(): void {
		parent::setUp();
		$this->repository = new License_Repository();
	}

	protected function tearDown(): void {
		remove_all_filters( 'stellarwp/uplink/legacy_licenses' );
		parent::tearDown();
	}

	/**
	 * @since 3.0.0
	 */
	public function it_returns_empty_array_when_no_filter_adds_licenses(): void {
		$this->assertSame( [], $this->repository->all() );
	}

	/**
	 * @since 3.0.0
	 */
	public function it_normalizes_array_items_to_legacy_license_instances(): void {
		add_filter(
			'stellarwp/uplink/legacy_licenses',
			static function ( array $licenses ) {
				return array_merge(
					$licenses,
					[
						[
							'key'      => 'key-1',
							'slug'     => 'plugin-one',
							'name'     => 'Plugin One',
							'brand'    => 'Brand',
							'status'   => 'valid',
							'page_url' => 'https://example.com/license',
						],
					]
				);
			}
		);

		$result = $this->repository->all();

		$this->assertCount( 1, $result );
		$this->assertInstanceOf( Legacy_License::class, $result[0] );
		$this->assertSame( 'key-1', $result[0]->key );
		$this->assertSame( 'plugin-one', $result[0]->slug );
	}

	/**
	 * @since 3.0.0
	 */
	public function it_merges_licenses_from_multiple_filter_callbacks(): void {
		add_filter(
			'stellarwp/uplink/legacy_licenses',
			static function ( array $licenses ) {
				return array_merge(
					$licenses,
					[
						[
							'key'   => 'key-a',
							'slug'  => 'plugin-a',
							'name'  => 'A',
							'brand' => 'Brand',
						],
					]
				);
			}
		);
		add_filter(
			'stellarwp/uplink/legacy_licenses',
			static function ( array $licenses ) {
				return array_merge(
					$licenses,
					[
						[
							'key'   => 'key-b',
							'slug'  => 'plugin-b',
							'name'  => 'B',
							'brand' => 'Brand',
						],
					]
				);
			}
		);

		$result = $this->repository->all();

		$this->assertCount( 2, $result );
		$this->assertSame( 'plugin-a', $result[0]->slug );
		$this->assertSame( 'plugin-b', $result[1]->slug );
	}

	/**
	 * @since 3.0.0
	 */
	public function it_ignores_non_array_items(): void {
		add_filter(
			'stellarwp/uplink/legacy_licenses',
			static function ( array $licenses ) {
				$licenses[] = (object) [ 'slug' => 'invalid' ];
				$licenses[] = [
					'key'   => 'valid-key',
					'slug'  => 'valid-plugin',
					'name'  => 'Valid',
					'brand' => 'Brand',
				];

				return $licenses;
			}
		);

		$result = $this->repository->all();

		$this->assertCount( 1, $result );
		$this->assertSame( 'valid-plugin', $result[0]->slug );
	}

	/**
	 * @since 3.0.0
	 */
	public function it_finds_license_by_slug(): void {
		add_filter(
			'stellarwp/uplink/legacy_licenses',
			static function ( array $licenses ) {
				return array_merge(
					$licenses,
					[
						[
							'key'   => 'k1',
							'slug'  => 'first',
							'name'  => 'First',
							'brand' => 'B',
						],
						[
							'key'   => 'k2',
							'slug'  => 'target',
							'name'  => 'Target',
							'brand' => 'B',
						],
						[
							'key'   => 'k3',
							'slug'  => 'third',
							'name'  => 'Third',
							'brand' => 'B',
						],
					]
				);
			}
		);

		$found = $this->repository->find( 'target' );

		$this->assertInstanceOf( Legacy_License::class, $found );
		$this->assertSame( 'target', $found->slug );
		$this->assertSame( 'k2', $found->key );
	}

	/**
	 * @since 3.0.0
	 */
	public function it_returns_null_when_slug_not_found(): void {
		add_filter(
			'stellarwp/uplink/legacy_licenses',
			static function ( array $licenses ) {
				return array_merge(
					$licenses,
					[
						[
							'key'   => 'k1',
							'slug'  => 'only-one',
							'name'  => 'Only',
							'brand' => 'B',
						],
					]
				);
			}
		);

		$this->assertNull( $this->repository->find( 'nonexistent' ) );
	}

	/**
	 * @since 3.0.0
	 */
	public function it_caches_results_across_multiple_calls(): void {
		$call_count = 0;

		add_filter(
			'stellarwp/uplink/legacy_licenses',
			static function ( array $licenses ) use ( &$call_count ) {
				$call_count++;

				return array_merge(
					$licenses,
					[
						[
							'key'   => 'k1',
							'slug'  => 's1',
							'name'  => 'N',
							'brand' => 'B',
						],
					]
				);
			}
		);

		$this->repository->all();
		$this->repository->all();
		$this->repository->find( 's1' );
		$this->repository->has_any();

		$this->assertSame( 1, $call_count, 'Filter should only be applied once per request cycle.' );
	}

	/**
	 * @since 3.0.0
	 */
	public function it_returns_false_for_has_any_when_empty(): void {
		$this->assertFalse( $this->repository->has_any() );
	}

	/**
	 * @since 3.0.0
	 */
	public function it_returns_true_for_has_any_when_licenses_exist(): void {
		add_filter(
			'stellarwp/uplink/legacy_licenses',
			static function ( array $licenses ) {
				return array_merge(
					$licenses,
					[
						[
							'key'   => 'k1',
							'slug'  => 's1',
							'name'  => 'N',
							'brand' => 'B',
						],
					]
				);
			}
		);

		$this->assertTrue( $this->repository->has_any() );
	}
}
