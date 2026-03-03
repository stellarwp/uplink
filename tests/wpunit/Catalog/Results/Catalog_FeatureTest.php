<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Catalog\Results;

use StellarWP\Uplink\Catalog\Results\Catalog_Feature;
use StellarWP\Uplink\Tests\UplinkTestCase;

final class Catalog_FeatureTest extends UplinkTestCase {

	private array $plugin_data = [
		'feature_slug' => 'kadence-security',
		'type'         => 'plugin',
		'minimum_tier' => 'kadence-pro',
		'plugin_file'  => 'kadence-security-pro/kadence-security-pro.php',
		'is_dot_org'   => false,
		'download_url' => 'https://licensing.stellarwp.com/api/plugins/kadence-security',
		'name'         => 'Kadence Security Pro',
		'description'  => 'WordPress security hardening and monitoring.',
		'category'     => 'security',
	];

	public function test_from_array_hydrates_all_fields(): void {
		$feature = Catalog_Feature::from_array( $this->plugin_data );

		$this->assertSame( 'kadence-security', $feature->get_feature_slug() );
		$this->assertSame( 'plugin', $feature->get_type() );
		$this->assertSame( 'kadence-pro', $feature->get_minimum_tier() );
		$this->assertSame( 'kadence-security-pro/kadence-security-pro.php', $feature->get_plugin_file() );
		$this->assertFalse( $feature->is_dot_org() );
		$this->assertSame( 'https://licensing.stellarwp.com/api/plugins/kadence-security', $feature->get_download_url() );
		$this->assertSame( 'Kadence Security Pro', $feature->get_name() );
		$this->assertSame( 'WordPress security hardening and monitoring.', $feature->get_description() );
		$this->assertSame( 'security', $feature->get_category() );
	}

	public function test_to_array_produces_expected_shape(): void {
		$feature = Catalog_Feature::from_array( $this->plugin_data );
		$result  = $feature->to_array();

		$this->assertSame( 'kadence-security', $result['feature_slug'] );
		$this->assertSame( 'plugin', $result['type'] );
		$this->assertSame( 'kadence-pro', $result['minimum_tier'] );
		$this->assertSame( 'kadence-security-pro/kadence-security-pro.php', $result['plugin_file'] );
		$this->assertFalse( $result['is_dot_org'] );
		$this->assertSame( 'https://licensing.stellarwp.com/api/plugins/kadence-security', $result['download_url'] );
	}

	public function test_round_trip(): void {
		$feature = Catalog_Feature::from_array( $this->plugin_data );
		$second  = Catalog_Feature::from_array( $feature->to_array() );

		$this->assertSame( $feature->to_array(), $second->to_array() );
	}

	public function test_nullable_fields_default_when_missing(): void {
		$data = [
			'feature_slug' => 'patchstack',
			'type'         => 'flag',
			'minimum_tier' => 'kadence-pro',
			'is_dot_org'   => false,
			'name'         => 'PatchStack Firewall',
			'description'  => 'Virtual patching.',
			'category'     => 'security',
		];

		$feature = Catalog_Feature::from_array( $data );

		$this->assertNull( $feature->get_plugin_file() );
		$this->assertNull( $feature->get_download_url() );
	}

	public function test_dot_org_theme(): void {
		$data = [
			'feature_slug' => 'kadence-theme',
			'type'         => 'theme',
			'minimum_tier' => 'kadence-basic',
			'is_dot_org'   => true,
			'download_url' => null,
			'name'         => 'Kadence Theme',
			'description'  => 'Starter theme for Kadence.',
			'category'     => 'core',
		];

		$feature = Catalog_Feature::from_array( $data );

		$this->assertTrue( $feature->is_dot_org() );
		$this->assertNull( $feature->get_download_url() );
		$this->assertNull( $feature->get_plugin_file() );
	}
}
