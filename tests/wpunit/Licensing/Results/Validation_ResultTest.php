<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Licensing\Results;

use StellarWP\Uplink\Licensing\Enums\Validation_Status;
use StellarWP\Uplink\Licensing\Results\Validation_Result;
use StellarWP\Uplink\Tests\UplinkTestCase;

final class Validation_ResultTest extends UplinkTestCase {

	private array $valid_data = [
		'status'       => 'valid',
		'is_valid'     => true,
		'license'      => [
			'key'    => 'LWSW-XXXX-XXXX-XXXX-XXXX-XXXX',
			'status' => 'active',
		],
		'subscription' => [
			'product_slug'    => 'kadence',
			'tier'            => 'professional',
			'site_limit'      => 5,
			'expiration_date' => '2026-12-31 23:59:59',
			'status'          => 'active',
		],
		'activation'   => [
			'domain'       => 'example.com',
			'activated_at' => '2025-01-15 10:30:00',
		],
	];

	public function test_from_array_hydrates_all_fields(): void {
		$result = Validation_Result::from_array( $this->valid_data );

		$this->assertSame( 'valid', $result->get_status() );
		$this->assertSame( 'LWSW-XXXX-XXXX-XXXX-XXXX-XXXX', $result->get_license()['key'] );
		$this->assertSame( 'active', $result->get_license()['status'] );
		$this->assertSame( 'kadence', $result->get_subscription()['product_slug'] );
		$this->assertSame( 'professional', $result->get_subscription()['tier'] );
		$this->assertSame( 5, $result->get_subscription()['site_limit'] );
		$this->assertSame( 'example.com', $result->get_activation()['domain'] );
	}

	public function test_to_array_produces_api_shape(): void {
		$result = Validation_Result::from_array( $this->valid_data );
		$array  = $result->to_array();

		$this->assertSame( 'valid', $array['status'] );
		$this->assertTrue( $array['is_valid'] );
		$this->assertIsArray( $array['license'] );
		$this->assertIsArray( $array['subscription'] );
		$this->assertIsArray( $array['activation'] );
	}

	public function test_round_trip(): void {
		$result = Validation_Result::from_array( $this->valid_data );
		$second = Validation_Result::from_array( $result->to_array() );

		$this->assertSame( $result->to_array(), $second->to_array() );
	}

	public function test_is_valid_returns_true_for_valid_status(): void {
		$result = Validation_Result::from_array( $this->valid_data );

		$this->assertTrue( $result->is_valid() );
	}

	public function test_is_valid_returns_false_for_non_valid_statuses(): void {
		$statuses = [
			Validation_Status::EXPIRED,
			Validation_Status::SUSPENDED,
			Validation_Status::CANCELLED,
			Validation_Status::LICENSE_SUSPENDED,
			Validation_Status::LICENSE_BANNED,
			Validation_Status::NO_SUBSCRIPTION,
			Validation_Status::NOT_ACTIVATED,
			Validation_Status::OUT_OF_ACTIVATIONS,
			Validation_Status::INVALID_KEY,
		];

		foreach ( $statuses as $status ) {
			$data   = array_merge( $this->valid_data, [ 'status' => $status ] );
			$result = Validation_Result::from_array( $data );

			$this->assertFalse(
				$result->is_valid(),
				sprintf( 'Expected status "%s" to not be valid.', $status )
			);
		}
	}

	public function test_handles_null_sub_arrays(): void {
		$data = [
			'status'       => 'invalid_key',
			'is_valid'     => false,
			'license'      => null,
			'subscription' => null,
			'activation'   => null,
		];

		$result = Validation_Result::from_array( $data );

		$this->assertSame( 'invalid_key', $result->get_status() );
		$this->assertNull( $result->get_license() );
		$this->assertNull( $result->get_subscription() );
		$this->assertNull( $result->get_activation() );
		$this->assertFalse( $result->is_valid() );
	}

	public function test_missing_sub_arrays_default_to_null(): void {
		$data = [ 'status' => 'not_activated' ];

		$result = Validation_Result::from_array( $data );

		$this->assertNull( $result->get_license() );
		$this->assertNull( $result->get_subscription() );
		$this->assertNull( $result->get_activation() );
	}

	public function test_non_array_sub_values_treated_as_null(): void {
		$data = [
			'status'       => 'valid',
			'license'      => 'not-an-array',
			'subscription' => 123,
			'activation'   => true,
		];

		$result = Validation_Result::from_array( $data );

		$this->assertNull( $result->get_license() );
		$this->assertNull( $result->get_subscription() );
		$this->assertNull( $result->get_activation() );
	}
}
