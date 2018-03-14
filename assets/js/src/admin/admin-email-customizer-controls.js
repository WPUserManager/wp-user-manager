/* global wp, jQuery, console, wpumCustomizeControls */
/* eslint consistent-this: [ "error", "control" ] */
/* eslint no-magic-numbers: ["error", { "ignore": [0,1] }] */
/* eslint complexity: ["error", 8] */
(function (wp, $) {
	'use strict'
	wp.customize.bind('ready', function () {
		// Email editor controller.
		var $editorButton = $('#wpum-email-editor-btn')
		var $editorContainer = $('#wpum-editor-window')
		var $editorButtonIcon = $editorButton.find('span.dashicons')
		var $editorActive = false

		// Move the editor window within the preview frame.
		$editorContainer.appendTo('.wp-full-overlay')

		// Don't ask me why but elements are duplicated and I'm tired of trying to figure this out.
		$('.wp-full-overlay').find('#wpum-editor-window').remove()

		$editorButton.click(function (e) {
			var $this = $(e.currentTarget)

			if ($editorActive === true) {
				wp.editor.remove('wpum-mail-content-editor')
			}

			$editorActive = !$editorActive // Flag the editor as active or inactive.

			// Toggle button text depending on the status.
			$this.find('span:not(.dashicons)').text(function (i, v) {
				return v === wpumCustomizeControls.labels.open ? wpumCustomizeControls.labels.close : wpumCustomizeControls.labels.open
			})

			// Toggle the class within the button
			if ($editorActive === true) {
				$editorButtonIcon.removeClass('dashicons-edit').addClass('dashicons-hidden')
			} else {
				$editorButtonIcon.removeClass('dashicons-hidden').addClass('dashicons-edit')
			}

			// Toggle the editor area.
			$editorContainer.toggleClass('is-active')

			$(document).on('tinymce-editor-setup', function (event, editor) {
				editor.settings.toolbar1 += ',wpumEmailTags';
				editor.addButton('wpumEmailTags', {
					text: 'Add email tags',
					icon: false,
					type: 'menubutton',
					tooltip: 'Merge tags allow you to dynamically add content to your email',
					menu: [
						{
							text: 'Sample Item 1',
							onclick: function () {
								editor.insertContent('[wdm_shortcode 1]');
							}
						},
						{
							text: 'Sample Item 2',
							onclick: function () {
								editor.insertContent('[wdm_shortcode 2]');
							}
						}
					]
				});
			});

			wp.editor.initialize('wpum-mail-content-editor')
		})
	})
})(window.wp, jQuery)
