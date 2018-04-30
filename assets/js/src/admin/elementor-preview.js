jQuery(document).ready(function ($) {
	elementor.pageSettings.addChangeCallback('simulated_tab', function (newValue) {
		elementor.saver.saveEditor();
		elementor.reloadPreview();
	});
});
