<?php declare( strict_types = 1 );

namespace StellarWP\Uplink\Tests\Auth\Token;

use StellarWP\Uplink\Auth\Token\Contracts\Token_Manager;
use StellarWP\Uplink\Config;
use StellarWP\Uplink\Register;
use StellarWP\Uplink\Resources\Collection;
use StellarWP\Uplink\Tests\Container;
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
	 * @before
	 */
	protected function multiple_tokens_setup(): void {
		$container = new Container();
		Config::set_container( $container );
		Config::set_hook_prefix( 'test' );

		Uplink::init();

		$this->container = Config::get_container();

		Config::set_token_auth_prefix( 'custom_' );

		// Run init again to reload the Token/Provider.
		Uplink::init();

		$this->token_manager = $this->container->get( Token_Manager::class );
	}

	/**
	 * Sets up the container and returns the slug of a resource.
	 *
	 * @param array $resource
	 *
	 * @return mixed
	 */
	public function setup_container_get_slug( array $resource ) {
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
	 * @return array
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
			$this->assertTrue( $this->token_manager->store( $token, $slug ) );
		}

		// Retrieve all tokens and perform assertion
		$all_tokens = $this->token_manager->get_all();
		$this->assertSame( $tokens, $all_tokens );

		// Perform individual assertions for each slug
		foreach ( $tokens as $slug => $expectedToken ) {
			$retrieved_token = $this->token_manager->get( $slug );
			$this->assertSame( $expectedToken, $retrieved_token );
		}
	}

	/**
	 * @test
	 */
	public function it_deletes_multiple_tokens(): void {
		$tokens = $this->get_token_slug_pairs();

		foreach ( $tokens as $slug => $token ) {
			$this->assertTrue( $this->token_manager->store( $token, $slug ) );
		}

		// Delete all tokens and assert they are removed
		foreach ( array_keys( $tokens ) as $slug ) {
			$this->token_manager->delete( $slug );
			$this->assertNull( $this->token_manager->get( $slug ) );
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
			$slug = $this->setup_container_get_slug( $resource )->get_slug();
			$this->assertFalse( $this->token_manager->store( '', $slug ) );
			$this->assertNull( $this->token_manager->get( $slug ) );
		}
	}

	/**
	 * @test
	 */
	public function it_should_have_backwards_compatibility_with_single_and_multiple_tokens(): void {
		Register::{'plugin'}(
			'single-plugin-1',
			'Single Plugin',
			'1.0.0',
			dirname( __DIR__, 2 ) . '/plugin.php',
			Uplink::class
		);

		// Step 1: Store a single token and verify
		$single_token = 'single-token';
		$single_slug  = 'single-plugin-1';
		$this->assertTrue( $this->token_manager->store( $single_token, $single_slug ) );
		$this->assertSame( $single_token, $this->token_manager->get( $single_slug ) );

		// Retrieve all tokens and include the single token
		$all_tokens      = $this->token_manager->get_all();
		$expected_tokens = [
			$single_slug => $single_token,
		];
		$this->assertSame( $expected_tokens, $all_tokens );

		// Step 2: Store multiple tokens and verify
		$tokens = $this->get_token_slug_pairs();

		foreach ( $tokens as $slug => $token ) {
			$this->assertTrue( $this->token_manager->store( $token, $slug ) );
			$expected_tokens[ $slug ] = $token; // Update expected tokens
		}

		// Retrieve all tokens and perform assertion
		$all_tokens = $this->token_manager->get_all();
		$this->assertSame( $expected_tokens, $all_tokens );

		// Perform individual assertions for each slug
		foreach ( $tokens as $slug => $expectedToken ) {
			$retrieved_token = $this->token_manager->get( $slug );
			$this->assertSame( $expectedToken, $retrieved_token );
		}

		// Step 3: Overwrite with a single token again and verify
		$new_single_token = 'new-single-token';
		$new_single_slug  = 'plugin-2';
		$this->assertTrue( $this->token_manager->store( $new_single_token, $new_single_slug ) );
		$this->assertSame( $new_single_token, $this->token_manager->get( $new_single_slug ) );

		// Update expected tokens with the new single token
		$expected_tokens[ $new_single_slug ] = $new_single_token;

		// Ensure the multiple tokens are still correct
		foreach ( $tokens as $slug => $expectedToken ) {
			if ( $slug !== $new_single_slug ) {
				$retrieved_token = $this->token_manager->get( $slug );
				$this->assertSame( $expectedToken, $retrieved_token );
			}
		}

		// Final check for all tokens
		$all_tokens = $this->token_manager->get_all();
		$this->assertSame( $expected_tokens, $all_tokens );
	}

	/**
	 * @test
	 */
	public function backwards_compatibility_with_original_token() {
		// Ensure no token is stored initially
		$this->assertNull( $this->token_manager->get() );

		$token = 'cd4b77be-985f-4737-89b7-eaa13b335fe8';

		// Force the store of the token as a string to mimic the original logic.
		update_network_option( get_current_network_id(), $this->token_manager->option_name(), $token );

		// Retrieve the stored token and verify
		$stored_token = $this->token_manager->get();

		// Confirm that we receive a string back.
		$this->assertIsString( $stored_token );

		// Confirm at this point that the token from the DB is a string.
		$db_token = get_network_option( get_current_network_id(), $this->token_manager->option_name() );
		$this->assertIsString( $db_token );

		// Confirm that the stored token is the same as the one grabbed directly from the DB.
		$this->assertSame( $stored_token, $db_token );

		// Let's update the key by using the token_manager to make sure it converts to an array.
		$new_token = 'af4b77be-985f-4537-89b7-eaa13b335fe8';
		$this->assertTrue( $this->token_manager->store( $new_token ) );

		// Retrieve the new stored token and verify
		$stored_new_token = $this->token_manager->get();

		// Confirm that the stored token in the DB is now an array.
		$db_new_token = get_network_option( get_current_network_id(), $this->token_manager->option_name() );
		$this->assertIsArray( $db_new_token );
		$this->assertCount( 1, $db_new_token );

		// Confirm that the stored token is the same as the one grabbed directly from the DB.
		$this->assertSame( $stored_new_token, array_values( $db_new_token )[0] );
	}

	/**
	 * @test
	 */
	public function it_should_delete_single_tokens() {
		// Ensure no token is stored initially
		$this->assertNull($this->token_manager->get());

		$token = 'cd4b77be-985f-4737-89b7-eaa13b335fe8';

		// Force the store of the token as a string to mimic the original logic.
		update_network_option(get_current_network_id(), $this->token_manager->option_name(), $token);

		// Retrieve the stored token and verify
		$stored_token = $this->token_manager->get();

		// Confirm that we receive a string back.
		$this->assertIsString($stored_token);
		$this->assertSame($token, $stored_token);

		// Delete the token and verify deletion
		$this->assertTrue($this->token_manager->delete());
		$this->assertNull($this->token_manager->get());
	}

	/**
	 * @test
	 */
	public function it_should_not_delete_if_no_slug_and_null_or_array() {
		// Ensure no token is stored initially
		$this->assertNull($this->token_manager->get());

		// There is no value to delete, expect false.
		$this->assertFalse($this->token_manager->delete());

		// Ensure no token is stored initially
		$this->assertNull($this->token_manager->get());

		// Force the store of the token as an array.
		$token = ['' => 'abc123'];
		update_network_option(get_current_network_id(), $this->token_manager->option_name(), $token);

		// Try to delete the token and expect true since it will clear the array.
		$this->assertTrue($this->token_manager->delete());
		$this->assertNull($this->token_manager->get());
	}

	/**
	 * @test
	 */
	public function it_should_do_nothing_when_nonexistent_slug() {
		$fake_slug = 'fake_slug';

		// Attempt to store a token with a nonexistent slug and verify it fails
		$this->assertFalse($this->token_manager->store('fake-token', $fake_slug));
		$this->assertNull($this->token_manager->get($fake_slug));

		// Attempt to delete a token with a nonexistent slug and verify it fails
		$this->assertFalse($this->token_manager->delete($fake_slug));

		// Verify that no tokens are stored
		$this->assertCount(0, $this->token_manager->get_all());
	}
}
