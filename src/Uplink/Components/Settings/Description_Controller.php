<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Components\Settings;

use StellarWP\Uplink\Components\Controller;
use StellarWP\Uplink\View\Exceptions\FileNotFoundException;

final class Description_Controller extends Controller {

	/**
	 * The view file, without ext, relative to the root views directory.
	 */
	public const VIEW = 'settings/description';

	/**
	 * Render a Settings Description component.
	 *
	 * @param  array{description?: string}  $args
	 *
	 * @throws FileNotFoundException
	 */
	public function render( array $args = [] ): void {
		$description = $args['description'] ?? '';

		if ( ! $description ) {
			return;
		}

		echo $this->view->render( self::VIEW, [
			'description' => $description,
		] );
	}

}
