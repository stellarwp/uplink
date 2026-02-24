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
	 * Constructor for a Feature object.
	 *
	 * @since TBD
	 *
	 * @param string $slug        The feature slug.
	 * @param string $name        The feature display name.
	 * @param string $description The feature description.
	 * @param string $type        The feature type identifier.
	 *
	 * @return void
	 */
	public function __construct( string $slug, string $name, string $description, string $type ) {
		$this->slug        = $slug;
		$this->name        = $name;
		$this->description = $description;
		$this->type        = $type;
	}

	/**
	 * Create a Feature instance from an associative array.
	 *
	 * @since TBD
	 *
	 * @param array<string, mixed> $data The feature data from the API response.
	 *
	 * @return static
	 */
	abstract public static function from_array( array $data );


	/**
	 * Get the feature slug.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_slug(): string {
		return $this->slug;
	}

	/**
	 * Get the feature display name.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_name(): string {
		return $this->name;
	}

	/**
	 * Get the feature description.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_description(): string {
		return $this->description;
	}

	/**
	 * Get the feature type identifier.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_type(): string {
		return $this->type;
	}
}
