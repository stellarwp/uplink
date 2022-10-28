<?php

namespace StellarWP\Uplink\Admin;

class Settings extends Page {

	protected string $path = '/resources/views/settings.php';

	public function add_admin_pages(): void {
		$plugin = $this->get_plugin();

		$this->register_page(
			[
				'id'       => sanitize_title( $plugin->get_name() ),
				'parent'   => 'options-general.php',
				'path'     => sanitize_title( $plugin->get_name() ),
				'title'    => esc_html__( sprintf( '%s Licensing', $plugin->get_name() ), 'stellarwp-uplink' ),
				'icon'     => $this->get_menu_icon(),
				'position' => 6,
			]
		);
	}

	public function maybe_add_network_settings_page(): void {
		$plugin = $this->get_plugin();

		if ( ! is_plugin_active_for_network( $plugin->get_path() ) ) {
			return;
		}

		$this->register_page(
			[
				'id'         => sanitize_title( $plugin->get_name() ),
				'parent'     => 'settings.php',
				'path'       => sanitize_title( $plugin->get_name() ),
				'title'      => esc_html__( sprintf( '%s Licensing', $plugin->get_name() ), 'stellarwp-uplink' ),
				'icon'       => $this->get_menu_icon(),
				'capability' => 'manage_network_options',
				'position'   => 6,
			]
		);
	}

	public function render_page(): void {
		echo $this->get_content( [] );
	}

}
