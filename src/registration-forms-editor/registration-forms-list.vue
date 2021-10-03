<template>
	<div class="wpum-registration-forms-list">

		<h1 class="wp-heading-inline">
			<img :src="pluginURL + 'assets/images/logo.svg'" alt="WP User Manager">
			{{labels.page_title}}
		</h1>
		<a href="#" class="page-title-action" id="wpum-add-form" @click="showAddRegistrationFormDialog()"><span class="dashicons dashicons-plus-alt"></span> <span v-text="sanitized(labels.table_add_form)"></span></a>
		<br/><br/>

		<wp-notice :type="messageStatus" v-if="showMessage">
			<strong>{{sanitized(messageText)}}</strong>
		</wp-notice>

		<v-dialog/>
		<modals-container/>

		<table class="wp-list-table widefat fixed striped wpum-registration-forms-table">
			<thead>
				<tr>
					<th scope="col" class="column-primary">{{labels.table_name}}</th>
					<th scope="col" style="width: 50px;">{{labels.table_fields}}</th>
					<th scope="col" style="width: 50px;" :data-balloon="labels.table_default_tooltip" data-balloon-pos="left">{{labels.table_default}}</th>
					<th scope="col">{{labels.table_role}}</th>
					<th scope="col" v-if="isAddonInstalled">{{labels.table_shortcode}}</th>
					<th scope="col" v-if="isAddonInstalled">{{labels.table_signup_total}}</th>
					<th scope="col" v-text="sanitized(labels.table_actions)"></th>
				</tr>
			</thead>
			<tbody>
				<tr class="no-items" v-if="loading">
					<td class="colspanchange" colspan="4">
						<div class="spinner is-active"></div>
					</td>
				</tr>
				<tr class="no-items" v-if="forms.length < 1 && ! loading"><td class="colspanchange" colspan="4"><strong>{{labels.table_not_found}}</strong></td></tr>
				<tr v-if="forms" v-for="form in forms" :key="form.id">
					<td>
						<strong>
							<router-link :to="{ name: 'form', params: { id: form.id }}">{{form.name}}</router-link></strong><br>
						<div class="row-actions">
							<span v-for="(action, key) in rowActions" :key="key">
								<a href="#" @click="onRowActionClick( form, action.action )" v-text="sanitized( action.text )"></a>
							</span>
						</div>
					</td>
					<td>
						{{form.count}}
					</td>
					<td>
						<span v-if="isDefault(form)" class="dashicons dashicons-yes"></span>
					</td>
					<td>
						{{form.role}}
					</td>
					<td v-if="isAddonInstalled">
						[wpum_register form_id="{{form.id}}"]
					</td>
					<td v-if="isAddonInstalled">
						{{form.total_signups}}
					</td>
					<td class="align-middle">
						<router-link :to="{ name: 'form', params: { id: form.id }}" tag="button" type="submit" class="button" v-if="isDefault(form) || isAddonInstalled"><span class="dashicons dashicons-admin-settings"></span> <span v-text="sanitized(labels.table_customize)"></span></router-link>
						<button type="submit" class="button delete-btn" v-if="! isDefault(form)" @click="showDeleteDialog( form.name, form.id )"><span class="dashicons dashicons-trash"></span> <span v-text="sanitized(labels.table_delete_form)"></span></button>
					</td>
				</tr>
			</tbody>
		</table>

	</div>
</template>

<script>
import axios from 'axios'
import Sanitize from 'sanitize-html'
import balloon from 'balloon-css'
import DeleteDialog from './dialogs/dialog-delete-form'
import EditFormDialog from './dialogs/dialog-edit-form'
import PremiumFormsDialog from './dialogs/dialog-premium'
import CreateFormDialog from './dialogs/dialog-create-form'
import removeFormByID from 'lodash.remove'
import findFormIndex from 'lodash.findindex'
import hooks from './../hooks'

export default {
	name: 'registration-forms-list',
	components: {
		DeleteDialog,
		EditFormDialog
	},
	data() {
		return {
			labels:    wpumRegistrationFormsEditor.labels,
			pluginURL: wpumRegistrationFormsEditor.pluginURL,
			loading:   false,
			forms:     '',
			showMessage: false,
			messageStatus: 'success',
			isAddonInstalled: wpumRegistrationFormsEditor.is_addon_installed,
			messageText: wpumRegistrationFormsEditor.labels.success_message,
		}
	},
	computed: {
		rowActions(){
			return hooks.applyFilters(
				'wpumrfListRowActions',
				[
					{
						text: this.labels.table_edit_form,
						action: (form) => {
							this.showEditFormDialog(form)
						}
					}
				]
			);
		}
	},
	created() {
		/**
		 * Retrieve forms on page load.
		*/
		this.getForms()
	},
	methods: {
		/**
		 * Sanitize strings (needed because strings can be translated)
		 */
		sanitized( content ) {
			return Sanitize( content )
		},
		/**
		 * Determine if the form is the default one or not.
		 * Needed to check wether we can delete it or not.
		 */
		isDefault( form ) {
			return form.default === true
		},
		/**
		 * Show the success status for the editor.
		 *
		 * - Disable loading spinner.
		 * - Enable message.
		 * - Set message status to success.
		 * - Inject the status message.
		 */
		showSuccess() {
			this.loading = false;
			this.showMessage = true;
			this.messageStatus = 'success';
			this.messageText = wpumRegistrationFormsEditor.labels.success_message;
			this.resetMessages()
		},
		/**
		 * Show an error message within the app.
		 *
		 * - Disable loading spinner.
		 * - Enable message.
		 * - Set message status to error
		 * - Inject the message from the server side.
		 */
		showError( message ) {
			this.loading = false
			this.showMessage = true
			this.messageStatus = 'error'
			this.messageText = message
			this.resetMessages()
		},
		/**
		 * Automatically hide the admin notice after 4 seconds.
		 */
		resetMessages() {
			let self = this
			setInterval(function() {
				self.$data.showMessage = false
			}, 4000)
		},
		/**
		 * Retrieve the list of created registration forms.
		 */
		getForms() {

			this.loading = true

			axios.get( wpumRegistrationFormsEditor.ajax, {
				params: {
					nonce:  wpumRegistrationFormsEditor.getFormsNonce,
					action: 'wpum_get_registration_forms'
				}
			})
			.then( response => {
				this.loading = false
				this.forms   = response.data.data
			})
			.catch( error => {
				this.loading = false
				console.log(error)
			})

		},
		/**
		 * Show the delete form modal.
		 */
		showDeleteDialog( form_name, form_id ) {
			this.$modal.show( DeleteDialog, {
				form: form_name,
				form_id: form_id,
				/**
				 * Pass a function to the component so we can
				 * then update the app status from the child component response.
				 */
				updateStatus:(status, id_or_message) => {
					if( status == 'error' ) {
						this.showError(id_or_message)
					} else {
						removeFormByID(this.forms, {
							id: id_or_message.data.data
						})
						this.showSuccess()
					}
				}
			},{
				height: '210px',
			})
		},
		/**
		 * Show the edit form dialog.
		 */
		showEditFormDialog( form ) {
			this.$modal.show( EditFormDialog , {
				form_id: form.id,
				form_name: form.name,
				/**
				 * Update the interface with the newly retrieve info from the backend.
				 * Show a success or error message depending on what happened.
				 */
				updateFormDetails: ( status, data_or_message ) => {
					if( status == 'error' ) {
						this.showError(data_or_message)
					} else {
						// Find object index of the updated group.
						const formIndex = findFormIndex( this.forms , function(o) { return o.id == data_or_message.id })
						// Now update the interface content.
						this.forms[formIndex].name = data_or_message.name
						// Show success message.
						this.showSuccess()
					}
				}
			},{
				height: '250px',
			})
		},
		/**
		 * Show the add new form modal or show the premium dialog if the addon isn't installed.
		 */
		showAddRegistrationFormDialog() {
			if( wpumRegistrationFormsEditor.is_addon_installed ) {
				this.$modal.show( CreateFormDialog, {
					addNewForm: ( status, data_or_message ) => {
						if( status == 'error' ) {
							this.showError(data_or_message)
						} else {
							this.showSuccess()
							this.forms.push(data_or_message)
						}
					}
				},{ height: '250px' })
			} else {
				this.$modal.show( PremiumFormsDialog, {},{ height: '220px' })
			}
		},
		onRowActionClick( form, action ) {
			action( form, this )
		}
	}
}
</script>
