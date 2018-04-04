<template>
	<div class="media-modal-content wpum-dialog" id="edit-field-dialog">
		<button type="button" class="media-modal-close" @click="$emit('close')"><span class="media-modal-icon"><span class="screen-reader-text">Close panel</span></span></button>
		<div class="media-frame mode-select wp-core-ui">
			<div class="media-frame-menu">
				<div class="media-menu">
					<a class="media-menu-item">{{labels.field_edit_general}}</a>
					<a class="media-menu-item">{{labels.field_edit_privacy}}</a>
					<a class="media-menu-item">{{labels.field_edit_customization}}</a>
					<div class="separator"></div>
				</div>
			</div>
			<div class="media-frame-title">
				<h1>{{field_name}}</h1>
			</div>
			<div class="media-frame-content">
				<div class="spinner is-active" v-if="loadingFields"></div>
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
			labels:         wpumFieldsEditor.labels,
			settingsFields: '',
			activeTab:      '',
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
					nonce: wpumFieldsEditor.get_fields_nonce,
					field_type: this.field_type
				}),
				{
					params: {
						action: 'wpum_get_field_settings'
					},
				}
			)
			.then( response => {
				this.loadingFields  = false
				this.settingsFields = response.data.data.settings
			})
			.catch( error => {
				this.loadingFields = false
			})
		}
	}
}
</script>
