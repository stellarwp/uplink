<?php

namespace StellarWP\Uplink\Admin;

class Provider extends \tad_DI52_ServiceProvider {
	/**
	 * Register the service provider.
	 *
	 * @since 1.0.0
	 */
	public function register() {
		$this->container->singleton( Plugins_Page::class, Plugins_Page::class );

		$this->register_hooks();
	}

	public function register_hooks() {
		add_action( 'admin_enqueue_scripts', $this->container->callback( Plugins_Page::class, 'display_plugin_messages' ) );
	}
}
