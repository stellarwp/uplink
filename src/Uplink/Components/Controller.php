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
	 */
	abstract public function render(): void;

	/**
	 * @param  Engine  $view
	 */
	public function __construct( Engine $view ) {
		$this->view = $view;
	}

}
