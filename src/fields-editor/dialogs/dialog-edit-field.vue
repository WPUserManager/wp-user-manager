<template>
	<div class="media-modal-content wpum-dialog" id="edit-field-dialog">
		<button type="button" class="media-modal-close" @click="$emit('close')"><span class="media-modal-icon"><span class="screen-reader-text">Close panel</span></span></button>
		<div class="media-frame mode-select wp-core-ui">
			<div class="media-frame-menu">
				<div class="media-menu">
					<a v-for="tab in tabs" :key="tab.id" v-if="! isTabDisabled( tab.id ) " @click="activateTab( tab.id )" :class="getTabClasses( tab.id )">{{tab.name}}</a>
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
				<wp-notice type="error" alternative v-if="error"><strong>{{errorMessage}}</strong></wp-notice>
				<!-- end error message -->
				<vue-form-generator v-if="!loadingFields" :schema="schema" :model="model" :options="formOptions" ref="vfg"></vue-form-generator>

				<!-- Dropdown options generator -->
				<div class="vue-form-generator" v-if="activeTab == 'general' && !loadingFields && needsOptions( field_type ) && ! primary_id">
					<div class="form-group field-input">
						<label for="placeholder">{{labels.field_options}}</label>
						<div class="hint"><p v-html="labels.field_options_hint"></p></div>
						<div class="field-wrap">
							<div class="wrapper">
								<draggable v-model="dropdownOptions" :options="{draggable:'.dragme', handle:'.option-sort', animation:150}">
									<div class="dropdown-option dragme" v-for="(option, index) in dropdownOptions" :key="index" v-if="dropdownOptions.length > 0">
										<div class="option-value wpum_one_fifth">
											<span class="dashicons dashicons-move option-sort"></span>
											<button type="button" class="button delete-btn" @click="deleteOption( index )"><span class="dashicons dashicons-trash"></span></button>
										</div>
										<div class="option-label wpum_two_fifth">
											<input @paste="onPaste($event, index)" type="text" :placeholder="labels.field_option_value" name="option[value][]" v-model="dropdownOptions[index].value">
										</div>
										<div class="option-label wpum_two_fifth last">
											<input type="text" :placeholder="labels.field_option_label" name="option[label][]" v-model="dropdownOptions[index].label">
										</div>
										<div class="wpum_clearfix"></div>
									</div>
								</draggable>
								<input type="button" :value="labels.field_add_option" class="button" @click="addOption">
							</div>
						</div>
					</div>
				</div>
				<!-- end dropdown options generator -->

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

<style lang="scss" scoped>
	.dropdown-option {
		margin-bottom: 10px;
		.button {
			&.delete-btn {
				color: red;
			}
			span {
				margin-top: 2px;
			}
		}
		.option-sort {
			margin-top: 5px;
			margin-right: 10px;
		}
	}
</style>

<script>
import axios from 'axios'
import qs from 'qs'
import VueFormGenerator from 'vue-form-generator'
import draggable from 'vuedraggable'
import lodashRemove from 'lodash.remove'
import lodashIncludes from 'lodash.includes'
import '../../../assets/css/src/_columns.scss'

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
		"vue-form-generator": VueFormGenerator.component,
		draggable
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

			originalModel: {},

			// Setup the settings fields for the current field.
			schema: {
      			fields: []
			},

			// Setup the options for the form.
			formOptions: {
				validateAfterLoad: true,
				validateAfterChanged: true,
				validateDebounceTime: 500,
				validateAsync: true
			},

			dropdownOptions: []

		}
	},
	created() {
		//setup tabs
		this.setupTabs();

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

		this.UserMetaKeyValidator();
	},
	methods: {
		confirmLeave() {
			return window.confirm(this.labels.confirm_message)
		},

		confirmStayInDirtyForm() {
			return this.isFormDirty() && !this.confirmLeave()
		},

		isFormDirty() {
			return JSON.stringify( this.model ) !== JSON.stringify( this.originalModel );
		},

		/**
		 * Lookup the settings for this field.
		 */
		getSettings() {
			// Reset any previously updated statuses.
			this.loadingFields = true
			this.error         = false
			this.schema.fields = []
			this.dropdownOptions = []

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
					this.schema.fields   = response.data.data.settings
					this.model           = response.data.data.model
					this.originalModel   = {...this.model}
					this.dropdownOptions = response.data.data.dropdownOptions

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
			if (this.confirmStayInDirtyForm()) {
				return;
			}

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
			} else if( this.primary_id == 'user_avatar' || this.primary_id == 'user_cover' ) {
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
		async editField() {
			let errors = await this.$refs.vfg.validate();

			// If there's validation errors - show another message within the editor.
			if ( errors.length > 0 ) {

				this.error = true
				this.errorMessage = this.labels.field_error_nosave

			} else {

				this.loading = true

				// Hide the error messages and reset the default error message.
				this.error = false
				this.errorMessage = this.labels.field_edit_settings_error

				// Make a call via ajax.
				axios.post( wpumFieldsEditor.ajax,
					qs.stringify({
						nonce:           wpumFieldsEditor.get_fields_nonce,
						field_id:        this.field_id,
						data:            this.model,
						settings:        this.schema.fields,
						dropdownOptions: this.dropdownOptions
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

		},
		/**
		 * Verify if the field needs an options generator.
		 */
		needsOptions( field_type ) {
			const allowedTypes = [ 'dropdown', 'multiselect', 'multicheckbox', 'radio' ]
			return ( allowedTypes.indexOf( field_type ) > -1 )
		},
		/**
		 * Add a new option to the options generator.
		 */
		addOption() {
			if( ! this.dropdownOptions instanceof Array ) {
				this.dropdownOptions = []
			}
      		this.dropdownOptions.push( { value: null, label: null } )
		},
		/*
		 * Delete an option from the dropdown field.
		 */
		deleteOption( index ) {
			this.dropdownOptions.splice( index, 1 );
		},
		/**
		* Setup tabs on init
		*/
		setupTabs(){

			if(this.field_type !== 'repeater'){
				this.tabs = this.tabs.filter( tab => tab.id !== 'fields' )
			}

			if(this.field_type !== 'file'){
				this.tabs = this.tabs.filter( tab => tab.id !== 'emails' )
			}

			this.activeTab = this.tabs && this.tabs[0].id
		},
		onPaste(event, index){
			var _this = this;
			const items = event.clipboardData.getData("text/plain").split(/\r?\n/);
			event.preventDefault();

			// First item always goes to the pasted element
			const item = items[0].split(",");
			this.dropdownOptions[index].value = item[0];
			this.dropdownOptions[index].label = item[1];

			items.slice(1).forEach(function(item) {
				item = item.split(",");
				_this.dropdownOptions.push( { value: item[0], label: item[1] } );
			});
		},
		UserMetaKeyValidator() {
			var _this = this;
			VueFormGenerator.validators.unique_user_meta_key = (value, field, model) => {
				if (value === "") {
					return [];
				}

				return new Promise((resolve, reject) => {
					axios.post( wpumFieldsEditor.ajax,
						qs.stringify({
							user_meta_key: value,
							field_id: _this.field_id
						}),
						{
							params: {
								action: "validate_user_meta_key"
							},
						}
					)
					.then(response => {
						resolve(response.data.data.error);
					})
					.catch(err => reject(err))
				});
			}
		}
	}
}
</script>
