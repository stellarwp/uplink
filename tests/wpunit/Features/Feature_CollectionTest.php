<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Features;

use ArrayIterator;
use StellarWP\Uplink\Features\Feature_Collection;
use StellarWP\Uplink\Features\Types\Feature;
use StellarWP\Uplink\Features\Types\Flag;
use StellarWP\Uplink\Features\Types\Plugin;
use StellarWP\Uplink\Tests\UplinkTestCase;

final class Feature_CollectionTest extends UplinkTestCase {

	/**
	 * Tests that features can be added and retrieved by slug.
	 *
	 * @return void
	 */
	public function test_it_adds_and_gets_features(): void {
		$collection = new Feature_Collection();
		$feature_a  = $this->makeEmpty( Feature::class, [ 'get_slug' => 'feature-a' ] );
		$feature_b  = $this->makeEmpty( Feature::class, [ 'get_slug' => 'feature-b' ] );

		$collection->add( $feature_a );
		$collection->add( $feature_b );

		$this->assertSame( 2, $collection->count() );
		$this->assertSame( $feature_a, $collection->get( 'feature-a' ) );
		$this->assertSame( $feature_b, $collection->get( 'feature-b' ) );
	}

	/**
	 * Tests that adding a feature with a duplicate slug does not overwrite the original.
	 *
	 * @return void
	 */
	public function test_it_does_not_duplicate_features_with_same_slug(): void {
		$collection = new Feature_Collection();
		$first      = $this->makeEmpty(
			Feature::class,
			[
				'get_slug' => 'test-feature',
				'get_name' => 'First',
			] 
		);
		$second     = $this->makeEmpty(
			Feature::class,
			[
				'get_slug' => 'test-feature',
				'get_name' => 'Second',
			] 
		);

		$collection->add( $first );
		$collection->add( $second );

		$this->assertSame( 1, $collection->count() );
		$this->assertSame( 'First', $collection->get( 'test-feature' )->get_name() );
	}

	/**
	 * Tests that null is returned when retrieving a slug that does not exist.
	 *
	 * @return void
	 */
	public function test_it_returns_null_for_unknown_slug(): void {
		$collection = new Feature_Collection();

		$this->assertNull( $collection->get( 'nonexistent' ) );
	}

	/**
	 * Tests a feature can be removed by slug.
	 *
	 * @return void
	 */
	public function test_it_removes_features(): void {
		$collection = new Feature_Collection();
		$collection->add( $this->makeEmpty( Feature::class, [ 'get_slug' => 'test-feature' ] ) );

		$this->assertSame( 1, $collection->count() );

		$collection->remove( 'test-feature' );

		$this->assertSame( 0, $collection->count() );
		$this->assertNull( $collection->get( 'test-feature' ) );
	}

	/**
	 * Tests that the collection implements ArrayAccess for isset and offset retrieval.
	 *
	 * @return void
	 */
	public function test_it_supports_array_access(): void {
		$collection = new Feature_Collection();
		$feature    = $this->makeEmpty( Feature::class, [ 'get_slug' => 'test-feature' ] );

		$collection->add( $feature );

		$this->assertTrue( isset( $collection['test-feature'] ) );
		$this->assertFalse( isset( $collection['nonexistent'] ) );
		$this->assertSame( $feature, $collection['test-feature'] );
	}

	/**
	 * Tests that the collection can be iterated with foreach.
	 *
	 * @return void
	 */
	public function test_it_iterates_over_features(): void {
		$collection = new Feature_Collection();
		$collection->add( $this->makeEmpty( Feature::class, [ 'get_slug' => 'test-feature-1' ] ) );
		$collection->add( $this->makeEmpty( Feature::class, [ 'get_slug' => 'test-feature-2' ] ) );

		$slugs = [];

		foreach ( $collection as $slug => $feature ) {
			$slugs[] = $slug;
		}

		$this->assertSame( [ 'test-feature-1', 'test-feature-2' ], $slugs );
	}

	/**
	 * Tests that the collection can be constructed from an ArrayIterator.
	 *
	 * @return void
	 */
	public function test_it_accepts_an_iterator(): void {
		$features = [
			'test-feature-1' => $this->makeEmpty( Feature::class, [ 'get_slug' => 'test-feature-1' ] ),
			'test-feature-2' => $this->makeEmpty( Feature::class, [ 'get_slug' => 'test-feature-2' ] ),
		];

		$collection = new Feature_Collection( new ArrayIterator( $features ) );

		$this->assertSame( 2, $collection->count() );
		$this->assertSame( $features['test-feature-1'], $collection->get( 'test-feature-1' ) );
		$this->assertSame( $features['test-feature-2'], $collection->get( 'test-feature-2' ) );
	}

	/**
	 * Tests that the collection can be constructed from a plain array.
	 *
	 * @return void
	 */
	public function test_it_accepts_an_array(): void {
		$features = [
			'test-feature-1' => $this->makeEmpty( Feature::class, [ 'get_slug' => 'test-feature-1' ] ),
			'test-feature-2' => $this->makeEmpty( Feature::class, [ 'get_slug' => 'test-feature-2' ] ),
		];

		$collection = new Feature_Collection( $features );

		$this->assertSame( 2, $collection->count() );
	}

	/**
	 * Tests from_array accepts already-hydrated Feature objects.
	 *
	 * @return void
	 */
	public function test_from_array_creates_collection_from_objects(): void {
		$flag   = Flag::from_array(
			[
				'slug'              => 'my-flag',
				'product'             => 'TEC',
				'tier'              => 'Tier 1',
				'name'              => 'My Flag',
				'description'       => '',
				'is_available'      => true,
				'documentation_url' => '',
			] 
		);
		$plugin = Plugin::from_array(
			[
				'slug'              => 'my-plugin',
				'product'             => 'LD',
				'tier'              => 'Tier 2',
				'name'              => 'My Plugin',
				'description'       => '',
				'is_available'      => false,
				'documentation_url' => '',
				'plugin_file'       => '',
				'authors'           => [],
			] 
		);

		$collection = Feature_Collection::from_array( [ $flag, $plugin ] );

		$this->assertSame( 2, $collection->count() );
		$this->assertSame( $flag, $collection->get( 'my-flag' ) );
		$this->assertSame( $plugin, $collection->get( 'my-plugin' ) );
	}

	/**
	 * Tests from_array creates Flag instances for type=flag.
	 *
	 * @return void
	 */
	public function test_from_array_creates_flag_features(): void {
		$data = [
			[
				'slug'              => 'my-flag',
				'product'             => 'TEC',
				'tier'              => 'Tier 1',
				'name'              => 'My Flag',
				'description'       => '',
				'type'              => 'flag',
				'is_available'      => true,
				'documentation_url' => '',
			],
		];

		$collection = Feature_Collection::from_array( $data );

		$this->assertSame( 1, $collection->count() );
		$this->assertInstanceOf( Flag::class, $collection->get( 'my-flag' ) );
		$this->assertSame( 'flag', $collection->get( 'my-flag' )->get_type() );
	}

	/**
	 * Tests from_array creates Plugin instances for type=plugin.
	 *
	 * @return void
	 */
	public function test_from_array_creates_plugin_features(): void {
		$data = [
			[
				'slug'              => 'my-plugin',
				'product'             => 'LearnDash',
				'tier'              => 'Tier 2',
				'name'              => 'My Plugin',
				'description'       => '',
				'type'              => 'plugin',
				'plugin_file'       => 'my-plugin/my-plugin.php',
				'is_available'      => false,
				'documentation_url' => '',
				'authors'           => [],
			],
		];

		$collection = Feature_Collection::from_array( $data );

		$this->assertSame( 1, $collection->count() );
		$this->assertInstanceOf( Plugin::class, $collection->get( 'my-plugin' ) );
		$this->assertSame( 'plugin', $collection->get( 'my-plugin' )->get_type() );
	}

	/**
	 * Tests from_array defaults unknown types to Flag.
	 *
	 * @return void
	 */
	public function test_from_array_defaults_unknown_type_to_flag(): void {
		$data = [
			[
				'slug'              => 'unknown-feature',
				'product'             => 'TEC',
				'tier'              => 'Tier 1',
				'name'              => 'Unknown',
				'description'       => '',
				'type'              => 'unknown_type',
				'is_available'      => true,
				'documentation_url' => '',
			],
		];

		$collection = Feature_Collection::from_array( $data );

		$this->assertSame( 1, $collection->count() );
		$this->assertInstanceOf( Flag::class, $collection->get( 'unknown-feature' ) );
	}

	/**
	 * Tests from_array skips non-array items.
	 *
	 * @return void
	 */
	public function test_from_array_skips_non_array_items(): void {
		$data = [
			[
				'slug'              => 'my-flag',
				'product'             => 'TEC',
				'tier'              => 'Tier 1',
				'name'              => 'My Flag',
				'description'       => '',
				'type'              => 'flag',
				'is_available'      => true,
				'documentation_url' => '',
			],
			'not-an-array',
		];

		$collection = Feature_Collection::from_array( $data );

		$this->assertSame( 1, $collection->count() );
	}

	/**
	 * Tests from_array returns an empty collection for empty input.
	 *
	 * @return void
	 */
	public function test_from_array_returns_empty_collection_for_empty_input(): void {
		$collection = Feature_Collection::from_array( [] );

		$this->assertSame( 0, $collection->count() );
	}

	/**
	 * Tests filtering by group returns only features matching the group.
	 *
	 * @return void
	 */
	public function test_filter_by_group(): void {
		$collection = $this->build_collection();

		$filtered = $collection->filter( 'TEC' );

		$this->assertInstanceOf( Feature_Collection::class, $filtered );
		$this->assertSame( 2, $filtered->count() );
		$this->assertNotNull( $filtered->get( 'tec-feature-1' ) );
		$this->assertNotNull( $filtered->get( 'tec-feature-2' ) );
		$this->assertNull( $filtered->get( 'ld-feature-1' ) );
	}

	/**
	 * Tests filtering by tier returns only features matching the tier.
	 *
	 * @return void
	 */
	public function test_filter_by_tier(): void {
		$collection = $this->build_collection();

		$filtered = $collection->filter( null, 'Tier 1' );

		$this->assertSame( 2, $filtered->count() );
		$this->assertNotNull( $filtered->get( 'tec-feature-1' ) );
		$this->assertNotNull( $filtered->get( 'ld-feature-1' ) );
	}

	/**
	 * Tests filtering by availability returns only features matching the flag.
	 *
	 * @return void
	 */
	public function test_filter_by_available_true(): void {
		$collection = $this->build_collection();

		$filtered = $collection->filter( null, null, true );

		$this->assertSame( 2, $filtered->count() );
		$this->assertNotNull( $filtered->get( 'tec-feature-1' ) );
		$this->assertNotNull( $filtered->get( 'ld-feature-1' ) );
	}

	/**
	 * Tests filtering by unavailable returns only unavailable features.
	 *
	 * @return void
	 */
	public function test_filter_by_available_false(): void {
		$collection = $this->build_collection();

		$filtered = $collection->filter( null, null, false );

		$this->assertSame( 1, $filtered->count() );
		$this->assertNotNull( $filtered->get( 'tec-feature-2' ) );
	}

	/**
	 * Tests filtering by type returns only features matching the type.
	 *
	 * @return void
	 */
	public function test_filter_by_type(): void {
		$collection = $this->build_collection();

		$filtered = $collection->filter( null, null, null, 'plugin' );

		$this->assertSame( 1, $filtered->count() );
		$this->assertNotNull( $filtered->get( 'ld-feature-1' ) );
	}

	/**
	 * Tests combining multiple filter criteria.
	 *
	 * @return void
	 */
	public function test_filter_by_multiple_criteria(): void {
		$collection = $this->build_collection();

		$filtered = $collection->filter( 'TEC', null, true );

		$this->assertSame( 1, $filtered->count() );
		$this->assertNotNull( $filtered->get( 'tec-feature-1' ) );
	}

	/**
	 * Tests that filter with no criteria returns all features.
	 *
	 * @return void
	 */
	public function test_filter_with_no_criteria_returns_all(): void {
		$collection = $this->build_collection();

		$filtered = $collection->filter();

		$this->assertSame( 3, $filtered->count() );
	}

	/**
	 * Tests that filter with no matches returns an empty collection.
	 *
	 * @return void
	 */
	public function test_filter_with_no_matches_returns_empty(): void {
		$collection = $this->build_collection();

		$filtered = $collection->filter( 'Nonexistent' );

		$this->assertSame( 0, $filtered->count() );
	}

	/**
	 * Tests that filter returns a new collection, leaving the original unchanged.
	 *
	 * @return void
	 */
	public function test_filter_does_not_modify_original(): void {
		$collection = $this->build_collection();

		$collection->filter( 'TEC' );

		$this->assertSame( 3, $collection->count() );
	}

	/**
	 * Builds a Feature_Collection with a known set of features for filter tests.
	 *
	 * - tec-feature-1: group=TEC, tier=Tier 1, available=true, type=flag
	 * - tec-feature-2: group=TEC, tier=Tier 2, available=false, type=flag
	 * - ld-feature-1:  group=LearnDash, tier=Tier 1, available=true, type=plugin
	 *
	 * @return Feature_Collection
	 */
	private function build_collection(): Feature_Collection {
		$collection = new Feature_Collection();

		$collection->add(
			$this->makeEmpty(
				Feature::class,
				[
					'get_slug'     => 'tec-feature-1',
					'get_product'  => 'TEC',
					'get_tier'     => 'Tier 1',
					'get_type'     => 'flag',
					'is_available' => true,
				] 
			) 
		);

		$collection->add(
			$this->makeEmpty(
				Feature::class,
				[
					'get_slug'     => 'tec-feature-2',
					'get_product'  => 'TEC',
					'get_tier'     => 'Tier 2',
					'get_type'     => 'flag',
					'is_available' => false,
				] 
			) 
		);

		$collection->add(
			$this->makeEmpty(
				Feature::class,
				[
					'get_slug'     => 'ld-feature-1',
					'get_product'  => 'LearnDash',
					'get_tier'     => 'Tier 1',
					'get_type'     => 'plugin',
					'is_available' => true,
				] 
			) 
		);

		return $collection;
	}
}
