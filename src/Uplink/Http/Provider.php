<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Http;

use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use StellarWP\Uplink\Contracts\Abstract_Provider;
use Symfony\Component\HttpClient\Psr18Client;

/**
 * Registers the PSR-18 HTTP client infrastructure in the DI container.
 *
 * Wires Symfony's Psr18Client as the default PSR-18 implementation,
 * backed by Nyholm's Psr17Factory for PSR-7/PSR-17 message creation.
 *
 * Consumers can override any of these bindings in the container to
 * swap in a different HTTP client (Guzzle, a WordPress adapter, etc.).
 *
 * @since 3.0.0
 */
final class Provider extends Abstract_Provider {

	/**
	 * @inheritDoc
	 */
	public function register(): void {
		$this->container->singleton(
			Psr17Factory::class,
			static function () {
				return new Psr17Factory();
			}
		);

		$this->container->singleton(
			RequestFactoryInterface::class,
			static function () {
				return new Psr17Factory();
			}
		);

		$this->container->singleton(
			StreamFactoryInterface::class,
			static function () {
				return new Psr17Factory();
			}
		);

		$this->container->singleton(
			ClientInterface::class,
			function () {
				/** @var Psr17Factory $factory */
				$factory = $this->container->get( Psr17Factory::class );

				return new Psr18Client( null, $factory, $factory );
			}
		);
	}
}
