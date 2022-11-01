<?php

namespace StellarWP\Uplink\Admin;

use StellarWP\Uplink\Container;
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
        $collection = Container::init()->make( Collection::class );
        $plugin     = $collection->current();

        add_settings_section(
            sprintf( '%s_%s', self::LICENSE_FIELD_ID, sanitize_title( $plugin->get_name() ) ),
            '',
            [ $this, 'description' ], // @phpstan-ignore-line
            self::get_group_name( sanitize_title( $plugin->get_name() ) )
        );

        register_setting( self::get_group_name( sanitize_title( $plugin->get_name() ) ), $plugin->get_license_object()->get_key_option_name() );

        add_settings_field(
            $plugin->get_license_object()->get_key_option_name(),
            __( 'License Number', 'stellar_uplink' ),
            [ $this, 'field_html' ],
            self::get_group_name( sanitize_title( $plugin->get_name() ) ),
            $this->get_section_name( $plugin ),
            [
                'id'          => $plugin->get_license_object()->get_key_option_name(),
                'label_for'   => $plugin->get_license_object()->get_key_option_name(),
                'type'        => 'text',
                'value'       => $plugin->get_license_key(),
                'placeholder' => __( 'License Number', 'stellar_uplink' ),
                'html'        => '',
            ]
        );
    }

    public function render(): void {
        echo $this->get_content( [
            'plugin' => $this->get_plugin()
        ] );
    }

    public function submission(): void {
        $capability = apply_filters( 'stellar_uplink_submission_capability', 'manage_options' );

        if ( ! current_user_can( $capability ) ) {
            return;
        }

        $option = $this->get_plugin()->get_license_object()->get_key_option_name();
        $input  = filter_input( INPUT_POST, $option );

        /**
         * Allow modifying license field data before save
         */
        $input = apply_filters( 'stellar_uplink_before_submit', $input );

        update_option( $option, $input );

        do_action( 'stellar_uplink_after_submit' );
    }

}
