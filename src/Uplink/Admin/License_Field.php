<?php

namespace StellarWP\Uplink\Admin;

use StellarWP\Uplink\Config;
use StellarWP\Uplink\Resources\Collection;
use StellarWP\Uplink\Resources\Plugin;

class License_Field extends Field {

	public const LICENSE_FIELD_ID = 'stellar_uplink_license';

	protected string $path = '/resources/views/fields/settings.php';

	/**
	 * @param Plugin $plugin
	 *
	 * @return string
	 */
	public function get_section_name( Plugin $plugin ): string {
		return sprintf( '%s_%s', self::LICENSE_FIELD_ID, sanitize_title( $plugin->get_name() ) );
	}

	public function register_settings(): void {
		$collection = Config::get_container()->get( Collection::class );
		$plugin     = $collection->current();

		add_settings_section(
			sprintf( '%s_%s', self::LICENSE_FIELD_ID, sanitize_title( $plugin->get_name() ) ),
			'',
			[ $this, 'description' ], // @phpstan-ignore-line
			$this->get_group_name( sanitize_title( $plugin->get_name() ) )
		);

		register_setting( $this->get_group_name( sanitize_title( $plugin->get_name() ) ), $plugin->get_license_object()->get_key_option_name() );

		add_settings_field(
			$plugin->get_license_object()->get_key_option_name(),
			__( 'License Key', '%stellar-uplink-domain%' ),
			[ $this, 'field_html' ],
			$this->get_group_name( sanitize_title( $plugin->get_name() ) ),
			$this->get_section_name( $plugin ),
			[
				'id'           => $plugin->get_license_object()->get_key_option_name(),
				'label_for'    => $plugin->get_license_object()->get_key_option_name(),
				'type'         => 'text',
				'path'         => $plugin->get_path(),
				'value'        => $plugin->get_license_key(),
				'placeholder'  => __( 'License Number', '%stellar-uplink-domain%' ),
				'html'         => $this->get_field_html( $plugin ),
				'html_classes' => 'stellar-uplink-license-key-field',
				'plugin'       => $plugin->get_path()
			]
		);
	}

	/**
	 * @param Plugin $plugin
	 * @return string
	 */
	public function get_field_html( Plugin $plugin ): string {
		$html = sprintf( '<p class="tooltip description">%s</p>', __( 'A valid license key is required for support and updates', '%stellar-uplink-domain%') );
		$html .= '<div class="license-test-results"><img src="' . esc_url( admin_url( 'images/wpspin_light.gif' ) ) . '" class="ajax-loading-license" alt="Loading" style="display: none"/>';
		$html .= '<div class="key-validity"></div></div>';

		return apply_filters( 'stellar_uplink_' . Config::get_hook_prefix(). 'license_field_html', $html, $plugin->get_slug() );
	}

	public function render(): void {
		echo $this->get_content( [
			'plugin' => $this->get_plugin()
		] );
	}

	public function enqueue_assets(): void{
		$handle = 'stellar-uplink-license-admin';
		$path   = preg_replace( '/.*\/vendor/', plugin_dir_url( $this->get_plugin()->get_path() ) . 'vendor', dirname( __DIR__, 2 ) );
		$js_src    = apply_filters( 'stellar_uplink_' . Config::get_hook_prefix(). 'admin_js_source', $path .  '/resources/js/key-admin.js' );

		wp_register_script( $handle, $js_src, [ 'jquery' ], '1.0.0', true );
		wp_enqueue_script( $handle );

		$css_src    = apply_filters( 'stellar_uplink_' . Config::get_hook_prefix(). 'admin_css_source', $path .  '/resources/css/main.css' );
		wp_enqueue_style( 'stellar-uplink-license-admin', $css_src );
	}

}
