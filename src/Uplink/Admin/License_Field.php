<?php

namespace StellarWP\Uplink\Admin;

use StellarWP\Uplink\Container;
use StellarWP\Uplink\Resources\Collection;
use StellarWP\Uplink\Resources\Plugin;

class License_Field extends Field {

    public const LICENSE_FIELD_ID = 'stellarwp_uplink_license';

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
            __( 'License Number', 'stellarwp_uplink' ),
            [ $this, 'field_html' ],
            self::get_group_name( sanitize_title( $plugin->get_name() ) ),
            $this->get_section_name( $plugin ),
            [
                'id'          => $plugin->get_license_object()->get_key_option_name(),
                'label_for'   => $plugin->get_license_object()->get_key_option_name(),
                'type'        => 'text',
                'value'       => $plugin->get_license_key(),
                'placeholder' => __( 'License Number', 'stellarwp_uplink' ),
                'html'        => '',
            ]
        );
    }

}
