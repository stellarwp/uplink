<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Features;

use StellarWP\Uplink\Features\Types\Feature;
use StellarWP\Uplink\Features\Types\Flag;
use StellarWP\Uplink\Features\Types\Plugin;
use StellarWP\Uplink\Features\Types\Theme;
use StellarWP\Uplink\Utils\Collection;

/**
 * A collection of Feature objects, keyed by slug.
 *
 * @since 3.0.0
 *
 * @extends Collection<Feature>
 */
class Feature_Collection extends Collection {

	/**
	 * Adds a feature to the collection.
	 *
	 * @since 3.0.0
	 *
	 * @param Feature $feature Feature instance.
	 *
	 * @return Feature
	 */
	public function add( Feature $feature ): Feature {
		if ( ! $this->offsetExists( $feature->get_slug() ) ) {
			$this->offsetSet( $feature->get_slug(), $feature );
		}

		return $this->offsetGet( $feature->get_slug() ) ?? $feature;
	}

	/**
	 * Alias of offsetGet().
	 *
	 * @since 3.0.0
	 *
	 * @param string $offset The feature slug.
	 *
	 * @return Feature|null
	 */
	public function get( $offset ): ?Feature { // phpcs:ignore Generic.CodeAnalysis.UselessOverridingMethod.Found -- Narrows return type for IDE support.
		return parent::get( $offset );
	}

	/**
	 * Converts the collection to an array of raw data arrays.
	 *
	 * @since 3.0.0
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function to_array(): array {
		$data = [];

		foreach ( $this as $feature ) {
			$data[] = $feature->to_array();
		}

		return $data;
	}

	/**
	 * Creates a Feature_Collection from an array of Feature objects or raw data arrays.
	 *
	 * When given raw arrays, dispatches to the correct subclass based on the 'type' field.
	 * Unknown types default to Flag.
	 *
	 * @since 3.0.0
	 *
	 * @param array<Feature|array<string, mixed>> $data Feature objects or raw arrays.
	 *
	 * @return self
	 */
	public static function from_array( array $data ): self {
		$collection = new self();

		foreach ( $data as $item ) {
			if ( $item instanceof Feature ) {
				$collection->add( $item );
			} elseif ( is_array( $item ) ) {
				$type = $item['type'] ?? '';

				if ( $type === 'plugin' ) {
					$feature = Plugin::from_array( $item );
				} elseif ( $type === 'theme' ) {
					$feature = Theme::from_array( $item );
				} else {
					$feature = Flag::from_array( $item );
				}

				$collection->add( $feature );
			}
		}

		return $collection;
	}

	/**
	 * Filters the collection by group, tier, availability and/or type.
	 *
	 * All parameters are optional. When null, that criterion is not applied.
	 *
	 * @since 3.0.0
	 *
	 * @param string|null $group     Filter by product group (e.g. 'LearnDash', 'TEC').
	 * @param string|null $tier      Filter by tier (e.g. 'Tier 1', 'Tier 2').
	 * @param bool|null   $available Filter by availability (true/false).
	 * @param string|null $type      Filter by feature type (e.g. 'plugin', 'flag').
	 *
	 * @return Feature_Collection
	 */
	public function filter( ?string $group = null, ?string $tier = null, ?bool $available = null, ?string $type = null ): Feature_Collection {
		$filtered = new self();

		foreach ( $this as $feature ) {
			if ( $group !== null && $feature->get_group() !== $group ) {
				continue;
			}

			if ( $tier !== null && $feature->get_tier() !== $tier ) {
				continue;
			}

			if ( $available !== null && $feature->is_available() !== $available ) {
				continue;
			}

			if ( $type !== null && $feature->get_type() !== $type ) {
				continue;
			}

			$filtered->add( $feature );
		}

		return $filtered;
	}
}
