<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\CLI;

use WP_CLI\Loggers\Base as Logger_Base;

/**
 * Minimal WP_CLI logger that records the last message for each level.
 *
 * Usage:
 *   $logger = new Spy_Logger();
 *   WP_CLI::set_logger( $logger );
 *   // … run command …
 *   $this->assertSame( 'Expected message', $logger->last_success );
 *
 * @internal Test-only.
 */
class Spy_Logger extends Logger_Base {

	/** @var list<string> */
	public array $info_messages = [];

	/** @var string|null */
	public ?string $last_info = null;

	/** @var string|null */
	public ?string $last_success = null;

	/** @var string|null */
	public ?string $last_error = null;

	/** @var string|null */
	public ?string $last_warning = null;

	public function info( $message ) {
		$this->last_info       = $message;
		$this->info_messages[] = $message;
	}

	public function success( $message ) {
		$this->last_success = $message;
	}

	public function warning( $message ) {
		$this->last_warning = $message;
	}

	public function error( $message ) {
		$this->last_error = $message;
	}
}
