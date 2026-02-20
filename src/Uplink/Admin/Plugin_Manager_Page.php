<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Admin;

use StellarWP\Uplink\Uplink;

class Plugin_Manager_Page {

	/**
	 * Whether this instance should render the unified plugin manager page.
	 *
	 * Determined at admin_menu time by comparing this instance's Uplink version
	 * against the highest version reported by all active instances.
	 *
	 * @since 3.0.0
	 *
	 * @var bool
	 */
	private $should_render = false;

	/**
	 * Evaluates whether this Uplink instance is the highest version and should
	 * own rendering of the unified plugin manager page.
	 *
	 * Uses a shared, non-prefixed filter so every active Uplink instance
	 * participates in the version election regardless of vendor prefix.
	 *
	 * @since 3.0.0
	 *
	 * @return bool
	 */
	public function should_render(): bool {
		$highest = apply_filters( 'stellarwp/uplink/highest_version', '0.0.0' );

		if ( version_compare( Uplink::VERSION, $highest, '<' ) ) {
			return false;
		}

		if ( did_action( 'stellarwp/uplink/unified_ui_registered' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Registers the unified plugin manager page if this instance wins the version election.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function maybe_register_page(): void {
		if ( ! $this->should_render() ) {
			return;
		}

		$this->should_render = true;

		do_action( 'stellarwp/uplink/unified_ui_registered' );

		add_menu_page(
			__( 'StellarWP Licenses', '%TEXTDOMAIN%' ),
			__( 'StellarWP', '%TEXTDOMAIN%' ),
			'manage_options',
			'stellarwp-licenses',
			[ $this, 'render' ],
			'dashicons-admin-network',
			81
		);
	}

	/**
	 * Discovers all StellarWP resources by querying wp_options for
	 * update status entries stored by any Uplink instance.
	 *
	 * @since 3.0.0
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function discover_resources(): array {
		global $wpdb;

		$prefix = 'stellarwp_uplink_update_status_';

		// @phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s",
				$wpdb->esc_like( $prefix ) . '%'
			)
		);

		$resources = [];

		if ( ! $rows ) {
			return $resources;
		}

		$site_domain = wp_parse_url( get_option( 'siteurl', '' ), PHP_URL_HOST ) ?: '';

		foreach ( $rows as $row ) {
			$slug          = substr( $row->option_name, strlen( $prefix ) );
			$update_status = maybe_unserialize( $row->option_value );

			if ( ! is_object( $update_status ) ) {
				continue;
			}

			$name    = $update_status->update->name ?? $slug;
			$version = $update_status->checked_version ?? '';

			$key_status_option = 'stellarwp_uplink_license_key_status_' . $slug . '_' . $site_domain;

			$resources[ $slug ] = [
				'slug'    => $slug,
				'name'    => $name,
				'version' => $version,
				'key'     => get_option( 'stellarwp_uplink_license_key_' . $slug, '' ),
				'status'  => get_option( $key_status_option, 'unknown' ),
			];
		}

		return $resources;
	}

	/**
	 * Handles form submissions from the unified plugin manager page.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function handle_submission(): void {
		if ( ! $this->should_render ) {
			return;
		}

		if ( empty( $_POST['stellarwp_uplink_unified_nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( $_POST['stellarwp_uplink_unified_nonce'], 'stellarwp_uplink_unified_save' ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$slug = sanitize_text_field( wp_unslash( $_POST['stellarwp_uplink_slug'] ?? '' ) );
		$key  = sanitize_text_field( wp_unslash( $_POST['stellarwp_uplink_key'] ?? '' ) );

		if ( empty( $slug ) ) {
			return;
		}

		if ( ! empty( $key ) ) {
			apply_filters( 'stellarwp/uplink/set_license_key', false, $slug, $key, 'local' );
		} else {
			apply_filters( 'stellarwp/uplink/delete_license_key', false, $slug, 'local' );
		}

		wp_safe_redirect( add_query_arg( [
			'page'    => 'stellarwp-licenses',
			'updated' => '1',
		], admin_url( 'admin.php' ) ) );

		exit;
	}

	/**
	 * Renders the unified plugin manager page.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function render(): void {
		$resources = $this->discover_resources();

		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'StellarWP Licenses', '%TEXTDOMAIN%' ) . '</h1>';

		if ( ! empty( $_GET['updated'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>';
			echo esc_html__( 'License updated.', '%TEXTDOMAIN%' );
			echo '</p></div>';
		}

		if ( empty( $resources ) ) {
			echo '<p>' . esc_html__( 'No StellarWP products found.', '%TEXTDOMAIN%' ) . '</p>';
			echo '</div>';
			return;
		}

		echo '<table class="widefat fixed striped" style="max-width:800px;margin-top:20px;">';
		echo '<thead><tr>';
		echo '<th style="width:30%;">' . esc_html__( 'Product', '%TEXTDOMAIN%' ) . '</th>';
		echo '<th style="width:10%;">' . esc_html__( 'Version', '%TEXTDOMAIN%' ) . '</th>';
		echo '<th style="width:40%;">' . esc_html__( 'License Key', '%TEXTDOMAIN%' ) . '</th>';
		echo '<th style="width:20%;">' . esc_html__( 'Status', '%TEXTDOMAIN%' ) . '</th>';
		echo '</tr></thead>';
		echo '<tbody>';

		foreach ( $resources as $slug => $resource ) {
			$status_label = $this->get_status_label( $resource['status'] ?? 'unknown' );
			$status_class = $this->get_status_class( $resource['status'] ?? 'unknown' );
			$display_name = $resource['name'] ?? $slug;
			$version      = $resource['version'] ?? '';
			$key          = $resource['key'] ?? '';

			echo '<tr>';
			echo '<td><strong>' . esc_html( $display_name ) . '</strong></td>';
			echo '<td>' . esc_html( $version ) . '</td>';
			echo '<td>';
			echo '<form method="post" style="display:flex;gap:8px;align-items:center;">';
			wp_nonce_field( 'stellarwp_uplink_unified_save', 'stellarwp_uplink_unified_nonce' );
			echo '<input type="hidden" name="stellarwp_uplink_slug" value="' . esc_attr( $slug ) . '">';
			echo '<input type="text" name="stellarwp_uplink_key" value="' . esc_attr( $key ) . '" class="regular-text" style="flex:1;">';
			echo '<button type="submit" class="button button-secondary">' . esc_html__( 'Save', '%TEXTDOMAIN%' ) . '</button>';
			echo '</form>';
			echo '</td>';
			echo '<td><span class="' . esc_attr( $status_class ) . '">' . esc_html( $status_label ) . '</span></td>';
			echo '</tr>';
		}

		echo '</tbody></table>';
		echo '</div>';
	}

	/**
	 * @since 3.0.0
	 */
	private function get_status_label( string $status ): string {
		$labels = [
			'valid'   => __( 'Valid', '%TEXTDOMAIN%' ),
			'invalid' => __( 'Invalid', '%TEXTDOMAIN%' ),
			'expired' => __( 'Expired', '%TEXTDOMAIN%' ),
		];

		return $labels[ $status ] ?? __( 'Unknown', '%TEXTDOMAIN%' );
	}

	/**
	 * @since 3.0.0
	 */
	private function get_status_class( string $status ): string {
		$classes = [
			'valid'   => 'stellarwp-status stellarwp-status--valid',
			'invalid' => 'stellarwp-status stellarwp-status--invalid',
			'expired' => 'stellarwp-status stellarwp-status--expired',
		];

		return $classes[ $status ] ?? 'stellarwp-status stellarwp-status--unknown';
	}
}
