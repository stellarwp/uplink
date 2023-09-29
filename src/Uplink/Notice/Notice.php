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
	 * Whether the notice is dismissible.
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

	public function __construct(
		string $type,
		string $message,
		bool $dismissible = false,
		bool $alt = false,
		bool $large = false
	) {
		if ( ! in_array( $type, self::ALLOWED_TYPES, true ) ) {
			throw new InvalidArgumentException( sprintf(
					'Notice $type must be one of: %s',
					implode( ', ', self::ALLOWED_TYPES ) )
			);
		}

		if ( empty( $message ) ) {
			throw new InvalidArgumentException( 'The $message cannot be empty' );
		}

		$this->type        = $type;
		$this->message     = $message;
		$this->dismissible = $dismissible;
		$this->alt         = $alt;
		$this->large       = $large;
	}

	public function get(): string {
		$type = sprintf( 'notice-%s', sanitize_html_class( $this->type ) );

		$class_map = [
			'notice'         => true,
			$type            => true,
			'is-dismissible' => $this->dismissible,
			'notice-alt'     => $this->alt,
			'notice-large'   => $this->large,
		];

		$classes = '';

		foreach ( $class_map as $class => $include ) {
			if ( ! $include ) {
				continue;
			}

			$classes .= sprintf( ' %s', $class );
		}

		return sprintf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $classes ), esc_html( $this->message ) );
	}

}
