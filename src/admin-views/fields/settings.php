<?php

/**
 * @var Resource $resource The Resource object.
 * @var bool $show_button Whether to show the submit button. Default true.
 * @var bool $show_title Whether to show the title. Default true.
 */

use StellarWP\Uplink\Admin\License_Field;
use StellarWP\Uplink\Config;

if ( empty( $plugin ) ) {
	return;
}

$field = Config::get_container()->get( License_Field::class );
$group = $field->get_group_name( sanitize_title( $plugin->get_slug() ) );

?>
<?php if ( $show_title ) : ?>
	<h3><?php echo esc_html( $plugin->get_name() ); ?></h3>
<?php endif; ?>

<div class="stellarwp-uplink" data-js="stellarwp-uplink">
	<div class="stellarwp-uplink__settings">
		<?php do_action( 'stellar_uplink_' . Config::get_hook_prefix(). 'license_field_before_form', $plugin->get_slug() ) ?>
		<form method="post" action="options.php">
			<?php settings_fields( $group ); ?>
			<?php do_action( 'stellar_uplink_' . Config::get_hook_prefix(). 'license_field_before_field', $plugin->get_slug() ) ?>
			<?php if ( $show_title ) : ?>
				<table class="form-table" role="presentation">
			<?php endif; ?>
				<div class="stellarwp-uplink__license-field">
					<?php $field->do_settings_fields( $group, sprintf( '%s_%s', License_Field::LICENSE_FIELD_ID, sanitize_title( $plugin->get_slug() ) ), $show_title ); ?>
				</div>
			<?php if ( $show_title ) : ?>
				</table>
			<?php endif; ?>
			<?php do_action( 'stellar_uplink_' . Config::get_hook_prefix(). 'license_field_after_field', $plugin->get_slug() ) ?>
			<?php if ( $show_button ) : ?>
				<?php submit_button( esc_html__( 'Save Changes', '%TEXTDOMAIN%' ) );?>
			<?php endif; ?>
		</form>
		<?php do_action( 'stellar_uplink_' . Config::get_hook_prefix(). 'license_field_after_form', $plugin->get_slug() ) ?>
	</div>
</div>
