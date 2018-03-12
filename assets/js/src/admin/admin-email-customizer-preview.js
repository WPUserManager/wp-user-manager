/* global wp, jQuery, console */
/* eslint consistent-this: [ "error", "control" ] */
/* eslint no-magic-numbers: ["error", { "ignore": [0,1] }] */
/* eslint complexity: ["error", 8] */
(function (wp, $) {
	'use strict'

	wp.customize('wpum_email[registration_confirmation][title]', function (value) {
		value.bind(function (newval) {
			$('h1').text(newval)
		})
	})
})(window.wp, jQuery)
