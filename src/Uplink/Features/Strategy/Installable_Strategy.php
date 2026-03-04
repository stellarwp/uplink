<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Features\Strategy;

use function delete_transient;
use function get_transient;
use function set_transient;

/**
 * Abstract base for strategies that install extensions (plugins, themes) from ZIP files.
 *
 * Provides shared infrastructure used by both Zip_Strategy (plugins) and
 * Theme_Strategy (themes):
 * - Transient-based per-slug locking to prevent concurrent installs.
 * - A feature resolver callable for sync-hook lookups.
 * - An abstract hook for loading WP admin includes.
 *
 * Does NOT template enable()/disable()/is_active() — the control flows for
 * plugins and themes diverge enough that a template method would be forced.
 *
 * @since 3.0.0
 */
abstract class Installable_Strategy extends Abstract_Strategy {

	/**
	 * Transient lock TTL in seconds.
	 *
	 * 120 seconds is generous enough to cover slow downloads on shared hosting,
	 * but short enough that a crashed install won't block retries for long.
	 *
	 * @since 3.0.0
	 */
	protected const LOCK_TTL = MINUTE_IN_SECONDS * 2;

	/**
	 * Transient key prefix for install locks.
	 *
	 * Full key: stellarwp_uplink_install_lock_{slug}
	 *
	 * @since 3.0.0
	 */
	protected const LOCK_PREFIX = 'stellarwp_uplink_install_lock_';

	/**
	 * Optional callable that resolves an identifier string to a Feature.
	 *
	 * The concrete type returned depends on the subclass:
	 * - Zip_Strategy: fn(string $plugin_file): ?Zip
	 * - Theme_Strategy: fn(string $stylesheet): ?Theme
	 *
	 * The Provider layer wires this to the Feature Collection. Until then,
	 * sync hook callbacks will silently no-op because the resolver returns null.
	 *
	 * @since 3.0.0
	 *
	 * @var callable|null
	 */
	protected $feature_resolver;

	/**
	 * Construct the Installable_Strategy.
	 *
	 * @since 3.0.0
	 *
	 * @param callable|null $feature_resolver Optional. Resolves an identifier
	 *                                        string to a Feature instance.
	 */
	public function __construct( ?callable $feature_resolver = null ) {
		$this->feature_resolver = $feature_resolver;
	}

	/**
	 * Resolve an identifier to a Feature via the configured resolver.
	 *
	 * Returns null if no resolver is configured or if the identifier doesn't
	 * correspond to a known feature. Subclasses should wrap this with a
	 * type-specific check (e.g. instanceof Zip).
	 *
	 * @since 3.0.0
	 *
	 * @param string $identifier The identifier to resolve (plugin_file, stylesheet, etc.).
	 *
	 * @return mixed The resolved feature or null.
	 */
	protected function resolve_feature( string $identifier ) {
		if ( $this->feature_resolver === null ) {
			return null;
		}

		return ( $this->feature_resolver )( $identifier );
	}

	/**
	 * Build the transient lock key for a given slug.
	 *
	 * @since 3.0.0
	 *
	 * @param string $slug The extension slug.
	 *
	 * @return string
	 */
	protected function build_lock_key( string $slug ): string {
		return self::LOCK_PREFIX . $slug;
	}

	/**
	 * Attempt to acquire a transient-based lock.
	 *
	 * Uses a simple set-if-absent pattern. The transient TTL ensures the lock
	 * auto-expires even if the process crashes without releasing it.
	 *
	 * @since 3.0.0
	 *
	 * @param string $lock_key Transient key for the lock.
	 *
	 * @return bool True if lock acquired, false if already held.
	 */
	protected function acquire_lock( string $lock_key ): bool {
		if ( get_transient( $lock_key ) !== false ) {
			return false;
		}

		set_transient( $lock_key, '1', self::LOCK_TTL );

		return true;
	}

	/**
	 * Release a transient-based install lock.
	 *
	 * @since 3.0.0
	 *
	 * @param string $lock_key Transient key for the lock.
	 *
	 * @return void
	 */
	protected function release_lock( string $lock_key ): void {
		delete_transient( $lock_key );
	}

	/**
	 * Load WordPress admin includes required for extension management.
	 *
	 * Subclasses implement this to load the specific files needed for their
	 * extension type (plugin.php, theme.php, upgrader classes, etc.).
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	abstract protected function load_wp_admin_includes(): void;
}
