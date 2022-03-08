(function($) {

	$( document ).ready( function() {

		$('.wpum-registration-form form').submit(function(e){
			if ( ! $('input[name="wpum_stripe_plan"]').length ) {
				return;
			}

			e.preventDefault();

			$('.wpum-message').remove();

			var $submit = $("input[type=submit]",this);
			$submit.attr('disabled', 'disabled').css('opacity', '0.8').val('Registering...');
			var self = this;

			var data = $( this ).serialize();
			data = data + '&' + $submit.attr('name') + '=1';

			$.post( wpum_stripe.ajaxurl, {
					action: 'wpum_stripe_register',
					data: data
				},
				function( response ) {
					if ( !response.success && response.data ) {
						$( self ).before( response.data );
						$submit.removeAttr( 'disabled' ).css( 'opacity', '1' ).val( 'Register' );
						return;
					}

					if ( response.data.id ) {
						const stripe = Stripe( wpum_stripe.stripe );

						stripe.redirectToCheckout( {
							sessionId: response.data.id,
						} );
					} else {
						window.location.href = '/';
					}
				} )
				.fail( function() {
					alert( "error" );
					$submit.removeAttr('disabled').css('opacity', '1').val('Register');
				} );
		} );

		$( '#wpum-stripe-manage-billing').on('click', function(e) {
			e.preventDefault();

			$( this ).attr( 'disabled', 'disabled' ).css( 'opacity', '0.8' )
			$.post( wpum_stripe.ajaxurl, {
					action: 'wpum_stripe_manage_billing',
				},
				function( response ) {
					if ( response.data.url ) {
						window.location.href = response.data.url;
					}
					$( this ).removeAttr( 'disabled' ).css( 'opacity', '1' );
				} )
				.fail( function() {
					alert( "error" );
					$( this ).removeAttr( 'disabled' ).css( 'opacity', '1' );
				} );
		} );

		$( '.wpum-stripe-checkout').on('click', function(e) {
			e.preventDefault();

			var plan_id = $(this).data('plan-id');

			$( this ).attr( 'disabled', 'disabled' ).css( 'opacity', '0.8' )
			$.post( wpum_stripe.ajaxurl, {
					action: 'wpum_stripe_checkout',
					plan: plan_id,
				},
				function( response ) {
					if ( response.data.id ) {
						const stripe = Stripe( wpum_stripe.stripe );

						stripe.redirectToCheckout( {
							sessionId: response.data.id,
						} );
					}
					$( this ).removeAttr( 'disabled' ).css( 'opacity', '1' );
				} )
				.fail( function() {
					alert( "error" );
					$( this ).removeAttr( 'disabled' ).css( 'opacity', '1' );
				} );
		} );

	});

}(jQuery));
