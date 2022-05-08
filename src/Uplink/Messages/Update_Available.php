<?php

namespace StellarWP\Uplink\Messages;

use StellarWP\Uplink\Container;
use StellarWP\Uplink\Resource\Resource_Abstract;

class Update_Available extends Message_Abstract {
	/**
	 * Resource instance.
	 *
	 * @var Resource_Abstract
	 */
	protected $resource;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param Resource_Abstract $resource Resource instance.
	 * @param Container|null $container Container instance.
	 */
	public function __construct( Resource_Abstract $resource, Container $container = null ) {
		parent::__construct( $container );

		$this->resource = $resource;
	}

	/**
	 * @inheritDoc
	 */
	public function get(): string {
		$message = sprintf(
			esc_html__( 'There is an update for %s. You\'ll need to %scheck your license%s to have access to updates, downloads, and support.', 'stellar-uplink-client' ),
			$this->resource->get_name(),
			'<a href="https://theeventscalendar.com/license-keys/">',
			'</a>'
		);

		return $message;
	}
}
