<?php

namespace StellarWP\Uplink\Admin;

use StellarWP\Uplink\Container;
use StellarWP\Uplink\Messages;
use StellarWP\Uplink\Resource\Collection;
use StellarWP\Uplink\Resource\Collection\Path_FilterIterator;
use StellarWP\Uplink\Resource\Collection\Plugin_FilterIterator;
use StellarWP\Uplink\Resource\Plugin;

class Plugins_Page {
	/**
	 * Container instance.
	 *
	 * @since 1.0.0
	 *
	 * @var Container
	 */
	protected $container;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param Container|null    $container       Container instance.
	 */
	public function __construct( Container $container = null ) {
		$this->container = $container ?: Container::init();
	}

	/**
	 * Displays messages on the plugins page in the dashboard.
	 *
	 * @since 1.0.0
	 *
	 * @param string $page
	 */
	public function display_plugin_messages( $page ): void {
		if ( 'plugins.php' !== $page ) {
			return;
		}

		$messages       = [];
		$plugin_updates = get_plugin_updates();

		/** @var Collection */
		$collection           = $this->container->make( Collection::class );
		$plugins              = $collection->get_plugins();
		$plugins_with_updates = $collection->get_by_paths( array_keys( $plugin_updates ), $plugins );
		$notices              = [];

		foreach ( $plugins_with_updates as $resource ) {
			if ( ! $resource instanceof Plugin ) {
				continue;
			}

			// @TODO figure out the message fetching (see PUE Checker line 1198+)
			$message = $this->get_plugin_message( $resource );

			if ( null === $message ) {
				continue;
			}

			$message = $message->get();

			if ( $resource->is_network_licensed() ) {
				continue;
			}

			// Wrap the message.
			$message = sprintf(
				'<div class="update-message notice inline notice-warning notice-alt">%s</div>',
				$message
			);

			// Wrap the message wrapper.
			$message = sprintf(
				'<tr class="plugin-update-tr active"><td colspan="3" class="plugin-update">%s</td></tr>',
				$message
			);

			$path = $resource->get_path();

			$notices[ $path ] = [
				'slug'             => $path,
				'message_row_html' => $message
			];
		}
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
}
