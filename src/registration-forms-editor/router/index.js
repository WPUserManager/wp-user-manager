import Vue from 'vue'
import Router from 'vue-router'
import RegistrationFormsList from '../registration-forms-list'

Vue.use(Router)

export default new Router({
	routes: [
		{
			path: '/',
			name: 'registration-forms-list',
			component: RegistrationFormsList
		}
	]
})
