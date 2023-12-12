<?php declare( strict_types=1 );

namespace StellarWP\Uplink\License;

use StellarWP\Uplink\Config;
use StellarWP\Uplink\Resources\Collection;

use function StellarWP\Uplink\get_license_key;

final class License_Key_Fetcher {

	/**
	 * @var License_Key_Strategy_Factory
	 */
	private $factory;

	/**
	 * @var Collection
	 */
	private $resources;

	/**
	 * @param  License_Key_Strategy_Factory  $factory
	 * @param  Collection                    $resources
	 */
	public function __construct(
		License_Key_Strategy_Factory $factory,
		Collection $resources
	) {
		$this->factory   = $factory;
		$this->resources = $resources;
	}

	/**
	 * Get a license key using one of the available licensing strategies.
	 *
	 * @see Config::set_license_key_strategy()
	 * @see License_Key_Strategy_Factory::make()
	 * @see get_license_key()
	 *
	 * @param  string  $slug  The product/service slug.
	 *
	 * @throws \RuntimeException
	 *
	 * @return string|null
	 */
	public function get_key( string $slug ): ?string {
		$resource = $this->resources->offsetGet( $slug );

		if ( ! $resource ) {
			return '';
		}

		return $this->factory->make( $resource )->get_key( $resource );
	}

}
