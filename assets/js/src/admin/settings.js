jQuery(document).ready(function ($) {

	$( '#optionskit-topbar .notice' ).each( function() {
		$( this ).insertAfter( $( this ).parent().find( '.save-area' ) );
		$( this ).css( 'margin', '15px 0 0' );
	} );


	if ($('#wpum-stripe-connect-account').length) {
		$.post( wpum_settings.ajaxurl, {
				action: 'wpum_stripe_connect_account_info',
				nonce: $('#wpum-stripe-connect-account').data('nonce'),
				account_id: $('#wpum-stripe-connect-account').data('account-id'),
			},
			function( response ) {
				$( '#wpum-stripe-connect-account' ).removeClass('notice-info');
				if ( response.success ) {
					$( '#wpum-stripe-connect-account' ).html( response.data.message + '<p>' + response.data.actions + '</p>' )
					if ( response.data.status === 'success' ) {
						$( '#wpum-stripe-connect-account' ).addClass('notice-success');
					} else if ( response.data.status === 'warning' ) {
						$( '#wpum-stripe-connect-account' ).addClass('notice-warning');
					}
				} else {
					$( '#wpum-stripe-connect-account p' ).html( response.data.message );
					$( '#wpum-stripe-connect-account' ).addClass('notice-error');
				}
			} )
			.fail( function() {
				alert( "Error" );
			} );
	}

});
