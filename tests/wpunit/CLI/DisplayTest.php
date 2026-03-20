<?php declare( strict_types=1 );

namespace wpunit\CLI;

use StellarWP\Uplink\CLI\Display;
use StellarWP\Uplink\Tests\UplinkTestCase;

/**
 * @since 3.0.0
 */
class DisplayTest extends UplinkTestCase {

	/**
	 * @test
	 *
	 * @since 3.0.0
	 */
	public function it_should_convert_true_to_display_string(): void {
		$this->assertSame( 'true', Display::bool( true ) );
	}

	/**
	 * @test
	 *
	 * @since 3.0.0
	 */
	public function it_should_convert_false_to_display_string(): void {
		$this->assertSame( 'false', Display::bool( false ) );
	}

	/**
	 * @test
	 *
	 * @since 3.0.0
	 */
	public function it_should_convert_nullable_true_to_display_string(): void {
		$this->assertSame( 'true', Display::nullable_bool( true ) );
	}

	/**
	 * @test
	 *
	 * @since 3.0.0
	 */
	public function it_should_convert_nullable_false_to_display_string(): void {
		$this->assertSame( 'false', Display::nullable_bool( false ) );
	}

	/**
	 * @test
	 *
	 * @since 3.0.0
	 */
	public function it_should_convert_null_to_empty_string(): void {
		$this->assertSame( '', Display::nullable_bool( null ) );
	}
}
