(function ($) {
	'use strict';

	var imageFields = 'input.wpum-image-field[type="file"]';

	/**
	 * Convert the pipe-separated mime types from the field markup to FilePond accepted types.
	 * Example: "image/jpeg|image/png" -> ["image/jpeg", "image/png"]
	 *
	 * @param {string} types
	 * @return {Array}
	 */
	function parseFileTypes(types) {
		var result = [];

		if (types) {
			String(types).split('|').forEach(function (type) {
				type = type.trim().toLowerCase();

				if (type.indexOf('/') !== -1) {
					result.push(type);
				}
			});
		}

		return result.length ? result : ['image/*'];
	}

	/**
	 * Load an existing image into FilePond from its URL.
	 *
	 * The image is added as a "local" file, so FilePond keeps it in a hidden input instead of
	 * posting it again as a new upload every time the form is saved.
	 */
	function loadExistingImage(source, load, error, progress, abort) {
		var request = new XMLHttpRequest();

		request.open('GET', source, true);
		request.responseType = 'blob';
		request.onload = function () {
			if (request.status >= 200 && request.status < 300) {
				load(request.response);
			} else {
				error(request.statusText);
			}
		};
		request.onerror = function () {
			error(request.statusText);
		};
		request.send();

		return {
			abort: function () {
				request.abort();
				abort();
			}
		};
	}

	/**
	 * Initialize FilePond on matching inputs.
	 */
	function initImageFields() {
		if (typeof $.fn.filepond !== 'function' || typeof window.FilePond === 'undefined') {
			// FilePond or its jQuery adapter is not loaded.
			return;
		}

		// Register only the plugins that are available.
		var plugins = [
			window.FilePondPluginImagePreview,
			window.FilePondPluginFileValidateType,
			window.FilePondPluginFileValidateSize
		].filter(function (plugin) {
			return !!plugin;
		});

		if (plugins.length) {
			window.FilePond.registerPlugin.apply(window.FilePond, plugins);
		}

		$(imageFields).each(function () {
			var $input = $(this);

			// Prevent double initialization.
			if ($input.closest('.filepond--root').length) {
				return;
			}

			var wrapper = $input.closest('fieldset');
			var fileUrl = wrapper.find('input[name="current_' + $input.attr('name') + '"]').val();
			var maxFileSize = parseInt($input.data('file_size'), 10);

			$input.filepond({
				acceptedFileTypes: parseFileTypes($input.data('file_types')),
				allowFileSizeValidation: maxFileSize > 0,
				allowFileTypeValidation: true,
				allowImagePreview: true,
				allowMultiple: false,
				credits: false,
				maxFileSize: maxFileSize > 0 ? maxFileSize : null,
				storeAsFile: true,
				server: {
					load: loadExistingImage
				},

				// Remove the current image when it is removed from the FilePond UI.
				onremovefile: function () {
					wrapper.find('.wpum-uploaded-image').html('');
				}
			});

			// Show the existing image.
			if (fileUrl) {
				$input.filepond('addFile', fileUrl, { type: 'local' });
			}
		});
	}

	$(document).ready(function () {
		initImageFields();
	});
})(jQuery);
