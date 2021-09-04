// The Vue build version to load with the `import` command
// (runtime-only or standalone) has been set in webpack.base.conf with an alias.
import Vue from 'vue'
import App from './app'
import router from './router'
import WPNotice from 'vue-wp-notice'
import VModal from 'vue-js-modal'
import VueFormitFields from 'vue-formit-fields'
import WPUMFields from './components/field-types'
import MultiselectField from './components/fields/multiselect'
import FieldRepeater from './../fields-editor/settings/field-type-repeater'
import MultiSelect from './../fields-editor/components/fields/multiselect'
import {Bootstrap} from './../bootstrap'

Vue.use(VueFormitFields)
Vue.use(WPNotice)
Vue.use(VModal, { dialog: true, dynamic: true })

Vue.component('formit-multiselect', MultiselectField)
Vue.component('field-multiselect', MultiSelect)
Vue.component('field-repeater', FieldRepeater)

// register wpum fields
WPUMFields.forEach( ( Field ) => { Vue.component( Field.name, Field ) } )

Vue.config.productionTip = false

Bootstrap({Vue, router})

/* eslint-disable no-new */
new Vue({
	el: '#wpum-registration-forms-editor',
	router,
	components: { App },
	template: '<App/>'
})
