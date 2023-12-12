<?php declare( strict_types=1 );

namespace StellarWP\Uplink\License\Manager\Pipeline\Traits;

use RuntimeException;
use StellarWP\Uplink\Utils\Checks;

trait Multisite_Trait {

	private function get_main_site_url(): string {
		return get_site_url( get_main_site_id() );
	}

	private function get_current_site_url(): string {
		return get_site_url();
	}

	/**
	 * Get the hostname for the main site.
	 *
	 * @throws RuntimeException
	 *
	 * @return string
	 */
	private function get_main_site_hostname(): string {
		$result = wp_parse_url( $this->get_main_site_url(), PHP_URL_HOST );

		if ( ! $result ) {
			throw new RuntimeException( 'Unable to determine the main site hostname' );
		}

		return $result;
	}

	/**
	 * Get the current site's hostname.
	 *
	 * @throws RuntimeException
	 *
	 * @return string
	 */
	private function get_current_site_hostname(): string {
		$result = wp_parse_url( $this->get_current_site_url(), PHP_URL_HOST );

		if ( ! $result ) {
			throw new RuntimeException( 'Unable to determine the current site hostname' );
		}

		return $result;
	}

	/**
	 * Checks if we're running multisite in subfolder mode.
	 *
	 * @return bool
	 */
	private function is_subfolder_install(): bool {
		return Checks::str_starts_with( $this->get_current_site_url(), $this->get_main_site_url() );
	}

	/**
	 * Check if the current site is a subdomain of the main site.
	 *
	 * @throws RuntimeException
	 *
	 * @return bool
	 */
	private function is_subdomain(): bool {
		return Checks::str_ends_with( $this->get_current_site_hostname(), $this->get_main_site_hostname() );
	}

	/**
	 * Checks if the current site is a different domain from the main site.
	 *
	 * @throws RuntimeException
	 *
	 * @return bool
	 */
	private function is_unique_domain(): bool {
		return ! str_contains( $this->get_current_site_hostname(), $this->get_main_site_hostname() );
	}

}
