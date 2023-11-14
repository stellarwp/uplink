<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Auth;

use StellarWP\Uplink\Auth\Nonce;
use StellarWP\Uplink\Tests\UplinkTestCase;

final class NonceTest extends UplinkTestCase {

	/**
	 * @var Nonce
	 */
	private $nonce;

	protected function setUp(): void {
		parent::setUp();

		// Force pretty permalinks.
		update_option( 'permalink_structure', '/%postname%/' );

		$this->nonce = $this->container->get( Nonce::class );
	}

	public function test_it_creates_a_nonce(): void {
		$nonce = $this->nonce->create();

		$this->assertNotEmpty( $nonce );
		$this->assertSame( 16, strlen( $nonce ) );
		$this->assertFalse( Nonce::verify( '') );
		$this->assertTrue( Nonce::verify( $nonce ) );
	}

	public function test_it_creates_a_nonce_url(): void {
		$url       = 'http://wordpress.test/wp-admin/import.php';
		$nonce_url = $this->nonce->create_url( $url );

		$this->assertStringStartsWith(
			'http://wordpress.test/wp-admin/import.php?_uplink_nonce=',
			$nonce_url
		);

		$query = wp_parse_url( $nonce_url, PHP_URL_QUERY );

		parse_str( $query, $parts );

		$nonce = $parts[ '_uplink_nonce' ];

		$this->assertNotEmpty( $nonce );
		$this->assertSame( 16, strlen( $nonce ) );
		$this->assertFalse( Nonce::verify( '') );
		$this->assertTrue( Nonce::verify( $nonce ) );
	}

	public function test_it_creates_a_nonce_url_with_extra_query_arguments(): void {
		$url = 'http://wordpress.test/wp-admin/post.php?post=1&action=edit';

		$nonce_url = $this->nonce->create_url( $url );

		$this->assertStringStartsWith(
			'http://wordpress.test/wp-admin/post.php?post=1&action=edit&_uplink_nonce=',
			$nonce_url
		);

		$query = wp_parse_url( $nonce_url, PHP_URL_QUERY );

		parse_str( $query, $parts );

		$nonce = $parts['_uplink_nonce'];

		$this->assertNotEmpty( $nonce );
		$this->assertSame( 16, strlen( $nonce ) );
		$this->assertFalse( Nonce::verify( '' ) );
		$this->assertTrue( Nonce::verify( $nonce ) );
	}

}
