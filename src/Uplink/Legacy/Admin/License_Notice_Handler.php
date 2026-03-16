<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Legacy\Admin;

use StellarWP\Uplink\Legacy\License_Repository;
use StellarWP\Uplink\Notice\Notice;
use StellarWP\Uplink\Notice\Notice_Controller;
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

		// Group by brand, skipping any slug already covered by v3.
		$by_brand = [];

		foreach ( $licenses as $license ) {
			if ( stellarwp_uplink_is_feature_available( $license->slug ) ) {
				continue;
			}

			$brand = $license->brand;

			if ( ! isset( $by_brand[ $brand ] ) ) {
				$by_brand[ $brand ] = [
					'page_url' => $license->page_url,
					'count'    => 0,
				];
			}

			$by_brand[ $brand ]['count']++;
		}

		foreach ( $by_brand as $brand => $data ) {
			$this->render_notice( $brand, $data );
		}
	}

	/**
	 * Render a single brand's license notice.
	 *
	 * @since 3.0.0
	 *
	 * @param string                                   $brand
	 * @param array{page_url: string, count: int} $data
	 *
	 * @return void
	 */
	private function render_notice( string $brand, array $data ): void {
		$message = sprintf(
			_n(
				'Your %1$s add-on is not receiving critical updates and new features because you have an inactive license. Please <a href="%2$s">activate your license</a> to receive updates.',
				'Your %1$s add-ons are not receiving critical updates and new features because you have %3$d inactive license keys. Please <a href="%2$s">activate your licenses</a> to receive updates.',
				$data['count'],
				'%TEXTDOMAIN%'
			),
			ucfirst( $brand ),
			esc_url( $data['page_url'] ),
			$data['count']
		);

		$this->controller->render( ( new Notice( Notice::ERROR, $message ) )->toArray() );
	}
}
