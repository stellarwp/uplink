<?php

namespace StellarWP\Uplink\Messages;
use StellarWP\Uplink\Config;

class Expired_Key extends Message_Abstract {
	/**
	 * @inheritDoc
	 */
	public function get(): string {
		// TEC only default link for backwards compatibility.
		$default_link = in_array(
			Config::get_hook_prefix(),
			[
				'the-events-calendar',
				'events-calendar-pro',
				'event-tickets',
				'event-tickets-plus',
				'tribe-filterbar',
				'events-virtual',
				'events-community',
				'events-community-tickets',
				'event-aggregator',
				'events-elasticsearch',
				'image-widget-plus',
				'advanced-post-manager',
				'tribe-eventbrite',
				'event-automator',
				'tec-seating',
			],
			true ) ? 'https://evnt.is/195y' : '';

		$message_link        = apply_filters( 'stellarwp/uplink/' . Config::get_hook_prefix() . '/messages/expired_key_link', $default_link );
		$renew_label         = __( 'Renew Your License Now', '%TEXTDOMAIN%' );
		$opens_in_new_window = __( '(opens in a new window)', '%TEXTDOMAIN%' );
		$notice_text         = __( 'Your license is expired', '%TEXTDOMAIN%' );
		
		if ( ! empty( $message_link ) ) {
			$message_content = sprintf(
				'<p>%s <a href="%s" target="_blank" class="button button-primary">%s <span class="screen-reader-text">%s</span></a></p>',
				esc_html( $notice_text ),
				esc_url( $message_link ),
				esc_html( $renew_label ),
				esc_html( $opens_in_new_window )
			);
		} else {
			$message_content = sprintf(
				'<p>%s</p>',
				esc_html( $notice_text )
			);
		}
		
		$message_content = apply_filters( 'stellarwp/uplink/' . Config::get_hook_prefix() . '/messages/expired_key', $message_content );
		
		$allowed_html = [
			'a' => [
				'href' => [],
				'title' => [],
				'class' => []
			],
			'br' => [],
			'em' => [],
			'strong' => [],
			'div' => [
				'class' => []
			],
			'p' => [
				'class' => []
			],
			'span' => [
				'class' => []
			],
		];

		$message = '<div class="notice notice-warning">';
		$message .= wp_kses( $message_content, $allowed_html );
        $message .= '</div>';

		return $message;
	}
}
