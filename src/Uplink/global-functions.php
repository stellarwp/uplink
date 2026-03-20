<?php
/**
 * Global (non-namespaced) Uplink helper functions.
 *
 * These functions are registered by whichever vendor-prefixed Uplink instance
 * is the version leader. They delegate to version-keyed closures stored in a
 * static registry so that the highest version's logic always runs, regardless
 * of which instance's copy of this file was included first.
 *
 * Plugin consumers should use these functions instead of the namespaced
 * equivalents to ensure they always execute the most up-to-date implementation.
 */

if ( ! function_exists( '_stellarwp_uplink_instance_registry' ) ) {
	/**
	 * Reads from or writes to the active Uplink instance registry.
	 *
	 * The static variable lives inside this single function so all
	 * vendor-prefixed copies share the same registry. Only currently-active
	 * instances can register themselves, so there is no stale-version problem.
	 *
	 * - Register: _stellarwp_uplink_instance_registry( '3.0.1' )
	 * - Read all:  _stellarwp_uplink_instance_registry()
	 *
	 * @internal Not intended for direct use by plugins.
	 *
	 * @param string $version Version to register (omit when reading).
	 *
	 * @return array<string, true> Map of registered version strings.
	 */
	function _stellarwp_uplink_instance_registry( string $version = '' ): array {
		/** @var array<string, true> $versions */
		static $versions = [];

		// Only accept registrations during the bootstrap window (before wp_loaded).
		// All real Uplink instances initialize during plugins_loaded, so anything
		// arriving after wp_loaded is outside the expected lifecycle and is ignored
		// to prevent external code from injecting fake versions into the registry.
		if ( $version !== '' && ! did_action( 'wp_loaded' ) ) {
			$versions[ $version ] = true;
		}

		return $versions;
	}
}

if ( ! function_exists( '_stellarwp_uplink_global_function_registry' ) ) {
	/**
	 * Reads from or writes to the global function registry.
	 *
	 * The static variable lives inside this single function so all callers
	 * share the same registry without relying on $GLOBALS.
	 *
	 * - Register: _stellarwp_uplink_global_function_registry( 'key', '1.0.0', $callable )
	 * - Retrieve leader callable: _stellarwp_uplink_global_function_registry( 'key' )
	 *
	 * @internal Not intended for direct use by plugins.
	 *
	 * @param string        $key      Function identifier.
	 * @param string        $version  Version registering the callable (omit when reading).
	 * @param callable|null $callback Callable to register (omit when reading).
	 *
	 * @return callable|null Null when writing, or the leader's callable when reading.
	 */
	function _stellarwp_uplink_global_function_registry( string $key, string $version = '', ?callable $callback = null ): ?callable {
		/** @var array<string, array<string, callable>> $registry */
		static $registry = [];

		if ( $callback !== null ) {
			// Mirror the instance registry's registration window: only accept
			// writes before wp_loaded so callbacks can't be injected after bootstrap.
			if ( ! did_action( 'wp_loaded' ) ) {
				$registry[ $key ][ $version ] = $callback;
			}
			return null;
		}

		$versions = array_keys( _stellarwp_uplink_instance_registry() );
		$highest  = array_reduce(
			$versions,
			static function ( string $carry, string $v ): string {
				return version_compare( $v, $carry, '>' ) ? $v : $carry;
			},
			'0.0.0'
		);

		return $registry[ $key ][ $highest ] ?? null;
	}
}

if ( ! function_exists( 'stellarwp_uplink_has_unified_license_key' ) ) {
	/**
	 * Whether the site has a unified license key stored or discoverable.
	 *
	 * Does not make any remote API calls — only checks local storage and
	 * registered products for an embedded key.
	 *
	 * @since 3.0.0
	 *
	 * @return bool
	 */
	function stellarwp_uplink_has_unified_license_key(): bool {
		$callback = _stellarwp_uplink_global_function_registry( 'stellarwp_uplink_has_unified_license_key' );

		return $callback ? (bool) $callback() : false;
	}
}

if ( ! function_exists( 'stellarwp_uplink_get_unified_license_key' ) ) {
	/**
	 * Get the unified license key.
	 *
	 * @since 3.0.0
	 *
	 * @return string|null The unified license key, or null if not found.
	 */
	function stellarwp_uplink_get_unified_license_key(): ?string {
		$callback = _stellarwp_uplink_global_function_registry( 'stellarwp_uplink_get_unified_license_key' );

		// @phpstan-ignore return.type
		return $callback ? $callback() : null;
	}
}

if ( ! function_exists( 'stellarwp_uplink_is_product_license_active' ) ) {
	/**
	 * Whether a specific product has an active, valid license.  *
	 *
	 * @since 3.0.0
	 *
	 * @param string $product The product slug (e.g. 'give', 'learndash', 'kadence', 'the-events-calendar').
	 *
	 * @return bool
	 */
	function stellarwp_uplink_is_product_license_active( string $product ): bool {
		$callback = _stellarwp_uplink_global_function_registry( 'stellarwp_uplink_is_product_license_active' );

		return $callback ? (bool) $callback( $product ) : false;
	}
}

if ( ! function_exists( 'stellarwp_uplink_is_feature_enabled' ) ) {
	/**
	 * Checks if a feature is available in the catalog AND enabled/active.
	 * Returns false if the feature is not in the catalog at all.
	 *
	 * @since 3.0.0
	 *
	 * @param string $slug The feature slug.
	 *
	 * @return bool
	 */
	function stellarwp_uplink_is_feature_enabled( string $slug ): bool {
		$callback = _stellarwp_uplink_global_function_registry( 'stellarwp_uplink_is_feature_enabled' );

		return $callback ? (bool) $callback( $slug ) : false;
	}
}

if ( ! function_exists( 'stellarwp_uplink_is_feature_available' ) ) {
	/**
	 * Checks if a feature is available in the catalog, regardless of enabled state.
	 *
	 * @since 3.0.0
	 *
	 * @param string $slug The feature slug.
	 *
	 * @return bool
	 */
	function stellarwp_uplink_is_feature_available( string $slug ): bool {
		$callback = _stellarwp_uplink_global_function_registry( 'stellarwp_uplink_is_feature_available' );

		return $callback ? (bool) $callback( $slug ) : false;
	}
}

if ( ! function_exists( 'stellarwp_uplink_get_license_page_url' ) ) {
	/**
	 * Returns the admin URL for the unified Feature Manager page.
	 *
	 * @since 3.0.0
	 *
	 * @return string The admin URL, or an empty string if no instance is active.
	 */
	function stellarwp_uplink_get_license_page_url(): string {
		$callback = _stellarwp_uplink_global_function_registry( 'stellarwp_uplink_get_license_page_url' );

		$result = $callback ? $callback() : '';

		return is_string( $result ) ? $result : '';
	}
}
