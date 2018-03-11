/* global wp, jQuery, console */
/* eslint consistent-this: [ "error", "control" ] */
/* eslint no-magic-numbers: ["error", { "ignore": [0,1] }] */
/* eslint complexity: ["error", 8] */
(function (wp, $) {
	'use strict'

	wp.customize('my_theme_mod_setting', function (value) {
		value.bind(function (newval) {
			$('h1').text(newval)
		})
	})
})(window.wp, jQuery)
