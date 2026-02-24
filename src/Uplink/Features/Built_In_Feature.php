<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Features;

/**
 * A feature built directly into a consumer plugin, toggled via a DB flag.
 *
 * Unlike Zip_Feature (which manages an external plugin), Built_In_Feature
 * has no additional properties — the base slug/name/description/type are
 * sufficient. The Built_In_Strategy uses a wp_option flag to track state.
 *
 * @since 3.0.0
 */
class Built_In_Feature extends Feature {

	/**
	 * Construct a Built_In_Feature.
	 *
	 * Hard-codes the type to "built_in" so the Manager can dispatch to
	 * Built_In_Strategy.
	 *
	 * @since 3.0.0
	 *
	 * @param string $slug        Unique feature identifier (e.g. "advanced-tickets").
	 * @param string $name        Human-readable display name.
	 * @param string $description Brief description of the feature.
	 */
	public function __construct( string $slug, string $name, string $description ) {
		parent::__construct( $slug, $name, $description, 'built_in' );
	}

}
