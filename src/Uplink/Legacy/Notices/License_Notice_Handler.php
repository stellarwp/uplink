<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Legacy\Notices;

use StellarWP\Uplink\Legacy\License_Repository;
use StellarWP\Uplink\Notice\Notice;
use StellarWP\Uplink\Notice\Notice_Controller;
use StellarWP\Uplink\Utils\Cast;
use StellarWP\Uplink\Utils\Version;

/**
 * Displays consolidated admin notices for legacy licenses that are not
 * covered by a v3 unified license.
 *
 * Only fires on the leader Uplink instance to prevent duplicate notices
 * when multiple plugins bundle Uplink.
 *
 * @since 3.0.0
 */
class License_Notice_Handler {

	/**
	 * User meta key that stores a map of notice ID => dismissed-until timestamp.
	 *
	 * @since 3.0.0
	 */
	public const DISMISSED_META_KEY = 'stellarwp_uplink_dismissed_notices';

	/**
	 * How long a dismissal lasts in seconds (7 days).
	 *
	 * @since 3.0.0
	 */
	public const DISMISS_TTL = 7 * DAY_IN_SECONDS;

	/**
	 * @var License_Repository
	 */
	private $repository;

	/**
	 * @var Notice_Controller
	 */
	private $controller;

	public function __construct( License_Repository $repository, Notice_Controller $controller ) {
		$this->repository = $repository;
		$this->controller = $controller;
	}

	/**
	 * Display notices for inactive legacy licenses that are not covered by a v3 unified license.
	 *
	 * @action admin_notices
	 *
	 * @return void
	 */
	public function display(): void {
		if ( ! Version::should_handle( 'legacy_license_notices' ) ) {
			return;
		}

		$licenses = $this->repository->all_inactive();

		if ( empty( $licenses ) ) {
			return;
		}

		// Group by brand, skipping any slug already covered by v3 or dismissed by the user.
		$by_brand = [];

		foreach ( $licenses as $license ) {
			if ( stellarwp_uplink_is_feature_available( $license->slug ) ) {
				continue;
			}

			$brand = $license->brand;
			$id    = 'legacy-' . $brand;

			if ( $this->is_dismissed( $id ) ) {
				continue;
			}

			if ( ! isset( $by_brand[ $brand ] ) ) {
				$by_brand[ $brand ] = [
					'id'       => $id,
					'page_url' => $license->page_url,
					'count'    => 0,
				];
			}

			++$by_brand[ $brand ]['count'];
		}

		if ( empty( $by_brand ) ) {
			return;
		}

		foreach ( $by_brand as $brand => $data ) {
			$this->render_notice( $brand, $data );
		}

		$this->enqueue_dismiss_script();
	}

	/**
	 * Whether a notice is currently dismissed for the current user.
	 *
	 * @since 3.0.0
	 *
	 * @param string $id The notice ID.
	 *
	 * @return bool
	 */
	private function is_dismissed( string $id ): bool {
		$dismissed = (array) get_user_meta( get_current_user_id(), self::DISMISSED_META_KEY, true );

		return isset( $dismissed[ $id ] ) && Cast::to_int( $dismissed[ $id ] ) > time();
	}

	/**
	 * Render a single brand's license notice.
	 *
	 * @since 3.0.0
	 *
	 * TODO: Decide on messaging for all brands.
	 *
	 * @param string                                          $brand
	 * @param array{id: string, page_url: string, count: int} $data
	 *
	 * @return void
	 */
	private function render_notice( string $brand, array $data ): void {
		$message = sprintf(
			_n(
				'You have an inactive %1$s license. Please <a href="%2$s">activate it</a> to receive critical updates and new features.',
				'You have %3$d inactive %1$s licenses. Please <a href="%2$s">activate them</a> to receive critical updates and new features.',
				$data['count'],
				'%TEXTDOMAIN%'
			),
			ucfirst( $brand ),
			esc_url( $data['page_url'] ),
			$data['count']
		);

		$this->controller->render(
			( new Notice( Notice::ERROR, $message, true, false, false, $data['id'] ) )->toArray()
		);
	}

	/**
	 * Register and enqueue the notice dismiss script, passing config via wp_localize_script.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	private function enqueue_dismiss_script(): void {
		$handle = 'stellarwp-uplink-notice-dismiss';

		if ( ! wp_script_is( $handle, 'registered' ) ) {
			$assets_url = trailingslashit( plugin_dir_url( __DIR__ . '/index.php' ) );

			wp_register_script(
				$handle,
				$assets_url . 'assets/js/notice-dismiss.js',
				[ 'wp-api-fetch' ],
				null,
				[ 'in_footer' => true ]
			);

			wp_localize_script(
				$handle,
				'uplinkNoticeDismiss',
				[
					'ttl'     => self::DISMISS_TTL,
					'metaKey' => self::DISMISSED_META_KEY,
				]
			);
		}

		wp_enqueue_script( $handle );
	}
}
