<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Legacy;

/**
 * Fluent builder that holds a resource's legacy suppression callback,
 * license provider, and license page URL.
 *
 * @since 3.1.0
 */
class LegacyConfig {

	/**
	 * Callable that suppresses legacy behaviour (hooks, cron, etc.).
	 *
	 * @var callable|null
	 */
	protected $suppressor;

	/**
	 * Callable that returns LegacyLicense[] from existing storage.
	 *
	 * @var callable|null
	 */
	protected $license_provider;

	/**
	 * URL to the plugin's legacy license management page.
	 *
	 * @var string
	 */
	protected $license_page_url = '';

	/**
	 * Provide a callable that suppresses legacy behaviour.
	 *
	 * The plugin knows best what to tear down — hooks, cron events,
	 * transients, etc. The callable receives no arguments.
	 *
	 * @since 3.1.0
	 *
	 * @param callable $suppressor
	 *
	 * @return $this
	 */
	public function on_suppress( callable $suppressor ): self {
		$this->suppressor = $suppressor;

		return $this;
	}

	/**
	 * Provide a callable that returns this resource's legacy licenses.
	 *
	 * These are reported to the cross-instance filter so the Uplink
	 * leader can discover all legacy licenses across all products.
	 *
	 * The callable receives no arguments and must return LegacyLicense[].
	 *
	 * @since 3.1.0
	 *
	 * @param callable(): LegacyLicense[] $provider
	 *
	 * @return $this
	 */
	public function licenses( callable $provider ): self {
		$this->license_provider = $provider;

		return $this;
	}

	/**
	 * Set the URL to this plugin's legacy license page so the
	 * unified UI can link back to it.
	 *
	 * @since 3.1.0
	 *
	 * @param string $url
	 *
	 * @return $this
	 */
	public function license_page( string $url ): self {
		$this->license_page_url = $url;

		return $this;
	}

	/**
	 * Get the legacy license page URL, if set.
	 *
	 * @since 3.1.0
	 *
	 * @return string
	 */
	public function get_license_page_url(): string {
		return $this->license_page_url;
	}

	/**
	 * Run the suppression callback.
	 *
	 * @since 3.1.0
	 *
	 * @return void
	 */
	public function suppress(): void {
		if ( $this->suppressor ) {
			call_user_func( $this->suppressor );
		}
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
	 * @return LegacyLicense[]
	 */
	public function get_licenses(): array {
		if ( ! $this->license_provider ) {
			return [];
		}

		return call_user_func( $this->license_provider );
	}
}
