( function() {
	var config   = window.uplinkNoticeDismiss;
	var TTL      = config.ttl;
	var META_KEY = config.metaKey;

	document.addEventListener( 'click', function( e ) {
		if ( ! e.target.classList.contains( 'notice-dismiss' ) ) {
			return;
		}

		var notice = e.target.closest( '[data-uplink-notice-id]' );
		if ( ! notice ) {
			return;
		}

		var id = notice.getAttribute( 'data-uplink-notice-id' );

		window.wp.apiFetch( { path: '/wp/v2/users/me' } ).then( function( user ) {
			var dismissed = Object.assign( {}, user.meta[ META_KEY ] || {} );
			dismissed[ id ] = Math.floor( Date.now() / 1000 ) + TTL;

			try {
				return window.wp.apiFetch( {
					path:   '/wp/v2/users/me',
					method: 'PATCH',
					data:   { meta: { [ META_KEY ]: dismissed } },
				} );
			} catch ( error ) {
				console.error( error );
				return false;
			}
		} );
	} );
} )();
