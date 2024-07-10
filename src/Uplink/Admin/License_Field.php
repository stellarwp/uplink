<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Admin;

use StellarWP\Uplink\Config;
use StellarWP\Uplink\Resources\Plugin;
use StellarWP\Uplink\Resources\Resource;
use StellarWP\Uplink\Resources\Service;
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

	/**
	 * Constructor. Initializes handle.
	 */
	public function __construct() {
		$this->handle = sprintf( 'stellarwp-uplink-license-admin-%s', Config::get_hook_prefix() );
	}

	/**
	 * @param Plugin|Service|Resource $plugin
	 *
	 * @return string
	 */
	public static function get_section_name( $plugin ) : string {
		return sprintf( '%s_%s', self::LICENSE_FIELD_ID, sanitize_title( $plugin->get_slug() ) );
	}

	/**
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_settings(): void {
		foreach ( $this->get_resources() as $resource ) {
			add_settings_section(
				self::get_section_name( $resource ),
				'',
				[ $this, 'description' ], // @phpstan-ignore-line
				$this->get_group_name( sanitize_title( $resource->get_slug() ) )
			);

			register_setting(
				$this->get_group_name( sanitize_title( $resource->get_slug() ) ),
				$resource->get_license_object()->get_key_option_name()
			);

			add_settings_field(
				$resource->get_license_object()->get_key_option_name(),
				__( 'License Key', '%TEXTDOMAIN%' ),
				[ $this, 'field_html' ],
				$this->get_group_name( sanitize_title( $resource->get_slug() ) ),
				self::get_section_name( $resource ),
				[
					'id'           => $resource->get_license_object()->get_key_option_name(),
					'label_for'    => $resource->get_license_object()->get_key_option_name(),
					'type'         => 'text',
					'path'         => $resource->get_path(),
					'value'        => $resource->get_license_key(),
					'placeholder'  => __( 'License Number', '%TEXTDOMAIN%' ),
					'html'         => $this->get_field_html( $resource ),
					'html_classes' => 'stellarwp-uplink-license-key-field',
					'plugin'       => $resource->get_path(),
					'plugin_slug'  => $resource->get_slug(),
				]
			);
		}
	}

	/**
	 * @since 1.0.0
	 *
	 * @param Plugin|Service|Resource $plugin
	 *
	 * @return string
	 */
	public function get_field_html( $plugin ) : string {
		$html = sprintf( '<p class="tooltip description">%s</p>', __( 'A valid license key is required for support and updates', '%TEXTDOMAIN%' ) );
		$html .= '<div class="license-test-results"><img src="' . esc_url( admin_url( 'images/wpspin_light.gif' ) ) . '" class="ajax-loading-license" alt="Loading" style="display: none"/>';
		$html .= '<div class="key-validity"></div></div>';

		return apply_filters( 'stellarwp/uplink/' . Config::get_hook_prefix() . '/license_field_html', $html, $plugin->get_slug() );
	}

	/**
	 * Renders a license field for all registered Resources.
	 *
	 * @inheritDoc
	 */
	public function render( bool $show_title = true, bool $show_button = true ): void {
		foreach ( $this->get_resources() as $resource ) {
			$this->render_single( $resource->get_slug(), $show_title, $show_button );
		}
	}

	/**
	 * Renders a single resource's license field.
	 *
	 * @param string $plugin_slug The plugin slug to render.
	 * @param bool $show_title Whether to show the title or not.
	 * @param bool $show_button Whether to show the submit button or not.
	 *
	 * @return void
	 */
	public function render_single( string $plugin_slug, bool $show_title = true, bool $show_button = true ): void {
		$this->enqueue_assets();

		$resource = $this->get_resources()->offsetGet( $plugin_slug );

		if ( ! $resource ) {
			return;
		}

		echo $this->get_content( [
			'plugin'      => $resource,
			'show_title'  => $show_title,
			'show_button' => $show_button,
		] );
	}


	/**
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_assets(): void {
		$path   = Config::get_container()->get( Uplink::UPLINK_ASSETS_URI );
		$js_src = apply_filters( 'stellarwp/uplink/' . Config::get_hook_prefix() . '/admin_js_source', $path . '/js/key-admin.js' );
		wp_register_script( $this->handle, $js_src, [ 'jquery' ], '1.0.0', true );

		$action_postfix = Config::get_hook_prefix_underscored();
		wp_localize_script( $this->handle, sprintf( 'stellarwp_config_%s', $action_postfix ), [ 'action' => sprintf( 'pue-validate-key-uplink-%s', $action_postfix ) ] );

		$css_src = apply_filters( 'stellarwp/uplink/' . Config::get_hook_prefix() . '/admin_css_source', $path . '/css/main.css' );
		wp_register_style( $this->handle, $css_src );
	}

	/**
	 * Enqueue the registered scripts and styles, only when rendering fields.
	 *
	 * @return void
	 */
	public function enqueue_assets(): void {
		wp_enqueue_script( $this->handle );
		wp_enqueue_style( $this->handle );
	}

}
