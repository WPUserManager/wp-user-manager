<template>
	<div>
		<h1 class="wp-heading-inline">
			<img :src="pluginURL + 'assets/images/logo.svg'" alt="WP User Manager">
			{{formName}}
		</h1>
		<router-link to="/" class="page-title-action wpum-icon-button circular" :data-balloon="labels.page_back" data-balloon-pos="down"><span class="dashicons dashicons-arrow-left-alt"></span></router-link>

		<br/><br/>

	  	<div class="widget-liquid-left">
			<div id="widgets-left">
				<div id="available-widgets-d" class="widgets-holder-wrap ui-droppable">
					<div class="sidebar-name">
						<h2>{{labels.editor_available_title}} <div class="spinner is-active" v-if="loading"></div></h2>
					</div>
					<div class="sidebar-description">
						<p class="description">{{labels.editor_available_desc}}</p>
					</div>

					<draggable v-model="availableFields" class="dragArea available-fields-holder" :options="{group:'formFields', sort:false, animation:150}">
						<div class="widget ui-draggable" v-for="element in availableFields" :key="element.name">
							<div class="widget-top">
								<div class="widget-title ui-draggable-handle">
									<h3>{{element.name}}</h3>
								</div>
							</div>
						</div>
      				</draggable>

				</div>
			</div>
		</div>

		<div class="widget-liquid-right">
			<div id="widgets-right" class="wp-clearfix">

				<div class="sidebars-column-1">
					<div class="widgets-holder-wrap">

						<wp-notice :type="messageStatus" alternative v-if="showMessage">{{messageContent}}</wp-notice>

						<div class="widgets-sortables ui-droppable ui-sortable">
							<div class="sidebar-name">
								<h2>{{formName}}
									<div class="spinner is-active" v-if="loading"></div>
								</h2>
							</div>
							<div class="sidebar-description">
								<p class="description">{{labels.editor_used_fields}}</p>
							</div>
							<!-- start fields list -->
							<draggable v-model="selectedFields" class="droppable-fields" :options="{group:'formFields', animation:150}" @sort="saveFields">
								<div class="widget" v-for="element in selectedFields" :key="element.name">
									<div class="widget-top">
										<div class="widget-title ui-sortable-handle">
											<h3>{{element.name}}</h3>
										</div>
									</div>
								</div>
							</draggable>
							<!-- end fields list -->
						</div>
					</div>
				</div>

				<div class="sidebars-column-2">
					<div class="widgets-holder-wrap">
						<wp-notice :type="messageStatus" alternative v-if="showMessageSettings">{{messageContent}}</wp-notice>
						<form action="post" @submit.prevent="saveSettings()">
							<div class="widgets-sortables ui-droppable ui-sortable">
								<div class="sidebar-name">
									<h2>{{labels.settings}}</h2>
								</div>
								<div class="settings-wrapper">
									<label for="role">{{labels.role_label}}</label>
									<select name="role" id="role" :disabled="loading || loadingSettings" v-model="selectedRole">
										<option v-for="role in allowedRoles" :key="role.value" :value="role.value">{{role.label}}</option>
									</select>
								</div>
							</div>

							<div id="major-publishing-actions">
								<div id="publishing-action">
									<div class="spinner is-active" v-if="loadingSettings"></div>
									<input type="submit" :value="labels.save" :disabled="loading || loadingSettings" class="button button-primary button-large">
								</div>
								<div class="clear"></div>
							</div>
						</form>
					</div>
				</div>

			</div>
		</div>

	</div>
</template>

<script>
import axios from 'axios'
import qs from 'qs'
import balloon from 'balloon-css'
import draggable from 'vuedraggable'

export default {
	name: 'registration-form-editor',
	components: {
		draggable
	},
	data() {
		return {
			labels:              wpumRegistrationFormsEditor.labels,
			pluginURL:           wpumRegistrationFormsEditor.pluginURL,
			loading:             false,
			loadingSettings:     false,
			formID:              '',
			formName:            '...',
			availableFields:     [],
			selectedFields:      [],
			selectedRole:        '',
			allowedRoles:        [],
			showMessage:         false,
			showMessageSettings: false,
			messageStatus:       'success',
			messageContent:      ''
		}
	},
	created() {
		// Grab the form id from the router.
		this.formID = this.$route.params.id
		// Retrieve the selected form.
		this.getForm()
	},
	methods: {
		/**
		 * Retrieve the registration form from the db.
		 */
		getForm() {
			this.loading = true

			axios.get( wpumRegistrationFormsEditor.ajax, {
				params: {
					nonce:   wpumRegistrationFormsEditor.getFormNonce,
					action:  'wpum_get_registration_form',
					form_id: this.formID
				}
			})
			.then( response => {
				this.loading         = false
				this.formName        = response.data.data.name
				this.availableFields = response.data.data.available_fields
				this.selectedFields  = response.data.data.stored_fields
				this.selectedRole    = response.data.data.selected_role,
				this.allowedRoles    = response.data.data.allowed_roles
			})
			.catch( error => {
				this.loading = false
				console.log(error)
			})
		},
		/**
		 * Automatically hide a notice after it's displayed.
		*/
		resetNotice() {
			setTimeout( () => {
				this.showMessage = false
				this.showMessageSettings = false
			}, 3000)
		},
		/**
		 * Save fields to the form.
		 */
		saveFields() {

			this.loading = true

			axios.post( wpumRegistrationFormsEditor.ajax,
				qs.stringify({
					nonce:   wpumRegistrationFormsEditor.saveFormNonce,
					action:  'wpum_save_registration_form',
					form_id: this.formID,
					fields:  this.selectedFields
				}),
				{
					params: {
						action: 'wpum_save_registration_form'
					},
				}
			)
			.then( response => {
				this.loading        = false
				this.showMessage    = true
				this.messageStatus  = 'success'
				this.messageContent =  wpumRegistrationFormsEditor.labels.success
				this.resetNotice()
			})
			.catch( error => {
				this.loading = false
				this.showMessage    = true
				this.messageStatus  = 'error'
				this.messageContent =  wpumRegistrationFormsEditor.labels.error
				this.resetNotice()
				console.log(error)
			})

		},
		/**
		 * Save settings to the form.
		 */
		saveSettings() {

			this.loadingSettings = true

			axios.post( wpumRegistrationFormsEditor.ajax,
				qs.stringify({
					nonce:   wpumRegistrationFormsEditor.saveFormSettingsNonce,
					form_id: this.formID,
					role:    this.selectedRole
				}),
				{
					params: {
						action: 'wpum_save_registration_form_settings'
					},
				}
			)
			.then( response => {
				this.loadingSettings     = false
				this.showMessageSettings = true
				this.messageStatus       = 'success'
				this.messageContent      = wpumRegistrationFormsEditor.labels.success
				this.resetNotice()
			})
			.catch( error => {
				this.loadingSettings     = false
				this.showMessageSettings = true
				this.messageStatus       = 'error'
				this.messageContent      = wpumRegistrationFormsEditor.labels.error
				this.resetNotice()
				console.log(error)
			})

		}
	}
}
</script>
