/*! WP User Manager - v2.0.0
 * http://wpusermanager.com
 * Copyright (c) 2018; * Licensed GPLv2+ */
jQuery(document).ready(function ($) {
	$(document.body).on('click', '.wpum-remove-uploaded-file', function () {
		$(this).closest('.wpum-uploaded-file').remove();
		return false;
	});
	$('.wpum-multiselect').select2({
		theme: 'default'
	});
	$('.wpum-datepicker').flatpickr({
		dateFormat: wpumFrontend.dateFormat
	});
});
