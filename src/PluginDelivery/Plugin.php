<?php

namespace StellarWP\PluginDelivery;

class Plugin extends \tad_DI52_ServiceProvider {
	/**
	 * Binds and sets up implementations.
	 */
	public function register() {
		$this->register_hooks();
	}

	/**
	 * Registers all hooks.
	 */
	private function register_hooks() {
	}
}