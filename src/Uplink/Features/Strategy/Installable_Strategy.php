<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Features\Strategy;

use StellarWP\Uplink\Features\Contracts\Installable;
use StellarWP\Uplink\Features\Error_Code;
use StellarWP\Uplink\Features\Types\Feature;
use WP_Error;

use function delete_transient;
use function get_transient;
use function set_transient;

/**
 * Abstract base for strategies that install extensions (plugins, themes) from ZIP files.
 *
 * Templates the shared enable/disable/is_active/ensure_installed control flow.
 * Subclasses provide WP-specific behavior via abstract hook methods (do_install,
 * do_activate, do_deactivate, verify_ownership, etc.).
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

	// ── Abstract hooks ──────────────────────────────────────────────────

	/**
	 * Returns the Feature class this strategy handles (e.g. Zip::class).
	 *
	 * @since 3.0.0
	 *
	 * @return class-string<Feature&Installable>
	 */
	abstract protected function get_feature_class(): string;

	/**
	 * Human-readable error message when a wrong feature type is passed.
	 *
	 * @since 3.0.0
	 *
	 * @return string
	 */
	abstract protected function get_type_mismatch_message(): string;

	/**
	 * Check whether the extension is currently active in WordPress.
	 *
	 * @since 3.0.0
	 *
	 * @param string $identifier The WP identifier (plugin_file or stylesheet).
	 *
	 * @return bool
	 */
	abstract protected function is_extension_active( string $identifier ): bool;

	/**
	 * Check whether the extension is installed on disk.
	 *
	 * @since 3.0.0
	 *
	 * @param string $identifier The WP identifier (plugin_file or stylesheet).
	 *
	 * @return bool
	 */
	abstract protected function is_extension_installed( string $identifier ): bool;

	/**
	 * Install the extension from its download source.
	 *
	 * @since 3.0.0
	 *
	 * @param Feature $feature Already type-guarded by the calling template.
	 *
	 * @return true|WP_Error
	 */
	abstract protected function do_install( Feature $feature );

	/**
	 * Activate the extension and update stored state.
	 *
	 * The subclass owns the full activation flow including state updates,
	 * because activation logic varies significantly between plugins and themes
	 * (e.g. fatal error protection for plugins).
	 *
	 * @since 3.0.0
	 *
	 * @param Feature $feature Already type-guarded by the calling template.
	 *
	 * @return true|WP_Error
	 */
	abstract protected function do_activate( Feature $feature );

	/**
	 * Deactivate the extension.
	 *
	 * The subclass owns the full deactivation flow after the common prefix
	 * (type-guard, includes, ownership) handled by the template's disable().
	 *
	 * @since 3.0.0
	 *
	 * @param Feature $feature Already type-guarded by the calling template.
	 *
	 * @return true|WP_Error
	 */
	abstract protected function do_deactivate( Feature $feature );

	/**
	 * Verify that the installed extension belongs to an expected author.
	 *
	 * @since 3.0.0
	 *
	 * @param Feature $feature Already type-guarded by the calling template.
	 *
	 * @return true|WP_Error True if ownership matches, WP_Error on mismatch.
	 */
	abstract protected function verify_ownership( Feature $feature );

	/**
	 * Error code for "extension not found after install".
	 *
	 * @since 3.0.0
	 *
	 * @return string An Error_Code constant value.
	 */
	abstract protected function get_not_found_after_install_error_code(): string;

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

	// ── Template methods ────────────────────────────────────────────────

	/**
	 * Enable a feature: install (if needed) and activate the extension.
	 *
	 * Idempotent: returns true if the extension is already active. Uses a
	 * transient lock to prevent concurrent installs of the same extension.
	 *
	 * @since 3.0.0
	 *
	 * @param Feature $feature Must be an instance of get_feature_class().
	 *
	 * @return true|WP_Error True on success, WP_Error on failure.
	 */
	final public function enable( Feature $feature ) {
		if ( ! is_a( $feature, $this->get_feature_class() ) ) {
			return new WP_Error(
				Error_Code::FEATURE_TYPE_MISMATCH,
				$this->get_type_mismatch_message()
			);
		}

		// Ensure WordPress admin functions are available. These may not be
		// loaded when called from REST API or AJAX contexts.
		$this->load_wp_admin_includes();

		/** @var Feature&Installable $feature */
		$identifier = $feature->get_wp_identifier();

		// Idempotent: if the extension is already active, verify ownership and bail.
		if ( $this->is_extension_active( $identifier ) ) {
			$ownership = $this->verify_ownership( $feature );

			if ( is_wp_error( $ownership ) ) {
				return $ownership;
			}

			$this->update_stored_state( $feature->get_slug(), true );

			return true;
		}

		// Verify ownership before attempting installation. This catches
		// cases where the extension folder is already occupied by a different
		// developer's extension. If nothing is on disk yet, this returns true
		// (no conflict) and we proceed to install.
		$ownership = $this->verify_ownership( $feature );

		if ( is_wp_error( $ownership ) ) {
			return $ownership;
		}

		// Ensure the extension is on disk — install from ZIP if needed.
		$ensure_result = $this->ensure_installed( $feature );

		if ( is_wp_error( $ensure_result ) ) {
			return $ensure_result;
		}

		// Verify ownership after installation. A fresh download may contain
		// an extension from an unexpected author.
		$ownership = $this->verify_ownership( $feature );

		if ( is_wp_error( $ownership ) ) {
			return $ownership;
		}

		// Activate — subclass owns state update.
		return $this->do_activate( $feature );
	}

	/**
	 * Disable a feature: deactivate the extension.
	 *
	 * The common prefix (type-guard, load includes, ownership verification)
	 * is handled here. The subclass's do_deactivate() owns the rest because
	 * plugin and theme disable flows diverge fundamentally.
	 *
	 * @since 3.0.0
	 *
	 * @param Feature $feature Must be an instance of get_feature_class().
	 *
	 * @return true|WP_Error True on success, WP_Error on failure.
	 */
	final public function disable( Feature $feature ) {
		if ( ! is_a( $feature, $this->get_feature_class() ) ) {
			return new WP_Error(
				Error_Code::FEATURE_TYPE_MISMATCH,
				$this->get_type_mismatch_message()
			);
		}

		$this->load_wp_admin_includes();

		// Refuse to touch an extension that belongs to a different developer.
		$ownership = $this->verify_ownership( $feature );

		if ( is_wp_error( $ownership ) ) {
			return $ownership;
		}

		// Subclass owns the full deactivation flow.
		return $this->do_deactivate( $feature );
	}

	/**
	 * Check whether a feature's extension is currently active.
	 *
	 * The live WordPress state is the source of truth. If the stored
	 * state (wp_options) disagrees with the live state, it is self-healed to
	 * match. This handles edge cases where extensions are activated/deactivated
	 * outside of the feature system (e.g. via the Plugins/Themes admin page).
	 *
	 * @since 3.0.0
	 *
	 * @param Feature $feature Must be an instance of get_feature_class().
	 *
	 * @return bool
	 */
	final public function is_active( Feature $feature ): bool {
		// Type-guard: non-matching features are never "active" from this strategy's perspective.
		if ( ! is_a( $feature, $this->get_feature_class() ) ) {
			return false;
		}

		$this->load_wp_admin_includes();

		/** @var Feature&Installable $feature */
		$live_active   = $this->is_extension_active( $feature->get_wp_identifier() );
		$stored_active = $this->get_stored_state( $feature->get_slug() );

		// Self-heal: if stored state doesn't match live state, correct it.
		// This is expected to be rare — it only happens when an extension's state
		// changes outside of the feature system.
		if ( $stored_active !== $live_active ) {
			$this->update_stored_state( $feature->get_slug(), $live_active );

			if ( $this->is_wp_debug() ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log(
					sprintf(
						'[Uplink] Self-healed feature state for "%s": stored=%s, live=%s',
						$feature->get_slug(),
						$stored_active === null ? 'null' : var_export( $stored_active, true ),
						$live_active ? 'true' : 'false'
					)
				);
			}
		}

		return $live_active;
	}

	/**
	 * Ensure the extension is installed on disk, downloading if needed.
	 *
	 * Acquires a per-slug transient lock to prevent concurrent installs,
	 * delegates to do_install() for the actual download, and verifies the
	 * expected file exists on disk afterward.
	 *
	 * If the extension is already installed, returns true immediately (no lock needed).
	 *
	 * @since 3.0.0
	 *
	 * @param Feature $feature Already type-guarded by the calling template.
	 *
	 * @return true|WP_Error True if installed (or already was), WP_Error on failure.
	 */
	final protected function ensure_installed( Feature $feature ) {
		/** @var Feature&Installable $feature */
		$identifier = $feature->get_wp_identifier();

		// Already on disk — ready for activation. Ownership is verified
		// by the caller (enable()) after this method returns.
		if ( $this->is_extension_installed( $identifier ) ) {
			return true;
		}

		// Acquire a per-slug transient lock to prevent concurrent installs.
		// Two simultaneous requests could both see "not installed" and race
		// the installer, causing file conflicts or corruption.
		$lock_key = $this->build_lock_key( $feature->get_extension_slug() );

		if ( ! $this->acquire_lock( $lock_key ) ) {
			return new WP_Error(
				Error_Code::INSTALL_LOCKED,
				sprintf(
					'Another installation is already in progress for "%s". Please try again in a few moments.',
					$feature->get_name()
				)
			);
		}

		try {
			$install_result = $this->do_install( $feature );

			if ( is_wp_error( $install_result ) ) {
				return $install_result;
			}

			// Post-install verification: the download's directory structure might not
			// match the expected path. Catch this early with a clear error rather than
			// a confusing "not found" during activation.
			// @phpstan-ignore-next-line booleanNot.alwaysTrue -- (do_install() creates files on disk; side effects invisible to static analysis).
			if ( ! $this->is_extension_installed( $identifier ) ) {
				return new WP_Error(
					$this->get_not_found_after_install_error_code(),
					sprintf(
						'The extension was not found after installing "%s". The downloaded package may have an unexpected directory structure.',
						$feature->get_name()
					)
				);
			}

			return true; // @phpstan-ignore deadCode.unreachable (The check above is a double check)
		} finally {
			// Always release the lock, even on early returns or exceptions.
			$this->release_lock( $lock_key );
		}
	}

	// ── Shared helpers ──────────────────────────────────────────────────

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
}
