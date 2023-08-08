<?php

namespace StellarWP\Uplink\Admin;

use StellarWP\Uplink\Config;
use StellarWP\Uplink\Uplink;
use StellarWP\Uplink\Contracts\Abstract_Provider;

class Provider extends Abstract_Provider {
	/**
	 * Register the service provider.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register() {
		$this->container->singleton( Plugins_Page::class, Plugins_Page::class );
		$this->container->singleton( License_Field::class, License_Field::class );
		$this->container->singleton( Notice::class, Notice::class );
		$this->container->singleton( Ajax::class, Ajax::class );
		$this->container->singleton( Package_Handler::class, Package_Handler::class );
		$this->container->singleton( Update_Prevention::class, Update_Prevention::class );

		$this->register_hooks();
	}

	/**
	 * Register the hooks for the service provider.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_filter( 'plugins_api', [ $this, 'filter_plugins_api' ], 10, 3 );
		add_filter( 'pre_set_site_transient_update_plugins', [ $this, 'filter_pre_set_site_transient_update_plugins' ], 10, 1 );
		add_filter( 'upgrader_pre_download', [ $this, 'filter_upgrader_pre_download' ], 5, 4 );
		add_filter( 'upgrader_install_package_result', [ $this, 'filter_upgrader_install_package_result' ], 10, 2 );
		add_filter( 'upgrader_source_selection', [ $this, 'filter_upgrader_source_selection_for_update_prevention' ], 15, 4 );

		$action = sprintf( 'wp_ajax_pue-validate-key-uplink-%s', Config::get_hook_prefix_underscored() );
		add_action($action, [ $this, 'ajax_validate_license' ], 10, 0 );
		add_action( 'admin_init', [ $this, 'admin_init' ], 10, 0 );
		add_action( 'admin_enqueue_scripts', [ $this, 'display_plugin_messages' ], 1, 1 );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ], 10, 0 );
		add_action( 'admin_enqueue_scripts', [ $this, 'store_admin_notices' ], 10, 1 );
		add_action( 'admin_notices', [ $this, 'admin_notices' ], 10, 0 );
		add_action( 'load-plugins.php', [ $this, 'remove_default_update_message' ], 50, 0 );
	}

	/**
	 * Filter the plugins API response.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed               $result
	 * @param string              $action
	 * @param array<mixed>|object $args
	 *
	 * @return mixed
	 */
	public function filter_plugins_api( $result, $action, $args ) {
		return $this->container->get( Plugins_Page::class )->inject_info( $result, $action, $args );
	}

	/**
	 * Filter the plugins transient.
	 *
	 * @since 1.0.0
	 *
	 * @param object $transient
	 *
	 * @return mixed
	 */
	public function filter_pre_set_site_transient_update_plugins( $transient ) {
		return $this->container->get( Plugins_Page::class )->check_for_updates( $transient );
	}

	/**
	 * Validate the license.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function ajax_validate_license() {
		$this->container->get( Ajax::class )->validate_license();
	}

	/**
	 * Hooked to the admin_init action. Register the settings.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function admin_init() {
		$this->container->get( License_Field::class )->register_settings();
	}

	/**
	 * Hooked to the admin_enqueue_scripts action. Enqueues assets.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		$this->container->get( License_Field::class )->enqueue_assets();
	}

	/**
	 * Hooked to the admin_notices action. Sets up notices.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function admin_notices() {
		$this->container->get( Notice::class )->setup_notices();
	}

	/**
	 * Display plugin messages.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $page
	 *
	 * @return void
	 */
	public function display_plugin_messages( $page ) {
		$this->container->get( Plugins_Page::class )->display_plugin_messages( $page );
	}

	/**
	 * Store admin notices.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $page
	 *
	 * @return void
	 */
	public function store_admin_notices( $page ) {
		$this->container->get( Plugins_Page::class )->store_admin_notices( $page );
	}

	/**
	 * Remove the default inline update message for a plugin.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function remove_default_update_message() {
		$this->container->get( Plugins_Page::class )->remove_default_inline_update_msg();
	}

	/**
	 * Filter the upgrader pre download.
	 *
	 * @since 1.0.0
	 *
	 * @param bool         $reply      Whether to bail without returning the package.
	 *                                 Default false.
	 * @param string       $package    The package file name or URL.
	 * @param \WP_Upgrader $upgrader   The WP_Upgrader instance.
	 * @param array        $hook_extra Extra arguments passed to hooked filters.
	 *
	 * @return mixed
	 */
	public function filter_upgrader_pre_download( $reply, $package, $upgrader, $hook_extra ) {
		return $this->container->get( Package_Handler::class )->filter_upgrader_pre_download( $reply, $package, $upgrader, $hook_extra );
	}

	/**
	 * Filter the upgrader source selection to handle final destination dir name.
	 *
	 * @since 1.0.0
	 *
	 * @param array $result Final arguments for the result.
	 * @param array $extras Extra arguments passed to hooked filters.
	 *
	 * @return array
	 */
	public function filter_upgrader_install_package_result( $result, $extras ) {
		return $this->container->get( Package_Handler::class )->filter_upgrader_install_package_result( $result, $extras );
	}

	/**
	 * Filter the upgrader source selection to handle Update Prevention.
	 *
	 * @since 1.0.0
	 *
	 * @param string       $source        File source location.
	 * @param mixed        $remote_source Remote file source location.
	 * @param \WP_Upgrader $upgrader      WP_Upgrader instance.
	 * @param array        $extras        Extra arguments passed to hooked filters.
	 *
	 * @return string|\WP_Error
	 */
	public function filter_upgrader_source_selection_for_update_prevention( $source, $remote_source, $upgrader, $extras ) {
		return $this->container->get( Update_Prevention::class )->filter_upgrader_source_selection( $source, $remote_source, $upgrader, $extras );
	}
}
