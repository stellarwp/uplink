<?php

declare(strict_types=1);

namespace StellarWP\Uplink\Legacy;

use StellarWP\Uplink\Utils\Cast;

/**
 * Represents a license key discovered from a plugin's legacy storage.
 *
 * @since 3.0.0
 */
class Legacy_License {

	/**
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public string $key;

	/**
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public string $slug;

	/**
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public string $name;

	/**
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public string $brand;

	/**
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public string $status;

	/**
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public string $page_url;

	/**
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public string $expires_at;

	/**
	 * @since 3.0.0
	 *
	 * @param array<string, mixed> $data The legacy license data.
	 */
	public static function from_data( array $data ): Legacy_License {
		$self = new self();

		$self->key        = Cast::to_string( $data['key'] ?? '' );
		$self->slug       = Cast::to_string( $data['slug'] ?? '' );
		$self->name       = Cast::to_string( $data['name'] ?? '' );
		$self->brand      = Cast::to_string( $data['brand'] ?? '' );
		$self->status     = Cast::to_string( $data['status'] ?? 'unknown' );
		$self->page_url   = Cast::to_string( $data['page_url'] ?? '' );
		$self->expires_at = Cast::to_string( $data['expires_at'] ?? '' );

		return $self;
	}
}
