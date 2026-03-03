<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Licensing;

use StellarWP\Uplink\Licensing\Contracts\Licensing_Client;
use StellarWP\Uplink\Licensing\Enums\Validation_Status;
use StellarWP\Uplink\Licensing\Results\Product_Entry;
use StellarWP\Uplink\Licensing\Results\Validation_Result;
use WP_Error;

/**
 * A fixture-based licensing client that reads from JSON files.
 *
 * Each license key maps to a JSON file in the fixture directory.
 * The filename is the kebab-case lowercase of the key.
 *
 * @since 3.0.0
 *
 * @phpstan-import-type ProductAttributes from Product_Entry
 * @phpstan-type FixtureData array{products: list<array<string, mixed>>}
 */
final class Fixture_Client implements Licensing_Client {

	/**
	 * The directory containing fixture JSON files.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	protected string $fixture_dir;

	/**
	 * In-memory cache of parsed products keyed by lowercase key.
	 *
	 * @since 3.0.0
	 *
	 * @var array<string, Product_Entry[]|WP_Error>
	 */
	protected array $cache = [];

	/**
	 * Constructor.
	 *
	 * @since 3.0.0
	 *
	 * @param string $fixture_dir Path to the directory containing fixture JSON files.
	 */
	public function __construct( string $fixture_dir ) {
		$this->fixture_dir = $fixture_dir;
	}

	/**
	 * Fetch the product catalog for a license key.
	 *
	 * Resolves the key to a kebab-case JSON filename in the fixture directory.
	 *
	 * @since 3.0.0
	 *
	 * @param string $key    License key.
	 * @param string $domain Site domain (accepted but ignored by fixture).
	 *
	 * @return Product_Entry[]|WP_Error
	 */
	public function get_products( string $key, string $domain ) {
		$cache_key = strtolower( $key );

		if ( isset( $this->cache[ $cache_key ] ) ) {
			return $this->cache[ $cache_key ];
		}

		$file = $this->fixture_dir . '/' . $cache_key . '.json';

		if ( ! file_exists( $file ) ) {
			$this->cache[ $cache_key ] = new WP_Error(
				Error_Code::INVALID_KEY,
				sprintf( 'License key not recognized: %s', $key )
			);

			return $this->cache[ $cache_key ];
		}

		$json = file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

		if ( $json === false ) {
			$this->cache[ $cache_key ] = new WP_Error(
				Error_Code::INVALID_RESPONSE,
				sprintf( 'License response could not be read: %s', $file )
			);

			return $this->cache[ $cache_key ];
		}

		$data = json_decode( $json, true );

		if ( ! is_array( $data ) || ! isset( $data['products'] ) || ! is_array( $data['products'] ) ) {
			$this->cache[ $cache_key ] = new WP_Error(
				Error_Code::INVALID_RESPONSE,
				sprintf( 'License response could not be decoded: %s', $file )
			);

			return $this->cache[ $cache_key ];
		}

		/** @var FixtureData $data */

		$this->cache[ $cache_key ] = array_map(
			[ Product_Entry::class, 'from_array' ],
			$data['products']
		);

		return $this->cache[ $cache_key ];
	}

	/**
	 * Validate a license for a specific product on a domain.
	 *
	 * Loads the fixture data via get_products() and finds the matching entry.
	 *
	 * @since 3.0.0
	 *
	 * @param string $key          License key.
	 * @param string $domain       Site domain.
	 * @param string $product_slug Product identifier.
	 *
	 * @return Validation_Result|WP_Error
	 */
	public function validate( string $key, string $domain, string $product_slug ) {
		$products = $this->get_products( $key, $domain );

		if ( is_wp_error( $products ) ) {
			return $products;
		}

		$entry = $this->find_product( $products, $product_slug );

		if ( $entry === null ) {
			return new WP_Error(
				Error_Code::PRODUCT_NOT_FOUND,
				sprintf( 'Product not found: %s', $product_slug )
			);
		}

		return Validation_Result::from_array( $this->build_validation_data( $entry, $key, $domain ) );
	}

	/**
	 * Find a product entry by slug.
	 *
	 * @since 3.0.0
	 *
	 * @param Product_Entry[] $products     The product entries to search.
	 * @param string          $product_slug The product slug to find.
	 *
	 * @return Product_Entry|null
	 */
	protected function find_product( array $products, string $product_slug ): ?Product_Entry {
		foreach ( $products as $entry ) {
			if ( $entry->get_product_slug() === $product_slug ) {
				return $entry;
			}
		}

		return null;
	}

	/**
	 * Build the validation data array from a product entry.
	 *
	 * @since 3.0.0
	 *
	 * @param Product_Entry $entry  The matched product entry.
	 * @param string        $key    The license key.
	 * @param string        $domain The site domain.
	 *
	 * @return array<string, mixed>
	 */
	protected function build_validation_data( Product_Entry $entry, string $key, string $domain ): array {
		$status = $entry->get_validation_status() ?? Validation_Status::NOT_ACTIVATED;

		$data = [
			'status'       => $status,
			'license'      => [
				'key'    => $key,
				'status' => 'active',
			],
			'subscription' => [
				'product_slug'    => $entry->get_product_slug(),
				'tier'            => $entry->get_tier(),
				'site_limit'      => $entry->get_site_limit(),
				'expiration_date' => $entry->get_expires()->format( 'Y-m-d H:i:s' ),
				'status'          => $entry->get_status(),
			],
		];

		if ( $entry->get_installed_here() === true ) {
			$data['activation'] = [
				'domain'       => $domain,
				'activated_at' => gmdate( 'Y-m-d H:i:s' ),
			];
		}

		return $data;
	}
}
