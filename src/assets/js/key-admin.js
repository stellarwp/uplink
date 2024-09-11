( function( $, obj ) {
	obj.init = function() {
		$( '.stellarwp-uplink-license-key-field' ).each( function() {
			var $el = $( this );
			var $field = $el.find( 'input[type="text"]' );
			var $oauth = $el.parent().next( '.uplink-authorize-container' );

			if ( '' === $field.val().trim() ) {
				$el.find( '.license-test-results' ).hide();
				$oauth.hide();
			}

			obj.validateKey( $el );
		} );

		$( document ).on( 'change', '.stellarwp-uplink-license-key-field', function() {
			const $el = $( this );
			obj.validateKey( $el );
		} );
	};

	obj.validateKey = function( $el ) {
		const field          = $el.find( 'input[type="text"]' )
		const action         = $el.data( 'action' );
		const slug           = $el.data( 'plugin-slug' );
		const $oauth         = $el.parent().next( '.uplink-authorize-container' ).find( 'a.uplink-authorize');
		let $validityMessage = $el.find( '.key-validity' );

		if ( '' === field.val().trim() ) {
			$oauth.hide();
			return;
		}

		$( $el ).find( '.license-test-results' ).show();
		$( $el ).find( '.tooltip' ).hide();
		$( $el ).find( '.ajax-loading-license' ).show();

		$validityMessage.hide();

		// Strip whitespace from key
		let licenseKey = field.val().trim();
		field.val( licenseKey );

		const nonceField = $($el).find('.wp-nonce-fluent') || $($el).find('.wp-nonce');

		const data = {
			action: window[`stellarwp_config_${action}`]['action'],
			slug: slug,
			key: licenseKey,
			_wpnonce: nonceField.val()
		};

		$.post(ajaxurl, data, function (response) {
			$validityMessage.show();
			$validityMessage.html(response.message);

			switch (response.status) {
				case 1:
					$validityMessage.addClass('valid-key').removeClass('invalid-key');
					$oauth.show();
					break;
				case 2:
					$validityMessage.addClass('valid-key service-msg');
					$oauth.show();
					break;
				default:
					$validityMessage.addClass('invalid-key').removeClass('valid-key');
					break;
			}
		}).fail(function(error) {
			$validityMessage.show();
			$validityMessage.html(error.message);
		}).always(function() {
			$($el).find('.ajax-loading-license').hide();
		});
	};

	$( function() {
		obj.init();
	} );
} )( jQuery, {}	 );
