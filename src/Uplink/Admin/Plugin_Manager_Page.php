<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Admin;

use StellarWP\Uplink\Utils\Version;

class Plugin_Manager_Page {

	/**
	 * Registers the unified plugin manager page if this instance is the version leader.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function maybe_register_page(): void {
		if ( ! Version::should_handle( 'admin_page' ) ) {
			return;
		}

		add_menu_page(
			__( 'StellarWP Licenses', '%TEXTDOMAIN%' ),
			__( 'StellarWP', '%TEXTDOMAIN%' ),
			'manage_options',
			'stellarwp-licenses',
			[ $this, 'render' ],
			'dashicons-admin-network',
			81
		);
	}

	/**
	 * Renders the unified plugin manager page.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function render(): void {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'StellarWP Licenses', '%TEXTDOMAIN%' ); ?></h1>
		</div>
		<?php
	}
}
