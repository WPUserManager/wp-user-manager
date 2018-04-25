jQuery(document).ready(function ($) {

	jQuery('.wpum-link-visibility-toggle select').each(function () {
		var selected_status = jQuery(this).val();
		var parent = $(this).parent().parent().parent();
		var closest = parent.find('.wpum-link-visibility-roles');
		if ( $(this).val() == 'in' ) {
			closest.show();
		} else {
			closest.hide();
		}
	});

	$('.wpum-link-visibility-toggle select').change(function () {
		var parent  = $(this).parent().parent().parent();
		var closest = parent.find( '.wpum-link-visibility-roles' );
		if ( $(this).val() == 'in' ) {
			closest.show();
		} else {
			closest.hide();
		}
	});
	$('.wpum-link-logout-toggle input').change(function () {
		var parent = $(this).parent().parent().parent().parent();
		var closestToggle = parent.find('.wpum-link-visibility-toggle');
		var closestRoles = parent.find('.wpum-link-visibility-roles');
		if (this.checked === true) {
			closestToggle.find('select').val('in');
			closestToggle.hide();
			closestRoles.hide();
		} else {
			closestToggle.find('select').val('');
			closestToggle.show();
		}
	});
});
