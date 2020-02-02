jQuery(document).ready(function ($) {
	$( '#wpum_sortby' ).on( 'change', function() {
		this.form.submit();
	} );
	$( '#wpum_amount' ).on( 'change', function() {
		this.form.submit();
	} );
});
