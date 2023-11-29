<?php

namespace StellarWP\Uplink\Admin;

use StellarWP\Uplink\Config;
use StellarWP\Uplink\Resources\Collection;
use StellarWP\Uplink\Resources\Plugin;

class License_Field extends Field {

	public const LICENSE_FIELD_ID = 'stellarwp_uplink_license';

	protected string $path = '/admin-views/fields/settings.php';

	/**
	 * @param Plugin $plugin
	 *
	 * @return string
	 */
	public function get_section_name( Plugin $plugin ) : string {
		return sprintf( '%s_%s', self::LICENSE_FIELD_ID, sanitize_title( $plugin->get_slug() ) );
	}

	/**
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_settings() {
		$collection = Config::get_container()->get( Collection::class );
		$plugin     = $collection->current();

		if ( ! $plugin ) {
			return;
		}

		add_settings_section(
			sprintf( '%s_%s', self::LICENSE_FIELD_ID, sanitize_title( $plugin->get_slug() ) ),
			'',
			[ $this, 'description' ], // @phpstan-ignore-line
			$this->get_group_name( sanitize_title( $plugin->get_slug() ) )
		);

		register_setting( $this->get_group_name( sanitize_title( $plugin->get_slug() ) ), $plugin->get_license_object()->get_key_option_name() );

		add_settings_field(
			$plugin->get_license_object()->get_key_option_name(),
			__( 'License Key', '%TEXTDOMAIN%' ),
			[ $this, 'field_html' ],
			$this->get_group_name( sanitize_title( $plugin->get_slug() ) ),
			$this->get_section_name( $plugin ),
			[
				'id'           => $plugin->get_license_object()->get_key_option_name(),
				'label_for'    => $plugin->get_license_object()->get_key_option_name(),
				'type'         => 'text',
				'path'         => $plugin->get_path(),
				'value'        => $plugin->get_license_key(),
				'placeholder'  => __( 'License Number', '%TEXTDOMAIN%' ),
				'html'         => $this->get_field_html( $plugin ),
				'html_classes' => 'stellarwp-uplink-license-key-field',
				'plugin'       => $plugin->get_path(),
			]
		);
	}

	/**
	 * @since 1.0.0
	 *
	 * @param Plugin $plugin
	 *
	 * @return string
	 */
	public function get_field_html( Plugin $plugin ) : string {
		$html = sprintf( '<p class="tooltip description">%s</p>', __( 'A valid license key is required for support and updates', '%TEXTDOMAIN%' ) );
		$html .= '<div class="license-test-results"><img src="' . esc_url( admin_url( 'images/wpspin_light.gif' ) ) . '" class="ajax-loading-license" alt="Loading" style="display: none"/>';
		$html .= '<div class="key-validity"></div></div>';

		return apply_filters( 'stellarwp/uplink/' . Config::get_hook_prefix() . '/license_field_html', $html, $plugin->get_slug() );
	}

	/**
	 * @inheritDoc
	 */
	public function render( bool $show_title = true, bool $show_button = true ) {
		$plugin = $this->get_plugin();

		if ( ! $plugin ) {
			return;
		}

		echo $this->get_content( [
			'plugin'      => $plugin,
			'show_title'  => $show_title,
			'show_button' => $show_button,
		] );
	}

	/**
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		$plugin = $this->get_plugin();

		if ( ! $plugin ) {
			return;
		}

		$handle = sprintf( 'stellarwp-uplink-license-admin-%s', Config::get_hook_prefix() );
		$path   = preg_replace( '/.*\/vendor/', plugin_dir_url( $plugin->get_path() ) . 'vendor', dirname( __DIR__, 2 ) );
		$js_src = apply_filters( 'stellarwp/uplink/' . Config::get_hook_prefix() . '/admin_js_source', $path . '/assets/js/key-admin.js' );
		wp_register_script( $handle, $js_src, [ 'jquery' ], '1.0.0', true );
		wp_enqueue_script( $handle );
		$action_postfix = Config::get_hook_prefix_underscored();
		wp_localize_script( $handle, sprintf( 'stellarwp_config_%s', $action_postfix ), [ 'action' => sprintf( 'pue-validate-key-uplink-%s', $action_postfix ) ] );

		$css_src = apply_filters( 'stellarwp/uplink/' . Config::get_hook_prefix() . '/admin_css_source', $path . '/assets/css/main.css' );
		wp_enqueue_style( $handle, $css_src );
	}
}
