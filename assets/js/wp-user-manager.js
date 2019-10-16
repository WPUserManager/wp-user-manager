/*! WP User Manager - v2.1.10
 * https://wpusermanager.com
 * Copyright (c) 2019; * Licensed GPLv2+ */
jQuery(document).ready(function ($) {
	$(document.body).on('click', '.wpum-remove-uploaded-file', function () {
		$(this).closest('.wpum-uploaded-file').remove();
		return false;
	});
	$('.wpum-multiselect').select2({
		theme: 'default'
	});
	$('.wpum-datepicker:not([readonly])').flatpickr({
		dateFormat: wpumFrontend.dateFormat
	});
});
