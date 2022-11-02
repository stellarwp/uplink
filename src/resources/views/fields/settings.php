<?php

use StellarWP\Uplink\Admin\License_Field;

if ( empty( $plugin ) ) {
	return;
}

$group = License_Field::get_group_name( sanitize_title( $plugin->get_name() ) );

?>
<h3>
	<?php echo $plugin->get_name(); ?>
</h3>

<div class="wrap stellarwp-uplink" data-js="stellarwp-uplink">
	<div class="stellarwp-uplink__settings">
		<?php do_action( 'stellar_uplink_license_field_before_form', $plugin->get_slug() ) ?>
		<form method="post" action="options.php">
			<?php settings_fields( $group ); ?>
			<?php do_action( 'stellar_uplink_license_field_before_field', $plugin->get_slug() ) ?>
			<table class="form-table" role="presentation">
				<?php do_settings_fields( $group, sprintf( '%s_%s', License_Field::LICENSE_FIELD_ID, sanitize_title( $plugin->get_name() ) ) ); ?>
			</table>
			<?php do_action( 'stellar_uplink_license_field_after_field', $plugin->get_slug() ) ?>
			<?php submit_button( esc_html__( 'Save Changes', 'stellar-uplink' ) );?>
		</form>
		<?php do_action( 'stellar_uplink_license_field_after_form', $plugin->get_slug() ) ?>
	</div>
</div>
