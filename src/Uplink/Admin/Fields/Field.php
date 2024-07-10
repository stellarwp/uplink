<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Admin\Fields;

use StellarWP\Uplink\Admin\License_Field;
use StellarWP\Uplink\Uplink;
use StellarWP\Uplink\Config;
use StellarWP\Uplink\Resources\Collection;
use StellarWP\Uplink\Resources\Resource;
// Use function statement is problematic with Strauss.
use StellarWP\Uplink as UplinkNamespace;

class Field {
	public const STELLARWP_UPLINK_GROUP = 'stellarwp_uplink_group';

	/**
	 * @var Resource
	 */
	protected Resource $resource;

	/**
	 * @var string
	 */
	protected string $field_id = '';

	/**
	 * @var string
	 */
	protected string $field_name = '';

	/**
	 * @var string
	 */
	protected string $label = '';

	/**
	 * @var string
	 */
	protected string $slug = '';

	/**
	 * @var bool
	 */
	protected bool $show_label = false;

	/**
	 * @var bool
	 */
	protected bool $show_heading = false;

	/**
	 * Constructor!
	 *
	 * @param string $slug Field slug.
	 */
	public function __construct( string $slug ) {
		$this->slug = $slug;

		$collection = Config::get_container()->get( Collection::class );
		$resource   = $collection->get( $slug );

		if ( ! $resource instanceof Resource ) {
			throw new \InvalidArgumentException( sprintf( 'Resource with slug "%s" does not exist.', $slug ) );
		}

		$this->resource = $resource;
	}

	/**
	 * Gets the field ID.
	 *
	 * @return string
	 */
	public function get_field_id(): string {
		if ( empty( $this->field_id ) ) {
			return $this->resource->get_license_object()->get_key_option_name();
		}

		return $this->field_id;
	}

	/**
	 * Gets the field name.
	 *
	 * @return string
	 */
	public function get_field_name(): string {
		return $this->field_name;
	}

	/**
	 * Gets the field value.
	 *
	 * @return string
	 */
	public function get_field_value(): string {
		return $this->resource->get_license_key();
	}

	/**
	 * Gets the  HTML for the key status information.
	 *
	 * @return string
	 */
	public function get_key_status_html(): string {
		ob_start();
		include Config::get_container()->get( Uplink::UPLINK_ADMIN_VIEWS_PATH ) . '/fields/key-status.php';
		$html = ob_get_clean();

		/**
		 * Filters the key status HTML.
		 *
		 * @param string $html The HTML.
		 * @param string $slug The plugin slug.
		 */
		return apply_filters( 'stellarwp/uplink/' . Config::get_hook_prefix() . '/license_field_key_status_html', $html, $this->get_slug() );
	}

	/**
	 * Gets the field label.
	 *
	 * @return string
	 */
	public function get_label(): string {
		return $this->label;
	}

	/**
	 * Gets the nonce action.
	 *
	 * @return string
	 */
	public function get_nonce_action() : string {
		/**
		 * Filters the nonce action.
		 *
		 * @param string $group The Settings group.
		 */
		return apply_filters( 'stellarwp/uplink/' . Config::get_hook_prefix() . '/license_field_group_name', Config::get_hook_prefix_underscored() );
	}

	/**
	 * Gets the nonce field.
	 *
	 * @return string
	 */
	public function get_nonce_field(): string {
		$nonce_name   = "stellarwp-uplink-license-key-nonce__" . $this->get_slug();
		$nonce_action = Config::get_container()->get( License_Field::class )->get_group_name();

		return '<input type="hidden" class="wp-nonce-fluent" name="' . esc_attr( $nonce_name ) . '" value="' . wp_create_nonce( $nonce_action ) . '" />';
	}

	/**
	 * Gets the field placeholder.
	 *
	 * @return string
	 */
	public function get_placeholder(): string {
		return __( 'License key', '%TEXTDOMAIN%' );
	}

	/**
	 * Gets the product name.
	 *
	 * @return string
	 */
	public function get_product(): string {
		return $this->resource->get_path();
	}

	/**
	 * Gets the product slug.
	 *
	 * @return string
	 */
	public function get_product_slug(): string {
		return $this->resource->get_slug();
	}

	/**
	 * Gets the field slug.
	 *
	 * @return string
	 */
	public function get_slug(): string {
		return $this->slug;
	}

	/**
	 * Gets the field classes.
	 *
	 * @return string
	 */
	public function get_classes(): string {
		return 'stellarwp-uplink-license-key-field';
	}

	/**
	 * Renders the field.
	 *
	 * @return string
	 */
	public function render(): string {
		if ( $this->resource->is_using_oauth() ) {
			ob_start();
			UplinkNamespace\render_authorize_button( $this->get_slug() );
			return ob_get_clean();
		}
		$field = $this;
		$group = Config::get_container()->get( License_Field::class )->get_group_name( $this->get_slug() );
		Config::get_container()->get( License_Field::class )->enqueue_assets();
		ob_start();
		include Config::get_container()->get( Uplink::UPLINK_ADMIN_VIEWS_PATH ) . '/fields/field.php';
		$html = ob_get_clean();

		/**
		 * Filters the field HTML.
		 *
		 * @param string $html The HTML.
		 * @param string $slug The plugin slug.
		 */
		return apply_filters( 'stellarwp/uplink/' . Config::get_hook_prefix() . '/license_field_html', $html, $this->get_slug() );
	}

	/**
	 * Sets the field ID.
	 *
	 * @param string $field_id Field ID.
	 *
	 * @return self
	 */
	public function set_field_id( string $field_id ): self {
		$this->field_id = $field_id;

		return $this;
	}

	/**
	 * Sets the field name.
	 *
	 * @param string $field_name Field name.
	 *
	 * @return self
	 */
	public function set_field_name( string $field_name ): self {
		$this->field_name = $field_name;

		return $this;
	}

	/**
	 * Sets the field label.
	 *
	 * @param string $label Field label.
	 *
	 * @return self
	 */
	public function set_label( string $label ): self {
		$this->label = $label;

		return $this;
	}

	/**
	 * Whether to show the field heading.
	 *
	 * @return bool
	 */
	public function should_show_heading(): bool {
		return $this->show_heading;
	}

	/**
	 * Whether to show the field label.
	 *
	 * @return bool
	 */
	public function should_show_label(): bool {
		return $this->show_label;
	}

	/**
	 * Whether to show the field heading.
	 *
	 * @param bool $state Whether to show the field heading.
	 *
	 * @return $this
	 */
	public function show_heading( bool $state = true ): self {
		$this->show_heading = $state;

		return $this;
	}

	/**
	 * Whether to show the field label.
	 *
	 * @param bool $state Whether to show the field label.
	 *
	 * @return $this
	 */
	public function show_label( bool $state = true ): self {
		$this->show_label = $state;

		return $this;
	}
}
