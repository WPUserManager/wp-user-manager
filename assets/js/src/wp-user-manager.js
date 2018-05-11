jQuery(document).ready(function ($) {
	$(document.body).on('click', '.wpum-remove-uploaded-file', function () {
		$(this).closest('.wpum-uploaded-file').remove();
		return false;
	});
});
