<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Traits;

use RuntimeException;
use StellarWP\Uplink\Tests\UplinkTestCase;
use StellarWP\Uplink\Traits\With_Debugging;
use WP_Error;

/**
 * Tests for the With_Debugging trait.
 *
 * Uses uopz to toggle WP_DEBUG and capture error_log() calls so we can
 * assert both the gating logic and the message format without side effects.
 */
final class With_DebuggingTest extends UplinkTestCase {

	use With_Uopz;

	/**
	 * Concrete class exposing the trait's protected methods for testing.
	 *
	 * @var object
	 */
	private $subject;

	/**
	 * Messages captured from error_log() via uopz.
	 *
	 * @var string[]
	 */
	private array $logged = [];

	protected function setUp(): void {
		parent::setUp();

		$this->subject = new class() {
			use With_Debugging;

			/**
			 * Expose is_wp_debug() for assertions.
			 */
			public function call_is_wp_debug(): bool {
				return $this->is_wp_debug();
			}

			/**
			 * Expose debug_log() for assertions.
			 */
			public function call_debug_log( string $message ): void {
				self::debug_log( $message );
			}

			/**
			 * Expose debug_log_throwable() for assertions.
			 */
			public function call_debug_log_throwable( \Throwable $e, string $context ): void {
				self::debug_log_throwable( $e, $context );
			}

			/**
			 * Expose debug_log_wp_error() for assertions.
			 */
			public function call_debug_log_wp_error( \WP_Error $error, string $context ): void {
				self::debug_log_wp_error( $error, $context );
			}
		};

		$this->logged = [];

		// Capture error_log calls instead of writing to the log file.
		$logged = &$this->logged;
		$this->set_fn_return(
			'error_log',
			static function ( string $message ) use ( &$logged ): bool {
				$logged[] = $message;

				return true;
			},
			true
		);
	}

	// ─── is_wp_debug() ─────────────────────────────────────────────────

	public function test_is_wp_debug_returns_true_when_wp_debug_is_true(): void {
		$this->set_const_value( 'WP_DEBUG', true );

		$this->assertTrue( $this->subject->call_is_wp_debug() ); // @phpstan-ignore method.notFound
	}

	public function test_is_wp_debug_returns_false_when_wp_debug_is_false(): void {
		$this->set_const_value( 'WP_DEBUG', false );

		$this->assertFalse( $this->subject->call_is_wp_debug() ); // @phpstan-ignore method.notFound
	}

	// ─── debug_log() ───────────────────────────────────────────────────

	public function test_debug_log_writes_to_error_log_when_wp_debug_is_true(): void {
		$this->set_const_value( 'WP_DEBUG', true );

		$this->subject->call_debug_log( 'Something happened.' ); // @phpstan-ignore method.notFound

		$this->assertCount( 1, $this->logged );
		$this->assertSame( 'Uplink: Something happened.', $this->logged[0] );
	}

	public function test_debug_log_does_not_write_when_wp_debug_is_false(): void {
		$this->set_const_value( 'WP_DEBUG', false );

		$this->subject->call_debug_log( 'Should not appear.' ); // @phpstan-ignore method.notFound

		$this->assertCount( 0, $this->logged );
	}

	public function test_debug_log_prefixes_message_with_uplink(): void {
		$this->set_const_value( 'WP_DEBUG', true );

		$this->subject->call_debug_log( 'test message' ); // @phpstan-ignore method.notFound

		$this->assertStringStartsWith( 'Uplink: ', $this->logged[0] );
	}

	public function test_debug_log_preserves_full_message_after_prefix(): void {
		$this->set_const_value( 'WP_DEBUG', true );

		$message = 'Fatal error installing "kadence-blocks": file not found';
		$this->subject->call_debug_log( $message ); // @phpstan-ignore method.notFound

		$this->assertSame( 'Uplink: ' . $message, $this->logged[0] );
	}

	public function test_debug_log_handles_empty_message(): void {
		$this->set_const_value( 'WP_DEBUG', true );

		$this->subject->call_debug_log( '' ); // @phpstan-ignore method.notFound

		$this->assertCount( 1, $this->logged );
		$this->assertSame( 'Uplink: ', $this->logged[0] );
	}

	public function test_debug_log_multiple_calls_log_multiple_entries(): void {
		$this->set_const_value( 'WP_DEBUG', true );

		$this->subject->call_debug_log( 'first' ); // @phpstan-ignore method.notFound
		$this->subject->call_debug_log( 'second' ); // @phpstan-ignore method.notFound
		$this->subject->call_debug_log( 'third' ); // @phpstan-ignore method.notFound

		$this->assertCount( 3, $this->logged );
		$this->assertSame( 'Uplink: first', $this->logged[0] );
		$this->assertSame( 'Uplink: second', $this->logged[1] );
		$this->assertSame( 'Uplink: third', $this->logged[2] );
	}

	// ─── debug_log_throwable() ─────────────────────────────────────────

	public function test_debug_log_throwable_logs_message_file_line_and_trace(): void {
		$this->set_const_value( 'WP_DEBUG', true );

		$exception = new RuntimeException( 'Something broke' );

		$this->subject->call_debug_log_throwable( $exception, 'Catalog sync' ); // @phpstan-ignore method.notFound

		$this->assertCount( 1, $this->logged );
		$this->assertStringStartsWith( 'Uplink: Catalog sync: Something broke', $this->logged[0] );
		$this->assertStringContainsString( $exception->getFile() . ':' . $exception->getLine(), $this->logged[0] );
		$this->assertStringContainsString( $exception->getTraceAsString(), $this->logged[0] );
	}

	public function test_debug_log_throwable_does_not_log_when_wp_debug_is_false(): void {
		$this->set_const_value( 'WP_DEBUG', false );

		$this->subject->call_debug_log_throwable( new RuntimeException( 'hidden' ), 'ctx' ); // @phpstan-ignore method.notFound

		$this->assertCount( 0, $this->logged );
	}

	// ─── debug_log_wp_error() ──────────────────────────────────────────

	public function test_debug_log_wp_error_logs_code_and_message(): void {
		$this->set_const_value( 'WP_DEBUG', true );

		$error = new WP_Error( 'http_request_failed', 'Connection timed out' );

		$this->subject->call_debug_log_wp_error( $error, 'License check' ); // @phpstan-ignore method.notFound

		$this->assertCount( 1, $this->logged );
		$this->assertSame(
			'Uplink: License check: [http_request_failed] Connection timed out',
			$this->logged[0]
		);
	}

	public function test_debug_log_wp_error_does_not_log_when_wp_debug_is_false(): void {
		$this->set_const_value( 'WP_DEBUG', false );

		$error = new WP_Error( 'fail', 'nope' );

		$this->subject->call_debug_log_wp_error( $error, 'ctx' ); // @phpstan-ignore method.notFound

		$this->assertCount( 0, $this->logged );
	}
}
