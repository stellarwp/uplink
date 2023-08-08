<?php

namespace StellarWP\Uplink\Messages;

use StellarWP\ContainerContract\ContainerInterface;

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
	 * @param ContainerInterface|null $container Container instance.
	 */
	public function __construct( $expiration, $container = null ) {
		parent::__construct( $container );

		$this->expiration = $expiration;
	}

	/**
	 * @inheritDoc
	 */
	public function get(): string {
		if ( $this->expiration ) {
			$message = sprintf(
				__( 'Valid key! Expires on %s.', '%TEXTDOMAIN%' ),
				$this->expiration
			);
		} else {
			$message = __( 'Valid key!', '%TEXTDOMAIN%' );
		}

		return esc_html( $message );
	}
}
