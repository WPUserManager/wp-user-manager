import Vue from 'vue'
import Router from 'vue-router'
import EditorInterface from '../editor-interface'

Vue.use(Router)

export default new Router({
	routes: [
		{
			path: '/',
			name: 'HelloWorld',
			component: EditorInterface
		}
	]
})
