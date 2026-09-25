// The Vue build version to load with the `import` command
// (runtime-only or standalone) has been set in webpack.base.conf with an alias.
import Vue from 'vue'
import EmailsList from './emails-list'
import { ToggleButton } from 'vue-js-toggle-button'
Vue.config.productionTip = false
Vue.component('ToggleButton', ToggleButton)

/* eslint-disable no-new */
new Vue({
	el: '#wpum-emails-list',
	components: { EmailsList },
	template: '<EmailsList/>'
})
