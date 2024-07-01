<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Admin\Fields;

use StellarWP\Uplink\Config;
use StellarWP\Uplink\Uplink;

class Form {
	/**
	 * @var array<string, Field>
	 */
	protected array $fields = [];

	/**
	 * @var string
	 */
	protected string $slug = '';

	/**
	 * @var bool
	 */
	protected bool $show_button = true;

	/**
	 * @var string
	 */
	protected string $button_text = '';

	/**
	 * Adds a field to the form.
	 *
	 * @param Field $field
	 *
	 * @return $this
	 */
	public function add_field( Field $field ): self {
		$this->fields[ $field->get_slug() ] = $field;

		return $this;
	}

	/**
	 * Gets the button text.
	 *
	 * @return string
	 */
	public function get_button_text(): string {
		if ( empty( $this->button_text ) ) {
			return esc_html__( 'Save Changes', '%TEXTDOMAIN%' );
		}

		return $this->button_text;
	}

	/**
	 * Gets the fields.
	 *
	 * @return array<string, Field>
	 */
	public function get_fields(): array {
		return $this->fields;
	}

	/**
	 * Renders the form.
	 *
	 * @return string
	 */
	public function render(): string {
		ob_start();
		include Config::get_container()->get( Uplink::UPLINK_ADMIN_VIEWS_PATH ) . '/fields/form.php';
		$html = ob_get_clean();

		return apply_filters( 'stellarwp/uplink/' . Config::get_hook_prefix() . '/license_form_html', $html );
	}

	/**
	 * Sets the submit button text.
	 *
	 * @param string $button_text The text to display on the button.
	 *
	 * @return $this
	 */
	public function set_button_text( string $button_text ): self {
		$this->button_text = $button_text;

		return $this;
	}

	/**
	 * Whether to show the field label.
	 *
	 * @param bool   $state       Whether to show the field label.
	 * @param string $button_text The button text.
	 *
	 * @return $this
	 */
	public function show_button( bool $state = true, string $button_text = '' ): self {
		if ( ! empty( $button_text ) ) {
			$this->set_button_text( $button_text );
		}

		$this->show_button = $state;

		return $this;
	}
}
