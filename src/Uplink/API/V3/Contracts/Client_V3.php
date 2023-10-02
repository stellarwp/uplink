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
	 * @return array|WP_Error
	 */
	public function get( string $endpoint, array $params = [] );


	/**
	 * Perform a POST request.
	 *
	 * @param  string  $endpoint
	 * @param  array<string, mixed>  $params
	 *
	 * @return array|WP_Error
	 */
	public function post( string $endpoint, array $params = [] );

	/**
	 * Perform any other request.
	 *
	 * @param  string  $endpoint
	 * @param  string  $method
	 * @param  array<string, mixed>  $params
	 *
	 * @return array|WP_Error
	 */
	public function request( string $endpoint, string $method = 'GET', array $params = [] );

}
