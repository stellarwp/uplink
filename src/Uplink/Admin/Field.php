<?php

namespace StellarWP\Uplink\Admin;

use StellarWP\Uplink\Container;
use StellarWP\Uplink\Resources\Collection;

abstract class Field {

    public const STELLARWP_UPLINK_GROUP = 'stellar_uplink_group';

    /**
     * Path to page template
     *
     * @since 1.0.0
     *
     * @var string
     */
    protected string $path = '';

    /**
     * Container.
     *
     * @since 1.0.0
     *
     * @var Container
     */
    protected $container;

    /**
     * Constructor.
     *
     * @since 1.0.0
     *
     * @param Container $container DI Container.
     */
    public function __construct( Container $container = null ) {
        $this->container = $container ?: Container::init();
    }

    abstract public function register_settings(): void;

    /**
     * @param array<string> $args
     */
    public function get_description( array $args = [] ): void {
        if ( empty( $args['description'] ) ) {
            return;
        }

        printf(
            '<p class="regular-text">%s</p>',
            esc_html( $args['description'] )
        );
    }

    /**
     * @param array<string> $args
     */
    public function get_html_content( array $args = [] ): void {
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
     * @param array<string> $args
     */
    public function field_html( array $args = [] ): void {
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

    abstract public function render(): void;

    /**
     * @param array<mixed> $context
     *
     * @return false|string
     */
    protected function get_content( array $context = [] ) {
        extract( $context );
        ob_start();
        include $this->get_path();

        return ob_get_clean();
    }

    /**
     * @return string
     */
    public function get_path(): string {
        return apply_filters( 'stellar_uplink_field-template_path', dirname( __DIR__, 2 ) . $this->path, $this->path );
    }

    /**
     * @return false|mixed
     */
    protected function get_plugin() {
        $collection = Container::init()->make( Collection::class );

        return $collection->current();
    }
}
