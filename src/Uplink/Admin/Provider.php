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
		$this->container->singleton( License_Field::class, License_Field::class );
		$this->container->singleton( Notice::class, Notice::class );
		$this->container->singleton( Ajax::class, Ajax::class );

		$this->register_hooks();
	}

	public function register_hooks(): void {
		add_action( 'admin_init', $this->container->callback( License_Field::class, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts',  $this->container->callback( License_Field::class, 'enqueue_assets' ) );
		add_action( 'admin_notices', $this->container->callback( Notice::class, 'setup_notices' ) );
		add_action( 'wp_ajax_pue-validate-key-uplink' , $this->container->callback( Ajax::class, 'validate_license' ) );
		add_action( 'admin_enqueue_scripts', $this->container->callback( Plugins_Page::class, 'display_plugin_messages' ), 1 );
		add_action( 'admin_enqueue_scripts', $this->container->callback( Plugins_Page::class, 'store_admin_notices' ) );
		add_action( 'load-plugins.php', $this->container->callback( Plugins_Page::class, 'remove_default_inline_update_msg' ), 50 );

		add_filter( 'plugins_api', $this->container->callback( Plugins_Page::class, 'inject_info' ), 10, 3 );
		if ( ( ! defined( 'TRIBE_DISABLE_PUE' ) || true !== TRIBE_DISABLE_PUE ) ) {
			add_filter( 'pre_set_site_transient_update_plugins', $this->container->callback( Plugins_Page::class, 'check_for_updates' ) );
		}
	}
}
