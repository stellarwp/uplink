<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Features;

/**
 * Abstract base value object for a "feature" that can be gated by license key.
 *
 * Immutable: all properties are set via the constructor and exposed through
 * getters only. Subclasses add strategy-specific data (e.g. Zip_Feature adds
 * plugin_file and download_url).
 *
 * The colleague building the full Feature system will extend this with a
 * from_array() factory and Collection integration.
 *
 * @since 3.0.0
 */
abstract class Feature {

	/**
	 * Unique identifier for this feature (e.g. "stellar-export").
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	protected $slug;

	/**
	 * Human-readable display name (e.g. "Stellar Export").
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * Brief description of what the feature does.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	protected $description;

	/**
	 * Strategy type identifier (e.g. "zip"). Each Feature subclass hard-codes
	 * this so the Manager can dispatch to the correct Strategy.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	protected $type;

	/**
	 * Construct a Feature value object with its core identity properties.
	 *
	 * @since 3.0.0
	 *
	 * @param string $slug        Unique feature identifier.
	 * @param string $name        Human-readable display name.
	 * @param string $description Brief description.
	 * @param string $type        Strategy type identifier.
	 */
	public function __construct( string $slug, string $name, string $description, string $type ) {
		$this->slug        = $slug;
		$this->name        = $name;
		$this->description = $description;
		$this->type        = $type;
	}

	/**
	 * Get the unique feature slug.
	 *
	 * @since 3.0.0
	 *
	 * @return string
	 */
	public function get_slug(): string {
		return $this->slug;
	}

	/**
	 * Get the human-readable feature name.
	 *
	 * @since 3.0.0
	 *
	 * @return string
	 */
	public function get_name(): string {
		return $this->name;
	}

	/**
	 * Get the feature description.
	 *
	 * @since 3.0.0
	 *
	 * @return string
	 */
	public function get_description(): string {
		return $this->description;
	}

	/**
	 * Get the strategy type identifier.
	 *
	 * @since 3.0.0
	 *
	 * @return string
	 */
	public function get_type(): string {
		return $this->type;
	}

}
