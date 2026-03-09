<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Licensing\Results;

use StellarWP\Uplink\Licensing\Enums\Validation_Status;
use StellarWP\Uplink\Utils\Cast;

/**
 * The outcome of validating a license for a product on a domain.
 *
 * Immutable value object hydrated from the POST /stellarwp/v4/licenses/validate response.
 * Mirrors the licensing service's Validation_Result API response shape.
 *
 * @since 3.0.0
 *
 * @phpstan-type LicenseData array{key: string, status: string}
 * @phpstan-type SubscriptionData array{product_slug: string, tier: string, site_limit: int, expiration_date: string, status: string}
 * @phpstan-type ActivationData array{domain: string, activated_at: string}
 * @phpstan-type ValidationAttributes array{
 *     status: string,
 *     license: ?LicenseData,
 *     subscription: ?SubscriptionData,
 *     activation: ?ActivationData,
 * }
 */
final class Validation_Result {

	/**
	 * The validation result attributes.
	 *
	 * @since 3.0.0
	 *
	 * @var ValidationAttributes
	 */
	protected array $attributes = [
		'status'       => '',
		'license'      => null,
		'subscription' => null,
		'activation'   => null,
	];

	/**
	 * Constructor for a Validation_Result.
	 *
	 * @since 3.0.0
	 *
	 * @phpstan-param ValidationAttributes $attributes
	 *
	 * @param array $attributes The validation result attributes.
	 *
	 * @return void
	 */
	public function __construct( array $attributes ) {
		$this->attributes = $attributes;
	}

	/**
	 * Creates a Validation_Result from an API response array.
	 *
	 * @since 3.0.0
	 *
	 * @param array<string, mixed> $data The validation data from the API response.
	 *
	 * @return self
	 */
	public static function from_array( array $data ): self {
		$license = isset( $data['license'] ) && is_array( $data['license'] ) ? [
			'key'    => Cast::to_string( $data['license']['key'] ?? '' ),
			'status' => Cast::to_string( $data['license']['status'] ?? '' ),
		] : null;

		$subscription = isset( $data['subscription'] ) && is_array( $data['subscription'] ) ? [
			'product_slug'    => Cast::to_string( $data['subscription']['product_slug'] ?? '' ),
			'tier'            => Cast::to_string( $data['subscription']['tier'] ?? '' ),
			'site_limit'      => Cast::to_int( $data['subscription']['site_limit'] ?? 0 ),
			'expiration_date' => Cast::to_string( $data['subscription']['expiration_date'] ?? '' ),
			'status'          => Cast::to_string( $data['subscription']['status'] ?? '' ),
		] : null;

		$activation = isset( $data['activation'] ) && is_array( $data['activation'] ) ? [
			'domain'       => Cast::to_string( $data['activation']['domain'] ?? '' ),
			'activated_at' => Cast::to_string( $data['activation']['activated_at'] ?? '' ),
		] : null;

		return new self(
			[
				'status'       => Cast::to_string( $data['status'] ?? '' ),
				'license'      => $license,
				'subscription' => $subscription,
				'activation'   => $activation,
			]
		);
	}

	/**
	 * Converts the validation result to an associative array matching the API response shape.
	 *
	 * @since 3.0.0
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return [
			'status'       => $this->get_status(),
			'is_valid'     => $this->is_valid(),
			'license'      => $this->get_license(),
			'subscription' => $this->get_subscription(),
			'activation'   => $this->get_activation(),
		];
	}

	/**
	 * Gets the validation status.
	 *
	 * @since 3.0.0
	 *
	 * @return string A Validation_Status constant value.
	 */
	public function get_status(): string {
		return $this->attributes['status'];
	}

	/**
	 * Gets the license data, or null if the key was not found.
	 *
	 * @since 3.0.0
	 *
	 * @phpstan-return LicenseData|null
	 */
	public function get_license(): ?array {
		return $this->attributes['license'];
	}

	/**
	 * Gets the subscription data, or null if not applicable.
	 *
	 * @since 3.0.0
	 *
	 * @phpstan-return SubscriptionData|null
	 */
	public function get_subscription(): ?array {
		return $this->attributes['subscription'];
	}

	/**
	 * Gets the activation data, or null if not activated on this domain.
	 *
	 * @since 3.0.0
	 *
	 * @phpstan-return ActivationData|null
	 */
	public function get_activation(): ?array {
		return $this->attributes['activation'];
	}

	/**
	 * Whether the validation status is valid.
	 *
	 * @since 3.0.0
	 *
	 * @return bool
	 */
	public function is_valid(): bool {
		return $this->get_status() === Validation_Status::VALID;
	}

	/**
	 * Returns the WP_Error code for a non-valid result.
	 *
	 * @since 3.0.0
	 *
	 * @return string An Error_Code constant value.
	 */
	public function error_code(): string {
		return Validation_Status::error_code( $this->get_status() );
	}

	/**
	 * Returns a human-readable error message for a non-valid result.
	 *
	 * Uses subscription data to provide contextual detail when available,
	 * such as the site limit for out_of_activations.
	 *
	 * @since 3.0.0
	 *
	 * @return string
	 */
	public function error_message(): string {
		$status       = $this->get_status();
		$subscription = $this->get_subscription();

		if ( $status === Validation_Status::OUT_OF_ACTIVATIONS && $subscription !== null ) {
			return sprintf(
				/* translators: %d: number of activation seats */
				__( 'All %d activation seats are in use.', '%TEXTDOMAIN%' ),
				$subscription['site_limit']
			);
		}

		return Validation_Status::message( $status );
	}
}
