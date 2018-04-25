jQuery(document).ready(function ($) {
	$('.wpum-link-visibility-toggle select').change(function () {
		if( $(this).val() == 'in' ) {
			$( '.wpum-link-visibility-roles' ).show();
		} else {
			$('.wpum-link-visibility-roles').hide();
		}
	});
});
