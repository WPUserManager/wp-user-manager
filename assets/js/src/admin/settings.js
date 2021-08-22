jQuery(document).ready(function ($) {

	$( '#optionskit-topbar .notice' ).each( function() {
		$( this ).insertAfter( $( this ).parent().find( '.save-area' ) );
		$( this ).css( 'margin', '15px 0 0' );
	} );

});
