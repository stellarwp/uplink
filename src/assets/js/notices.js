/**
 * Appends license key notifications inline within the plugin table.
 *
 * This is done via JS because the options for achieving the same things
 * server-side are currently limited.
 */
(function( $, my ) {
	'use strict';

	my.init = function() {
		$( 'tr.active' ).each( function() {
			var $el = $( this );
			var slug = $el.data( 'slug' ).replace( '-', '_' );
			var row = window[`stellarwp_uplink_plugin_notices_${slug}`];

			if (!row) {
				return;
			}

			for ( var plugin_file in stellarwp_uplink_plugin_notices ) {
				if ( ! stellarwp_uplink_plugin_notices.hasOwnProperty( plugin_file ) ) { // eslint-disable-line no-prototype-builtins,max-len
					continue;
				}

				var $row = $( stellarwp_uplink_plugin_notices[ plugin_file ].message_row_html );

				// Add the .update class to the plugin row and append our new row with the update message
				$el.addClass( 'update' ).after( $row );
			}
		} );
	};

	$( function() {
		my.init();
	});
})( jQuery, {} );
