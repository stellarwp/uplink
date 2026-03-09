<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Licensing\Repositories;

use StellarWP\Uplink\Licensing\Error_Code;
use StellarWP\Uplink\Licensing\Product_Collection;
use StellarWP\Uplink\Licensing\Repositories\License_Repository;
use StellarWP\Uplink\Licensing\Results\Product_Entry;
use StellarWP\Uplink\Tests\UplinkTestCase;
use WP_Error;

/**
 * @since 3.0.0
 */
final class License_RepositoryTest extends UplinkTestCase {

	private License_Repository $repository;

	protected function setUp(): void {
		parent::setUp();
		$this->repository = new License_Repository();
		delete_option( License_Repository::KEY_OPTION_NAME );
		delete_option( License_Repository::LAST_ACTIVE_DATES_OPTION_NAME );
		delete_transient( License_Repository::PRODUCTS_TRANSIENT_KEY );
	}

	protected function tearDown(): void {
		delete_option( License_Repository::KEY_OPTION_NAME );
		delete_option( License_Repository::LAST_ACTIVE_DATES_OPTION_NAME );
		delete_transient( License_Repository::PRODUCTS_TRANSIENT_KEY );
		parent::tearDown();
	}

	// -------------------------------------------------------------------------
	// get_key() / store_key() / delete_key() / key_exists()
	// -------------------------------------------------------------------------

	public function test_get_returns_null_when_no_key_stored(): void {
		$this->assertNull( $this->repository->get_key() );
	}

	public function test_store_and_get_round_trip(): void {
		$this->repository->store_key( 'LWSW-UNIFIED-PRO-2026' );

		$this->assertSame( 'LWSW-UNIFIED-PRO-2026', $this->repository->get_key() );
	}

	public function test_store_returns_true_on_success(): void {
		$result = $this->repository->store_key( 'LWSW-UNIFIED-PRO-2026' );

		$this->assertTrue( $result );
	}

	public function test_store_is_idempotent_when_key_unchanged(): void {
		$this->repository->store_key( 'LWSW-UNIFIED-PRO-2026' );

		// Storing the same key again should still return true.
		$this->assertTrue( $this->repository->store_key( 'LWSW-UNIFIED-PRO-2026' ) );
	}

	public function test_store_overwrites_existing_key(): void {
		$this->repository->store_key( 'OLD-KEY' );
		$this->repository->store_key( 'NEW-KEY' );

		$this->assertSame( 'NEW-KEY', $this->repository->get_key() );
	}

	public function test_store_sanitizes_key(): void {
		$this->repository->store_key( 'LWSW-"UNIFIED\'-PRO`-2026' );

		$this->assertSame( 'LWSW-UNIFIED-PRO-2026', $this->repository->get_key() );
	}

	public function test_delete_removes_stored_key(): void {
		$this->repository->store_key( 'LWSW-UNIFIED-PRO-2026' );
		$this->repository->delete_key();

		$this->assertNull( $this->repository->get_key() );
	}

	public function test_delete_returns_true_when_key_existed(): void {
		$this->repository->store_key( 'LWSW-UNIFIED-PRO-2026' );

		$this->assertTrue( $this->repository->delete_key() );
	}

	public function test_exists_returns_false_when_no_key_stored(): void {
		$this->assertFalse( $this->repository->key_exists() );
	}

	public function test_exists_returns_true_after_storing_key(): void {
		$this->repository->store_key( 'LWSW-UNIFIED-PRO-2026' );

		$this->assertTrue( $this->repository->key_exists() );
	}

	public function test_exists_returns_false_after_deleting_key(): void {
		$this->repository->store_key( 'LWSW-UNIFIED-PRO-2026' );
		$this->repository->delete_key();

		$this->assertFalse( $this->repository->key_exists() );
	}

	public function test_get_returns_null_for_empty_string(): void {
		update_option( License_Repository::KEY_OPTION_NAME, '' );

		$this->assertNull( $this->repository->get_key() );
	}

	// -------------------------------------------------------------------------
	// get_products() / set_products() / delete_products()
	// -------------------------------------------------------------------------

	public function test_get_products_returns_false_on_cache_miss(): void {
		$this->assertNull( $this->repository->get_products() );
	}

	public function test_set_and_get_products_round_trip(): void {
		$collection = Product_Collection::from_array(
			[
				Product_Entry::from_array(
					[
						'product_slug' => 'give',
						'tier'         => 'give-pro',
						'status'       => 'active',
						'expires'      => '2026-12-31 23:59:59',
					]
				),
			]
		);

		$this->repository->set_products( $collection );

		$result = $this->repository->get_products();

		$this->assertInstanceOf( Product_Collection::class, $result );
		$this->assertSame( 'give', $result->get( 'give' )->get_product_slug() );
	}

	public function test_get_products_hydrates_from_stored_array(): void {
		$raw = [
			[
				'product_slug' => 'give',
				'tier'         => 'give-pro',
				'status'       => 'active',
				'expires'      => '2026-12-31 23:59:59',
				'activations'  => [
					'site_limit'   => 0,
					'active_count' => 0,
				],
			],
		];

		set_transient( License_Repository::PRODUCTS_TRANSIENT_KEY, $raw );

		$result = $this->repository->get_products();

		$this->assertInstanceOf( Product_Collection::class, $result );
		$this->assertSame( 'give', $result->get( 'give' )->get_product_slug() );
	}

	public function test_set_products_stores_plain_array_in_transient(): void {
		$collection = Product_Collection::from_array(
			[
				Product_Entry::from_array(
					[
						'product_slug' => 'give',
						'tier'         => 'give-pro',
						'status'       => 'active',
						'expires'      => '2026-12-31 23:59:59',
					]
				),
			]
		);

		$this->repository->set_products( $collection );

		$raw = get_transient( License_Repository::PRODUCTS_TRANSIENT_KEY );

		$this->assertIsArray( $raw );
		$this->assertCount( 1, $raw );
		$this->assertIsArray( $raw[0] );
		$this->assertSame( 'give', $raw[0]['product_slug'] );
	}

	public function test_set_products_caches_wp_error(): void {
		$error = new WP_Error( Error_Code::INVALID_KEY, 'Bad key' );

		$this->repository->set_products( $error );

		$result = $this->repository->get_products();

		$this->assertInstanceOf( WP_Error::class, $result );
	}

	public function test_delete_products_clears_cache(): void {
		$collection = Product_Collection::from_array(
			[
				Product_Entry::from_array(
					[
						'product_slug' => 'give',
						'tier'         => 'give-pro',
						'status'       => 'active',
						'expires'      => '2026-12-31 23:59:59',
					]
				),
			]
		);

		$this->repository->set_products( $collection );
		$this->repository->delete_products();

		$this->assertNull( $this->repository->get_products() );
	}

	// -------------------------------------------------------------------------
	// get_product()
	// -------------------------------------------------------------------------

	public function test_get_product_returns_null_on_cache_miss(): void {
		$this->assertNull( $this->repository->get_product( 'give' ) );
	}

	public function test_get_product_returns_null_for_unknown_slug(): void {
		$this->repository->set_products(
			Product_Collection::from_array(
				[
					Product_Entry::from_array(
						[
							'product_slug'      => 'give',
							'tier'              => 'give-pro',
							'status'            => 'active',
							'expires'           => '2026-12-31 23:59:59',
							'validation_status' => 'valid',
						]
					),
				]
			)
		);

		$this->assertNull( $this->repository->get_product( 'unknown-product' ) );
	}

	public function test_get_product_returns_entry_for_known_slug(): void {
		$this->repository->set_products(
			Product_Collection::from_array(
				[
					Product_Entry::from_array(
						[
							'product_slug'      => 'give',
							'tier'              => 'give-pro',
							'status'            => 'active',
							'expires'           => '2026-12-31 23:59:59',
							'validation_status' => 'valid',
						]
					),
				]
			)
		);

		$result = $this->repository->get_product( 'give' );

		$this->assertInstanceOf( Product_Entry::class, $result );
		$this->assertSame( 'give', $result->get_product_slug() );
	}

	public function test_get_product_returns_null_when_cache_contains_wp_error(): void {
		$this->repository->set_products( new WP_Error( Error_Code::INVALID_KEY, 'Bad key' ) );

		$this->assertNull( $this->repository->get_product( 'give' ) );
	}

	// -------------------------------------------------------------------------
	// has_product()
	// -------------------------------------------------------------------------

	public function test_has_product_returns_false_on_cache_miss(): void {
		$this->assertFalse( $this->repository->has_product( 'give' ) );
	}

	public function test_has_product_returns_false_for_unknown_slug(): void {
		$this->repository->set_products(
			Product_Collection::from_array(
				[
					Product_Entry::from_array(
						[
							'product_slug' => 'give',
							'tier'         => 'give-pro',
							'status'       => 'active',
							'expires'      => '2026-12-31 23:59:59',
						]
					),
				]
			)
		);

		$this->assertFalse( $this->repository->has_product( 'unknown-product' ) );
	}

	public function test_has_product_returns_true_for_known_slug(): void {
		$this->repository->set_products(
			Product_Collection::from_array(
				[
					Product_Entry::from_array(
						[
							'product_slug' => 'give',
							'tier'         => 'give-pro',
							'status'       => 'active',
							'expires'      => '2026-12-31 23:59:59',
						]
					),
				]
			)
		);

		$this->assertTrue( $this->repository->has_product( 'give' ) );
	}

	// -------------------------------------------------------------------------
	// is_product_valid()
	// -------------------------------------------------------------------------

	public function test_is_product_valid_returns_false_on_cache_miss(): void {
		$this->assertFalse( $this->repository->is_product_valid( 'give' ) );
	}

	public function test_is_product_valid_returns_false_for_unknown_slug(): void {
		$this->repository->set_products(
			Product_Collection::from_array(
				[
					Product_Entry::from_array(
						[
							'product_slug'      => 'give',
							'tier'              => 'give-pro',
							'status'            => 'active',
							'expires'           => '2026-12-31 23:59:59',
							'validation_status' => 'valid',
						]
					),
				]
			)
		);

		$this->assertFalse( $this->repository->is_product_valid( 'unknown-product' ) );
	}

	public function test_is_product_valid_returns_true_for_valid_product(): void {
		$this->repository->set_products(
			Product_Collection::from_array(
				[
					Product_Entry::from_array(
						[
							'product_slug'      => 'give',
							'tier'              => 'give-pro',
							'status'            => 'active',
							'expires'           => '2026-12-31 23:59:59',
							'validation_status' => 'valid',
						]
					),
				]
			)
		);

		$this->assertTrue( $this->repository->is_product_valid( 'give' ) );
	}

	public function test_is_product_valid_returns_false_for_invalid_product(): void {
		$this->repository->set_products(
			Product_Collection::from_array(
				[
					Product_Entry::from_array(
						[
							'product_slug'      => 'give',
							'tier'              => 'give-pro',
							'status'            => 'active',
							'expires'           => '2026-12-31 23:59:59',
							'validation_status' => 'not_activated',
						]
					),
				]
			)
		);

		$this->assertFalse( $this->repository->is_product_valid( 'give' ) );
	}

	public function test_store_fires_action_when_key_changes(): void {
		$fired = [];

		add_action(
			'stellarwp/uplink/unified_license_key_changed',
			static function ( string $new_key, string $old_key ) use ( &$fired ) {
				$fired[] = [ $new_key, $old_key ];
			},
			10,
			2
		);

		$this->repository->store_key( 'LWSW-FIRST-KEY' );

		$this->assertCount( 1, $fired );
		$this->assertSame( 'LWSW-FIRST-KEY', $fired[0][0] );
		$this->assertSame( '', $fired[0][1] );
	}

	public function test_store_does_not_fire_action_when_key_unchanged(): void {
		$this->repository->store_key( 'LWSW-SAME-KEY' );

		$fired = false;

		add_action(
			'stellarwp/uplink/unified_license_key_changed',
			static function () use ( &$fired ) {
				$fired = true;
			}
		);

		$this->repository->store_key( 'LWSW-SAME-KEY' );

		$this->assertFalse( $fired );
	}

	public function test_store_fires_action_with_old_key_on_overwrite(): void {
		$this->repository->store_key( 'LWSW-OLD-KEY' );

		$fired = [];

		add_action(
			'stellarwp/uplink/unified_license_key_changed',
			static function ( string $new_key, string $old_key ) use ( &$fired ) {
				$fired[] = [ $new_key, $old_key ];
			},
			10,
			2
		);

		$this->repository->store_key( 'LWSW-NEW-KEY' );

		$this->assertCount( 1, $fired );
		$this->assertSame( 'LWSW-NEW-KEY', $fired[0][0] );
		$this->assertSame( 'LWSW-OLD-KEY', $fired[0][1] );
	}

	public function test_delete_fires_action_when_key_existed(): void {
		$this->repository->store_key( 'LWSW-DELETE-ME' );

		$fired = [];

		add_action(
			'stellarwp/uplink/unified_license_key_changed',
			static function ( string $new_key, string $old_key ) use ( &$fired ) {
				$fired[] = [ $new_key, $old_key ];
			},
			10,
			2
		);

		$this->repository->delete_key();

		$this->assertCount( 1, $fired );
		$this->assertSame( '', $fired[0][0] );
		$this->assertSame( 'LWSW-DELETE-ME', $fired[0][1] );
	}

	public function test_delete_does_not_fire_action_when_no_key_existed(): void {
		$fired = false;

		add_action(
			'stellarwp/uplink/unified_license_key_changed',
			static function () use ( &$fired ) {
				$fired = true;
			}
		);

		$this->repository->delete_key();

		$this->assertFalse( $fired );
	}

	// -------------------------------------------------------------------------
	// get_last_active_date() / set_last_active_date()
	// -------------------------------------------------------------------------

	public function test_get_last_active_date_returns_null_when_not_set(): void {
		$this->assertNull( $this->repository->get_last_active_date( 'give' ) );
	}

	public function test_set_and_get_last_active_date_round_trip(): void {
		$timestamp = time();

		$this->repository->set_last_active_date( 'give', $timestamp );

		$this->assertSame( $timestamp, $this->repository->get_last_active_date( 'give' ) );
	}

	public function test_last_active_date_is_stored_per_slug(): void {
		$now = time();

		$this->repository->set_last_active_date( 'give', $now );
		$this->repository->set_last_active_date( 'tec', $now - 1000 );

		$this->assertSame( $now, $this->repository->get_last_active_date( 'give' ) );
		$this->assertSame( $now - 1000, $this->repository->get_last_active_date( 'tec' ) );
		$this->assertNull( $this->repository->get_last_active_date( 'unknown' ) );
	}

	public function test_set_last_active_date_overwrites_previous_value(): void {
		$this->repository->set_last_active_date( 'give', 1000 );
		$this->repository->set_last_active_date( 'give', 2000 );

		$this->assertSame( 2000, $this->repository->get_last_active_date( 'give' ) );
	}

	// -------------------------------------------------------------------------
	// is_product_active() — grace period behaviour
	// -------------------------------------------------------------------------

	public function test_is_product_active_returns_true_when_product_is_valid(): void {
		$this->repository->set_products(
			Product_Collection::from_array(
				[
					Product_Entry::from_array(
						[
							'product_slug'      => 'give',
							'tier'              => 'give-pro',
							'status'            => 'active',
							'expires'           => '2026-12-31 23:59:59',
							'validation_status' => 'valid',
						]
					),
				]
			)
		);

		$this->assertTrue( $this->repository->is_product_active( 'give' ) );
	}

	public function test_is_product_active_returns_false_when_no_catalog_and_no_last_active_date(): void {
		$this->assertFalse( $this->repository->is_product_active( 'give' ) );
	}

	public function test_is_product_active_returns_true_within_grace_period(): void {
		// Record an active date 1 day ago — well within the 30-day grace window.
		$one_day_ago = time() - DAY_IN_SECONDS;
		$this->repository->set_last_active_date( 'give', $one_day_ago );

		// No catalog cached — simulates an expired/unavailable license server response.
		$this->assertTrue( $this->repository->is_product_active( 'give' ) );
	}

	public function test_is_product_active_returns_false_after_grace_period_expires(): void {
		// Record an active date 31 days ago — past the 30-day grace window.
		$thirty_one_days_ago = time() - ( 31 * DAY_IN_SECONDS );
		$this->repository->set_last_active_date( 'give', $thirty_one_days_ago );

		$this->assertFalse( $this->repository->is_product_active( 'give' ) );
	}

	public function test_is_product_active_returns_true_when_product_is_valid_even_without_last_active_date(): void {
		$this->repository->set_products(
			Product_Collection::from_array(
				[
					Product_Entry::from_array(
						[
							'product_slug'      => 'give',
							'tier'              => 'give-pro',
							'status'            => 'active',
							'expires'           => '2026-12-31 23:59:59',
							'validation_status' => 'valid',
						]
					),
				]
			)
		);

		// No last active date recorded — but catalog says valid, so still active.
		$this->assertTrue( $this->repository->is_product_active( 'give' ) );
	}

	public function test_grace_period_is_per_slug(): void {
		$one_day_ago     = time() - DAY_IN_SECONDS;
		$thirty_one_days = time() - ( 31 * DAY_IN_SECONDS );

		$this->repository->set_last_active_date( 'give', $one_day_ago );
		$this->repository->set_last_active_date( 'tec', $thirty_one_days );

		$this->assertTrue( $this->repository->is_product_active( 'give' ) );
		$this->assertFalse( $this->repository->is_product_active( 'tec' ) );
	}
}
