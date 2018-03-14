// The Vue build version to load with the `import` command
// (runtime-only or standalone) has been set in webpack.base.conf with an alias.
import Vue from 'vue'
import EmailContentEditor from './email-content-editor'
import DomPortal from 'vue-dom-portal'
Vue.use(DomPortal)
Vue.config.productionTip = false;

(function (wp, $) {
	'use strict'
	wp.customize.bind('ready', function () {
		wp.customize.panel('registration_confirmation', function (section) {
			section.expanded.bind(function (isExpanding) {
				var vm = false
				if (!vm) {
					new Vue({
						el: '#wpum-email-content-editor',
						components: {
							EmailContentEditor
						},
						template: '<EmailContentEditor/>'
					})
					vm = true
				}
			})
		})
	})
})(window.wp, jQuery)
