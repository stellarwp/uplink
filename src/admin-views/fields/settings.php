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

$group = Config::get_container()->get( License_Field::class )->get_group_name( sanitize_title( $plugin->get_slug() ) );

?>
<h3>
	<?php echo $plugin->get_name(); ?>
</h3>

<div class="wrap stellarwp-uplink" data-js="stellarwp-uplink">
	<div class="stellarwp-uplink__settings">
		<?php do_action( 'stellar_uplink_' . Config::get_hook_prefix(). 'license_field_before_form', $plugin->get_slug() ) ?>
		<form method="post" action="options.php">
			<?php settings_fields( $group ); ?>
			<?php do_action( 'stellar_uplink_' . Config::get_hook_prefix(). 'license_field_before_field', $plugin->get_slug() ) ?>
			<table class="form-table" role="presentation">
				<?php do_settings_fields( $group, sprintf( '%s_%s', License_Field::LICENSE_FIELD_ID, sanitize_title( $plugin->get_slug() ) ) ); ?>
			</table>
			<?php do_action( 'stellar_uplink_' . Config::get_hook_prefix(). 'license_field_after_field', $plugin->get_slug() ) ?>
			<?php submit_button( esc_html__( 'Save Changes', '%TEXTDOMAIN%' ) );?>
		</form>
		<?php do_action( 'stellar_uplink_' . Config::get_hook_prefix(). 'license_field_after_form', $plugin->get_slug() ) ?>
	</div>
</div>
