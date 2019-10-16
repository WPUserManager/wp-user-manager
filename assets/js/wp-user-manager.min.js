/*! WP User Manager - v2.1.10
 * https://wpusermanager.com
 * Copyright (c) 2019; * Licensed GPLv2+ */
jQuery(document).ready(function(a){a(document.body).on("click",".wpum-remove-uploaded-file",function(){return a(this).closest(".wpum-uploaded-file").remove(),!1}),a(".wpum-multiselect").select2({theme:"default"}),a(".wpum-datepicker:not([readonly])").flatpickr({dateFormat:wpumFrontend.dateFormat})});