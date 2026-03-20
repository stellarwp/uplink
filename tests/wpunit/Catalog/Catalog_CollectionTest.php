<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Catalog;

use StellarWP\Uplink\Catalog\Catalog_Collection;
use StellarWP\Uplink\Catalog\Results\Product_Catalog;
use StellarWP\Uplink\Tests\UplinkTestCase;

final class Catalog_CollectionTest extends UplinkTestCase {

	public function test_it_adds_and_gets_catalogs(): void {
		$collection = new Catalog_Collection();
		$kadence    = Product_Catalog::from_array(
			[
				'product_slug' => 'kadence',
				'tiers'        => [],
				'features'     => [],
			]
		);
		$tec        = Product_Catalog::from_array(
			[
				'product_slug' => 'tec',
				'tiers'        => [],
				'features'     => [],
			]
		);

		$collection->add( $kadence );
		$collection->add( $tec );

		$this->assertSame( 2, $collection->count() );
		$this->assertSame( $kadence, $collection->get( 'kadence' ) );
		$this->assertSame( $tec, $collection->get( 'tec' ) );
	}

	public function test_it_does_not_duplicate_catalogs_with_same_slug(): void {
		$collection = new Catalog_Collection();
		$first      = Product_Catalog::from_array(
			[
				'product_slug' => 'kadence',
				'tiers'        => [],
				'features'     => [],
			]
		);
		$second     = Product_Catalog::from_array(
			[
				'product_slug' => 'kadence',
				'tiers'        => [],
				'features'     => [],
			]
		);

		$collection->add( $first );
		$collection->add( $second );

		$this->assertSame( 1, $collection->count() );
		$this->assertSame( $first, $collection->get( 'kadence' ) );
	}

	public function test_it_returns_null_for_unknown_slug(): void {
		$collection = new Catalog_Collection();

		$this->assertNull( $collection->get( 'nonexistent' ) );
	}

	public function test_it_iterates_over_catalogs(): void {
		$collection = new Catalog_Collection();
		$collection->add(
			Product_Catalog::from_array(
				[
					'product_slug' => 'kadence',
					'tiers'        => [],
					'features'     => [],
				]
			)
		);
		$collection->add(
			Product_Catalog::from_array(
				[
					'product_slug' => 'tec',
					'tiers'        => [],
					'features'     => [],
				]
			)
		);

		$slugs = [];

		foreach ( $collection as $slug => $catalog ) {
			$slugs[] = $slug;
		}

		$this->assertSame( [ 'kadence', 'tec' ], $slugs );
	}

	public function test_it_counts_catalogs(): void {
		$collection = new Catalog_Collection();

		$this->assertSame( 0, $collection->count() );

		$collection->add(
			Product_Catalog::from_array(
				[
					'product_slug' => 'kadence',
					'tiers'        => [],
					'features'     => [],
				]
			)
		);

		$this->assertSame( 1, $collection->count() );
	}

	public function test_from_array_creates_collection_from_objects(): void {
		$kadence = Product_Catalog::from_array(
			[
				'product_slug' => 'kadence',
				'tiers'        => [],
				'features'     => [],
			] 
		);
		$tec     = Product_Catalog::from_array(
			[
				'product_slug' => 'tec',
				'tiers'        => [],
				'features'     => [],
			] 
		);

		$collection = Catalog_Collection::from_array( [ $kadence, $tec ] );

		$this->assertSame( 2, $collection->count() );
		$this->assertSame( $kadence, $collection->get( 'kadence' ) );
		$this->assertSame( $tec, $collection->get( 'tec' ) );
	}

	public function test_from_array_creates_collection_from_raw_data(): void {
		$data = [
			[
				'product_slug' => 'kadence',
				'tiers'        => [
					[
						'slug'         => 'basic',
						'name'         => 'Basic',
						'rank'         => 1,
						'purchase_url' => '',
					],
				],
				'features'     => [],
			],
			[
				'product_slug' => 'tec',
				'tiers'        => [],
				'features'     => [],
			],
		];

		$collection = Catalog_Collection::from_array( $data );

		$this->assertSame( 2, $collection->count() );
		$this->assertInstanceOf( Product_Catalog::class, $collection->get( 'kadence' ) );
		$this->assertInstanceOf( Product_Catalog::class, $collection->get( 'tec' ) );
		$this->assertSame( 'kadence', $collection->get( 'kadence' )->get_product_slug() );
	}

	public function test_from_array_skips_non_array_items(): void {
		$data = [
			[
				'product_slug' => 'kadence',
				'tiers'        => [],
				'features'     => [],
			],
			'not-an-array',
		];

		$collection = Catalog_Collection::from_array( $data );

		$this->assertSame( 1, $collection->count() );
	}

	public function test_from_array_returns_empty_collection_for_empty_input(): void {
		$collection = Catalog_Collection::from_array( [] );

		$this->assertSame( 0, $collection->count() );
	}
}
