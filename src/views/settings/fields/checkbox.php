<?php declare( strict_types=1 );

/**
 * @var string $id The unique field ID.
 * @var string $label The label to display.
 * @var string $description The description to display.
 * @var string $data_attr Escaped and formatted data attributes.
 * @var string $classes The CSS classes added to the fieldset.
 * @var bool $value The current value.
 */
defined( 'ABSPATH' ) || exit;

use StellarWP\Uplink\Components\Settings\Description_Controller;

use function StellarWP\Uplink\render_component;

?>
<fieldset <?php echo $data_attr ?><?php echo $classes ? sprintf( ' class="%s"', esc_attr( $classes ) ) : '' ?>>
	<legend class="screen-reader-text">
		<span><?php echo esc_html( $label ) ?></span>
	</legend>
	<label for="<?php echo esc_attr( $id ) ?>">
		<input
			type="checkbox"
			name="<?php echo esc_attr( $id ) ?>"
			id="<?php echo esc_attr( $id ) ?>"
			value="1"
			<?php checked( 1, $value ) ?>
		>
		<?php echo esc_html( $label ) ?>
		<?php render_component( Description_Controller::class, [
			'description' => $description,
		] ) ?>
	</label>
</fieldset>
