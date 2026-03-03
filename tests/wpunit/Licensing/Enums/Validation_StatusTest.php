<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Licensing\Enums;

use StellarWP\Uplink\Licensing\Enums\Validation_Status;
use StellarWP\Uplink\Tests\UplinkTestCase;

final class Validation_StatusTest extends UplinkTestCase {

	public function test_constants_match_licensing_service(): void {
		$this->assertSame( 'valid', Validation_Status::VALID );
		$this->assertSame( 'expired', Validation_Status::EXPIRED );
		$this->assertSame( 'suspended', Validation_Status::SUSPENDED );
		$this->assertSame( 'cancelled', Validation_Status::CANCELLED );
		$this->assertSame( 'license_suspended', Validation_Status::LICENSE_SUSPENDED );
		$this->assertSame( 'license_banned', Validation_Status::LICENSE_BANNED );
		$this->assertSame( 'no_subscription', Validation_Status::NO_SUBSCRIPTION );
		$this->assertSame( 'not_activated', Validation_Status::NOT_ACTIVATED );
		$this->assertSame( 'out_of_activations', Validation_Status::OUT_OF_ACTIVATIONS );
		$this->assertSame( 'invalid_key', Validation_Status::INVALID_KEY );
	}

	public function test_all_returns_all_statuses(): void {
		$all = Validation_Status::all();

		$this->assertCount( 10, $all );
		$this->assertContains( 'valid', $all );
		$this->assertContains( 'expired', $all );
		$this->assertContains( 'suspended', $all );
		$this->assertContains( 'cancelled', $all );
		$this->assertContains( 'license_suspended', $all );
		$this->assertContains( 'license_banned', $all );
		$this->assertContains( 'no_subscription', $all );
		$this->assertContains( 'not_activated', $all );
		$this->assertContains( 'out_of_activations', $all );
		$this->assertContains( 'invalid_key', $all );
	}

	public function test_is_valid_returns_true_for_known_values(): void {
		foreach ( Validation_Status::all() as $status ) {
			$this->assertTrue(
				Validation_Status::is_valid( $status ),
				sprintf( 'Expected "%s" to be a valid status.', $status )
			);
		}
	}

	public function test_is_valid_returns_false_for_unknown_values(): void {
		$this->assertFalse( Validation_Status::is_valid( '' ) );
		$this->assertFalse( Validation_Status::is_valid( 'unknown' ) );
		$this->assertFalse( Validation_Status::is_valid( 'VALID' ) );
		$this->assertFalse( Validation_Status::is_valid( 'active' ) );
	}
}
