<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Components\Settings\Fields;

use StellarWP\Uplink\Components\Controller;
use StellarWP\Uplink\View\Exceptions\FileNotFoundException;

final class Checkbox_Controller extends Controller {

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

	/**
	 * Get the value from the options table. This should automatically be stored by
	 * WordPress via register_setting().
	 *
	 * @see register_setting()
	 *
	 * @param  string  $id  The ID that matches the $option_name in register_setting().
	 * @param  bool    $default The default value if never saved before.
	 *
	 * @return bool
	 */
	private function get_value( string $id, bool $default ): bool {
		return (bool) get_option( $id, $default );
	}

}
