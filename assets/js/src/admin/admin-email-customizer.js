/* global wp, jQuery, console */
/* eslint consistent-this: [ "error", "control" ] */
/* eslint no-magic-numbers: ["error", { "ignore": [0,1] }] */
/* eslint complexity: ["error", 8] */
(function (wp, $) {
	'use strict'

	// Bail if the Customizer isn't initialized.
	if (!wp || !wp.customize) {
		return
	}

	var api = wp.customize
	var EmailCustomizerOldPreview

	api.EmailCustomizerPreview = {
		init: function () {
			var self = this
			this.preview.bind('active', function () {
				var $body = $('body')
				var $document = $( document )
				var $heading = $('h1')

				//$heading.append('<button class="wpum-customizer-event-button customizer-event-overlay" data-wpum-customizer-event="login-designer-edit-logo"></button>')

				$document.on('touch click', '.wpum-customizer-event-button', function (e) {
					var $this = $(this)
					console.log($this.attr('data-wpum-customizer-event'))
				})
			})
		}
	}

	EmailCustomizerOldPreview = api.Preview

	api.Preview = EmailCustomizerOldPreview.extend({
		initialize: function (params, options) {
			api.EmailCustomizerPreview.preview = this
			EmailCustomizerOldPreview.prototype.initialize.call(this, params, options)
		}
	})

	$(function () {
		api.EmailCustomizerPreview.init()
	})

	api('wpum_email[password_recovery_email][title]', function (value) {
		value.bind(function (newval) {
			$('h1').text(newval)
		})
	})
})(window.wp, jQuery)
