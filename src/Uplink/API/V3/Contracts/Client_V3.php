<?php declare( strict_types=1 );

namespace StellarWP\Uplink\API\V3\Contracts;

use WP_Error;

interface Client_V3 {

	/**
	 * Perform a GET request.
	 *
	 * @param  string  $endpoint
	 * @param  array<string, mixed>  $params
	 *
	 * @return WP_Error|array{
	 *      'response' : array{
	 *          'code' : int,
	 *          'message' : string,
	 *          'headers' : array<string, string>,
	 *          'body' : array<string, mixed>,
	 *      },
	 *      'cookies' : array<string, string>,
	 *      'filename' : string,
	 *  }
	 */
	public function get( string $endpoint, array $params = [] );


	/**
	 * Perform a POST request.
	 *
	 * @param  string  $endpoint
	 * @param  array<string, mixed>  $params
	 *
	 * @return WP_Error|array{
	 *       'response' : array{
	 *           'code' : int,
	 *           'message' : string,
	 *           'headers' : array<string, string>,
	 *           'body' : array<string, mixed>,
	 *       },
	 *       'cookies' : array<string, string>,
	 *       'filename' : string,
	 *   }
	 */
	public function post( string $endpoint, array $params = [] );

	/**
	 * Perform any other request.
	 *
	 * @param  string  $endpoint
	 * @param  string  $method
	 * @param  array<string, mixed>  $params
	 *
	 * @return WP_Error|array{
	 *       'response' : array{
	 *           'code' : int,
	 *           'message' : string,
	 *           'headers' : array<string, string>,
	 *           'body' : array<string, mixed>,
	 *       },
	 *       'cookies' : array<string, string>,
	 *       'filename' : string,
	 *   }
	 */
	public function request( string $endpoint, string $method = 'GET', array $params = [] );

}
