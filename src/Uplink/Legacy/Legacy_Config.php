<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Legacy;

/**
 * Fluent builder that holds a resource's legacy suppression callback,
 * license provider, and license page URL.
 *
 * @since 3.0.0
 */
class Legacy_Config {

	/**
	 * Condition callback that determines whether suppression should run.
	 *
	 * @since 3.0.0
	 *
	 * @var callable|null
	 */
	protected $suppress_when;

	/**
	 * Action callback that performs the actual legacy teardown.
	 *
	 * @since 3.0.0
	 *
	 * @var callable|null
	 */
	protected $suppress_handle;

	/**
	 * Callable that returns Legacy_License[] from existing storage.
	 *
	 * @var callable|null
	 */
	protected $license_provider;

	/**
	 * Provide a callable that returns this resource's legacy licenses.
	 *
	 * These are reported to the cross-instance filter so the Uplink
	 * leader can discover all legacy licenses across all products.
	 *
	 * The callable receives no arguments and must return Legacy_License[].
	 *
	 * @since 3.0.0
	 *
	 * @param callable(): Legacy_License[] $provider
	 *
	 * @return $this
	 */
	public function licenses( callable $provider ): self {
		$this->license_provider = $provider;

		return $this;
	}

	/**
	 * Register suppression callbacks.
	 *
	 * @since 3.0.0
	 *
	 * @param callable $when   Condition callback — return true to allow suppression.
	 * @param callable $handle Action callback — performs the actual legacy teardown.
	 *
	 * @return $this
	 */
	public function suppress( callable $when, callable $handle ): self {
		$this->suppress_when   = $when;
		$this->suppress_handle = $handle;

		return $this;
	}

	/**
	 * Whether this config has a suppressor registered.
	 *
	 * @since 3.0.0
	 *
	 * @return bool
	 */
	public function has_suppressor(): bool {
		return $this->suppress_when !== null && $this->suppress_handle !== null;
	}

	/**
	 * Evaluate the suppression condition and, if met, run the handler.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function run_suppressor(): void {
		if ( ! $this->has_suppressor() ) {
			return;
		}

		if ( call_user_func( $this->suppress_when ) ) {
			call_user_func( $this->suppress_handle );
		}
	}

	/**
	 * Whether this config has a license provider.
	 *
	 * @since 3.0.0
	 *
	 * @return bool
	 */
	public function has_licenses(): bool {
		return $this->license_provider !== null;
	}

	/**
	 * Get the legacy licenses from this resource.
	 *
	 * @since 3.0.0
	 *
	 * @return Legacy_License[]
	 */
	public function get_licenses(): array {
		if ( ! $this->license_provider ) {
			return [];
		}

		return call_user_func( $this->license_provider );
	}
}
