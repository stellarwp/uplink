<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Legacy;

use StellarWP\ContainerContract\ContainerInterface;
use StellarWP\Uplink\Contracts\Abstract_Provider;
use StellarWP\Uplink\Legacy\Notices\License_Notice_Handler;
use StellarWP\Uplink\Notice\Notice_Controller;

/**
 * Registers services for legacy license discovery.
 *
 * @since 3.0.0
 */
class Provider extends Abstract_Provider {

	/**
	 * @inheritDoc
	 */
	public function register(): void {
		$this->container->singleton( License_Repository::class, License_Repository::class );

		$this->container->singleton(
			License_Notice_Handler::class,
			static function ( ContainerInterface $c ): License_Notice_Handler {
				return new License_Notice_Handler(
					$c->get( License_Repository::class ),
					$c->get( Notice_Controller::class )
				);
			}
		);

		$this->register_dismissed_notices_meta();

		add_action( 'admin_notices', [ $this->container->get( License_Notice_Handler::class ), 'display' ], 10, 0 );
	}

	/**
	 * Register the user meta field that tracks dismissed notice IDs and their
	 * expiry timestamps, exposed via the REST API for JS to read and update.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function register_dismissed_notices_meta(): void {
		register_meta(
			'user',
			License_Notice_Handler::DISMISSED_META_KEY,
			[
				'type'         => 'object',
				'single'       => true,
				'default'      => [],
				'show_in_rest' => [
					'schema' => [
						'type'                 => 'object',
						'additionalProperties' => [
							'type' => 'integer',
						],
					],
				],
				'auth_callback' => static function (): bool {
					return is_user_logged_in();
				},
			]
		);
	}
}
