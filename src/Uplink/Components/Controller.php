<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Components;

use League\Plates\Engine;

abstract class Controller {

	/**
	 * The Plates View Engine.
	 *
	 * @var Engine
	 */
	protected $view;

	/**
	 * Echo the plates view.
	 *
	 * @param mixed[] $args An optional array of arguments to utilize when rendering.
	 */
	abstract public function render( array $args = [] ): void;

	/**
	 * @param  Engine  $view
	 */
	public function __construct( Engine $view ) {
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

}
