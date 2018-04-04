<template>
	<div class="media-modal-content wpum-dialog" id="edit-field-dialog">
		<button type="button" class="media-modal-close" @click="$emit('close')"><span class="media-modal-icon"><span class="screen-reader-text">Close panel</span></span></button>
		<div class="media-frame mode-select wp-core-ui">
			<div class="media-frame-menu">
				<div class="media-menu">
					<a v-for="tab in tabs" :key="tab.id" @click="activateTab( tab.id )" :class="getTabClasses( tab.id )">{{tab.name}}</a>
					<div class="separator"></div>
				</div>
			</div>
			<div class="media-frame-title">
				<h1>{{field_name}}</h1>
			</div>
			<div class="media-frame-content">

				<!-- loading indicator -->
				<div class="spinner is-active" v-if="loadingFields"></div>
				<!-- end loading indicator -->

				<!-- error message if any -->
				<wp-notice type="error" alternative v-if="error">{{errorMessage}}</wp-notice>
				<!-- end error message -->

				<vue-form-generator v-if="!loadingFields" :schema="schema" :model="model" :options="formOptions"></vue-form-generator>

			</div>
			<div class="media-frame-toolbar">
				<div class="media-toolbar">
					<div class="media-toolbar-primary search-form">
						<div class="spinner is-active" v-if="loading"></div>
						<button type="button" class="button media-button button-primary button-large media-button-insert" :disabled="loading" @click="editField()">{{labels.save}}</button>
					</div>
				</div>
			</div>
		</div>
	</div>
</template>

<script>
import axios from 'axios'
import qs from 'qs'
import VueFormGenerator from 'vue-form-generator'

export default {
	name: 'dialog-edit-field',
	props: {
		field_id: '',
		field_type: '',
		field_name: ''
	},
	components:{
    	"vue-form-generator": VueFormGenerator.component
  	},
	data() {
		return {
			loading:        false,
			loadingFields:  false,
			error:          false,
			errorMessage:   wpumFieldsEditor.labels.field_edit_settings_error,
			labels:         wpumFieldsEditor.labels,
			tabs:           wpumFieldsEditor.edit_dialog_tabs,
			settingsFields: '',
			activeTab:      'general',

			// Current field data.
			model: {},

			// Setup the settings fields for the current field.
			schema: {
      			fields: []
			},

			// Setup the options for the form.
			formOptions: {
				validateAfterLoad: true,
				validateAfterChanged: true
			}

		}
	},
	created() {
		// Retrieve the settings for this field type via ajax.
		this.getSettings()
	},
	methods: {
		/**
		 * Lookup the settings for this field.
		 */
		getSettings() {
			this.loadingFields = true
			// Make a call via ajax.
			axios.post( wpumFieldsEditor.ajax,
				qs.stringify({
					nonce:      wpumFieldsEditor.get_fields_nonce,
					group:      this.activeTab,
					field_type: this.field_type
				}),
				{
					params: {
						action: 'wpum_get_field_settings'
					},
				}
			)
			.then( response => {
				this.loadingFields = false
				this.schema.fields = response.data.data.settings
				this.model         = response.data.data.model
			})
			.catch( error => {
				this.loadingFields = false
				this.error         = true
			})
		},
		/**
		 * Activate the selected tab and load the appropriate fields via ajax.
		 */
		activateTab( tab_id ) {
			this.activeTab = tab_id
			this.getSettings()
		},
		/**
		 * Toggle the active class status if the active tab is the current one.
		 */
		getTabClasses( tab_id ) {
			return [
				'media-menu-item',
				this.activeTab == tab_id ? 'active' : ''
			]
		}
	}
}
</script>
