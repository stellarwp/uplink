<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Admin;

use StellarWP\Uplink\Uplink;

/**
 * Manages the unified feature manager admin page.
 *
 * @since TBD
 *
 * @package StellarWP\Uplink
 */
class Feature_Manager_Page {

	/**
	 * Hook suffix returned by add_menu_page().
	 * Empty string until the page is registered.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	private string $page_hook = '';

	/**
	 * Evaluates whether this Uplink instance is the highest version and should
	 * own rendering of the unified feature manager page.
	 *
	 * Uses a shared, non-prefixed filter so every active Uplink instance
	 * participates in the version election regardless of vendor prefix.
	 *
	 * @since TBD
	 *
	 * @return bool
	 */
	public function should_render(): bool {
		$highest = apply_filters( 'stellarwp/uplink/highest_version', '0.0.0' );

		if ( version_compare( Uplink::VERSION, $highest, '<' ) ) {
			return false;
		}

		if ( did_action( 'stellarwp/uplink/unified_ui_registered' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Registers the unified feature manager page if this instance wins the version election.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function maybe_register_page(): void {
		if ( ! $this->should_render() ) {
			return;
		}

		do_action( 'stellarwp/uplink/unified_ui_registered' );

		$this->page_hook = add_menu_page(
			__( 'Liquid Web Software', '%TEXTDOMAIN%' ),
			__( 'LW Software', '%TEXTDOMAIN%' ),
			'manage_options',
			'lws-feature-manager',
			[ $this, 'render' ],
			'dashicons-admin-network',
			81
		);

		add_action( 'admin_enqueue_scripts', [ $this, 'maybe_enqueue_assets' ] );
	}

	/**
	 * Enqueues the React Feature Manager UI assets only on the lws-feature-manager page.
	 *
	 * Called on admin_enqueue_scripts. The hook suffix is compared against
	 * $this->page_hook — the value returned by add_menu_page() — to ensure
	 * the React bundle is loaded only on this specific admin page.
	 *
	 * @since TBD
	 *
	 * @param string $hook_suffix Current admin page hook suffix.
	 *
	 * @return void
	 */
	public function maybe_enqueue_assets( string $hook_suffix ): void {
		if ( $hook_suffix !== $this->page_hook ) {
			return;
		}

		$this->enqueue_assets();
	}

	/**
	 * Registers and enqueues the React Feature Manager UI JS and CSS.
	 *
	 * Loads from build-dev/ when WP_DEBUG is true (source maps included),
	 * from build/ otherwise (minified, no source maps).
	 *
	 * Path resolution from this file:
	 *   __DIR__                               → src/Uplink/Admin
	 *   dirname(__DIR__)                      → src/Uplink
	 *   dirname(dirname(__DIR__))             → src
	 *   dirname(dirname(dirname(__DIR__)))    → plugin root (uplink/)
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	private function enqueue_assets(): void {
		$build_dir       = ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ? 'build-dev' : 'build';
		$plugin_root_url = trailingslashit(
			plugin_dir_url( dirname( dirname( dirname( __DIR__ ) ) ) . '/index.php' )
		);
		$handle = 'stellarwp-uplink-ui';

		wp_register_script(
			$handle,
			$plugin_root_url . $build_dir . '/index.js',
			[ 'wp-element' ],  // wp-element provides React + ReactDOM from WP Core
			null,              // null = no ?ver= query string; cache busting via contenthash
			[ 'in_footer' => true ]
		);

		wp_localize_script(
			$handle,
			'uplinkData',
			[
				'restUrl' => rest_url( 'uplink/v1/' ),
				'nonce'   => wp_create_nonce( 'wp_rest' ),
			]
		);

		wp_register_style(
			$handle,
			$plugin_root_url . $build_dir . '/index.css',
			[],
			null
		);

		wp_enqueue_script( $handle );
		wp_enqueue_style( $handle );
	}

	/**
	 * Renders the unified feature manager page.
	 *
	 * Outputs the React application mount point. The React bundle
	 * (index.js + index.css) is registered and enqueued by enqueue_assets(),
	 * called via maybe_enqueue_assets() on admin_enqueue_scripts.
	 *
	 * The .uplink-ui class activates CSS scoping for Tailwind styles,
	 * preventing conflicts with WordPress Admin global styles.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function render(): void {
		?>
		<div class="wrap">
			<div id="uplink-root" class="uplink-ui"></div>
		</div>
		<?php
	}
}
