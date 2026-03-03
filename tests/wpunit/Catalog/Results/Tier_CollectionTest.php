<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Catalog\Results;

use StellarWP\Uplink\Catalog\Results\Catalog_Tier;
use StellarWP\Uplink\Catalog\Results\Tier_Collection;
use StellarWP\Uplink\Tests\UplinkTestCase;

final class Tier_CollectionTest extends UplinkTestCase {

	public function test_it_adds_and_gets_tiers(): void {
		$collection = new Tier_Collection();
		$basic      = Catalog_Tier::from_array( [ 'slug' => 'basic', 'name' => 'Basic', 'rank' => 1, 'purchase_url' => '' ] );
		$pro        = Catalog_Tier::from_array( [ 'slug' => 'pro', 'name' => 'Pro', 'rank' => 2, 'purchase_url' => '' ] );

		$collection->add( $basic );
		$collection->add( $pro );

		$this->assertSame( 2, $collection->count() );
		$this->assertSame( $basic, $collection->get( 'basic' ) );
		$this->assertSame( $pro, $collection->get( 'pro' ) );
	}

	public function test_it_does_not_duplicate_tiers_with_same_slug(): void {
		$collection = new Tier_Collection();
		$first      = Catalog_Tier::from_array( [ 'slug' => 'basic', 'name' => 'First', 'rank' => 1, 'purchase_url' => '' ] );
		$second     = Catalog_Tier::from_array( [ 'slug' => 'basic', 'name' => 'Second', 'rank' => 1, 'purchase_url' => '' ] );

		$collection->add( $first );
		$collection->add( $second );

		$this->assertSame( 1, $collection->count() );
		$this->assertSame( 'First', $collection->get( 'basic' )->get_name() );
	}

	public function test_it_returns_null_for_unknown_slug(): void {
		$collection = new Tier_Collection();

		$this->assertNull( $collection->get( 'nonexistent' ) );
	}

	public function test_it_iterates_over_tiers(): void {
		$collection = new Tier_Collection();
		$collection->add( Catalog_Tier::from_array( [ 'slug' => 'basic', 'name' => 'Basic', 'rank' => 1, 'purchase_url' => '' ] ) );
		$collection->add( Catalog_Tier::from_array( [ 'slug' => 'pro', 'name' => 'Pro', 'rank' => 2, 'purchase_url' => '' ] ) );

		$slugs = [];

		foreach ( $collection as $slug => $tier ) {
			$slugs[] = $slug;
		}

		$this->assertSame( [ 'basic', 'pro' ], $slugs );
	}

	public function test_it_counts_tiers(): void {
		$collection = new Tier_Collection();

		$this->assertSame( 0, $collection->count() );

		$collection->add( Catalog_Tier::from_array( [ 'slug' => 'basic', 'name' => 'Basic', 'rank' => 1, 'purchase_url' => '' ] ) );

		$this->assertSame( 1, $collection->count() );
	}
}
