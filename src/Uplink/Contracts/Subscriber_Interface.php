<?php

namespace StellarWP\Uplink\Contracts;

interface Subscriber_Interface {
	/**
	 * Register action/filter listeners to hook into WordPress
	 *
	 * @return void
	 */
	public function register();
}
