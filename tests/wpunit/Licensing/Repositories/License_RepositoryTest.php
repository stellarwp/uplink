<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Licensing\Repositories;

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
		delete_option( License_Repository::OPTION_NAME );
	}

	protected function tearDown(): void {
		delete_option( License_Repository::OPTION_NAME );
		parent::tearDown();
	}

	public function test_get_returns_null_when_no_key_stored(): void {
		$this->assertNull( $this->repository->get() );
	}

	public function test_store_and_get_round_trip(): void {
		$this->repository->store( 'LWSW-UNIFIED-PRO-2026' );

		$this->assertSame( 'LWSW-UNIFIED-PRO-2026', $this->repository->get() );
	}

	public function test_store_returns_true_on_success(): void {
		$result = $this->repository->store( 'LWSW-UNIFIED-PRO-2026' );

		$this->assertTrue( $result );
	}

	public function test_store_is_idempotent_when_key_unchanged(): void {
		$this->repository->store( 'LWSW-UNIFIED-PRO-2026' );

		// Storing the same key again should still return true.
		$this->assertTrue( $this->repository->store( 'LWSW-UNIFIED-PRO-2026' ) );
	}

	public function test_store_overwrites_existing_key(): void {
		$this->repository->store( 'OLD-KEY' );
		$this->repository->store( 'NEW-KEY' );

		$this->assertSame( 'NEW-KEY', $this->repository->get() );
	}

	public function test_store_sanitizes_key(): void {
		$this->repository->store( 'LWSW-"UNIFIED\'-PRO`-2026' );

		$this->assertSame( 'LWSW-UNIFIED-PRO-2026', $this->repository->get() );
	}

	public function test_delete_removes_stored_key(): void {
		$this->repository->store( 'LWSW-UNIFIED-PRO-2026' );
		$this->repository->delete();

		$this->assertNull( $this->repository->get() );
	}

	public function test_delete_returns_true_when_key_existed(): void {
		$this->repository->store( 'LWSW-UNIFIED-PRO-2026' );

		$this->assertTrue( $this->repository->delete() );
	}

	public function test_exists_returns_false_when_no_key_stored(): void {
		$this->assertFalse( $this->repository->exists() );
	}

	public function test_exists_returns_true_after_storing_key(): void {
		$this->repository->store( 'LWSW-UNIFIED-PRO-2026' );

		$this->assertTrue( $this->repository->exists() );
	}

	public function test_exists_returns_false_after_deleting_key(): void {
		$this->repository->store( 'LWSW-UNIFIED-PRO-2026' );
		$this->repository->delete();

		$this->assertFalse( $this->repository->exists() );
	}

	public function test_get_returns_null_for_empty_string(): void {
		update_option( License_Repository::OPTION_NAME, '' );

		$this->assertNull( $this->repository->get() );
	}
}
