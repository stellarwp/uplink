<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Legacy;

/**
 * Fluent builder that holds a resource's legacy license provider.
 *
 * @since 3.1.0
 */
class Legacy_Config {

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
	 * @since 3.1.0
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
	 * Whether this config has a license provider.
	 *
	 * @since 3.1.0
	 *
	 * @return bool
	 */
	public function has_licenses(): bool {
		return $this->license_provider !== null;
	}

	/**
	 * Get the legacy licenses from this resource.
	 *
	 * @since 3.1.0
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
