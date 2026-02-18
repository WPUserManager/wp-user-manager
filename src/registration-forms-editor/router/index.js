import Vue from 'vue'
import Router from 'vue-router'
import RegistrationFormsList from '../registration-forms-list'
import RegistrationFormEditor from '../registration-form-editor'
import RegistrationFormSettings from '../registration-form-settings'

Vue.use(Router)

const OptionsTabs = []

// Default tabs
OptionsTabs.push(
	{
		path: '/',
		name: 'registration-forms-list',
		component: RegistrationFormsList
	},
	{
		path: '/form/:id',
		name: 'form',
		component: RegistrationFormEditor,
		meta: {
			label: 'Fields',
			key: ''
		}
	}
);

Object.keys(wpumRegistrationFormsEditor.edit_form_sections).forEach(function (key) {
	// Setup the starting path.
	let path = '/form/:id';

	// Create main route and child routes if any.
	OptionsTabs.push(
		{
			path: path + '/' + key,
			name: wpumRegistrationFormsEditor.edit_form_sections[key],
			component: RegistrationFormSettings,
			meta: {
				label: wpumRegistrationFormsEditor.edit_form_sections[key],
				key: key
			}
		}
	)
})

export default new Router({
	routes: OptionsTabs
})
