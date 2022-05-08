let stellarUplink = stellarUplink || {};

( function( $, obj ) {

	obj.init = function() {
		$( '.stellar-uplink-license-key-field' ).each( function() {
			var $el = $( this );
			var $field = $el.find( 'input' );

			if ( '' === $field.val().trim() ) {
				$el.find( '.license-test-results' ).hide();
			}

			obj.valdateKey( $el );
		} );

		$( document ).on( 'change', '.stellar-uplink-license-key-field', function() {
			const $el = $( this );
			obj.valdateKey( $el );
		} );
	};

	obj.validateKey = function( $el ) {
		const fieldID        = $el.attr( 'id' );
		const slug           = $el.data( 'slug' );
		let $validityMessage = $( fieldID + ' .key-validity' );

		if ( '' === $( fieldID + ' input' ).val().trim() ) {
			return;
		}

		$( fieldID + ' .license-test-results' ).show();
		$( fieldID + ' .tooltip' ).hide();
		$( fieldID + ' .ajax-loading-license' ).show();

		$validityMessage.hide();

		// Strip whitespace from key
		let licenseKey = $( fieldID + ' input' ).val().trim();
		$( fieldID + ' input' ).val( licenseKey );

		var data = {
			action: 'pue-validate-key_' + slug,
			key: licenseKey,
			_wpnonce: $( fieldID + ' .wp-nonce' ).val()
		};

		$.post( ajaxurl, data, function ( response ) {
			var data = $.parseJSON( response );

			$( fieldID + ' .ajax-loading-license' ).hide();
			$validityMessage.show();
			$validityMessage.html( data.message );

			switch ( data.status ) {
				case 1: $validityMessage.addClass( 'valid-key' ).removeClass( 'invalid-key' ); break;
				case 2: $validityMessage.addClass( 'valid-key service-msg' ); break;
				default: $validityMessage.addClass( 'invalid-key' ).removeClass( 'valid-key' ); break;
			}
		} );
	};

	$( function() {
		obj.init();
	} );
} )( jQuery, stellarUplink );
