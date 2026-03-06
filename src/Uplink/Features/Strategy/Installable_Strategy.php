<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Features\Strategy;

use StellarWP\Uplink\Features\Error_Code;
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
 * Plugin and theme features derive their active state entirely from live
 * WordPress state (plugin activation status / theme disk presence). No DB
 * option is stored — only Flag features use wp_options for state.
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
	 * Transient key for the global install lock.
	 *
	 * Only one installable feature can be installed at a time, regardless of
	 * type (plugin or theme). This prevents filesystem conflicts when multiple
	 * install requests arrive concurrently.
	 *
	 * @since 3.0.0
	 */
	protected const LOCK_KEY = 'stellarwp_uplink_install_lock';


	// ── Abstract hooks ──────────────────────────────────────────────────

	/**
	 * Check whether the extension is currently active in WordPress.
	 *
	 * @since 3.0.0
	 *
	 * @return bool
	 */
	abstract protected function check_active(): bool;

	/**
	 * Check whether the extension is installed on disk.
	 *
	 * @since 3.0.0
	 *
	 * @return bool
	 */
	abstract protected function check_installed(): bool;

	/**
	 * Install the extension from its download source.
	 *
	 * @since 3.0.0
	 *
	 * @return true|WP_Error
	 */
	abstract protected function do_install();

	/**
	 * Activate the extension.
	 *
	 * The subclass owns the full activation flow because activation logic
	 * varies significantly between plugins and themes (e.g. fatal error
	 * protection for plugins).
	 *
	 * @since 3.0.0
	 *
	 * @return true|WP_Error
	 */
	abstract protected function do_activate();

	/**
	 * Deactivate the extension.
	 *
	 * The subclass owns the full deactivation flow after the common prefix
	 * (type-guard, includes, ownership) handled by the template's disable().
	 *
	 * @since 3.0.0
	 *
	 * @return true|WP_Error
	 */
	abstract protected function do_deactivate();

	/**
	 * Verify that the installed extension belongs to an expected author.
	 *
	 * @since 3.0.0
	 *
	 * @return true|WP_Error True if ownership matches, WP_Error on mismatch.
	 */
	abstract protected function verify_ownership();

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
	 * Enable the feature: install (if needed) and activate the extension.
	 *
	 * Idempotent: returns true if the extension is already active. Uses a
	 * global transient lock to prevent concurrent installs of any extension.
	 *
	 * @since 3.0.0
	 *
	 * @return true|WP_Error True on success, WP_Error on failure.
	 */
	final public function enable() {
		// Ensure WordPress admin functions are available. These may not be
		// loaded when called from REST API or AJAX contexts.
		$this->load_wp_admin_includes();

		// Idempotent: if the extension is already active, verify ownership and bail.
		if ( $this->check_active() ) {
			$ownership = $this->verify_ownership();

			if ( is_wp_error( $ownership ) ) {
				return $ownership;
			}

			return true;
		}

		// Verify ownership before attempting installation. This catches
		// cases where the extension folder is already occupied by a different
		// developer's extension. If nothing is on disk yet, this returns true
		// (no conflict) and we proceed to install.
		$ownership = $this->verify_ownership();

		if ( is_wp_error( $ownership ) ) {
			return $ownership;
		}

		// Ensure the extension is on disk — install from ZIP if needed.
		$ensure_result = $this->ensure_installed();

		if ( is_wp_error( $ensure_result ) ) {
			return $ensure_result;
		}

		// Verify ownership after installation. A fresh download may contain
		// an extension from an unexpected author.
		$ownership = $this->verify_ownership();

		if ( is_wp_error( $ownership ) ) {
			return $ownership;
		}

		// Activate — subclass owns state update.
		return $this->do_activate();
	}

	/**
	 * Disable the feature: deactivate the extension.
	 *
	 * The common prefix (load includes, ownership verification) is handled
	 * here. The subclass's do_deactivate() owns the rest because plugin and
	 * theme disable flows diverge fundamentally.
	 *
	 * @since 3.0.0
	 *
	 * @return true|WP_Error True on success, WP_Error on failure.
	 */
	final public function disable() {
		$this->load_wp_admin_includes();

		// Refuse to touch an extension that belongs to a different developer.
		$ownership = $this->verify_ownership();

		if ( is_wp_error( $ownership ) ) {
			return $ownership;
		}

		// Subclass owns the full deactivation flow.
		return $this->do_deactivate();
	}

	/**
	 * Check whether the feature's extension is currently active.
	 *
	 * For plugins, this means the plugin is activated in WordPress.
	 * For themes, this means the theme is installed on disk.
	 * No DB option is involved — the live WordPress state is the sole truth.
	 *
	 * @since 3.0.0
	 *
	 * @return bool
	 */
	final public function is_active(): bool {
		$this->load_wp_admin_includes();

		return $this->check_active();
	}

	/**
	 * Ensure the extension is installed on disk, downloading if needed.
	 *
	 * Acquires a global transient lock to prevent concurrent installs of any
	 * feature, delegates to do_install() for the actual download, and verifies
	 * the expected file exists on disk afterward.
	 *
	 * If the extension is already installed, returns true immediately (no lock needed).
	 *
	 * @since 3.0.0
	 *
	 * @return true|WP_Error True if installed (or already was), WP_Error on failure.
	 */
	final protected function ensure_installed() {
		// Already on disk — ready for activation. Ownership is verified
		// by the caller (enable()) after this method returns.
		if ( $this->check_installed() ) {
			return true;
		}

		// Acquire a global transient lock to prevent concurrent installs.
		// Only one feature can be installed at a time — two simultaneous
		// requests could race the installer, causing file conflicts or corruption.
		if ( ! $this->acquire_lock( self::LOCK_KEY ) ) {
			return new WP_Error(
				Error_Code::INSTALL_LOCKED,
				sprintf(
					/* translators: %s: feature name */
					__( 'Another installable feature is already being installed. Cannot install "%s" right now. Please try again in a few moments.', '%TEXTDOMAIN%' ),
					$this->feature->get_name()
				)
			);
		}

		try {
			$install_result = $this->do_install();

			if ( is_wp_error( $install_result ) ) {
				return $install_result;
			}

			// Post-install verification: the download's directory structure might not
			// match the expected path. Catch this early with a clear error rather than
			// a confusing "not found" during activation.
			// @phpstan-ignore-next-line booleanNot.alwaysTrue -- (do_install() creates files on disk; side effects invisible to static analysis).
			if ( ! $this->check_installed() ) {
				return new WP_Error(
					$this->get_not_found_after_install_error_code(),
					sprintf(
						/* translators: %s: feature name */
						__( 'The extension was not found after installing "%s". The downloaded package may have an unexpected directory structure.', '%TEXTDOMAIN%' ),
						$this->feature->get_name()
					)
				);
			}

			return true; // @phpstan-ignore deadCode.unreachable (The check above is a double check)
		} finally {
			// Always release the lock, even on early returns or exceptions.
			$this->release_lock( self::LOCK_KEY );
		}
	}

	// ── Shared helpers ──────────────────────────────────────────────────

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
