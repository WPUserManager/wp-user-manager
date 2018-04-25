jQuery(document).ready(function ($) {
	$('.wpum-link-visibility-toggle select').change(function () {
		if( $(this).val() == 'in' ) {
			$( '.wpum-link-visibility-roles' ).show();
		} else {
			$('.wpum-link-visibility-roles').hide();
		}
	});
	$('.wpum-link-logout-toggle input').change(function () {
		if( this.checked === true ) {
			$('.wpum-link-visibility-toggle select').val( 'out' );
			$('.wpum-link-visibility-toggle').hide();
			$('.wpum-link-visibility-roles').hide();
		} else {
			$('.wpum-link-visibility-toggle select').val('');
			$('.wpum-link-visibility-toggle').show();
		}
	});
});
