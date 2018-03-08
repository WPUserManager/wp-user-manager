// The Vue build version to load with the `import` command
// (runtime-only or standalone) has been set in webpack.base.conf with an alias.
import Vue from 'vue'
import EmailsList from './emails-list'

Vue.config.productionTip = false

/* eslint-disable no-new */
new Vue({
	el: '#wpum-emails-list',
	components: { EmailsList },
	template: '<EmailsList/>'
})
