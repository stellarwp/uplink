<?php declare( strict_types = 1 );

namespace StellarWP\Uplink\Tests\Auth\Token;

use StellarWP\Uplink\Auth\Token\Contracts\Token_Manager;
use StellarWP\Uplink\Auth\Token\Token_Manager as ConcreteTokenManager;
use StellarWP\Uplink\Config;
use StellarWP\Uplink\Register;
use StellarWP\Uplink\Resources\Collection;
use StellarWP\Uplink\Resources\Resource;
use StellarWP\Uplink\Tests\TestUtils;
use StellarWP\Uplink\Tests\UplinkTestCase;
use StellarWP\Uplink\Uplink;

final class MultipleTokensTest extends UplinkTestCase {

	use TestUtils;

	/**
	 * @var Token_Manager
	 */
	private $token_manager;

	/**
	 * @var Collection
	 */
	private $collection;

	protected function setUp(): void {
		parent::setUp();

		Config::set_token_auth_prefix( 'custom_' );

		// Run init again to reload the Token/Provider.
		Uplink::init();

		$this->collection    = $this->container->get( Collection::class );
		$this->token_manager = $this->container->get( Token_Manager::class );
	}

	/**
	 * Sets up the container and returns the slug of a resource.
	 *
	 * @param array $resource
	 *
	 * @return Resource
	 */
	public function setup_container_get_slug( array $resource ): Resource {
		Register::{$resource['type']}(
			$resource['slug'],
			$resource['name'],
			$resource['version'],
			$resource['path'],
			$resource['class']
		);

		return $this->collection->get( $resource['slug'] );
	}

	/**
	 * Get token-slug pairs from test resources.
	 *
	 * @return array<string, string>
	 */
	private function get_token_slug_pairs(): array {
		$resources          = $this->get_test_resources();
		$tokens             = [];
		$dynamicTokenPrefix = 'dynamic-token-value_';

		foreach ( $resources as $resource ) {
			$slug            = $this->setup_container_get_slug( $resource )->get_slug();
			$tokens[ $slug ] = $dynamicTokenPrefix . $slug;
		}

		return $tokens;
	}

	/**
	 * @test
	 */
	public function it_should_register_multiple_tokens(): void {
		$tokens = $this->get_token_slug_pairs();

		foreach ( $tokens as $slug => $token ) {
			$plugin = $this->collection->get( $slug );

			$this->assertTrue( $this->token_manager->store( $token, $plugin ) );
		}

		// Retrieve all tokens and perform assertion
		$all_tokens = $this->token_manager->get_all();
		$this->assertSame( $tokens, $all_tokens );

		// Perform individual assertions for each slug
		foreach ( $tokens as $slug => $expectedToken ) {
			$plugin = $this->collection->get( $slug );

			$retrieved_token = $this->token_manager->get( $plugin );
			$this->assertSame( $expectedToken, $retrieved_token );
		}
	}

	/**
	 * @test
	 */
	public function it_deletes_multiple_tokens(): void {
		$tokens = $this->get_token_slug_pairs();

		foreach ( $tokens as $slug => $token ) {
			$plugin = $this->collection->get( $slug );

			$this->assertTrue( $this->token_manager->store( $token, $plugin ) );
		}

		// Delete all tokens and assert they are removed
		foreach ( array_keys( $tokens ) as $slug ) {
			$this->token_manager->delete( $slug );
			$plugin = $this->collection->get( $slug );

			$this->assertNull( $this->token_manager->get( $plugin ) );
		}

		// Assert get_all is empty after deletion
		$all_tokens = $this->token_manager->get_all();
		$this->assertEmpty( $all_tokens );
	}

	/**
	 * @test
	 */
	public function it_does_not_store_empty_tokens(): void {
		$resources = $this->get_test_resources();

		foreach ( $resources as $resource ) {
			$plugin = $this->setup_container_get_slug( $resource );

			$this->assertFalse( $this->token_manager->store( '', $plugin ) );
			$this->assertNull( $this->token_manager->get( $plugin ) );
		}
	}

	/**
	 * @test
	 */
	public function it_can_delete_the_legacy_token(): void {
		$slug   = 'single-plugin-1';
		$plugin = Register::{'plugin'}(
			$slug,
			'Single Plugin',
			'1.0.0',
			dirname( __DIR__, 2 ) . '/plugin.php',
			Uplink::class
		);

		$this->assertNull( $this->token_manager->get( $plugin ) );

		$token = '0904a5c8-0458-4982-8fc9-ce32d6dd8c03';

		// Manually store a legacy string token.
		$this->assertTrue( update_network_option( get_current_network_id(), $this->token_manager->option_name(), $token ) );
		$this->assertSame( $token, $this->token_manager->get( $plugin ) );
		$this->assertTrue( $this->token_manager->delete( $slug ) );
		$this->assertNull( $this->token_manager->get( $plugin ) );
	}

	/**
	 * @test
	 */
	public function it_should_have_backwards_compatibility_with_fetching_legacy_tokens(): void {
		$plugin = Register::{'plugin'}(
			'single-plugin-1',
			'Single Plugin',
			'1.0.0',
			dirname( __DIR__, 2 ) . '/plugin.php',
			Uplink::class
		);

		$this->assertNull( $this->token_manager->get( $plugin ) );

		$token = '53ca40ab-c6c7-4482-a1eb-14c56da31015';

		// Step 1: Manually store a legacy string token.
		$this->assertTrue( update_network_option( get_current_network_id(), $this->token_manager->option_name(), $token ) );
		$this->assertSame( $token, $this->token_manager->get( $plugin ) );

		// Retrieve all tokens and include the single token.
		$all_tokens      = $this->token_manager->get_all();
		$expected_tokens = [
			ConcreteTokenManager::LEGACY_INDEX => $token, // This will be the legacy token format.
		];
		$this->assertSame( $expected_tokens, $all_tokens );

		// Step 2: Update the plugin's token to the new format.
		$new_token = '3349c16e-fc4a-4f07-9156-7c8b305ce938';

		$this->assertTrue( $this->token_manager->store( $new_token, $plugin ) );

		// New token will be fetched from now on for this slug.
		$this->assertSame( $new_token, $this->token_manager->get( $plugin ) );

		// Appended the new token.
		$expected_tokens[ $plugin->get_slug() ] = $new_token;

		$this->assertSame( $expected_tokens, $this->token_manager->get_all() );

		// Step 3: Store multiple tokens and verify.
		$tokens = $this->get_token_slug_pairs();

		foreach ( $tokens as $slug => $token ) {
			$plugin = $this->collection->get( $slug );
			$this->assertTrue( $this->token_manager->store( $token, $plugin ) );
			$expected_tokens[ $slug ] = $token; // Update expected tokens
		}

		// Retrieve all tokens and perform assertion.
		$all_tokens = $this->token_manager->get_all();
		$this->assertSame( $expected_tokens, $all_tokens );
	}
}
