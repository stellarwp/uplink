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
        <table class="form-table" role="presentation">
            <?php do_settings_fields( $group, sprintf( '%s_%s', License_Field::LICENSE_FIELD_ID, sanitize_title( $plugin->get_name() ) ) ); ?>
        </table>
    </div>
</div>
