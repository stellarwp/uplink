<?php

namespace StellarWP\Uplink\Admin;

use StellarWP\Uplink\Config;
use StellarWP\Uplink\Resources\Collection;
use StellarWP\Uplink\Resources\Plugin;
use StellarWP\Uplink\Uplink;

class License_Field extends Field {

	public const LICENSE_FIELD_ID = 'stellarwp_uplink_license';

	/**
	 * @var string
	 */
	protected $path = '/admin-views/fields/settings.php';

	/**
	 * The script and style handle when registering assets for this field.
	 *
	 * @var string
	 */
	private $handle;

	public function __construct() {
		$this->handle = sprintf( 'stellarwp-uplink-license-admin-%s', Config::get_hook_prefix() );
	}

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
	public function register_settings(): void {
		$collection = Config::get_container()->get( Collection::class );
		$plugin     = $collection->current();

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
	public function render( bool $show_title = true, bool $show_button = true ): void {
		wp_enqueue_script( $this->handle );
		wp_enqueue_style( $this->handle );

		echo $this->get_content( [
			'plugin'      => $this->get_plugin(),
			'show_title'  => $show_title,
			'show_button' => $show_button,
		] );
	}

	/**
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function enqueue_assets(): void {
		$path   = Config::get_container()->get( Uplink::UPLINK_ASSETS_URI );
		$js_src = apply_filters( 'stellarwp/uplink/' . Config::get_hook_prefix() . '/admin_js_source', $path . '/js/key-admin.js' );
		wp_register_script( $this->handle, $js_src, [ 'jquery' ], '1.0.0', true );

		$action_postfix = Config::get_hook_prefix_underscored();
		wp_localize_script( $this->handle, sprintf( 'stellarwp_config_%s', $action_postfix ), [ 'action' => sprintf( 'pue-validate-key-uplink-%s', $action_postfix ) ] );

		$css_src = apply_filters( 'stellarwp/uplink/' . Config::get_hook_prefix() . '/admin_css_source', $path . '/css/main.css' );
		wp_register_style( $this->handle, $css_src );
	}
}
