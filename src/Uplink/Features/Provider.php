<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Features;

use StellarWP\ContainerContract\ContainerInterface;
use StellarWP\Uplink\Contracts\Abstract_Provider;
use StellarWP\Uplink\Features\API\Client;
use StellarWP\Uplink\Features\Strategy\Built_In_Strategy;
use StellarWP\Uplink\Features\Strategy\Resolver;
use StellarWP\Uplink\Features\Strategy\Zip_Strategy;
use StellarWP\Uplink\Features\Types\Built_In;
use StellarWP\Uplink\Features\Types\Zip;
use StellarWP\Uplink\Utils\Cast;

/**
 * Registers the Features subsystem in the DI container and hooks.
 *
 * @since 3.0.0
 */
class Provider extends Abstract_Provider {

	/**
	 * @inheritDoc
	 */
	public function register(): void {
		$this->container->singleton( Client::class, Client::class );

		$this->container->singleton(
			Resolver::class,
			static function ( ContainerInterface $c ) {
				$container = $c->get( ContainerInterface::class );

				return new Resolver( $container );
			}
		);

		$this->container->singleton( Feature_Collection::class, Feature_Collection::class );

		$this->container->singleton(
			Manager::class,
			static function ( ContainerInterface $c ) {
				$client   = $c->get( Client::class );
				$resolver = $c->get( Resolver::class );

				return new Manager( $client, $resolver );
			}
		);

		$this->register_default_types();
		$this->register_default_strategies();
	}

	/**
	 * Registers the default feature type to class mappings.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	private function register_default_types(): void {
		$client = $this->container->get( Client::class );
		$client->register_type( 'zip', Zip::class );
		$client->register_type( 'built_in', Built_In::class );
	}

	/**
	 * Registers the default feature type strategies.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	private function register_default_strategies(): void {
		$this->container->singleton( Zip_Strategy::class, Zip_Strategy::class );
		$this->container->singleton( Built_In_Strategy::class, Built_In_Strategy::class );

		$resolver = $this->container->get( Resolver::class );
		$resolver->register( 'zip', Zip_Strategy::class );
		$resolver->register( 'built_in', Built_In_Strategy::class );
	}

	/**
	 * Mock plugins_api() for zip features during development.
	 *
	 * Intercepts plugin_information requests for known zip feature slugs
	 * and returns a response with a download_link pointing to a ZIP built
	 * on-the-fly from the plugin source in tests/_data/Features/Zips/{slug}/.
	 *
	 * TODO: Replace with real implementation that returns download links
	 *       from the Commerce Portal catalog.
	 *
	 * @since 3.0.0
	 *
	 * @param false|object|array<mixed> $result The result object or array. Default false.
	 * @param string                    $action The type of information being requested.
	 * @param object                    $args   Plugin API arguments.
	 *
	 * @return false|object|array<mixed>
	 */
	public function mock_plugins_api_for_zip_features( $result, $action, $args ) {
		if ( $action !== 'plugin_information' ) {
			return $result;
		}

		$slug       = Cast::to_string( $args->slug ?? '' );
		$uplink_dir = WP_PLUGIN_DIR . '/uplink';
		$source_dir = $uplink_dir . '/tests/_data/Features/Zips/' . $slug;

		if ( ! is_dir( $source_dir ) ) {
			return $result;
		}

		$zip_path = $this->build_test_zip( $slug, $source_dir );

		if ( $zip_path === null ) {
			return $result;
		}

		$download_url = plugins_url(
			'tests/_data/Features/Zips/' . $slug . '.zip',
			$uplink_dir . '/index.php'
		);

		return (object) [
			'name'          => $slug,
			'slug'          => $slug,
			'version'       => '1.0.0',
			'download_link' => $download_url,
		];
	}

	/**
	 * Build a ZIP from a test plugin source directory.
	 *
	 * Creates {slug}.zip alongside the source folder in tests/_data/Features/Zips/.
	 * Skips rebuild if the ZIP already exists and is newer than all source files.
	 *
	 * TODO: Remove this method once the real plugins_api filter is implemented.
	 *
	 * @since 3.0.0
	 *
	 * @param string $slug       The plugin slug.
	 * @param string $source_dir Absolute path to the source directory.
	 *
	 * @return string|null The path to the ZIP file, or null on failure.
	 */
	private function build_test_zip( string $slug, string $source_dir ): ?string {
		$zip_path = dirname( $source_dir ) . '/' . $slug . '.zip';

		// Skip rebuild if ZIP exists and is newer than all source files.
		if ( file_exists( $zip_path ) ) {
			$zip_mtime     = filemtime( $zip_path );
			$needs_rebuild = false;

			/** @var \SplFileInfo $file */
			foreach ( new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $source_dir, \FilesystemIterator::SKIP_DOTS ) ) as $file ) {
				if ( $file->getMTime() > $zip_mtime ) {
					$needs_rebuild = true;
					break;
				}
			}

			if ( ! $needs_rebuild ) {
				return $zip_path;
			}
		}

		$zip = new \ZipArchive();

		if ( $zip->open( $zip_path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE ) !== true ) {
			return null;
		}

		/** @var \SplFileInfo $file */
		foreach ( new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $source_dir, \FilesystemIterator::SKIP_DOTS ) ) as $file ) {
			if ( $file->isDir() ) {
				continue;
			}

			$relative_path = $slug . '/' . ltrim(
				str_replace( $source_dir, '', $file->getPathname() ),
				'/'
			);

			$zip->addFile( $file->getPathname(), $relative_path );
		}

		$zip->close();

		return $zip_path;
	}

	/**
	 * Serve local ZIP files directly to the upgrader, bypassing HTTP download.
	 *
	 * Intercepts download requests for test zip feature URLs and copies the
	 * local ZIP to a temp file, avoiding SSL and loopback issues.
	 *
	 * TODO: Remove this method once the real plugins_api filter is implemented.
	 *
	 * @since 3.0.0
	 *
	 * @param bool|WP_Error $reply    Whether to bail without returning the package. Default false.
	 * @param string        $package  The package file name or URL.
	 * @param \WP_Upgrader  $upgrader The WP_Upgrader instance.
	 *
	 * @return bool|string|WP_Error The local file path or the original $reply.
	 */
	public function serve_local_zip_for_upgrader( $reply, $package, $upgrader ) {
		$uplink_dir = WP_PLUGIN_DIR . '/uplink';
		$zips_dir   = $uplink_dir . '/tests/_data/Features/Zips/';
		$zips_url   = plugins_url( 'tests/_data/Features/Zips/', $uplink_dir . '/index.php' );

		if ( strpos( $package, $zips_url ) !== 0 ) {
			return $reply;
		}

		$filename = basename( $package );
		$local    = $zips_dir . $filename;

		if ( ! file_exists( $local ) ) {
			return $reply;
		}

		// Copy to a temp file so the upgrader can move/delete it freely.
		$tmp = wp_tempnam( $filename );
		copy( $local, $tmp );

		return $tmp;
	}
}
