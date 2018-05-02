/*! WP User Manager - v2.0.0
 * http://wpusermanager.com
 * Copyright (c) 2018; * Licensed GPLv2+ */
jQuery(document).ready(function ($) {
	/**
	 * Frontend Scripts
	 */
	var WPUM_Frontend = {
		init: function () {
			this.directory_sort();
		},
		directory_sort: function () {
			$("#wpum-dropdown, #wpum-amount-dropdown").change(function () {
				location.href = $(this).val();
			});
		},

	}
});
