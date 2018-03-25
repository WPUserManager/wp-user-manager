import Vue from 'vue'
import Router from 'vue-router'
import GroupEditorInterface from '../group-editor-interface'
import FieldsEditorInterface from '../fields-editor-interface'

Vue.use(Router)

export default new Router({
	routes: [
		{
			path: '/',
			name: 'Editor',
			component: GroupEditorInterface
		},
		{
			name: 'group',
			path: '/group/:id',
			component: FieldsEditorInterface
		}
	]
})
