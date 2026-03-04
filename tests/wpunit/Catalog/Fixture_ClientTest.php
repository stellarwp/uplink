<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Catalog;

use StellarWP\Uplink\Catalog\Catalog_Collection;
use StellarWP\Uplink\Catalog\Fixture_Client;
use StellarWP\Uplink\Catalog\Results\Catalog_Tier;
use StellarWP\Uplink\Catalog\Results\Product_Catalog;
use StellarWP\Uplink\Tests\UplinkTestCase;

final class Fixture_ClientTest extends UplinkTestCase {

	private Fixture_Client $client;

	protected function setUp(): void {
		parent::setUp();

		$this->client = new Fixture_Client( codecept_data_dir( 'catalog.json' ) );
	}

	public function test_get_catalog_returns_all_products(): void {
		$result = $this->client->get_catalog();

		$this->assertInstanceOf( Catalog_Collection::class, $result );
		$this->assertCount( 4, $result );

		foreach ( $result as $catalog ) {
			$this->assertInstanceOf( Product_Catalog::class, $catalog );
		}
	}

	public function test_get_catalog_contains_expected_slugs(): void {
		$result = $this->client->get_catalog();

		$this->assertNotNull( $result->get( 'kadence' ) );
		$this->assertNotNull( $result->get( 'the-events-calendar' ) );
		$this->assertNotNull( $result->get( 'give' ) );
		$this->assertNotNull( $result->get( 'learndash' ) );
	}

	public function test_tiers_have_slug_and_rank(): void {
		$result = $this->client->get_catalog();

		foreach ( $result as $catalog ) {
			$tiers = $catalog->get_tiers();

			$this->assertNotEmpty( $tiers, sprintf( '%s should have tiers', $catalog->get_product_slug() ) );

			foreach ( $tiers as $tier ) {
				$this->assertInstanceOf( Catalog_Tier::class, $tier );
				$this->assertNotEmpty( $tier->get_slug() );
				$this->assertGreaterThan( 0, $tier->get_rank() );
			}
		}
	}

	public function test_tier_rank_ordering(): void {
		$result = $this->client->get_catalog();

		foreach ( $result as $catalog ) {
			$prev = null;

			foreach ( $catalog->get_tiers() as $tier ) {
				if ( $prev !== null ) {
					$this->assertGreaterThan(
						$prev->get_rank(),
						$tier->get_rank(),
						sprintf( '%s: tier %s should rank higher than %s', $catalog->get_product_slug(), $tier->get_slug(), $prev->get_slug() )
					);
				}

				$prev = $tier;
			}
		}
	}

	public function test_all_three_feature_types_present(): void {
		$result = $this->client->get_catalog();
		$types  = [];

		foreach ( $result as $catalog ) {
			foreach ( $catalog->get_features() as $feature ) {
				$types[ $feature->get_type() ] = true;
			}
		}

		$this->assertArrayHasKey( 'flag', $types );
		$this->assertArrayHasKey( 'plugin', $types );
		$this->assertArrayHasKey( 'theme', $types );
	}

	public function test_plugin_features_have_plugin_file(): void {
		$result = $this->client->get_catalog();

		foreach ( $result as $catalog ) {
			foreach ( $catalog->get_features() as $feature ) {
				if ( $feature->get_type() === 'plugin' ) {
					$this->assertNotNull( $feature->get_plugin_file(), sprintf( '%s should have plugin_file', $feature->get_feature_slug() ) );
				}
			}
		}
	}

	public function test_flag_features_have_no_plugin_file(): void {
		$result = $this->client->get_catalog();

		foreach ( $result as $catalog ) {
			foreach ( $catalog->get_features() as $feature ) {
				if ( $feature->get_type() === 'flag' ) {
					$this->assertNull( $feature->get_plugin_file(), sprintf( '%s should not have plugin_file', $feature->get_feature_slug() ) );
				}
			}
		}
	}

	public function test_get_catalog_caches_result(): void {
		$first  = $this->client->get_catalog();
		$second = $this->client->get_catalog();

		$this->assertSame( $first, $second );
	}
}
