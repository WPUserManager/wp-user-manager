<template>
	<div class="media-modal-content wpum-dialog" id="edit-field-dialog">
		<button type="button" class="media-modal-close" @click="$emit('close')"><span class="media-modal-icon"><span class="screen-reader-text">Close panel</span></span></button>
		<div class="media-frame mode-select wp-core-ui">
			<div class="media-frame-menu">
				<div class="media-menu">
					<a v-for="tab in tabs" :key="tab.id" v-if="! isTabDisabled( tab.id ) " @click="activateTab( tab.id )" :class="getTabClasses( tab.id )">{{tab.name}}</a>
					<div class="separator"></div>
					<a @click="activateTab( 'registration' )" :class="getTabClasses( 'registration' )">Configure registration</a>
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
				<wp-notice type="error" alternative v-if="error"><strong>{{errorMessage}}</strong></wp-notice>
				<!-- end error message -->

				<!-- registration form settings -->
				<wp-notice type="info" alternative v-if="activeTab == 'registration' && !loadingFields"><strong>{{labels.registration_info}}</strong></wp-notice>

				<form action="post" v-if="activeTab == 'registration' && !loadingFields" class="vue-form-generator">
					<div class="form-group field-input">
						<label for="registration-forms">{{labels.registration_label}}</label>
						<div class="field-wrap">
							<div class="wrapper">
								<div class="reg-form" v-for="form in availableRegistrationForms" :key="form.value">
									<label :for="form.value">
										<input type="checkbox" :id="form.value" :value="form.value" v-model="selectedRegistrationForms">
										{{form.name}}
									</label>
								</div>
							</div>
						</div>
					</div>
				</form>
				<!-- end registration form settings -->

				<vue-form-generator v-if="!loadingFields && activeTab !== 'registration'" :schema="schema" :model="model" :options="formOptions" ref="vfg"></vue-form-generator>

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
import lodashRemove from 'lodash.remove'
import lodashIncludes from 'lodash.includes'

export default {
	name: 'dialog-edit-field',
	props: {
		field_id:     '',
		field_type:   '',
		field_name:   '',
		primary_id:   '',
		updateStatus: '',
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
			infoAvailable:  false,
			infoMessage:    '',
			labels:         wpumFieldsEditor.labels,
			tabs:           wpumFieldsEditor.edit_dialog_tabs,
			settingsFields: '',
			activeTab:      'general',
			disabledTabs:   [],

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
			},

			// Setup the registration forms enabled for the field.
			availableRegistrationForms: [],
			selectedRegistrationForms: []

		}
	},
	created() {
		// Retrieve the settings for this field type via ajax.
		this.getSettings()

		// Remove sidebar sections that aren't needed.
		if( this.primary_id !== undefined || this.primary_id !== null || this.primary_id !== '' ) {
			this.maybeRemoveSidebarTabs()
		}

		// Translate the error messages part of the Vue Form Generator.
		let res                        = VueFormGenerator.validators.resources;
			res.fieldIsRequired        = this.labels.field_error_required
			res.invalidTextContainSpec = this.labels.field_error_special
	},
	methods: {
		/**
		 * Lookup the settings for this field.
		 */
		getSettings() {
			// Reset any previously updated statuses.
			this.loadingFields = true
			this.error         = false
			this.schema.fields = []

			// Make a call via ajax.
			axios.post( wpumFieldsEditor.ajax,
				qs.stringify({
					nonce:      wpumFieldsEditor.get_fields_nonce,
					group:      this.activeTab,
					field_id:   this.field_id,
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
				if( response.data.data.settings === null ) {
					this.error = true // Show an error if no settings found.
				} else {

					// Load the setting fields into the app.
					this.schema.fields = response.data.data.settings
					this.model         = response.data.data.model

				}
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
			if( tab_id == 'registration' ) {
				this.showRegistrationTab()
			} else {
				this.getSettings()
			}
		},
		/**
		 * Show the registration tab content.
		 */
		showRegistrationTab() {

			this.loadingFields = true

			axios.get( wpumFieldsEditor.ajax, {
				params: {
					nonce:  wpumFieldsEditor.getFormsNonce,
					action: 'wpum_get_registration_forms'
				}
			})
			.then( response => {

				this.loadingFields = false
				this.availableRegistrationForms = response.data.data

			})
			.catch( error => {
				this.loadingFields = false
				console.log(error)
			})

		},
		/**
		 * Toggle the active class status if the active tab is the current one.
		 */
		getTabClasses( tab_id ) {
			return [
				'media-menu-item',
				this.activeTab == tab_id ? 'active' : ''
			]
		},
		/**
		 * Check wether there are any sidebar tabs that need to be removed.
		 * Sidebars are removed when they make no sense for specific field types.
		 */
		maybeRemoveSidebarTabs() {
			if( this.primary_id == 'username' ) {
				this.disabledTabs = [ 'validation', 'permissions' ]
			} else if( this.primary_id == 'user_email' ) {
				this.disabledTabs = [ 'validation' ]
			} else if( this.primary_id == 'user_password' ) {
				this.disabledTabs = [ 'validation', 'privacy', 'permissions' ]
			} else if( this.primary_id == 'user_nickname' || this.primary_id == 'user_displayname' ) {
				this.disabledTabs = [ 'validation' ]
			} else if( this.primary_id == 'user_avatar' ) {
				this.disabledTabs = [ 'privacy', 'permissions' ]
			}
		},
		/**
		 * Check if a given tab is disabled.
		 */
		isTabDisabled( tab_id ) {
			return lodashIncludes( this.disabledTabs, tab_id )
		},
		/**
		 * Process the settings and update the database.
		 */
		editField() {
			let valid = this.$refs.vfg.validate();

			// If there's validation errors - show another message within the editor.
			if( valid === false ) {

				this.error = true
				this.errorMessage = this.labels.field_error_nosave

			} else if( valid === true ) {

				this.loading = true

				// Hide the error messages and reset the default error message.
				this.error = false
				this.errorMessage = this.labels.field_edit_settings_error

				// Make a call via ajax.
				axios.post( wpumFieldsEditor.ajax,
					qs.stringify({
						nonce:    wpumFieldsEditor.get_fields_nonce,
						field_id: this.field_id,
						data:     this.model,
						settings: this.schema.fields
					}),
					{
						params: {
							action: 'wpum_update_field'
						},
					}
				)
				.then( response => {
					this.loading = false
					this.updateStatus( 'success' )
					this.$emit('close')
				})
				.catch( error => {
					this.loading = false
					this.updateStatus( 'error' )
					this.$emit('close')
				})

			}

		}
	}
}
</script>
