<?php declare(strict_types=1);

namespace StellarWP\Uplink\Tests\Auth\Token;

use StellarWP\Uplink\Auth\Token\Contracts\Token_Manager;
use StellarWP\Uplink\Config;
use StellarWP\Uplink\Register;
use StellarWP\Uplink\Resources\Collection;
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
	 * @before
	 */
	protected function multiple_tokens_setup(): void {
		parent::setUp();

		Config::set_token_auth_prefix('custom_');

		// Run init again to reload the Token/Provider.
		Uplink::init();

		$this->token_manager = $this->container->get(Token_Manager::class);
	}

	/**
	 * Sets up the container and returns the slug of a resource.
	 *
	 * @param array $resource
	 * @return mixed
	 */
	public function setup_container_get_slug(array $resource) {
		$collection = Config::get_container()->get(Collection::class);

		Register::{$resource['type']}(
			$resource['slug'],
			$resource['name'],
			$resource['version'],
			$resource['path'],
			$resource['class']
		);

		return $collection->get($resource['slug']);
	}

	/**
	 * Get token-slug pairs from test resources.
	 *
	 * @return array
	 */
	private function get_token_slug_pairs(): array {
		$resources = $this->get_test_resources();
		$tokens = [];
		$dynamicTokenPrefix = 'dynamic-token-value_';

		foreach ($resources as $resource) {
			$slug = $this->setup_container_get_slug($resource)->get_slug();
			$tokens[$slug] = $dynamicTokenPrefix . $slug;
		}

		return $tokens;
	}

	/**
	 * @test
	 */
	public function it_should_register_multiple_tokens(): void {
		$tokens = $this->get_token_slug_pairs();

		foreach ($tokens as $slug => $token) {
			$this->assertTrue($this->token_manager->store($token, $slug));
		}

		// Retrieve all tokens and perform assertion
		$all_tokens = $this->token_manager->get_all();
		$this->assertSame($tokens, $all_tokens);

		// Perform individual assertions for each slug
		foreach ($tokens as $slug => $expectedToken) {
			$retrieved_token = $this->token_manager->get($slug);
			$this->assertSame($expectedToken, $retrieved_token);
		}
	}

	/**
	 * @test
	 */
	public function it_deletes_multiple_tokens(): void {
		$tokens = $this->get_token_slug_pairs();

		foreach ($tokens as $slug => $token) {
			$this->assertTrue($this->token_manager->store($token, $slug));
		}

		// Delete all tokens and assert they are removed
		foreach (array_keys($tokens) as $slug) {
			$this->token_manager->delete($slug);
			$this->assertNull($this->token_manager->get($slug));
		}

		// Assert get_all is empty after deletion
		$all_tokens = $this->token_manager->get_all();
		$this->assertEmpty($all_tokens);
	}

	/**
	 * @test
	 */
	public function it_does_not_store_empty_tokens(): void {
		$resources = $this->get_test_resources();

		foreach ($resources as $resource) {
			$slug = $this->setup_container_get_slug($resource)->get_slug();
			$this->assertFalse($this->token_manager->store('', $slug));
			$this->assertNull($this->token_manager->get($slug));
		}
	}

	/**
	 * @test
	 */
	public function it_should_have_backwards_compatibility_with_single_and_multiple_tokens(): void {
		$collection = Config::get_container()->get(Collection::class);

		Register::{'plugin'}(
			'single-plugin-1',
			'Single Plugin',
			'1.0.0',
			dirname(__DIR__, 2) . '/plugin.php',
			Uplink::class
		);

		// Step 1: Store a single token and verify
		$single_token = 'single-token';
		$single_slug = 'single-plugin-1';
		$this->assertTrue($this->token_manager->store($single_token, $single_slug));
		$this->assertSame($single_token, $this->token_manager->get($single_slug));

		// Retrieve all tokens and include the single token
		$all_tokens = $this->token_manager->get_all();
		$expected_tokens = [
			$single_slug => $single_token
		];
		$this->assertSame($expected_tokens, $all_tokens);

		// Step 2: Store multiple tokens and verify
		$tokens = $this->get_token_slug_pairs();

		foreach ($tokens as $slug => $token) {
			$this->assertTrue($this->token_manager->store($token, $slug));
			$expected_tokens[$slug] = $token; // Update expected tokens
		}

		// Retrieve all tokens and perform assertion
		$all_tokens = $this->token_manager->get_all();
		$this->assertSame($expected_tokens, $all_tokens);

		// Perform individual assertions for each slug
		foreach ($tokens as $slug => $expectedToken) {
			$retrieved_token = $this->token_manager->get($slug);
			$this->assertSame($expectedToken, $retrieved_token);
		}

		// Step 3: Overwrite with a single token again and verify
		$new_single_token = 'new-single-token';
		$new_single_slug = 'plugin-2';
		$this->assertTrue($this->token_manager->store($new_single_token, $new_single_slug));
		$this->assertSame($new_single_token, $this->token_manager->get($new_single_slug));

		// Update expected tokens with the new single token
		$expected_tokens[$new_single_slug] = $new_single_token;

		// Ensure the multiple tokens are still correct
		foreach ($tokens as $slug => $expectedToken) {
			if ($slug !== $new_single_slug) {
				$retrieved_token = $this->token_manager->get($slug);
				$this->assertSame($expectedToken, $retrieved_token);
			}
		}

		// Final check for all tokens
		$all_tokens = $this->token_manager->get_all();
		$this->assertSame($expected_tokens, $all_tokens);
	}
}
