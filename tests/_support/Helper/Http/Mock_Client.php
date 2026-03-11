<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Http;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

/**
 * A PSR-18 test double that returns canned responses.
 *
 * Usage:
 *
 *   $client = new Mock_Client();
 *   $client->add_response( new Response( 200, [], '{"ok":true}' ) );
 *   $response = $client->sendRequest( $request ); // returns the queued response
 *
 * @since 3.0.0
 */
final class Mock_Client implements ClientInterface {

	/**
	 * Queued responses to return in FIFO order.
	 *
	 * @var ResponseInterface[]
	 */
	private array $responses = [];

	/**
	 * Captured requests in the order they were sent.
	 *
	 * @var RequestInterface[]
	 */
	private array $requests = [];

	/**
	 * Queue a response to return on the next sendRequest call.
	 *
	 * @param ResponseInterface $response The response to return.
	 *
	 * @return self
	 */
	public function add_response( ResponseInterface $response ): self {
		$this->responses[] = $response;

		return $this;
	}

	/**
	 * @inheritDoc
	 */
	public function sendRequest( RequestInterface $request ): ResponseInterface {
		$this->requests[] = $request;

		if ( count( $this->responses ) === 0 ) {
			throw new RuntimeException( 'Mock_Client: No responses queued.' );
		}

		return array_shift( $this->responses );
	}

	/**
	 * Get the last request that was sent.
	 *
	 * @return RequestInterface
	 */
	public function get_last_request(): RequestInterface {
		if ( count( $this->requests ) === 0 ) {
			throw new RuntimeException( 'Mock_Client: No requests captured.' );
		}

		return end( $this->requests );
	}

	/**
	 * Get all captured requests.
	 *
	 * @return RequestInterface[]
	 */
	public function get_requests(): array {
		return $this->requests;
	}
}
