<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Features\Update;

use StellarWP\ContainerContract\ContainerInterface;
use StellarWP\Uplink\Catalog\Catalog_Repository;
use StellarWP\Uplink\Contracts\Abstract_Provider;
use StellarWP\Uplink\Features\Feature_Repository;
use StellarWP\Uplink\Licensing\License_Manager;
use StellarWP\Uplink\Site\Data;
use StellarWP\Uplink\Utils\Version;

/**
 * Registers the feature update pathway in the DI container and hooks.
 *
 * @since 3.0.0
 */
class Provider extends Abstract_Provider {

	/**
	 * Registers singletons and defers hook registration to init.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function register(): void {
		$this->container->singleton(
			Resolve_Update_Data::class,
			static function ( ContainerInterface $c ) {
				return new Resolve_Update_Data(
					$c->get( Feature_Repository::class ),
					$c->get( Catalog_Repository::class )
				);
			}
		);

		$this->container->singleton(
			Update_Repository::class,
			static function ( ContainerInterface $c ) {
				return new Update_Repository(
					$c->get( Resolve_Update_Data::class )
				);
			}
		);

		$this->container->singleton(
			Plugin_Handler::class,
			static function ( ContainerInterface $c ) {
				return new Plugin_Handler(
					$c->get( Update_Repository::class ),
					$c->get( Feature_Repository::class ),
					$c->get( Data::class ),
					$c->get( License_Manager::class )->get_key() ?? ''
				);
			}
		);

		add_action(
			'stellarwp/uplink/unified_license_key_changed',
			static function () {
				delete_transient( Update_Repository::TRANSIENT_KEY );
			}
		);

		add_action( 'init', [ $this, 'register_hooks' ] );
	}

	/**
	 * Registers the feature update filters if this is the highest Uplink version.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		if ( ! Version::should_handle( 'feature_updates' ) ) {
			return;
		}

		$handler = $this->container->get( Plugin_Handler::class );

		// Priority 15 to run after the plugins_api filter in the Plugins_Page class.
		add_filter( 'plugins_api', [ $handler, 'filter_plugins_api' ], 15, 3 );
		add_filter( 'pre_set_site_transient_update_plugins', [ $handler, 'filter_update_check' ], 15, 1 );
		add_filter( 'site_transient_update_plugins', [ $handler, 'filter_update_check' ], 15, 1 );
	}
}
