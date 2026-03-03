<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Licensing;

use StellarWP\Uplink\Licensing\Error_Code;
use StellarWP\Uplink\Licensing\Enums\Validation_Status;
use StellarWP\Uplink\Licensing\Fixture_Client;
use StellarWP\Uplink\Licensing\Results\Product_Entry;
use StellarWP\Uplink\Licensing\Results\Validation_Result;
use StellarWP\Uplink\Tests\UplinkTestCase;
use WP_Error;

final class Fixture_ClientTest extends UplinkTestCase {

	private Fixture_Client $client;

	protected function setUp(): void {
		parent::setUp();

		$this->client = new Fixture_Client( codecept_data_dir( 'licensing' ) );
	}

	public function test_get_products_unified_pro_returns_four_entries(): void {
		$products = $this->client->get_products( 'LW-UNIFIED-PRO-2025', 'example.com' );

		$this->assertIsArray( $products );
		$this->assertCount( 4, $products );

		foreach ( $products as $entry ) {
			$this->assertInstanceOf( Product_Entry::class, $entry );
		}
	}

	public function test_get_products_unified_pro_returns_correct_slugs(): void {
		$products = $this->client->get_products( 'LW-UNIFIED-PRO-2025', 'example.com' );

		$slugs = array_map(
			static function ( Product_Entry $entry ): string {
				return $entry->get_product_slug();
			},
			$products
		);

		$this->assertSame( [ 'give', 'the-events-calendar', 'learndash', 'kadence' ], $slugs );
	}

	public function test_get_products_unified_pro_all_tiers_are_pro(): void {
		$products = $this->client->get_products( 'LW-UNIFIED-PRO-2025', 'example.com' );

		foreach ( $products as $entry ) {
			$this->assertSame( 'pro', $entry->get_tier(), sprintf( '%s should be pro tier', $entry->get_product_slug() ) );
		}
	}

	public function test_get_products_unified_basic_all_tiers_are_starter(): void {
		$products = $this->client->get_products( 'LW-UNIFIED-BASIC-2025', 'example.com' );

		$this->assertIsArray( $products );
		$this->assertCount( 4, $products );

		foreach ( $products as $entry ) {
			$this->assertSame( 'starter', $entry->get_tier(), sprintf( '%s should be starter tier', $entry->get_product_slug() ) );
		}
	}

	public function test_get_products_unified_agency_unlimited_seats(): void {
		$products = $this->client->get_products( 'LW-UNIFIED-AGENCY-2025', 'example.com' );

		$this->assertIsArray( $products );

		foreach ( $products as $entry ) {
			$this->assertSame( 'agency', $entry->get_tier() );
			$this->assertSame( 0, $entry->get_site_limit(), sprintf( '%s should have unlimited seats', $entry->get_product_slug() ) );
			$this->assertFalse( $entry->is_over_limit() );
		}
	}

	public function test_get_products_expired_key(): void {
		$products = $this->client->get_products( 'LW-UNIFIED-PRO-EXPIRED', 'example.com' );

		$this->assertIsArray( $products );
		$this->assertCount( 4, $products );

		foreach ( $products as $entry ) {
			$this->assertSame( Validation_Status::EXPIRED, $entry->get_validation_status() );
			$this->assertFalse( $entry->is_valid() );
			$this->assertSame( 'expired', $entry->get_status() );
		}
	}

	public function test_get_products_single_product_key(): void {
		$products = $this->client->get_products( 'LW-UNIFIED-KAD-PRO-2025', 'example.com' );

		$this->assertIsArray( $products );
		$this->assertCount( 1, $products );
		$this->assertSame( 'kadence', $products[0]->get_product_slug() );
		$this->assertSame( 'pro', $products[0]->get_tier() );
	}

	public function test_get_products_two_product_key(): void {
		$products = $this->client->get_products( 'LW-UNIFIED-KAD-GIVE-2025', 'example.com' );

		$this->assertIsArray( $products );
		$this->assertCount( 2, $products );

		$slugs = array_map(
			static function ( Product_Entry $entry ): string {
				return $entry->get_product_slug();
			},
			$products
		);

		$this->assertContains( 'kadence', $slugs );
		$this->assertContains( 'give', $slugs );
	}

	public function test_key_to_filename_conversion(): void {
		$products = $this->client->get_products( 'LW-UNIFIED-PRO-2025', 'example.com' );

		$this->assertIsArray( $products );
		$this->assertCount( 4, $products );
	}

	public function test_get_products_unknown_key_returns_error(): void {
		$result = $this->client->get_products( 'NON-EXISTENT-KEY', 'example.com' );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( Error_Code::INVALID_KEY, $result->get_error_code() );
	}

	public function test_validate_returns_validation_result(): void {
		$result = $this->client->validate( 'LW-UNIFIED-PRO-2025', 'example.com', 'kadence' );

		$this->assertInstanceOf( Validation_Result::class, $result );
	}

	public function test_validate_valid_product(): void {
		$result = $this->client->validate( 'LW-UNIFIED-PRO-2025', 'example.com', 'kadence' );

		$this->assertSame( Validation_Status::VALID, $result->get_status() );
		$this->assertTrue( $result->is_valid() );
		$this->assertNotNull( $result->get_activation() );
		$this->assertSame( 'example.com', $result->get_activation()['domain'] );
	}

	public function test_validate_not_activated_product(): void {
		$result = $this->client->validate( 'LW-UNIFIED-PRO-2025', 'example.com', 'learndash' );

		$this->assertSame( Validation_Status::NOT_ACTIVATED, $result->get_status() );
		$this->assertFalse( $result->is_valid() );
		$this->assertNull( $result->get_activation() );
	}

	public function test_validate_expired_product(): void {
		$result = $this->client->validate( 'LW-UNIFIED-PRO-EXPIRED', 'example.com', 'give' );

		$this->assertSame( Validation_Status::EXPIRED, $result->get_status() );
		$this->assertFalse( $result->is_valid() );
	}

	public function test_validate_includes_subscription_data(): void {
		$result = $this->client->validate( 'LW-UNIFIED-PRO-2025', 'example.com', 'kadence' );

		$subscription = $result->get_subscription();

		$this->assertNotNull( $subscription );
		$this->assertSame( 'kadence', $subscription['product_slug'] );
		$this->assertSame( 'pro', $subscription['tier'] );
		$this->assertSame( 3, $subscription['site_limit'] );
		$this->assertSame( 'active', $subscription['status'] );
	}

	public function test_validate_includes_license_data(): void {
		$result = $this->client->validate( 'LW-UNIFIED-PRO-2025', 'example.com', 'kadence' );

		$license = $result->get_license();

		$this->assertNotNull( $license );
		$this->assertSame( 'LW-UNIFIED-PRO-2025', $license['key'] );
		$this->assertSame( 'active', $license['status'] );
	}

	public function test_validate_unknown_product_returns_error(): void {
		$result = $this->client->validate( 'LW-UNIFIED-PRO-2025', 'example.com', 'nonexistent-plugin' );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( Error_Code::PRODUCT_NOT_FOUND, $result->get_error_code() );
	}

	public function test_validate_propagates_get_products_error(): void {
		$result = $this->client->validate( 'NON-EXISTENT-KEY', 'example.com', 'kadence' );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( Error_Code::INVALID_KEY, $result->get_error_code() );
	}
}
