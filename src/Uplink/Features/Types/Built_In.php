<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Features\Types;

/**
 * A Feature built in to an existing plugin, gated by a DB option flag.
 *
 * @since TBD
 */
final class Built_In extends Feature {

	/**
	 * Constructor for a Feature built in to an existing plugin.
	 *
	 * @since TBD
	 *
	 * @param string $slug        The feature slug.
	 * @param string $name        The feature display name.
	 * @param string $description The feature description.
	 *
	 * @return void
	 */
	public function __construct( string $slug, string $name, string $description ) {
		parent::__construct( $slug, $name, $description, 'built_in' );
	}

	/**
	 * @inheritDoc
	 */
	public static function from_array( array $data ) {
		return new self(
			$data['slug'],
			$data['name'],
			$data['description'] ?? ''
		);
	}
}
