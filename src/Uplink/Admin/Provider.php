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
        $this->container->singleton( Settings::class, Settings::class );
        $this->container->singleton( License_Field::class, License_Field::class );
		$this->container->singleton( Notice::class, Notice::class );

		$this->register_hooks();
	}

	public function register_hooks(): void {
		add_action( 'admin_enqueue_scripts', $this->container->callback( Plugins_Page::class, 'display_plugin_messages' ) );
        add_action( 'admin_menu', $this->container->callback( Settings::class, 'add_admin_pages' ), 11 );
        add_action( 'network_admin_menu', $this->container->callback( Settings::class, 'maybe_add_network_settings_page' ) );
        add_action( 'admin_init', $this->container->callback( License_Field::class, 'register_settings' ) );
		add_action( 'admin_notices', $this->container->callback( Notice::class, 'setup_notices' ) );
	}
}
