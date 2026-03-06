<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Features;

use StellarWP\ContainerContract\ContainerInterface;
use StellarWP\Uplink\Catalog\Catalog_Repository;
use StellarWP\Uplink\Contracts\Abstract_Provider;
use StellarWP\Uplink\Features\Strategy\Strategy_Factory;
use StellarWP\Uplink\Features\Types\Feature;
use StellarWP\Uplink\Features\Types\Flag;
use StellarWP\Uplink\Features\Types\Plugin;
use StellarWP\Uplink\Features\Types\Theme;
use StellarWP\Uplink\Licensing\License_Manager;
use StellarWP\Uplink\Site\Data;
use StellarWP\Uplink\Utils\Cast;
use WP_Error;

/**
 * Registers the Features subsystem in the DI container and hooks.
 *
 * @since 3.0.0
 */
class Provider extends Abstract_Provider {

	/**
	 * Registers singletons and hooks for the Features subsystem.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function register(): void {
		$this->container->singleton( Strategy_Factory::class, Strategy_Factory::class );

		$this->container->singleton(
			Resolve_Feature_Collection::class,
			function ( ContainerInterface $c ) {
				$resolver = new Resolve_Feature_Collection(
					$c->get( Catalog_Repository::class ),
					$c->get( License_Manager::class )
				);

				$this->register_default_types( $resolver );

				return $resolver;
			}
		);

		$this->container->singleton(
			Feature_Repository::class,
			static function ( ContainerInterface $c ) {
				return new Feature_Repository(
					$c->get( Resolve_Feature_Collection::class )
				);
			}
		);

		$this->container->singleton( Feature_Collection::class, Feature_Collection::class );

		$this->container->singleton(
			Manager::class,
			static function ( ContainerInterface $c ) {
				return new Manager(
					$c->get( Feature_Repository::class ),
					$c->get( Strategy_Factory::class ),
					$c->get( License_Manager::class )->get_key() ?? '',
					$c->get( Data::class )->get_domain()
				);
			}
		);

		$this->register_hooks();
	}

	/**
	 * Registers the default feature type to class mappings.
	 *
	 * @since 3.0.0
	 *
	 * @param Resolve_Feature_Collection $resolver The feature collection resolver.
	 *
	 * @return void
	 */
	private function register_default_types( Resolve_Feature_Collection $resolver ): void {
		$resolver->register_type( Feature::TYPE_PLUGIN, Plugin::class );
		$resolver->register_type( Feature::TYPE_FLAG, Flag::class );
		$resolver->register_type( Feature::TYPE_THEME, Theme::class );
	}

	/**
	 * Registers WordPress hooks for the Features subsystem.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	private function register_hooks(): void {
		add_filter( 'plugins_api', [ $this, 'mock_plugins_api_for_plugin_features' ], 5, 3 );
		add_filter( 'themes_api', [ $this, 'mock_themes_api_for_theme_features' ], 5, 3 );

		// TODO: Remove this once the real plugins_api filter is implemented.
		add_filter( 'upgrader_pre_download', [ $this, 'serve_local_zip_for_upgrader' ], 10, 2 );

		// TODO: Wire switch_theme and activated_plugin/deactivated_plugin sync
		// hooks once the Feature Collection and feature resolver are built.

		add_action(
			'stellarwp/uplink/unified_license_key_changed',
			static function () {
				delete_transient( Feature_Repository::TRANSIENT_KEY );
			}
		);
	}

	/**
	 * Mock plugins_api() for plugin features during development.
	 *
	 * Intercepts plugin_information requests for known plugin feature slugs
	 * and returns a response with a download_link pointing to a ZIP built
	 * on-the-fly from the plugin source in tests/_data/Features/Plugins/{slug}/.
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
	public function mock_plugins_api_for_plugin_features( $result, $action, $args ) {
		if ( $action !== 'plugin_information' ) {
			return $result;
		}

		$slug       = Cast::to_string( $args->slug ?? '' );
		$uplink_dir = WP_PLUGIN_DIR . '/uplink';
		$source_dir = $uplink_dir . '/tests/_data/Features/Plugins/' . $slug;

		if ( ! is_dir( $source_dir ) ) {
			return $result;
		}

		$zip_path = $this->build_test_zip_from_dir( $slug, $source_dir );

		if ( $zip_path === null ) {
			return $result;
		}

		return (object) [
			'name'          => $slug,
			'slug'          => $slug,
			'version'       => '1.0.0',
			'download_link' => 'stellarwp-uplink-local://' . $slug . '.zip',
		];
	}

	/**
	 * Mock themes_api() for theme features during development.
	 *
	 * Intercepts theme_information requests for known theme feature stylesheets
	 * and returns a response with a download_link pointing to a ZIP built
	 * on-the-fly from the theme source in tests/_data/Features/Themes/{stylesheet}/.
	 *
	 * TODO: Replace with real implementation that returns download links
	 *       from the Commerce Portal catalog.
	 *
	 * @since 3.0.0
	 *
	 * @param false|object|array<mixed> $result The result object or array. Default false.
	 * @param string                    $action The type of information being requested.
	 * @param object                    $args   Theme API arguments.
	 *
	 * @return false|object|array<mixed>
	 */
	public function mock_themes_api_for_theme_features( $result, $action, $args ) {
		if ( $action !== 'theme_information' ) {
			return $result;
		}

		$slug       = Cast::to_string( $args->slug ?? '' );
		$uplink_dir = WP_PLUGIN_DIR . '/uplink';
		$source_dir = $uplink_dir . '/tests/_data/Features/Themes/' . $slug;

		if ( ! is_dir( $source_dir ) ) {
			return $result;
		}

		$zip_path = $this->build_test_zip_from_dir( $slug, $source_dir );

		if ( $zip_path === null ) {
			return $result;
		}

		return (object) [
			'name'          => $slug,
			'slug'          => $slug,
			'version'       => '1.0.0',
			'download_link' => 'stellarwp-uplink-local://' . $slug . '.zip',
		];
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
	 *
	 * @return bool|string|WP_Error The local file path or the original $reply.
	 */
	public function serve_local_zip_for_upgrader( $reply, $package ) {
		if ( strpos( $package, 'stellarwp-uplink-local://' ) !== 0 ) {
			return $reply;
		}

		$filename = substr( $package, strlen( 'stellarwp-uplink-local://' ) );
		$local    = get_temp_dir() . $filename;

		if ( ! file_exists( $local ) ) {
			return $reply;
		}

		// Copy to a temp file so the upgrader can move/delete it freely.
		$tmp = wp_tempnam( $filename );
		copy( $local, $tmp );

		return $tmp;
	}

	/**
	 * Build a ZIP from a test source directory.
	 *
	 * Creates {slug}.zip in the system temp directory.
	 * Skips rebuild if the ZIP already exists and is newer than all source files.
	 *
	 * TODO: Remove this method once the real API filters are implemented.
	 *
	 * @since 3.0.0
	 *
	 * @param string $slug       The slug (used as root folder in the ZIP).
	 * @param string $source_dir Absolute path to the source directory.
	 *
	 * @return string|null The path to the ZIP file, or null on failure.
	 */
	private function build_test_zip_from_dir( string $slug, string $source_dir ): ?string {
		$zip_path = get_temp_dir() . $slug . '.zip';

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
}
