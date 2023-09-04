<?php

namespace StellarWP\Uplink\Tests\Utils;

class SanitizeTest extends \StellarWP\Uplink\Tests\UplinkTestCase {
	public function test_freemius_based_keys() {
		$this->assertSame(
			StellarWP\Uplink\Utils\Sanitize::key( '!@#$%^&*()-_[]{}<>~+=.;:?' ),
			'!@#$%^&*()-_[]{}<>~+=.;:?',
			'A freemius based key characters are missed after sanitization.'
		);

		$this->assertSame(
			StellarWP\Uplink\Utils\Sanitize::key( 'sk_sdfj4<sdlgfne>fsgdfg' ),
			'sk_sdfj4<sdlgfne>fsgdfg',
			'The freemius based key was missing characters after sanitization.'
		);
	}
}
