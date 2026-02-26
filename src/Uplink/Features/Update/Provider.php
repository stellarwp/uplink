<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Features\Update;

use StellarWP\ContainerContract\ContainerInterface;
use StellarWP\Uplink\Contracts\Abstract_Provider;
use StellarWP\Uplink\Features\API\Feature_Client;
use StellarWP\Uplink\Features\API\Update_Client;
use StellarWP\Uplink\Resources\Collection;
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
		$this->container->singleton( Update_Client::class, Update_Client::class );

		$this->container->singleton(
			Handler::class,
			static function ( ContainerInterface $c ) {
				return new Handler(
					$c->get( Update_Client::class ),
					$c->get( Feature_Client::class ),
					$c->get( Collection::class ),
					$c->get( Data::class )
				);
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

		$handler = $this->container->get( Handler::class );

		add_filter( 'plugins_api', [ $handler, 'filter_plugins_api' ], 5, 3 );
		add_filter( 'pre_set_site_transient_update_plugins', [ $handler, 'filter_update_check' ], 5, 1 );
	}
}
