var stellarwp_uplink_plugin_notices = stellarwp_uplink_plugin_notices || {};

/**
 * Appends license key notifications inline within the plugin table.
 *
 * This is done via JS because the options for achieving the same things
 * server-side are currently limited.
 */
(function( $, my ) {
    'use strict';

    my.init = function() {
        for ( var plugin_file in stellarwp_uplink_plugin_notices ) {
            if ( ! stellarwp_uplink_plugin_notices.hasOwnProperty( plugin_file ) ) { // eslint-disable-line no-prototype-builtins,max-len
                continue;
            }

            var $row = $( stellarwp_uplink_plugin_notices[ plugin_file ].message_row_html );
            var $active_plugin_row = $( 'tr[data-plugin="' + plugin_file + '"].active' );

            // Add the .update class to the plugin row and append our new row with the update message
            $active_plugin_row.addClass( 'update' ).after( $row );
        }
    };

    $( function() {
        console.log(stellarwp_uplink_plugin_notices)
        if ( 'object' === typeof stellarwp_uplink_plugin_notices ) {
            my.init();
        }
    });
})( jQuery, stellarwp_uplink_plugin_notices );
