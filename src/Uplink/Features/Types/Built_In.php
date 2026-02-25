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
	 * @param string $slug          The feature slug.
	 * @param string $group         The product group (e.g. 'LearnDash', 'TEC').
	 * @param string $tier          The feature tier (e.g. 'Tier 1', 'Tier 2').
	 * @param string $name          The feature display name.
	 * @param string $description   The feature description.
	 * @param bool   $is_available  Whether the feature is available.
	 * @param string $documentation The URL to the feature documentation.
	 *
	 * @return void
	 */
	public function __construct( string $slug, string $group, string $tier, string $name, string $description, bool $is_available, string $documentation = '' ) {
		parent::__construct( $slug, $group, $tier, $name, $description, 'built_in', $is_available, $documentation );
	}

	/**
	 * @inheritDoc
	 */
	public static function from_array( array $data ) {
		return new self(
			$data['slug'],
			$data['group'],
			$data['tier'],
			$data['name'],
			$data['description'] ?? '',
			$data['is_available'],
			$data['documentation'] ?? ''
		);
	}

	/**
	 * @inheritDoc
	 */
	public function to_array(): array {
		return [
			'slug' => $this->get_slug(),
			'group' => $this->get_group(),
			'tier' => $this->get_tier(),
			'name' => $this->get_name(),
			'description' => $this->get_description(),
			'type' => $this->get_type(),
			'is_available' => $this->is_available(),
			'documentation' => $this->get_documentation(),
		];
	}
}
