(function ($) {
	'use strict';

	const imageFields = 'input.wpum-image-field[type="file"]';

	/**
	 * Convert pipe-separated extensions to FilePond accepted types.
	 * Example: "jpg|png|webp" -> [".jpg", ".png", ".webp"]
	 */
	function parseFileTypes(types) {
		if (!types) {
			return ['image/*'];
		}

		var result = [];

		types.split('|').forEach(function (type) {
			type = type.trim().toLowerCase();

			if (!type) {
				return;
			}

			if (type.indexOf('/') !== -1) {
				result.push(type);
			} else {
				result.push('image/' + type);
			}
		});

		return result;
	}

	/**
	 * Initialize FilePond on matching inputs.
	 */
	function initImageFields() {
		if (typeof $.fn.filepond !== 'function') {
			// FilePond jQuery adapter is not loaded.
			return;
		}

		if (typeof FilePond === 'undefined') {
			// Core FilePond is not loaded.
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
			FilePond.registerPlugin.apply(FilePond, plugins);
		}

		$(imageFields).each(function () {
			const $input = $(this);
			const fileTypes = parseFileTypes($input.data('file_types'));

			// Prevent double initialization.
			if ($input.hasClass('filepond--root') || $input.data('filepond-initialized')) {
				return;
			}

			$input.filepond({
				acceptedFileTypes: fileTypes,
				allowFileSizeValidation: true,
				allowFileTypeValidation: true,
				allowImagePreview: true,
				allowMultiple: false,
				credits: false,
				maxFileSize: $input.data('file_size'),
				required: $input.prop('required'),
				storeAsFile: true
			});

			$input.data('filepond-initialized', true);
		});
	}

	$(document).ready(function () {
		initImageFields();
	});
})(jQuery);