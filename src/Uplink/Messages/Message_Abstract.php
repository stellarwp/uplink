<?php

namespace StellarWP\Uplink\Messages;

use StellarWP\Uplink\Container;

abstract class Message_Abstract {
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
	 * @param Container|null $container Container instance.
	 */
	public function __construct( Container $container = null ) {
		$this->container = $container ?: Container::init();
	}

	/**
	 * Gets the fully built message.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	abstract public function get(): string;

	/**
	 * Returns the message as a string.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function __toString(): string {
		return $this->get();
	}
}
