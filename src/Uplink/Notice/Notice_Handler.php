<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Notice;

/**
 * An improved admin notice system for general messages.
 *
 * @see \StellarWP\Uplink\Admin\Notice
 */
final class Notice_Handler {

	public const TRANSIENT = 'stellarwp_uplink_notices';

	/**
	 * Handles rendering notices.
	 *
	 * @var Notice_Controller
	 */
	private $controller;

	/**
	 * @var Notice[]
	 */
	private $notices;

	public function __construct( Notice_Controller $controller ) {
		$this->notices    = $this->all();
		$this->controller = $controller;
	}

	/**
	 * Add a notice to display.
	 *
	 * @param  Notice  $notice
	 *
	 * @return void
	 */
	public function add( Notice $notice ): void {
		$this->notices = array_merge( $this->all(), [ $notice ] );
		$this->save();
	}

	/**
	 * Display all notices and then clear them.
	 *
	 * @action admin_notices
	 *
	 * @return void
	 */
	public function display(): void {
		if ( count( $this->notices ) <= 0 ) {
			return;
		}

		foreach ( $this->notices as $notice ) {
			$this->controller->render( $notice->toArray() );
		}

		$this->clear();
	}

	/**
	 * Get all notices.
	 *
	 * @return Notice[]
	 */
	private function all(): array {
		return array_filter( (array) get_transient( self::TRANSIENT ) );
	}

	/**
	 * Save the existing state of notices.
	 *
	 * @return bool
	 */
	private function save(): bool {
		return set_transient( self::TRANSIENT, $this->notices, 300 );
	}

	/**
	 * Clear all notices.
	 *
	 * @return bool
	 */
	private function clear(): bool {
		return delete_transient( self::TRANSIENT );
	}

}
