<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Catalog\Results;

use StellarWP\Uplink\Catalog\Results\Catalog_Tier;
use StellarWP\Uplink\Tests\UplinkTestCase;

final class Catalog_TierTest extends UplinkTestCase {

	private array $valid_data = [
		'slug'         => 'kadence-pro',
		'name'         => 'Pro',
		'rank'         => 2,
		'purchase_url' => 'https://software.liquidweb.com/kadence?tier=pro',
	];

	public function test_from_array_hydrates_all_fields(): void {
		$tier = Catalog_Tier::from_array( $this->valid_data );

		$this->assertSame( 'kadence-pro', $tier->get_slug() );
		$this->assertSame( 'Pro', $tier->get_name() );
		$this->assertSame( 2, $tier->get_rank() );
		$this->assertSame( 'https://software.liquidweb.com/kadence?tier=pro', $tier->get_purchase_url() );
	}

	public function test_to_array_produces_expected_shape(): void {
		$tier   = Catalog_Tier::from_array( $this->valid_data );
		$result = $tier->to_array();

		$this->assertSame( 'kadence-pro', $result['slug'] );
		$this->assertSame( 'Pro', $result['name'] );
		$this->assertSame( 2, $result['rank'] );
		$this->assertSame( 'https://software.liquidweb.com/kadence?tier=pro', $result['purchase_url'] );
	}

	public function test_round_trip(): void {
		$tier   = Catalog_Tier::from_array( $this->valid_data );
		$second = Catalog_Tier::from_array( $tier->to_array() );

		$this->assertSame( $tier->to_array(), $second->to_array() );
	}

	public function test_missing_fields_default(): void {
		$tier = Catalog_Tier::from_array( [] );

		$this->assertSame( '', $tier->get_slug() );
		$this->assertSame( '', $tier->get_name() );
		$this->assertSame( 0, $tier->get_rank() );
		$this->assertSame( '', $tier->get_purchase_url() );
	}
}
