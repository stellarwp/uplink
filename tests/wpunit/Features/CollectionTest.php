<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Features;

use ArrayIterator;
use StellarWP\Uplink\Features\Collection;
use StellarWP\Uplink\Features\Types\Feature;
use StellarWP\Uplink\Tests\UplinkTestCase;

final class CollectionTest extends UplinkTestCase {

	/**
	 * Tests that features can be added and retrieved by slug.
	 *
	 * @return void
	 */
	public function test_it_adds_and_gets_features(): void {
		$collection = new Collection();
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
		$collection = new Collection();
		$first      = $this->makeEmpty( Feature::class, [ 'get_slug' => 'test-feature', 'get_name' => 'First' ] );
		$second     = $this->makeEmpty( Feature::class, [ 'get_slug' => 'test-feature', 'get_name' => 'Second' ] );

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
		$collection = new Collection();

		$this->assertNull( $collection->get( 'nonexistent' ) );
	}

	/**
	 * Tests a feature can be removed by slug.
	 *
	 * @return void
	 */
	public function test_it_removes_features(): void {
		$collection = new Collection();
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
		$collection = new Collection();
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
		$collection = new Collection();
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

		$collection = new Collection( new ArrayIterator( $features ) );

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

		$collection = new Collection( $features );

		$this->assertSame( 2, $collection->count() );
	}
}
