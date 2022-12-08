<?php

namespace StellarWP\Uplink\Messages;

use StellarWP\ContainerContract\ContainerInterface;
use StellarWP\Uplink\Resources\Resource;

class Update_Available extends Message_Abstract {
	/**
	 * Resource instance.
	 *
	 * @var Resource
	 */
	protected $resource;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param Resource $resource Resource instance.
	 * @param ContainerInterface|null $container Container instance.
	 */
	public function __construct( Resource $resource, ContainerInterface $container = null ) {
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
