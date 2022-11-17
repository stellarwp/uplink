<?php

namespace StellarWP\Uplink\Messages;

use StellarWP\Uplink\Container;

class Valid_Key extends Message_Abstract {
	/**
	 * Expiration date.
	 *
	 * @var string
	 */
	protected $expiration;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param string $expiration Expiration date.
	 * @param Container|null $container Container instance.
	 */
	public function __construct( $expiration, Container $container = null ) {
		parent::__construct( $container );

		$this->expiration = $expiration;
	}

	/**
	 * @inheritDoc
	 */
	public function get(): string {
		$message = sprintf(
			__( 'Valid key! Expires on %s.', '%stellar-uplink-domain%' ),
			$this->expiration
		);

		return esc_html( $message );
	}
}
