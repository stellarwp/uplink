<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Rest;

use StellarWP\Uplink\Contracts\Abstract_Provider;
use StellarWP\Uplink\Rest\V1\Webhook_Controller;

final class Provider extends Abstract_Provider {

	public const VERSION   = 'stellarwp.uplink.rest_version';
	public const NAMESPACE = 'stellarwp.uplink.rest_namespace_base';

	/**
	 * @inheritDoc
	 */
	public function register(): void {
		$this->container->singleton( self::VERSION, '1' );
		$this->container->singleton( self::NAMESPACE, 'uplink' );

		$this->webhook_endpoint();

		// Register our endpoints with WordPress.
		add_action( 'rest_api_init', function(): void {
			$this->container->get( Webhook_Controller::class )->register_routes();
		}, 10, 0 );
	}

	private function webhook_endpoint(): void {
		$this->container->singleton(
			Webhook_Controller::class,
			new Webhook_Controller(
				$this->container->get( self::NAMESPACE ),
				$this->container->get( self::VERSION ),
				'webhooks'
			)
		);
	}

}
