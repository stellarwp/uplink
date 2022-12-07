<?php

namespace StellarWP\Uplink\Admin;

use StellarWP\Uplink\Config;
use StellarWP\Uplink\Messages\Expired_Key;
use StellarWP\Uplink\Messages\Unlicensed;

class Notice {

	const INVALID_KEY = 'invalid_key';
	const UPGRADE_KEY = 'upgrade_key';
	const EXPIRED_KEY = 'expired_key';
	const STORE_KEY   = 'stellar_uplink_key_notices';

	/**
	 * @var array<mixed>
	 */
	protected array $saved_notices = [];

	/**
	 * @var array<mixed>
	 */
	protected array $notices = [];

	public function setup_notices(): void {
		if ( empty( $this->notices ) ) {
			return;
		}

		foreach ( $this->notices as $notice_type => $plugin ) {
			$message = null;

			switch ( $notice_type ) {
				case self::EXPIRED_KEY:
					$message = new Expired_Key();
					break;
				case self::INVALID_KEY:
					$message = new Unlicensed();
					break;
			}

			if ( empty( $message ) ) {
				continue;
			}

			echo $message;
		}
	}

	/**
	 * @param string $notice_type
	 * @param string $plugin_name
	 */
	public function add_notice( string $notice_type, string $plugin_name ): void {
		$this->clear_notices( $plugin_name, true );
		$this->notices[ $notice_type ][ $plugin_name ] = true;
		$this->save_notices();
	}

	/**
	 * Removes any notifications for the specified plugin.
	 *
	 * Useful when a valid license key is detected for a plugin, where previously
	 * it might have been included under a warning notification.
	 *
	 * If the optional second param is set to true then this change will not
	 * immediately be committed to storage (useful if we know this will happen in
	 * any case later on in the same request).
	 *
	 * @param string $plugin_name
	 * @param bool $defer_saving_change = false
	 */
	public function clear_notices( string $plugin_name, bool $defer_saving_change = false ): void {
		foreach ( $this->notices as $notice_type => &$list_of_plugins ) {
			unset( $list_of_plugins[ $plugin_name ] );
		}

		if ( ! $defer_saving_change ) {
			$this->save_notices();
		}
	}

	/**
	 * Saves any license key notices already added.
	 */
	public function save_notices(): void {
		update_option( self::STORE_KEY, $this->notices );

		/**
		 * Fires after PUE license key notices have been saved.
		 *
		 * @param array $current_notices
		 * @param array $previously_saved_notices
		 */
		do_action( 'stellar_uplink_' . Config::get_hook_prefix(). 'notices_save_notices', $this->notices, $this->saved_notices );
	}

}
