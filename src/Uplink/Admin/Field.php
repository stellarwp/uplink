<?php

namespace StellarWP\Uplink\Admin;

abstract class Field {

    public const STELLARWP_UPLINK_GROUP = 'stellarwp_uplink_group';

    abstract public function register_settings(): void;

    /**
     * @param array $args
     */
    public function get_description( array $args ): void {
        if ( empty( $args['description'] ) ) {
            return;
        }

        printf(
            '<p class="regular-text">%s</p>',
            esc_html( $args['description'] )
        );
    }

    /**
     * @param array $args
     */
    public function get_html_content( array $args ): void {
        if ( empty( $args['html'] ) ) {
            return;
        }

        printf( $args['html'] );
    }

    /**
     * @param string $group_modifier
     *
     * @return string
     */
    public static function get_group_name( string $group_modifier = '' ): string {
        return sprintf( '%s_%s', self::STELLARWP_UPLINK_GROUP, $group_modifier );
    }

    /**
     * @param array $args
     */
    public function field_html( array $args ): void {
        printf(
            '<fieldset class="stellarwp-uplink__settings-group">
				<input type="%1$s" id="%2$s" name="%2$s" value="%3$s" placeholder="%4$s" class="regular-text stellarwp-uplink__settings-field" data-js="" />
			</fieldset>',
            esc_attr( $args['type'] ),
            esc_attr( $args['id'] ),
            esc_attr( $args['value'] ),
            esc_attr( $args['placeholder'] )
        );

        $this->get_description( $args );
        $this->get_html_content( $args );
    }

}
