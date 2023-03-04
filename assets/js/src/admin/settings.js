jQuery(document).ready(function ($) {

	$( '#optionskit-topbar .notice' ).each( function() {
		$( this ).insertAfter( $( this ).parent().find( '.save-area' ) );
		$( this ).css( 'margin', '15px 0 0' );
	} );

	function refreshStripeConnect() {
        var gateway_mode = $('#stripe_gateway_mode').val();
		$( '.wpum-stripe-connect-account-info' ).each( function( i, obj ) {

			if ( $( obj ).data( 'gateway-mode' ) !== gateway_mode ) {
				return;
			}

			$.post( wpum_settings.ajaxurl, {
					action      : 'wpum_stripe_connect_account_info',
					nonce       : $( obj ).data( 'nonce' ),
					account_id  : $( obj ).data( 'account-id' ),
					gateway_mode: $( obj ).data( 'gateway-mode' ),
				},
				function( response ) {
					$( obj ).removeClass( 'notice-info' );
					if ( response.success ) {
						$( obj ).html( response.data.message + '<p>' + response.data.actions + '</p>' )
						if ( response.data.status === 'success' ) {
							$( obj ).addClass( 'notice-success' );
						} else if ( response.data.status === 'warning' ) {
							$( obj ).addClass( 'notice-warning' );
						}
					} else {
						$( obj ).find( 'p' ).html( response.data.message );
						$( obj ).addClass( 'notice-error' );
					}
				} )
				.fail( function() {
					alert( "Error" );
				} );
		} );
	}

	if ($('.wpum-stripe-connect-account-info').length) {
		refreshStripeConnect();
	}

	$('#stripe_gateway_mode').on('change', function() {
		refreshStripeConnect();
	});

	$(document).on('click', '#optionskit-navigation ul li a', function () {
		if ( $(this).attr('href') === '#/stripe') {
			refreshStripeConnect();
		}
	});

});
