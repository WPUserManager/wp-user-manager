// The Vue build version to load with the `import` command
// (runtime-only or standalone) has been set in webpack.base.conf with an alias.
import Vue from 'vue'
import LoginForm from './login-form'

Vue.config.productionTip = false

/* eslint-disable no-new */
new Vue({
  	el: '#wpum-login-form',
	components: { LoginForm },
	template: '<LoginForm/>'
})
