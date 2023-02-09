<?php

namespace StellarWP\Uplink\Admin;

use StellarWP\ContainerContract\ContainerInterface;
use StellarWP\Uplink\Config;
use StellarWP\Uplink\Messages;
use StellarWP\Uplink\Resources\Collection;
use StellarWP\Uplink\Resources\Plugin;

class Plugins_Page {

	/**
	 * Storing the `plugin_notice` message.
	 *
	 * @var array<mixed>
	 */
	public array $plugin_notice = [];

	/**
	 * Displays messages on the plugins page in the dashboard.
	 *
	 * @since 1.0.0
	 *
	 * @param string $page
	 */
	public function display_plugin_messages( string $page ) {
		if ( 'plugins.php' !== $page ) {
			return;
		}

		$messages       = [];
		$plugin_file    = $this->get_plugin()->get_path();
		$plugin_updates = get_plugin_updates();
		$resource       = $plugin_updates[ $plugin_file ] ?? null;

		if ( empty( $resource ) ) {
			return;
		}

		if ( ! empty( $resource->update->license_error ) ) {
			$messages[] = $resource->update->license_error;
		} elseif ( current_user_can( 'update_plugins' ) ) {
			if ( empty( $resource->update->new_version ) ) {
				return;
			}
			// A plugin update is available
			$update_now = sprintf(
				esc_html__( 'Update now to version %s.', '%TEXTDOMAIN%' ),
				$resource->update->new_version
			);

			$href = sprintf(
				'%s&key=%s',
				$resource->update->package, // @phpstan-ignore-line
				$this->get_plugin()->get_license_key()
			);

			$update_now_link = sprintf(
				' <a href="%1$s" class="update-link">%2$s</a>',
				$href,
				$update_now
			);

			if ( ! empty ( $resource->update->upgrade_notice ) ) {
				$update_message = sprintf(
					esc_html__( '%1$s. %2$s', '%TEXTDOMAIN%' ),
					$resource->update->upgrade_notice,
					$update_now_link
				);
			} else {
				$update_message = sprintf(
					esc_html__( 'There is a new version of %1$s available. %2$s', '%TEXTDOMAIN%' ),
					$this->get_plugin()->get_name(),
					$update_now_link
				);
			}



			$messages[] = sprintf(
				'<p>%s</p>',
				$update_message
			);
		}

		if ( empty( $messages ) ) {
			return;
		}

		$message_row_html = '';

		foreach ( $messages as $message ) {
			$message_row_html .= sprintf(
				'<div class="update-message notice inline notice-warning notice-alt">%s</div>',
				$message
			);
		}

		$message_row_html = sprintf(
			'<tr class="plugin-update-tr active"><td colspan="4" class="plugin-update">%s</td></tr>',
			$message_row_html
		);

		$this->plugin_notice = [
			'slug'             => $this->get_plugin()->get_path(),
			'message_row_html' => $message_row_html,
		];

		add_filter( 'stellar_uplink_' . Config::get_hook_prefix(). 'plugin_notices', [ $this, 'add_notice_to_plugin_notices' ] );
	}

	/**
	 * @param array<mixed> $notices
	 *
	 * @return array<mixed>
	 */
	public function add_notice_to_plugin_notices( array $notices ): array {
		if ( ! $this->plugin_notice || $this->get_plugin()->is_network_licensed() ) {
			return $notices;
		}

		$notices[ $this->plugin_notice['slug'] ] = $this->plugin_notice;

		return $notices;
	}

	/**
	 * Add notices as JS variable
	 *
	 * @param string $page
	 */
	public function store_admin_notices( string $page ) {
		if ( 'plugins.php' !== $page ) {
			return;
		}
		$notices = apply_filters( 'stellar_uplink_' . Config::get_hook_prefix(). 'plugin_notices', [] );
		$path    = preg_replace( '/.*\/vendor/', plugin_dir_url( $this->get_plugin()->get_path() ) . 'vendor', dirname( __DIR__, 2 ) );
		$js_src  = apply_filters( 'stellar_uplink_' . Config::get_hook_prefix(). 'admin_js_source', $path .  '/resources/js/notices.js' );
		$handle  = 'stellar_uplink-notices';

		wp_register_script( $handle, $js_src, [ 'jquery' ], '1.0.0', true );
		wp_localize_script( $handle, 'stellar_uplink_plugin_notices', $notices );
		wp_enqueue_script( $handle );
	}

	/**
	 * Get the plugin message.
	 *
	 * @since 1.0.0
	 *
	 * @param Plugin $resource
	 *
	 * @return Messages\Message_Abstract|null
	 */
	public function get_plugin_message( Plugin $resource ) {
		return new Messages\Expired_Key();
	}

	/**
	 * Prevent the default inline update-available messages from appearing, as we
	 * have implemented our own
	 */
	public function remove_default_inline_update_msg() {
		remove_action( "after_plugin_row_{$this->get_plugin()->get_path()}", 'wp_plugin_update_row' );
	}

	/**
	 * @param mixed $transient
	 *
	 * @return mixed
	 */
	public function check_for_updates( $transient ) {
		try {
			return $this->get_plugin()->check_for_updates( $transient );
		} catch ( \Throwable $exception ) {
			return $transient;
		}
	}

	/**
	 * @return false|mixed
	 */
	protected function get_plugin() {
		$collection = Config::get_container()->get( Collection::class );

		return $collection->current();
	}

	/**
	 * Intercept plugins_api() calls that request information about our plugin and
	 * use the configured API endpoint to satisfy them.
	 *
	 * @see plugins_api()
	 *
	 * @param mixed        $result
	 * @param string       $action
	 * @param array<mixed>|object $args
	 *
	 * @return mixed
	 */
	public function inject_info( $result, string $action = null, $args = null ) {
		$relevant = ( 'plugin_information' === $action ) && isset( $args->slug ) && ( $args->slug === $this->get_plugin()->get_slug() );

		if ( ! $relevant ) {
			return $result;
		}

		$plugin_info = $this->get_plugin()->validate_license();

		if ( $plugin_info ) {
			return $plugin_info->to_wp_format();
		}

		return $result;
	}

}
