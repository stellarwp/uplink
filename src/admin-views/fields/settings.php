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

$field          = Config::get_container()->get( License_Field::class );
$group          = $field->get_group_name( sanitize_title( $plugin->get_slug() ) );
$action_postfix = Config::get_hook_prefix_underscored();
$form_action    = is_multisite() && is_network_admin() ? network_admin_url( 'settings.php' ) : 'options.php';

?>
<?php if ( $show_title ) : ?>
	<h3><?php echo esc_html( $plugin->get_name() ); ?></h3>
<?php endif; ?>

<div class="stellarwp-uplink" data-js="stellarwp-uplink">
	<div class="stellarwp-uplink__settings">
		<?php do_action( 'stellarwp/uplink/' . Config::get_hook_prefix(). '/license_field_before_form', $plugin->get_slug() ) ?>
		<form method="post" action="<?php echo esc_url( $form_action ) ?>">
			<?php settings_fields( $group ); ?>
			<?php do_action( 'stellarwp/uplink/' . Config::get_hook_prefix(). '/license_field_before_field', $plugin->get_slug() ) ?>
			<?php if ( $show_title ) : ?>
				<table class="form-table" role="presentation">
			<?php endif; ?>
				<div class="stellarwp-uplink__license-field">
					<?php $field->do_settings_fields( $group, License_Field::get_section_name( $plugin ), $action_postfix, $show_title ); ?>
				</div>
			<?php if ( $show_title ) : ?>
				</table>
			<?php endif; ?>
			<?php do_action( 'stellarwp/uplink/' . Config::get_hook_prefix(). '/license_field_after_field', $plugin->get_slug() ) ?>
			<?php if ( $show_button ) : ?>
				<?php submit_button( esc_html__( 'Save Changes', '%TEXTDOMAIN%' ) );?>
			<?php endif; ?>
		</form>
		<?php do_action( 'stellarwp/uplink/' . Config::get_hook_prefix(). '/license_field_after_form', $plugin->get_slug() ) ?>
	</div>
</div>
