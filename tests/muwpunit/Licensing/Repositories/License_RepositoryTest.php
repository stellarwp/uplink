<?php declare( strict_types=1 );

namespace muwpunit\Licensing\Repositories;

use StellarWP\Uplink\Licensing\Repositories\License_Repository;
use StellarWP\Uplink\Tests\UplinkTestCase;

/**
 * @since 3.0.0
 */
final class License_RepositoryTest extends UplinkTestCase {

	private License_Repository $repository;

	protected function setUp(): void {
		parent::setUp();
		$this->repository = new License_Repository();
		delete_network_option( null, License_Repository::OPTION_NAME );
		delete_option( License_Repository::OPTION_NAME );
	}

	protected function tearDown(): void {
		delete_network_option( null, License_Repository::OPTION_NAME );
		delete_option( License_Repository::OPTION_NAME );
		parent::tearDown();
	}

	public function test_get_prefers_network_key_over_site_key(): void {
		update_option( License_Repository::OPTION_NAME, 'SITE-KEY' );
		update_network_option( null, License_Repository::OPTION_NAME, 'NETWORK-KEY' );

		$this->assertSame( 'NETWORK-KEY', $this->repository->get() );
	}

	public function test_get_falls_back_to_site_key_when_no_network_key(): void {
		update_option( License_Repository::OPTION_NAME, 'SITE-KEY' );

		$this->assertSame( 'SITE-KEY', $this->repository->get() );
	}

	public function test_get_returns_null_when_neither_key_exists(): void {
		$this->assertNull( $this->repository->get() );
	}

	public function test_store_with_network_true_writes_to_network_option(): void {
		$this->repository->store( 'NETWORK-KEY', true );

		$this->assertSame( 'NETWORK-KEY', get_network_option( null, License_Repository::OPTION_NAME ) );
		$this->assertEmpty( get_option( License_Repository::OPTION_NAME ) );
	}

	public function test_store_with_network_false_writes_to_site_option(): void {
		$this->repository->store( 'SITE-KEY', false );

		$this->assertSame( 'SITE-KEY', get_option( License_Repository::OPTION_NAME ) );
		$this->assertEmpty( get_network_option( null, License_Repository::OPTION_NAME ) );
	}

	public function test_store_network_is_idempotent_when_key_unchanged(): void {
		$this->repository->store( 'NETWORK-KEY', true );

		$this->assertTrue( $this->repository->store( 'NETWORK-KEY', true ) );
	}

	public function test_delete_with_network_true_removes_network_option(): void {
		update_network_option( null, License_Repository::OPTION_NAME, 'NETWORK-KEY' );

		$this->repository->delete( true );

		$this->assertEmpty( get_network_option( null, License_Repository::OPTION_NAME ) );
	}

	public function test_delete_with_network_false_does_not_affect_network_option(): void {
		update_network_option( null, License_Repository::OPTION_NAME, 'NETWORK-KEY' );
		update_option( License_Repository::OPTION_NAME, 'SITE-KEY' );

		$this->repository->delete( false );

		$this->assertSame( 'NETWORK-KEY', get_network_option( null, License_Repository::OPTION_NAME ) );
		$this->assertEmpty( get_option( License_Repository::OPTION_NAME ) );
	}

	public function test_exists_returns_true_when_only_network_key_set(): void {
		update_network_option( null, License_Repository::OPTION_NAME, 'NETWORK-KEY' );

		$this->assertTrue( $this->repository->exists() );
	}

	public function test_exists_returns_true_when_only_site_key_set(): void {
		update_option( License_Repository::OPTION_NAME, 'SITE-KEY' );

		$this->assertTrue( $this->repository->exists() );
	}

	public function test_exists_returns_false_after_both_keys_deleted(): void {
		update_network_option( null, License_Repository::OPTION_NAME, 'NETWORK-KEY' );
		update_option( License_Repository::OPTION_NAME, 'SITE-KEY' );

		$this->repository->delete( true );
		$this->repository->delete( false );

		$this->assertFalse( $this->repository->exists() );
	}
}
