<?php

namespace StellarWP\Uplink\Admin;


class Settings extends Page {

    protected string $path = '/resources/views/settings.php';

    public function add_admin_pages() {
        $this->maybe_register_settings_page();
    }

    public function maybe_register_settings_page() {
        $this->register_page(
            [
                'id'       => 'uplink-licensing',
                'parent'   => 'options-general.php',
                'path'     => 'uplink-licensing',
                'title'    => esc_html__( 'Uplink Licensing', 'stellarwp-uplink' ),
                'icon'     => $this->get_menu_icon(),
                'position' => 6,
            ]
        );
    }

    public function render_page() {
        echo $this->get_content( [] );
    }

}
