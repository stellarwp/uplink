<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Admin;

use StellarWP\Uplink\Config;
use StellarWP\Uplink\Resources\Plugin;
use StellarWP\Uplink\Resources\Resource;
use StellarWP\Uplink\Resources\Service;

class License_Field extends Field {

	public const LICENSE_FIELD_ID = 'stellarwp_uplink_license';

	/**
	 * @var string
	 */
	protected $path = '/admin-views/fields/settings.php';

	/**
	 * @var Assets
	 */
	protected $assets;

	/**
	 * License_Field constructor.
	 *
	 * @param Group $group
	 * @param Assets $assets
	 */
	public function __construct( Group $group, Assets $assets ) {
		parent::__construct( $group );
		$this->assets = $assets;
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
				$this->group->get_name( sanitize_title( $resource->get_slug() ) )
			);

			register_setting(
				$this->group->get_name( sanitize_title( $resource->get_slug() ) ),
				$resource->get_license_object()->get_key_option_name()
			);

			add_settings_field(
				$resource->get_license_object()->get_key_option_name(),
				__( 'License Key', '%TEXTDOMAIN%' ),
				[ $this, 'field_html' ],
				$this->group->get_name( sanitize_title( $resource->get_slug() ) ),
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
		$this->assets->enqueue_assets();

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
}
