<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Auth\Admin;

use StellarWP\Uplink\API\V3\Auth\Token_Authorizer_Cache_Decorator;
use StellarWP\Uplink\Auth\Authorizer;
use StellarWP\Uplink\Auth\Token\Disconnector;
use StellarWP\Uplink\Auth\Token\Token_Factory;
use StellarWP\Uplink\Notice\Notice_Handler;
use StellarWP\Uplink\Notice\Notice;
use StellarWP\Uplink\Resources\Resource;

final class Disconnect_Controller {

	public const ARG       = 'uplink_disconnect';
	public const SLUG      = 'uplink_slug';
	public const CACHE_KEY = 'uplink_cache';

	/**
	 * @var Authorizer
	 */
	private $authorizer;

	/**
	 * @var Disconnector
	 */
	private $disconnect;

	/**
	 * @var Notice_Handler
	 */
	private $notice;

	/**
	 * @var Token_Factory
	 */
	private $token_factory;

	/**
	 * @var Token_Authorizer_Cache_Decorator
	 */
	private $cache;

	/**
	 * @param  Authorizer                        $authorizer     The authorizer.
	 * @param  Disconnector                      $disconnect     Disconnects a Token, if the user has the capability.
	 * @param  Notice_Handler                    $notice         Handles storing and displaying notices.
	 * @param  Token_Factory                     $token_factory  The token factory.
	 * @param  Token_Authorizer_Cache_Decorator  $cache          The token cache.
	 */
	public function __construct(
		Authorizer $authorizer,
		Disconnector $disconnect,
		Notice_Handler $notice,
		Token_Factory $token_factory,
		Token_Authorizer_Cache_Decorator $cache
	) {
		$this->authorizer    = $authorizer;
		$this->disconnect    = $disconnect;
		$this->notice        = $notice;
		$this->token_factory = $token_factory;
		$this->cache         = $cache;
	}

	/**
	 * Get the disconnect URL to render.
	 *
	 * @param  Resource  $plugin  The plugin/service.
	 *
	 * @return string
	 */
	public function get_url( Resource $plugin ): string {
		$token = $this->token_factory->make( $plugin )->get();

		if ( ! $token ) {
			return '';
		}

		$cache_key = $this->cache->build_transient( [ $token ] );

		return wp_nonce_url( add_query_arg( [
			self::ARG       => true,
			self::SLUG      => $plugin->get_slug(),
			self::CACHE_KEY => $cache_key,
		], get_admin_url( get_current_blog_id() ) ), self::ARG );
	}

	/**
	 * Disconnect (delete) a token if the user is allowed to.
	 *
	 * @action stellarwp/uplink/{$prefix}/admin_action_{$slug}
	 *
	 * @throws \RuntimeException
	 *
	 * @return void
	 */
	public function maybe_disconnect(): void {
		if ( empty( $_GET[ self::ARG ] ) || empty( $_GET['_wpnonce'] ) || empty( $_GET[ self::SLUG ] ) || empty( $_GET[ self::CACHE_KEY ] ) ) {
			return;
		}

		if ( ! is_admin() || wp_doing_ajax() ) {
			return;
		}

		if ( wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), self::ARG ) ) {
			if ( $this->authorizer->can_auth() && $this->disconnect->disconnect( $_GET[ self::SLUG ], $_GET[ self::CACHE_KEY ] ) ) {
				$this->notice->add(
					new Notice( Notice::SUCCESS,
						__( 'Token disconnected.', '%TEXTDOMAIN%' ),
						true
					)
				);
			} else {
				$this->notice->add(
					new Notice( Notice::ERROR,
						__( 'Unable to disconnect token, ensure you have admin permissions.', '%TEXTDOMAIN%' ),
						true
					)
				);
			}
		} else {
			$this->notice->add(
				new Notice( Notice::ERROR,
					__( 'Unable to disconnect token: nonce verification failed.', '%TEXTDOMAIN%' ),
					true
				)
			);
		}

		$this->maybe_redirect_back();
	}

	/**
	 * Attempts to redirect the user back to their previous dashboard page while
	 * ensuring that any "Connect" token query variables are removed if they immediately
	 * attempt to Disconnect after Connecting. This prevents them from automatically
	 * getting connected again if the nonce is still valid.
	 *
	 * This will ensure the Notices set above are displayed.
	 *
	 * @return void
	 */
	private function maybe_redirect_back(): void {
		$referer = wp_get_referer();

		if ( ! $referer ) {
			return;
		}

		$referer = remove_query_arg(
			[
				Connect_Controller::TOKEN,
				Connect_Controller::LICENSE,
				Connect_Controller::SLUG,
				Connect_Controller::NONCE,
			],
			$referer
		);

		wp_safe_redirect( esc_url_raw( $referer ) );
		exit;
	}

}
