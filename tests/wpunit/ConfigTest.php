<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests;

use InvalidArgumentException;
use StellarWP\Uplink\Auth\Token\Contracts\Token_Manager;
use StellarWP\Uplink\Config;

final class ConfigTest extends UplinkTestCase {

	public function test_it_sets_a_token_prefix(): void {
		Config::set_token_auth_prefix( 'my_custom_prefix' );

		$this->assertSame(
			'my_custom_prefix_' . Token_Manager::TOKEN_SUFFIX,
			$this->container->get( Config::TOKEN_OPTION_NAME )
		);
	}

	public function test_it_sanitizes_invalid_prefixes(): void {
		Config::set_token_auth_prefix( 'my~  invalid—prefix`' );

		$this->assertSame(
			'my_invalid_prefix_' . Token_Manager::TOKEN_SUFFIX,
			$this->container->get( Config::TOKEN_OPTION_NAME )
		);
	}

	public function test_it_sets_token_with_exactly_173_characters_and_no_trailing_hyphen(): void {
		$prefix = 'fluffy_unicorn_rainbow_sunshine_happy_smile_peace_joy_love_puppy_harmony_giggles_dreams_celebrate_fantastic_wonderful_whimsical_serendipity_butterfly_magic_sparkle_sweetness';

		Config::set_token_auth_prefix( $prefix );

		$this->assertSame(
			$prefix . '_' . Token_Manager::TOKEN_SUFFIX,
			$this->container->get( Config::TOKEN_OPTION_NAME )
		);
	}

	public function test_it_sets_token_with_exactly_174_characters_and_a_trailing_hyphen(): void {
		$prefix = 'fluffy_unicorn_rainbow_sunshine_happy_smile_peace_joy_love_puppy_harmony_giggles_dreams_celebrate_fantastic_wonderful_whimsical_serendipity_butterfly_magic_sparkle_sweetness_';

		Config::set_token_auth_prefix( $prefix );

		$this->assertSame(
			$prefix . Token_Manager::TOKEN_SUFFIX,
			$this->container->get( Config::TOKEN_OPTION_NAME )
		);
	}

	public function test_it_throws_exception_with_long_prefix(): void {
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'The token auth prefix must be at most 174 characters, including a trailing hyphen.' );
		Config::set_token_auth_prefix( 'fluffy_unicorn_rainbow_sunshine_happy_smile_peace_joy_love_puppy_harmony_giggles_dreams_celebrate_fantastic_wonderful_whimsical_serendipity_butterfly_magic_sparkle_sweetness_trust_' );
	}

	public function test_it_gets_and_sets_auth_token_cache_expiration(): void {
		$this->assertSame( 21600, Config::get_auth_cache_expiration() );

		Config::set_auth_cache_expiration( DAY_IN_SECONDS );

		$this->assertSame( DAY_IN_SECONDS, Config::get_auth_cache_expiration() );
	}

}
