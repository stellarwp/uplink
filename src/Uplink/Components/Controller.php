<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Components;

use StellarWP\Uplink\View\Contracts\View;

/**
 * Component/View controller made to accept arguments and render
 * them in a view file.
 */
abstract class Controller {

	/**
	 * The View Engine to render views.
	 *
	 * @var View
	 */
	protected $view;

	/**
	 * Render the view file.
	 *
	 * @param  mixed[]  $args  An optional array of arguments to utilize when rendering.
	 */
	abstract public function render( array $args = [] ): void;

	/**
	 * @param  View  $view  The View Engine to render views.
	 */
	public function __construct( View $view ) {
		$this->view = $view;
	}

	/**
	 * Format an array of CSS classes into a string.
	 *
	 * @param  array  $classes
	 *
	 * @return string
	 */
	protected function classes( array $classes ): string {
		if ( ! $classes ) {
			return '';
		}

		$classes = array_unique( array_map( 'sanitize_html_class', array_filter( $classes ) ) );

		return implode( ' ', $classes );
	}

	/**
	 * Format data attributes, escaped and ready for output in the view.
	 *
	 * @param  array<string, mixed>  $attributes An array of attributes indexed by their name.
	 *
	 * @return string
	 */
	protected function data_attr( array $attributes ): string {
		if ( ! $attributes ) {
			return '';
		}

		$formatted = [];

		foreach ( $attributes as $name => $value ) {
			$formatted[] = sprintf(
				'data-%s="%s"',
				esc_html( sanitize_html_class( $name ) ),
				esc_attr( $value )
			);
		}

		return implode( ' ', $formatted );
	}

}
