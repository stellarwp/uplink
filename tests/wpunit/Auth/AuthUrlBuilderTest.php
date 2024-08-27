<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Auth;

use StellarWP\Uplink\API\V3\Auth\Auth_Url;
use StellarWP\Uplink\Auth\Auth_Url_Builder;
use StellarWP\Uplink\Auth\Nonce;
use StellarWP\Uplink\Config;
use StellarWP\Uplink\Tests\Traits\With_Uopz;
use StellarWP\Uplink\Tests\UplinkTestCase;
use StellarWP\Uplink\Uplink;

final class AuthUrlBuilderTest extends UplinkTestCase {

	use With_Uopz;

	/**
	 * @var Auth_Url_Builder
	 */
	private $auth_url_builder;

	protected function setUp(): void {
		parent::setUp();

		Config::set_token_auth_prefix( 'kadence_' );

		// Run init again to reload all providers.
		Uplink::init();

		$this->set_class_fn_return( Auth_Url::class, 'get', 'https://theeventscalendar.com/account-auth' );
		$this->set_class_fn_return( Nonce::class, 'create', 'abcd1234' );

		$this->auth_url_builder = $this->container->get( Auth_Url_Builder::class );
	}

	public function test_it_builds_url(): void {
		$url = $this->auth_url_builder->build( 'the-events-calendar', 'theeventscalendar.com' );

		$callback = base64_encode( 'http://wordpress.test/wp-admin/index.php?uplink_domain=theeventscalendar.com&uplink_slug=the-events-calendar&_uplink_nonce=abcd1234' );
		$expected = 'https://theeventscalendar.com/account-auth?uplink_callback=' . rawurlencode( $callback );

		$this->assertSame( $expected, $url );
	}

	public function test_it_builds_url_with_license(): void {
		$url = $this->auth_url_builder->set_license('some-license-key')->build( 'the-events-calendar', 'theeventscalendar.com' );

		$callback = base64_encode( 'http://wordpress.test/wp-admin/index.php?uplink_domain=theeventscalendar.com&uplink_slug=the-events-calendar&uplink_license=some-license-key&_uplink_nonce=abcd1234' );
		$expected = 'https://theeventscalendar.com/account-auth?uplink_callback=' . rawurlencode( $callback );

		$this->assertSame( $expected, $url );
	}

	public function test_it_builds_url_with_empty_license(): void {
		$url = $this->auth_url_builder->set_license('')->build( 'the-events-calendar', 'theeventscalendar.com' );

		$callback = base64_encode( 'http://wordpress.test/wp-admin/index.php?uplink_domain=theeventscalendar.com&uplink_slug=the-events-calendar&_uplink_nonce=abcd1234' );
		$expected = 'https://theeventscalendar.com/account-auth?uplink_callback=' . rawurlencode( $callback );

		$this->assertSame( $expected, $url );
	}

}
