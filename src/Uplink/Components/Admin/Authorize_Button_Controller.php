<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Components\Admin;

use InvalidArgumentException;
use StellarWP\Uplink\API\V3\Auth\Contracts\Auth_Url;
use StellarWP\Uplink\Auth\Admin\Disconnect_Controller;
use StellarWP\Uplink\Auth\Authorizer;
use StellarWP\Uplink\Auth\Nonce;
use StellarWP\Uplink\Auth\Token\Contracts\Token_Manager;
use StellarWP\Uplink\Components\Controller;
use StellarWP\Uplink\Config;
use StellarWP\Uplink\View\Contracts\View;

final class Authorize_Button_Controller extends Controller {

	/**
	 * The view file, without ext, relative to the root views directory.
	 */
	public const VIEW = 'admin/authorize-button';

	/**
	 * @var Authorizer
	 */
	private $authorizer;

	/**
	 * @var Token_Manager
	 */
	private $token_manager;

	/**
	 * @var Nonce
	 */
	private $nonce;

	/**
	 * @var Auth_Url
	 */
	private $auth_url_manager;

	/**
	 * The auth URL for the origin as fetched remotely from the
	 * licensing server when we want to render the button.
	 *
	 * @var string
	 */
	private $auth_url = '';

	public function __construct(
		View $view,
		Authorizer $authorizer,
		Token_Manager $token_manager,
		Nonce $nonce,
		Auth_Url $auth_url_manager
	) {
		parent::__construct( $view );

		$this->authorizer       = $authorizer;
		$this->token_manager    = $token_manager;
		$this->nonce            = $nonce;
		$this->auth_url_manager = $auth_url_manager;
	}

	/**
	 * Renders the authorize-button view.
	 *
	 * @param  array{slug?: string} $args The Product slug.
	 *
	 * @see src/views/admin/authorize-button.php
	 *
	 * @throws InvalidArgumentException
	 */
	public function render( array $args = [] ): void {
		global $pagenow;

		$slug = $args['slug'] ?? '';

		if ( empty ( $slug ) ) {
			throw new InvalidArgumentException( __( 'The Product slug cannot be empty', '%TEXTDOMAIN%' ) );
		}

		$this->auth_url = $this->auth_url_manager->get( $slug );

		if ( ! $this->auth_url ) {
			return;
		}

		$authenticated = false;
		$target        = '_blank';
		$link_text     = __( 'Connect', '%TEXTDOMAIN%' );
		$url           = $this->build_auth_url();
		$classes       = [
			'uplink-authorize',
			'not-authorized',
		];

		if ( ! $this->authorizer->can_auth() ) {
			$target    = '_self';
			$link_text = __( 'Contact your network administrator to connect', '%TEXTDOMAIN%' );
			$url       = get_admin_url( get_current_blog_id(), 'network/' );
		} elseif ( $this->token_manager->get() ) {
			$authenticated = true;
			$target        = '_self';
			$link_text     = __( 'Disconnect', '%TEXTDOMAIN%' );
			$url           = wp_nonce_url( add_query_arg( [ Disconnect_Controller::ARG => true ], get_admin_url( get_current_blog_id() ) ), Disconnect_Controller::ARG );
			$classes[1]    = 'authorized';
		}

		$hook_prefix = Config::get_hook_prefix();

		/**
		 * Filter the link text.
		 *
		 * @param  string  $link_text  The current link text.
		 * @param  bool  $authenticated  Whether they are authenticated.
		 * @param  string|null  $pagenow  The value of WordPress's pagenow.
		 */
		$link_text = apply_filters(
			"stellarwp/uplink/$hook_prefix/view/authorize_button/link_text",
			$link_text,
			$authenticated,
			$pagenow
		);

		/**
		 * Filter the hyperlink url.
		 *
		 * @param  string  $url  The current hyperlink url.
		 * @param  bool  $authenticated  Whether they are authenticated.
		 * @param  string|null  $pagenow  The value of WordPress's pagenow.
		 */
		$url = apply_filters(
			"stellarwp/uplink/$hook_prefix/view/authorize_button/url",
			$url,
			$authenticated,
			$pagenow
		);

		/**
		 * Filter the link target.
		 *
		 * @param  string  $target  The current link target.
		 * @param  bool  $authenticated  Whether they are authenticated.
		 * @param  string|null  $pagenow  The value of WordPress's pagenow.
		 */
		$target = apply_filters(
			"stellarwp/uplink/$hook_prefix/view/authorize_button/target",
			$target,
			$authenticated,
			$pagenow
		);

		/**
		 * Filter the HTML wrapper tag.
		 *
		 * @param  string  $tag  The HTML tag to use for the wrapper.
		 * @param  bool  $authenticated  Whether they are authenticated.
		 * @param  string|null  $pagenow  The value of WordPress's pagenow.
		 */
		$tag = apply_filters(
			"stellarwp/uplink/$hook_prefix/view/authorize_button/tag",
			'div',
			$authenticated,
			$pagenow
		);

		/**
		 * Filter the CSS classes
		 *
		 * @param  array  $classes  An array of CSS classes.
		 * @param  bool  $authenticated  Whether they are authenticated.
		 * @param  string|null  $pagenow  The value of WordPress's pagenow.
		 */
		$classes = (array) apply_filters(
			"stellarwp/uplink/$hook_prefix/view/authorize_button/classes",
			$classes,
			$authenticated,
			$pagenow
		);

		echo $this->view->render( self::VIEW, [
			'link_text' => $link_text,
			'url'       => $url,
			'target'    => $target,
			'tag'       => $tag,
			'classes'   => $this->classes( $classes ),
		] );
	}

	/**
	 * We assume this button is only displayed within wp-admin,
	 *
	 * Build the callback URL with the current URL the user is on.
	 */
	private function build_auth_url(): string {
		global $pagenow;

		if ( empty( $pagenow ) ) {
			return '';
		}

		$url = add_query_arg( $_GET, admin_url( $pagenow ) );

		return sprintf( '%s?%s',
			$this->auth_url,
			http_build_query( [
				'uplink_callback' => $this->nonce->create_url( $url ),
			] )
		);
	}

}
