import Vue from 'vue'
import Router from 'vue-router'
import RolesList from '../roles-list'
import RoleEditor from '../role-editor'

Vue.use(Router)

export default new Router({
	routes: [
		{
			path: '/',
			name: 'roles-list',
			component: RolesList
		},
		{
			name: 'role',
			path: '/:id',
			component: RoleEditor
		}
	]
})
