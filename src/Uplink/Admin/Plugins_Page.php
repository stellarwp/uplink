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
	 *
	 * @return void
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

			$href = wp_nonce_url(
				self_admin_url( 'update.php?action=upgrade-plugin&plugin=' ) . $plugin_file,
				'upgrade-plugin_' . $plugin_file
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
			'slug'             => $this->get_plugin()->get_slug(),
			'message_row_html' => $message_row_html,
		];
	}

	/**
	 * Get plugin notice.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_plugin_notice() {
		return apply_filters( 'stellarwp/uplink/' . Config::get_hook_prefix() . '/plugin_notices', $this->plugin_notice );
	}

	/**
	 * Add notices as JS variable
	 *
	 * @param string $page
	 *
	 * @return void
	 */
	public function store_admin_notices( string $page ) {
		if ( 'plugins.php' !== $page ) {
			return;
		}

		add_action( 'admin_footer', [ $this, 'output_notices_script' ] );
	}

	/**
	 * Output the plugin-specific notices script.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function output_notices_script() {
		$slug = $this->get_plugin()->get_slug();
		$notice = $this->get_plugin_notice();

		if ( empty( $notice ) ) {
			return;
		}
		?>
		<script>
		/**
		 * Appends license key notifications inline within the plugin table.
		 *
		 * This is done via JS because the options for achieving the same things
		 * server-side are currently limited.
		 */
		(function( $, my ) {
			'use strict';

			my.init = function() {
				var $active_plugin_row = $( 'tr.active[data-slug="<?php echo esc_attr( $slug ); ?>"]' );

				if ( ! $active_plugin_row.length ) {
					return;
				}

				var notice = <?php echo wp_json_encode( $notice ); ?>;

				if ( ! notice.message_row_html ) {
					return;
				}

				// Add the .update class to the plugin row and append our new row with the update message
				$active_plugin_row.addClass( 'update' ).after( notice.message_row_html );
			};

			$( function() {
				my.init();
			} );
		})( jQuery, {} );
		</script>
		<?php
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
	 *
	 * @return void
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
	 * @param mixed               $result
	 * @param string              $action
	 * @param array<mixed>|object $args
	 *
	 * @return mixed
	 */
	public function inject_info( $result, string $action = null, $args = null ) {
		$relevant = ( 'plugin_information' === $action ) && is_object( $args ) && isset( $args->slug ) && ( $args->slug === $this->get_plugin()->get_slug() );

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
