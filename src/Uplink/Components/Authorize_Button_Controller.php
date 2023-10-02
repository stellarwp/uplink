<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Components;

use League\Plates\Engine;
use StellarWP\Uplink\Auth\Admin\Disconnect_Controller;
use StellarWP\Uplink\Auth\Authorizer;
use StellarWP\Uplink\Auth\Nonce;
use StellarWP\Uplink\Auth\Token\Contracts\Token_Manager;
use StellarWP\Uplink\Config;

final class Authorize_Button_Controller extends Controller {

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

	public function __construct(
		Engine $view,
		Authorizer $authorizer,
		Token_Manager $token_manager,
		Nonce $nonce
	) {
		parent::__construct( $view );

		$this->authorizer    = $authorizer;
		$this->token_manager = $token_manager;
		$this->nonce         = $nonce;
	}

	/**
	 * Render the authorize-button view.
	 *
	 * @see src/views/admin/authorize-button.php
	 */
	public function render(): void {
		global $pagenow;

		$authenticated = false;
		$target        = '_blank';
		$link_text     = __( 'Connect', '%TEXTDOMAIN%' );
		$url           = $this->build_auth_url();
		$classes       = [
			'uplink-authorize',
			'not-authorized'
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

	private function build_auth_url(): string {
		return sprintf( '%s?%s',
			'https://kadencewp.com/authorize', // TODO: This needs to come from Licensing /api/stellarwp/v3/tokens/auth_url
			http_build_query( [
				'uplink_callback' => $this->nonce->create_url( rest_url( '/uplink/v1/webhooks/receive-auth' ) ),
			] )
		);
	}

}
