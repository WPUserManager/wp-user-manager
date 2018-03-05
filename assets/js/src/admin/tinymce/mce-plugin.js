(function (tinymce) {

	tinymce.PluginManager.add('wpum_shortcode', function (editor) {
		editor.addCommand('WPUM_Shortcode', function () {

			if (window.wpumForm) {

				window.wpumForm.open(editor.id);
			}
		});
	});

})(window.tinymce);
