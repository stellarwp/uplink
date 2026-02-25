<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Features\Types;

/**
 * Abstract base class for a Feature.
 *
 * Features are immutable value objects hydrated from the Commerce Portal API.
 * Each subclass implements from_array() to handle type-specific fields.
 *
 * @since TBD
 */
abstract class Feature {

	/**
	 * The feature slug.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected string $slug;

	/**
	 * The product group the feature belongs to (e.g. 'LearnDash', 'TEC').
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected string $group;

	/**
	 * The feature tier (e.g. 'Tier 1', 'Tier 2').
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected string $tier;

	/**
	 * The feature display name.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected string $name;

	/**
	 * The feature description.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected string $description;

	/**
	 * The feature type identifier (e.g. 'zip', 'built_in').
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected string $type;

	/**
	 * Whether the feature is available for the current site.
	 *
	 * @since TBD
	 *
	 * @var bool
	 */
	protected bool $is_available;

	/**
	 * The URL linking to the feature documentation or learn-more page.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected string $documentation;

	/**
	 * Constructor for a Feature object.
	 *
	 * @since TBD
	 *
	 * @param string $slug          The feature slug.
	 * @param string $group         The product group (e.g. 'LearnDash', 'TEC').
	 * @param string $tier          The feature tier (e.g. 'Tier 1', 'Tier 2').
	 * @param string $name          The feature display name.
	 * @param string $description   The feature description.
	 * @param string $type          The feature type identifier.
	 * @param bool   $is_available  Whether the feature is available.
	 * @param string $documentation The URL to the feature documentation.
	 *
	 * @return void
	 */
	public function __construct( string $slug, string $group, string $tier, string $name, string $description, string $type, bool $is_available, string $documentation = '' ) {
		$this->slug          = $slug;
		$this->group         = $group;
		$this->tier          = $tier;
		$this->name          = $name;
		$this->description   = $description;
		$this->type          = $type;
		$this->is_available  = $is_available;
		$this->documentation = $documentation;
	}

	/**
	 * Creates a Feature instance from an associative array.
	 *
	 * @since TBD
	 *
	 * @param array<string, mixed> $data The feature data from the API response.
	 *
	 * @return static
	 */
	abstract public static function from_array( array $data );


	/**
	 * Gets the feature slug.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_slug(): string {
		return $this->slug;
	}

	/**
	 * Gets the product group the feature belongs to.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_group(): string {
		return $this->group;
	}

	/**
	 * Gets the feature tier.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_tier(): string {
		return $this->tier;
	}

	/**
	 * Gets the feature display name.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_name(): string {
		return $this->name;
	}

	/**
	 * Gets the feature description.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_description(): string {
		return $this->description;
	}

	/**
	 * Gets the feature type identifier.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_type(): string {
		return $this->type;
	}

	/**
	 * Checks whether the feature is available for the current site.
	 *
	 * @since TBD
	 *
	 * @return bool
	 */
	public function is_available(): bool {
		return $this->is_available;
	}

	/**
	 * Gets the URL to the feature documentation or learn-more page.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_documentation(): string {
		return $this->documentation;
	}
}
