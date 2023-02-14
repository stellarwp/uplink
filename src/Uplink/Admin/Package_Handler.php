<?php

namespace StellarWP\Uplink\Admin;

use StellarWP\Uplink\API;
use StellarWP\Uplink\Config;
use WP_Upgrader;

class Package_Handler {

	/**
	 * @var WP_Upgrader
	 */
	public $upgrader;

	/**
	 * Filters the package download step to store the downloaded file with a shorter file name.
	 *
	 * @param bool        $reply    Whether to bail without returning the package.
	 *                              Default false.
	 * @param string      $package  The package file name or URL.
	 * @param WP_Upgrader $upgrader The WP_Upgrader instance.
	 *
	 * @return mixed
	 */
	public function filter_upgrader_pre_download( bool $reply, string $package, WP_Upgrader $upgrader ) {
		if ( empty( $package ) || 'invalid_license' === $package ) {
			return new \WP_Error(
				'download_failed',
				__( 'Failed to update plugin. Check your license details first.', '%TEXTDOMAIN%' ),
				''
			);
		}
		if ( $this->is_uplink_package( $package ) ) {
			$this->upgrader = $upgrader;

			return $this->download( $package );
		}

		return $reply;
	}

	/**
	 * Whether the current package is an StellarWP product or not.
	 *
	 * @param string $package The package file name or URL.
	 *
	 * @return bool
	 */
	protected function is_uplink_package( string $package ) : bool {
		if (
			empty( $package )
			|| ! preg_match( '!^(http|https|ftp)://!i', $package )
		) {
			return false;
		}

		$query_vars = parse_url( $package, PHP_URL_QUERY );

		if ( empty( $query_vars ) ) {
			return false;
		}

		$container    = Config::get_container();
		$api_base_url = $container->get( API\Client::class )->get_api_base_url();

		return preg_match( '!^' . preg_quote( $api_base_url, '!' ) . '!i', $package );
	}

	/**
	 * A mimic of the `WP_Upgrader::download_package` method that adds a step to store the temp file with a shorter
	 * file name.
	 *
	 * @see WP_Upgrader::download_package()
	 *
	 * @param string $package The URI of the package. If this is the full path to an
	 *                        existing local file, it will be returned untouched.
	 *
	 * @return string|bool|\WP_Error The full path to the downloaded package file, or a WP_Error object.
	 */
	protected function download( string $package ) {
		if ( empty( $this->filesystem ) ) {
			// try to connect
			// @phpstan-ignore-next-line
			$this->upgrader->fs_connect( [ WP_CONTENT_DIR, WP_PLUGIN_DIR ] );

			global $wp_filesystem;

			// still empty?
			if ( empty( $wp_filesystem ) ) {
				// bail
				return false;
			}

			// @phpstan-ignore-next-line
			$this->filesystem = $wp_filesystem;
		}

		$this->upgrader->skin->feedback( 'downloading_package', $package );

		$download_file = download_url( $package );

		if ( is_wp_error( $download_file ) ) {
			return new \WP_Error(
				'download_failed',
				$this->upgrader->strings['download_failed'],
				$download_file->get_error_message()
			);
		}

		$file = $this->get_short_filename( $download_file );

		$moved = $this->filesystem->move( $download_file, $file );

		if ( empty( $moved ) ) {
			// We tried, we failed, we bail and let WP do its job
			return false;
		}

		return $file;
	}

	/**
	 * Returns the absolute path to a shorter filename version of the original download temp file.
	 *
	 * The path will point to the same temp dir (WP handled) but shortening the filename to a
	 * 6 chars hash to cope with OSes limiting the max number of chars in a file path.
	 * The original filename would be a sanitized version of the URL including query args.
	 *
	 * @param string $download_file The absolute path to the original download file.
	 *
	 * @return string The absolute path to a shorter name version of the downloaded file.
	 */
	protected function get_short_filename( string $download_file ) : string {
		$extension = pathinfo( $download_file, PATHINFO_EXTENSION );
		$filename  = substr( md5( $download_file ), 0, 5 );
		$file      = dirname( $download_file ) . '/' . $filename . '.' . $extension;

		return $file;
	}
}
