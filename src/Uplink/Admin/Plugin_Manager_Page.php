<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Admin;

use StellarWP\Uplink\Uplink;

class Plugin_Manager_Page {

	/**
	 * Evaluates whether this Uplink instance is the highest version and should
	 * own rendering of the unified plugin manager page.
	 *
	 * Uses a shared, non-prefixed filter so every active Uplink instance
	 * participates in the version election regardless of vendor prefix.
	 *
	 * @since 3.0.0
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
	 * Registers the unified plugin manager page if this instance wins the version election.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function maybe_register_page(): void {
		if ( ! $this->should_render() ) {
			return;
		}

		do_action( 'stellarwp/uplink/unified_ui_registered' );

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
