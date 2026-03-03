<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Licensing\Results;

use DateTimeImmutable;
use StellarWP\Uplink\Licensing\Results\Product_Entry;
use StellarWP\Uplink\Tests\UplinkTestCase;

final class Product_EntryTest extends UplinkTestCase {

	/**
	 * A complete API response array for a valid product entry.
	 *
	 * @var array<string, mixed>
	 */
	private array $valid_data = [
		'product_slug'      => 'kadence',
		'tier'              => 'professional',
		'pending_tier'      => null,
		'status'            => 'active',
		'expires'           => '2026-12-31 23:59:59',
		'activations'       => [
			'site_limit'   => 5,
			'active_count' => 3,
			'over_limit'   => false,
		],
		'installed_here'    => true,
		'validation_status' => 'valid',
		'is_valid'          => true,
	];

	/**
	 * Tests that from_array() hydrates all fields correctly.
	 *
	 * @return void
	 */
	public function test_from_array_hydrates_all_fields(): void {
		$entry = Product_Entry::from_array( $this->valid_data );

		$this->assertSame( 'kadence', $entry->get_product_slug() );
		$this->assertSame( 'professional', $entry->get_tier() );
		$this->assertNull( $entry->get_pending_tier() );
		$this->assertSame( 'active', $entry->get_status() );
		$this->assertInstanceOf( DateTimeImmutable::class, $entry->get_expires() );
		$this->assertSame( '2026-12-31 23:59:59', $entry->get_expires()->format( 'Y-m-d H:i:s' ) );
		$this->assertSame( 5, $entry->get_site_limit() );
		$this->assertSame( 3, $entry->get_active_count() );
		$this->assertTrue( $entry->get_installed_here() );
		$this->assertSame( 'valid', $entry->get_validation_status() );
	}

	/**
	 * Tests that to_array() produces the API response shape with nested activations.
	 *
	 * @return void
	 */
	public function test_to_array_produces_api_shape(): void {
		$entry  = Product_Entry::from_array( $this->valid_data );
		$result = $entry->to_array();

		$this->assertSame( 'kadence', $result['product_slug'] );
		$this->assertSame( 'professional', $result['tier'] );
		$this->assertNull( $result['pending_tier'] );
		$this->assertSame( 'active', $result['status'] );
		$this->assertSame( '2026-12-31 23:59:59', $result['expires'] );

		$this->assertArrayHasKey( 'activations', $result );
		$this->assertSame( 5, $result['activations']['site_limit'] );
		$this->assertSame( 3, $result['activations']['active_count'] );
		$this->assertFalse( $result['activations']['over_limit'] );

		$this->assertTrue( $result['installed_here'] );
		$this->assertSame( 'valid', $result['validation_status'] );
		$this->assertTrue( $result['is_valid'] );
	}

	/**
	 * Tests that from_array(to_array()) round-trips to identical output.
	 *
	 * @return void
	 */
	public function test_round_trip(): void {
		$entry  = Product_Entry::from_array( $this->valid_data );
		$second = Product_Entry::from_array( $entry->to_array() );

		$this->assertSame( $entry->to_array(), $second->to_array() );
	}

	/**
	 * Tests that is_valid() returns true when validation_status is 'valid'.
	 *
	 * @return void
	 */
	public function test_is_valid_returns_true_for_valid_status(): void {
		$entry = Product_Entry::from_array( $this->valid_data );

		$this->assertTrue( $entry->is_valid() );
	}

	/**
	 * Tests that is_valid() returns false for non-valid statuses.
	 *
	 * @return void
	 */
	public function test_is_valid_returns_false_for_non_valid_status(): void {
		$data = array_merge(
			$this->valid_data,
			[
				'validation_status' => 'expired',
				'is_valid'          => false,
			]
		);

		$entry = Product_Entry::from_array( $data );

		$this->assertFalse( $entry->is_valid() );
	}

	/**
	 * Tests that is_valid() returns false when validation_status is null.
	 *
	 * @return void
	 */
	public function test_is_valid_returns_false_when_status_is_null(): void {
		$data = $this->valid_data;
		unset( $data['validation_status'], $data['is_valid'] );

		$entry = Product_Entry::from_array( $data );

		$this->assertFalse( $entry->is_valid() );
	}

	/**
	 * Tests that is_over_limit() returns true when active_count exceeds site_limit.
	 *
	 * @return void
	 */
	public function test_is_over_limit_when_exceeded(): void {
		$data                                = $this->valid_data;
		$data['activations']['active_count'] = 6;

		$entry = Product_Entry::from_array( $data );

		$this->assertTrue( $entry->is_over_limit() );
	}

	/**
	 * Tests that is_over_limit() returns false when within limits.
	 *
	 * @return void
	 */
	public function test_is_over_limit_when_within_limit(): void {
		$entry = Product_Entry::from_array( $this->valid_data );

		$this->assertFalse( $entry->is_over_limit() );
	}

	/**
	 * Tests that is_over_limit() returns false when site_limit is 0 (unlimited).
	 *
	 * @return void
	 */
	public function test_is_over_limit_returns_false_for_unlimited(): void {
		$data                                = $this->valid_data;
		$data['activations']['site_limit']   = 0;
		$data['activations']['active_count'] = 100;

		$entry = Product_Entry::from_array( $data );

		$this->assertFalse( $entry->is_over_limit() );
	}

	/**
	 * Tests that optional fields are omitted from to_array() when null.
	 *
	 * @return void
	 */
	public function test_optional_fields_omitted_when_null(): void {
		$data = $this->valid_data;
		unset( $data['installed_here'], $data['validation_status'], $data['is_valid'] );

		$entry  = Product_Entry::from_array( $data );
		$result = $entry->to_array();

		$this->assertArrayNotHasKey( 'installed_here', $result );
		$this->assertArrayNotHasKey( 'validation_status', $result );
		$this->assertArrayNotHasKey( 'is_valid', $result );
	}

	/**
	 * Tests that from_array() handles missing optional fields gracefully.
	 *
	 * @return void
	 */
	public function test_from_array_handles_missing_optional_fields(): void {
		$data = [
			'product_slug' => 'givewp',
			'tier'         => 'starter',
			'status'       => 'active',
			'expires'      => '2026-12-31 23:59:59',
			'activations'  => [
				'site_limit'   => 1,
				'active_count' => 1,
				'over_limit'   => false,
			],
		];

		$entry = Product_Entry::from_array( $data );

		$this->assertSame( 'givewp', $entry->get_product_slug() );
		$this->assertNull( $entry->get_pending_tier() );
		$this->assertNull( $entry->get_installed_here() );
		$this->assertNull( $entry->get_validation_status() );
	}

	/**
	 * Tests that from_array() handles a completely missing activations key.
	 *
	 * @return void
	 */
	public function test_from_array_handles_missing_activations(): void {
		$data = [
			'product_slug' => 'kadence',
			'tier'         => 'pro',
			'status'       => 'active',
			'expires'      => '2026-12-31 23:59:59',
		];

		$entry = Product_Entry::from_array( $data );

		$this->assertSame( 0, $entry->get_site_limit() );
		$this->assertSame( 0, $entry->get_active_count() );
	}

	/**
	 * Tests that get_pending_tier() returns a string when set.
	 *
	 * @return void
	 */
	public function test_get_pending_tier_returns_string_when_set(): void {
		$data                 = $this->valid_data;
		$data['pending_tier'] = 'starter';

		$entry = Product_Entry::from_array( $data );

		$this->assertSame( 'starter', $entry->get_pending_tier() );
	}
}
