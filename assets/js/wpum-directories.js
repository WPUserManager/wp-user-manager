/*! WP User Manager - v2.9
 * https://wpusermanager.com
 * Copyright (c) 2023; * Licensed GPLv2+ */
jQuery(document).ready(function ($) {
	$( '#wpum_sortby' ).on( 'change', function() {
		this.form.submit();
	} );
	$( '#wpum_amount' ).on( 'change', function() {
		this.form.submit();
	} );
});
