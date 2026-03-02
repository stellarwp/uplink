<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Licensing\Results;

use DateTimeImmutable;
use StellarWP\Uplink\Licensing\Enums\Validation_Status;
use StellarWP\Uplink\Utils\Cast;

/**
 * A single product entry from the v4 licensing catalog.
 *
 * Immutable value object hydrated from the GET /stellarwp/v4/products response.
 * Mirrors the licensing service's Catalog_Entry_Result API response shape.
 *
 * @since 3.0.0
 *
 * @phpstan-type ProductAttributes array{
 *     product_slug: string,
 *     tier: string,
 *     pending_tier: ?string,
 *     status: string,
 *     expires: string,
 *     site_limit: int,
 *     active_count: int,
 *     installed_here: ?bool,
 *     validation_status: ?string,
 * }
 */
class Product_Entry {

	/**
	 * The product entry attributes.
	 *
	 * @since 3.0.0
	 *
	 * @var ProductAttributes
	 */
	protected array $attributes = [
		'product_slug'      => '',
		'tier'              => '',
		'pending_tier'      => null,
		'status'            => '',
		'expires'           => '',
		'site_limit'        => 0,
		'active_count'      => 0,
		'installed_here'    => null,
		'validation_status' => null,
	];

	/**
	 * Constructor for a Product_Entry.
	 *
	 * @since 3.0.0
	 *
	 * @phpstan-param ProductAttributes $attributes
	 *
	 * @param array $attributes The product entry attributes.
	 *
	 * @return void
	 */
	public function __construct( array $attributes ) {
		$this->attributes = $attributes;
	}

	/**
	 * Creates a Product_Entry from an API response array.
	 *
	 * @since 3.0.0
	 *
	 * @param array<string, mixed> $data The product data from the API response.
	 *
	 * @return self
	 */
	public static function from_array( array $data ): self {
		$activations = isset( $data['activations'] ) && is_array( $data['activations'] ) ? $data['activations'] : [];

		return new self(
			[
				'product_slug'      => Cast::to_string( $data['product_slug'] ?? '' ),
				'tier'              => Cast::to_string( $data['tier'] ?? '' ),
				'pending_tier'      => isset( $data['pending_tier'] ) ? Cast::to_string( $data['pending_tier'] ) : null,
				'status'            => Cast::to_string( $data['status'] ?? '' ),
				'expires'           => Cast::to_string( $data['expires'] ?? '' ),
				'site_limit'        => Cast::to_int( $activations['site_limit'] ?? 0 ),
				'active_count'      => Cast::to_int( $activations['active_count'] ?? 0 ),
				'installed_here'    => isset( $data['installed_here'] ) ? Cast::to_bool( $data['installed_here'] ) : null,
				'validation_status' => isset( $data['validation_status'] ) ? Cast::to_string( $data['validation_status'] ) : null,
			]
		);
	}

	/**
	 * Converts the product entry to an associative array matching the API response shape.
	 *
	 * @since 3.0.0
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		$data = [
			'product_slug' => $this->get_product_slug(),
			'tier'         => $this->get_tier(),
			'pending_tier' => $this->get_pending_tier(),
			'status'       => $this->get_status(),
			'expires'      => $this->attributes['expires'],
			'activations'  => [
				'site_limit'   => $this->get_site_limit(),
				'active_count' => $this->get_active_count(),
				'over_limit'   => $this->is_over_limit(),
			],
		];

		if ( $this->get_installed_here() !== null ) {
			$data['installed_here'] = $this->get_installed_here();
		}

		if ( $this->get_validation_status() !== null ) {
			$data['validation_status'] = $this->get_validation_status();
			$data['is_valid']          = $this->is_valid();
		}

		return $data;
	}

	/**
	 * Gets the product slug.
	 *
	 * @since 3.0.0
	 *
	 * @return string
	 */
	public function get_product_slug(): string {
		return $this->attributes['product_slug'];
	}

	/**
	 * Gets the subscription tier.
	 *
	 * @since 3.0.0
	 *
	 * @return string
	 */
	public function get_tier(): string {
		return $this->attributes['tier'];
	}

	/**
	 * Gets the pending tier (scheduled downgrade), or null if none.
	 *
	 * @since 3.0.0
	 *
	 * @return string|null
	 */
	public function get_pending_tier(): ?string {
		return $this->attributes['pending_tier'];
	}

	/**
	 * Gets the subscription status.
	 *
	 * @since 3.0.0
	 *
	 * @return string
	 */
	public function get_status(): string {
		return $this->attributes['status'];
	}

	/**
	 * Gets the expiration date.
	 *
	 * @since 3.0.0
	 *
	 * @return DateTimeImmutable
	 */
	public function get_expires(): DateTimeImmutable {
		return new DateTimeImmutable( $this->attributes['expires'] );
	}

	/**
	 * Gets the maximum number of site activations (0 = unlimited).
	 *
	 * @since 3.0.0
	 *
	 * @return int
	 */
	public function get_site_limit(): int {
		return $this->attributes['site_limit'];
	}

	/**
	 * Gets the current number of active site activations.
	 *
	 * @since 3.0.0
	 *
	 * @return int
	 */
	public function get_active_count(): int {
		return $this->attributes['active_count'];
	}

	/**
	 * Gets whether this product is activated on the requesting domain.
	 *
	 * Returns null when no domain was provided in the request.
	 *
	 * @since 3.0.0
	 *
	 * @return bool|null
	 */
	public function get_installed_here(): ?bool {
		return $this->attributes['installed_here'];
	}

	/**
	 * Gets the validation status string, or null when not provided.
	 *
	 * @since 3.0.0
	 *
	 * @return string|null A Validation_Status constant value, or null.
	 */
	public function get_validation_status(): ?string {
		return $this->attributes['validation_status'];
	}

	/**
	 * Whether the product's validation status is valid.
	 *
	 * @since 3.0.0
	 *
	 * @return bool
	 */
	public function is_valid(): bool {
		return $this->get_validation_status() === Validation_Status::VALID;
	}

	/**
	 * Whether the product has exceeded its activation seat limit.
	 *
	 * @since 3.0.0
	 *
	 * @return bool
	 */
	public function is_over_limit(): bool {
		return $this->get_site_limit() > 0 && $this->get_active_count() > $this->get_site_limit();
	}
}
