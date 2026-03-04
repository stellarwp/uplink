<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Features\Types;

/**
 * A Feature built in to an existing plugin, gated by a DB option flag.
 *
 * @since 3.0.0
 */
final class Built_In extends Feature {

	/**
	 * Constructor for a Feature built in to an existing plugin.
	 *
	 * @since 3.0.0
	 *
	 * @param array<string, mixed> $attributes The feature attributes.
	 *
	 * @return void
	 */
	public function __construct( array $attributes ) {
		$attributes['type'] = 'built_in';

		parent::__construct( $attributes );
	}

	/**
	 * Creates a Feature instance from an associative array.
	 *
	 * @since 3.0.0
	 *
	 * @param array<string, mixed> $data The feature data from the API response.
	 *
	 * @return static
	 */
	public static function from_array( array $data ) {
		return new self( self::base_attributes( $data ) );
	}
}
