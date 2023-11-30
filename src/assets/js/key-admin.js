( function( $, obj ) {

	// TODO: This all needs a look over for the situation where multiple license fields appear together.
	obj.init = function() {
		$( '.stellarwp-uplink-license-key-field' ).each( function() {
			var $el = $( this );
			var $field = $el.find( 'input[type="text"]' );

			if ( '' === $field.val().trim() ) {
				$el.find( '.license-test-results' ).hide();
			}

			obj.validateKey( $el );
		} );

		$( document ).on( 'change', '.stellarwp-uplink-license-key-field', function() {
			const $el = $( this );
			obj.validateKey( $el );
		} );
	};

	obj.validateKey = function( $el ) {
		const field       	 = $el.find( 'input[type="text"]' )
		const plugin         = $el.data( 'plugin' );
		const slug           = $el.data( 'plugin-slug' );
		let $validityMessage = $el.find( '.key-validity' );

		if ( '' === field.val().trim() ) {
			return;
		}

		$( $el ).find( '.license-test-results' ).show();
		$( $el ).find( '.tooltip' ).hide();
		$( $el ).find( '.ajax-loading-license' ).show();

		$validityMessage.hide();

		// Strip whitespace from key
		let licenseKey = field.val().trim();
		field.val( licenseKey );

		const data = {
			action: window[`stellarwp_config_${slug}`]['action'],
			plugin: plugin,
			key: licenseKey,
			_wpnonce: $($el).find('.wp-nonce').val()
		};

		$.post(ajaxurl, data, function (response) {
			$.each(response, function (index, value) {
				$validityMessage.show();
				$validityMessage.html(value.message);

				switch (value.status) {
					case 1:
						$validityMessage.addClass('valid-key').removeClass('invalid-key');
						break;
					case 2:
						$validityMessage.addClass('valid-key service-msg');
						break;
					default:
						$validityMessage.addClass('invalid-key').removeClass('valid-key');
						break;
				}
			});
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
