import Vue from 'vue'
import Router from 'vue-router'
import RegistrationFormsList from '../registration-forms-list'
import RegistrationFormEditor from '../registration-form-editor'
import RegistrationFormSettings from '../registration-form-settings'

Vue.use(Router)

export default new Router({
	routes: [
		{
			path: '/',
			name: 'registration-forms-list',
			component: RegistrationFormsList
		},
		{
			name: 'form',
			path: '/form/:id',
			component: RegistrationFormEditor
		},
		{
			name: 'form-settings',
			path: '/form/:id/settings',
			component: RegistrationFormSettings
		}
	]
})
