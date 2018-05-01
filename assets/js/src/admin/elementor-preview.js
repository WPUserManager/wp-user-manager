jQuery(document).ready(function ($) {
	if (typeof elementor != "undefined" && typeof elementor.settings.page != "undefined") {
		elementor.pageSettings.addChangeCallback('simulated_tab', function (newValue) {
			elementor.saver.update({
				onSuccess: function () {
					elementor.reloadPreview();
					elementor.once('preview:loaded', function () {
						elementor.getPanelView().setPage('page_settings');
					});
				}
			});
		});
	}
});
