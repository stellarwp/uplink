<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Notice;

use InvalidArgumentException;

/**
 * A Notice to display in wp-admin.
 */
final class Notice {

	public const INFO    = 'info';
	public const SUCCESS = 'success';
	public const WARNING = 'warning';
	public const ERROR   = 'error';

	public const ALLOWED_TYPES = [
		self::INFO,
		self::SUCCESS,
		self::WARNING,
		self::ERROR,
	];

	/**
	 * The notice type, one of the above constants.
	 *
	 * @var string
	 */
	private $type;

	/**
	 * The already translated message to display.
	 *
	 * @see __()
	 *
	 * @var string
	 */
	private $message;

	/**
	 * Whether this notice is dismissible.
	 *
	 * @var bool
	 */
	private $dismissible;

	/**
	 * Whether this is an alt-notice.
	 *
	 * @var bool
	 */
	private $alt;

	/**
	 * Whether this should be a large notice.
	 *
	 * @var bool
	 */
	private $large;

	/**
	 * Optional unique identifier used for persistent dismissal.
	 *
	 * @var string
	 */
	private $id;

	/**
	 * @param string $type        The notice type, one of the above constants.
	 * @param string $message     The already translated message to display.
	 * @param bool   $dismissible Whether this notice is dismissible.
	 * @param bool   $alt         Whether this is an alt-notice.
	 * @param bool   $large       Whether this should be a large notice.
	 * @param string $id          Optional unique ID for persistent dismissal.
	 */
	public function __construct(
		string $type,
		string $message,
		bool $dismissible = false,
		bool $alt = false,
		bool $large = false,
		string $id = ''
	) {
		if ( ! in_array( $type, self::ALLOWED_TYPES, true ) ) {
			throw new InvalidArgumentException(
				sprintf(
					__( 'Notice $type must be one of: %s', '%TEXTDOMAIN%' ),
					implode( ', ', self::ALLOWED_TYPES ) 
				)
			);
		}

		if ( empty( $message ) ) {
			throw new InvalidArgumentException( __( 'The $message cannot be empty', '%TEXTDOMAIN%' ) );
		}

		$this->type        = $type;
		$this->message     = $message;
		$this->dismissible = $dismissible;
		$this->alt         = $alt;
		$this->large       = $large;
		$this->id          = $id;
	}

	/**
	 * @return array{type: string, message: string, dismissible: bool, alt: bool, large: bool, id: string}
	 */
	public function toArray(): array {
		return [
			'type'        => $this->type,
			'message'     => $this->message,
			'dismissible' => $this->dismissible,
			'alt'         => $this->alt,
			'large'       => $this->large,
			'id'          => $this->id,
		];
	}
}
