/*!
 * WPUM Admin Shortcodes JS
 */

/* global ajaxurl, jQuery, wpumShortcodes, tinymce */
var jq = jQuery.noConflict();

var wpumShortcode, wpumButton;

/**
 * Show continue button title setting field only if display style is not All Fields.
 */
var render_continue_button_title_field = function () {
	var selected_display_style = jq('.mce-txt', '.mce-wpum-display-style').text(),
		expected_display_styles = ['- Select -', 'All Fields'];

	if (-1 !== jq.inArray(selected_display_style, expected_display_styles)) {
		jq('.mce-wpum-continue-button-title').closest('.mce-container').hide()
	} else {
		jq('.mce-wpum-continue-button-title').closest('.mce-container').show()
	}
};

var wpumForm = {

	open: function (editor_id) {
		var editor = tinymce.get(editor_id);

		if (!editor) {
			return;
		}

		var data, field, required, valid, win;

		data = {
			action: 'wpum_shortcode',
			shortcode: wpumShortcode
		};

		jq.post(ajaxurl, data, function (response) {

			// what happens if response === false?
			if (!response.body) {
				console.error('Bad AJAX response!');
				return;
			}

			if (response.body.length === 0) {
				window.send_to_editor('[' + response.shortcode + '][/' + response.shortcode + ']');

				wpumForm.destroy();

				return;
			}

			/**
			 * Render continue button title setting field on basis of display style value.
			 */
			jq.each(response.body, function (index, item) {

				if ('display_style' === item.name) {
					response.body[index].onselect = function () {
						render_continue_button_title_field();
					};
				}
			});

			var popup = {
				title: response.title,
				body: response.body,
				classes: 'wpum-popup',
				minWidth: 320,
				buttons: [{
					text: response.ok,
					classes: 'primary wpum-primary',
					onclick: function () {
						// Get the top most window object
						win = editor.windowManager.getWindows()[0];

						// Get the shortcode required attributes
						required = wpumShortcodes[wpumShortcode];

						valid = true;

						// Do some validation voodoo
						for (var id in required) {
							if (required.hasOwnProperty(id)) {

								field = win.find('#' + id)[0];

								if (typeof field !== 'undefined' && field.state.data.value === '') {

									valid = false;

									alert(required[id]);

									break;
								}
							}
						}

						if (valid) {
							win.submit();
						}
					}
				},
				{
					text: response.close,
					onclick: 'close'
				},],
				onsubmit: function (e) {
					var attributes = '';

					for (var key in e.data) {
						if (e.data.hasOwnProperty(key) && e.data[key] !== '') {
							attributes += ' ' + key + '="' + e.data[key] + '"';
						}
					}

					// Insert shortcode into the WP_Editor
					window.send_to_editor('[' + response.shortcode + attributes + '][/' + response.shortcode + ']');
				},
				onclose: function () {
					wpumForm.destroy();
				},
				onopen: function () {
					// Conditional fields.
					console.log(response);

					render_continue_button_title_field();
				}
			};

			// Change the buttons if server-side validation failed
			if (response.ok.constructor === Array) {
				popup.buttons[0].text = response.ok[0];
				popup.buttons[0].onclick = 'close';
				delete popup.buttons[1];
			}

			editor.windowManager.open(popup);
		});
	},

	destroy: function () {
		var tmp = jq('#wpumTemp');

		if (tmp.length) {
			tinymce.get('wpumTemp').remove();
			tmp.remove();
		}
	}
};

jq(function ($) {
	var wpumOpen = function () {
		wpumButton.addClass('active').parent().find('.wpum-menu').show();
	};

	var wpumClose = function () {
		if (typeof wpumButton !== 'undefined') {
			wpumButton.removeClass('active').parent().find('.wpum-menu').hide();
		}
	};

	$(document).on('click', function (e) {
		if (!$(e.target).closest('.wpum-wrap').length) {
			wpumClose();
		}
	});

	$(document).on('click', '.wpum-button', function (e) {
		e.preventDefault();

		wpumButton = $(this);

		if (wpumButton.hasClass('active')) {
			wpumClose();
		} else {
			wpumOpen();
		}
	});

	$(document).on('click', '.wpum-shortcode', function (e) {
		e.preventDefault();

		// wpumShortcode is used by wpumForm to trigger the correct popup
		wpumShortcode = $(this).attr('data-shortcode');

		if (wpumShortcode) {
			if (!tinymce.get(window.wpActiveEditor)) {

				if (!$('#wpumTemp').length) {

					$('body').append('<textarea id="wpumTemp" style="display: none;" />');

					tinymce.init({
						mode: "exact",
						elements: "wpumTemp",
						plugins: ['wpum_shortcode', 'wplink']
					});
				}

				setTimeout(function () { tinymce.execCommand('WPUM_Shortcode'); }, 200);
			} else {
				tinymce.execCommand('WPUM_Shortcode');
			}

			setTimeout(function () { wpumClose(); }, 100);
		} else {
			console.warn('That is not a valid shortcode link.');
		}
	});
});
