<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Features;

use StellarWP\Uplink\Features\Error_Code;
use StellarWP\Uplink\Tests\UplinkTestCase;

final class Error_CodeTest extends UplinkTestCase {

	/**
	 * Provides every known error code with its expected HTTP status.
	 *
	 * @return array<string, array{string, int}>
	 */
	public function known_code_provider(): array {
		return [
			'FEATURE_TYPE_MISMATCH'          => [ Error_Code::FEATURE_TYPE_MISMATCH, 400 ],
			'FEATURE_NOT_FOUND'              => [ Error_Code::FEATURE_NOT_FOUND, 404 ],
			'INSTALL_LOCKED'                 => [ Error_Code::INSTALL_LOCKED, 409 ],
			'PLUGIN_OWNERSHIP_MISMATCH'      => [ Error_Code::PLUGIN_OWNERSHIP_MISMATCH, 409 ],
			'THEME_OWNERSHIP_MISMATCH'       => [ Error_Code::THEME_OWNERSHIP_MISMATCH, 409 ],
			'THEME_IS_ACTIVE'                => [ Error_Code::THEME_IS_ACTIVE, 409 ],
			'THEME_DELETE_REQUIRED'          => [ Error_Code::THEME_DELETE_REQUIRED, 409 ],
			'DEACTIVATION_FAILED'            => [ Error_Code::DEACTIVATION_FAILED, 409 ],
			'REQUIREMENTS_NOT_MET'           => [ Error_Code::REQUIREMENTS_NOT_MET, 422 ],
			'INSTALL_FAILED'                 => [ Error_Code::INSTALL_FAILED, 422 ],
			'ACTIVATION_FATAL'               => [ Error_Code::ACTIVATION_FATAL, 422 ],
			'ACTIVATION_FAILED'              => [ Error_Code::ACTIVATION_FAILED, 422 ],
			'PLUGIN_NOT_FOUND_AFTER_INSTALL' => [ Error_Code::PLUGIN_NOT_FOUND_AFTER_INSTALL, 422 ],
			'THEME_NOT_FOUND_AFTER_INSTALL'  => [ Error_Code::THEME_NOT_FOUND_AFTER_INSTALL, 422 ],
			'DOWNLOAD_LINK_MISSING'          => [ Error_Code::DOWNLOAD_LINK_MISSING, 422 ],
			'INVALID_RESPONSE'               => [ Error_Code::INVALID_RESPONSE, 502 ],
			'FEATURE_CHECK_FAILED'           => [ Error_Code::FEATURE_CHECK_FAILED, 502 ],
			'FEATURE_REQUEST_FAILED'         => [ Error_Code::FEATURE_REQUEST_FAILED, 502 ],
			'PLUGINS_API_FAILED'             => [ Error_Code::PLUGINS_API_FAILED, 502 ],
			'THEMES_API_FAILED'              => [ Error_Code::THEMES_API_FAILED, 502 ],
			'UNKNOWN_FEATURE_TYPE'           => [ Error_Code::UNKNOWN_FEATURE_TYPE, 422 ],
		];
	}

	/**
	 * Tests that every known error code maps to the expected HTTP status.
	 *
	 * @dataProvider known_code_provider
	 *
	 * @param string $code     The error code constant value.
	 * @param int    $expected The expected HTTP status code.
	 *
	 * @return void
	 */
	public function test_known_code_returns_expected_status( string $code, int $expected ): void {
		$this->assertSame( $expected, Error_Code::http_status( $code ) );
	}

	/**
	 * Tests that an unknown error code falls back to 422.
	 *
	 * @return void
	 */
	public function test_unknown_code_defaults_to_422(): void {
		$this->assertSame( 422, Error_Code::http_status( 'totally-unknown-code' ) );
	}

	/**
	 * Tests that every constant in the class has a mapping in http_status().
	 *
	 * Guards against adding a new constant without a corresponding HTTP status.
	 *
	 * @return void
	 */
	public function test_all_constants_are_mapped(): void {
		// Arrange.

		$reflection     = new \ReflectionClass( Error_Code::class );
		$constants      = $reflection->getConstants();
		$provider_codes = array_column( $this->known_code_provider(), 0 );

		// Assert.

		foreach ( $constants as $name => $value ) {
			$this->assertContains(
				$value,
				$provider_codes,
				sprintf(
					'Error_Code::%s ("%s") is not covered by known_code_provider() — add an explicit entry for its expected HTTP status.',
					$name,
					$value
				)
			);
		}
	}

	/**
	 * Tests that http_status() never returns a 5xx code.
	 *
	 * @dataProvider known_code_provider
	 *
	 * @param string $code The error code constant value.
	 *
	 * @return void
	 */
	public function test_no_code_maps_to_500( string $code ): void {
		$this->assertNotSame( 500, Error_Code::http_status( $code ) );
	}
}
