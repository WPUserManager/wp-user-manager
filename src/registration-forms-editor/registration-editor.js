// The Vue build version to load with the `import` command
// (runtime-only or standalone) has been set in webpack.base.conf with an alias.
import Vue from 'vue'
import App from './app'
import router from './router'
import WPNotice from 'vue-wp-notice'

Vue.use(WPNotice)

Vue.config.productionTip = false

/* eslint-disable no-new */
new Vue({
	el: '#wpum-registration-forms-editor',
	router,
	components: { App },
	template: '<App/>'
})
