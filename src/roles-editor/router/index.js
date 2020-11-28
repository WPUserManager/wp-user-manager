import Vue from 'vue'
import Router from 'vue-router'
import RolesList from '../roles-list'
//import RolesEditor from '../roles-editor'

Vue.use(Router)

export default new Router({
	routes: [
		{
			path: '/',
			name: 'roles-list',
			component: RolesList
		},
		// {
		// 	name: 'role',
		// 	path: '/role/:id',
		// 	component: RolesEditor
		// }
	]
})
