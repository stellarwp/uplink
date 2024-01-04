<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Components\Settings\Fields;

use StellarWP\Uplink\Components\Controller;
use StellarWP\Uplink\Components\Settings\Traits\Get_Value_Trait;
use StellarWP\Uplink\View\Exceptions\FileNotFoundException;

class Checkbox_Controller extends Controller {

	use Get_Value_Trait;

	/**
	 * The view file, without ext, relative to the root views directory.
	 */
	public const VIEW = 'settings/fields/checkbox';

	/**
	 * Render a WordPress settings checkbox field.
	 *
	 * @param  array{id?: string, label?: string, description?: string, default?: bool, data_attr?: array<string, mixed>, classes?: string[]}  $args
	 *
	 * @throws FileNotFoundException
	 */
	public function render( array $args = [] ): void {
		$id          = $args['id'] ?? '';
		$label       = $args['label'] ?? '';
		$description = $args['description'] ?? '';
		$default     = $args['default'] ?? false;
		$classes     = $args['classes'] ?? [ $id ];

		echo $this->view->render( self::VIEW, [
			'id'          => $id,
			'label'       => $label,
			'description' => $description,
			'data_attr'   => $this->data_attr( $args['data_attr'] ?? [] ),
			'classes'     => $this->classes( $classes ),
			'value'       => $this->get_value( $id, $default ),
		] );
	}

}
