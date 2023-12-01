<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Auth\Admin;

use StellarWP\Uplink\Auth\Nonce;
use StellarWP\Uplink\Auth\Token\Connector;
use StellarWP\Uplink\Auth\Token\Exceptions\InvalidTokenException;
use StellarWP\Uplink\Notice\Notice_Handler;
use StellarWP\Uplink\Notice\Notice;
use StellarWP\Uplink\Resources\Collection;

/**
 * Handles storing token data after successfully redirecting
 * back from an Origin site that has authorized their license.
 */
final class Connect_Controller {

	public const TOKEN   = 'uplink_token';
	public const LICENSE = 'uplink_license';
	public const SLUG    = 'uplink_slug';
	public const NONCE   = '_uplink_nonce';

	/**
	 * @var Connector
	 */
	private $connector;

	/**
	 * @var Notice_Handler
	 */
	private $notice;

	/**
	 * @var Collection
	 */
	private $collection;


	public function __construct( Connector $connector, Notice_Handler $notice, Collection $collection ) {
		$this->connector  = $connector;
		$this->notice     = $notice;
		$this->collection = $collection;
	}

	/**
	 * Store the token data passed back from the Origin site.
	 *
	 * @action admin_init
	 */
	public function maybe_store_token_data(): void {
		if ( ! is_admin() || wp_doing_ajax() ) {
			return;
		}

		if ( ! is_user_logged_in() ) {
			return;
		}

		$args = array_intersect_key( $_GET, [
			self::TOKEN   => true,
			self::NONCE   => true,
			self::LICENSE => true,
			self::SLUG    => true,
		] );

		if ( ! $args ) {
			return;
		}

		if ( ! Nonce::verify( $args[ self::NONCE ] ?? '' ) ) {
			$this->notice->add( new Notice( Notice::ERROR,
				__( 'Unable to save token data: nonce verification failed.', '%TEXTDOMAIN%' ),
				true
			) );

			return;
		}

		$slug   = $args[ self::SLUG ] ?? '';
		$plugin = $this->collection->offsetGet( $slug );

		if ( ! $plugin ) {
			$this->notice->add( new Notice( Notice::ERROR,
				__( 'Plugin or Service slug not found.', '%TEXTDOMAIN%' ),
				true
			) );

			return;
		}

		try {
			if ( ! $this->connector->connect( $args[ self::TOKEN ] ?? '', $plugin ) ) {
				$this->notice->add( new Notice( Notice::ERROR,
					__( 'Error storing token.', '%TEXTDOMAIN%' ),
					true
				) );

				return;
			}
		} catch ( InvalidTokenException $e ) {
			$this->notice->add( new Notice( Notice::ERROR,
				sprintf( '%s.', $e->getMessage() ),
				true
			) );

			return;
		}

		$license = $args[ self::LICENSE ] ?? '';

		// Store or override an existing license.
		if ( $license ) {
			if ( ! $plugin->set_license_key( $license, 'network' ) ) {
				$this->notice->add( new Notice( Notice::ERROR,
					__( 'Error storing license key.', '%TEXTDOMAIN%' ),
				true
				) );

				return;
			}
		}

		$this->notice->add(
			new Notice( Notice::SUCCESS,
				__( 'Connected successfully.', '%TEXTDOMAIN%' ),
				true
			)
		);
	}
}
