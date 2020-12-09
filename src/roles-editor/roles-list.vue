<template>
	<div class="wpum-roles-list">

		<h1 class="wp-heading-inline">
			<img :src="pluginURL + 'assets/images/logo.svg'" alt="WP User Manager">
			{{labels.page_title}}
		</h1>
		<a href="#" class="page-title-action" id="wpum-add-role" @click="showAddRoleDialog( 0 )"><span class="dashicons dashicons-plus-alt"></span> <span v-text="sanitized(labels.table_add_role)"></span></a>
		<br/><br/>

		<wp-notice :type="messageStatus" v-if="showMessage">
			<strong>{{sanitized(messageText)}}</strong>
		</wp-notice>

		<v-dialog/>
		<modals-container/>

		<table class="wp-list-table widefat fixed striped wpum-roles-table">
			<thead>
				<tr>
					<th scope="col" class="column-primary">{{labels.table_name}}</th>
					<th scope="col">{{labels.table_slug}}</th>
					<th class="col-default" scope="col" :data-balloon="labels.table_default_tooltip" data-balloon-pos="left">{{labels.table_default}}</th>
					<th class="col-stat" scope="col">{{labels.table_users}}</th>
					<th class="col-stat" scope="col">{{labels.table_granted}}</th>
					<th class="col-stat" scope="col">{{labels.table_denied}}</th>
					<th scope="col" v-text="sanitized(labels.table_actions)"></th>
				</tr>
			</thead>
			<tbody>
				<tr class="no-items" v-if="loading">
					<td class="colspanchange" colspan="7">
						<div class="spinner is-active"></div>
					</td>
				</tr>
				<tr class="no-items" v-if="roles.length < 1 && ! loading"><td class="colspanchange" colspan="4"><strong>{{labels.table_not_found}}</strong></td></tr>
				<tr v-if="roles" v-for="role in roles" :key="role.id">
					<td>
						<strong>
							<router-link :to="{ name: 'role', params: { id: role.id }}">{{role.name}}</router-link></strong><br>
						<div class="row-actions">
							<router-link :to="{ name: 'role', params: { id: role.id }}"><span v-text="sanitized(labels.table_edit)"></span></router-link>
							|
							<span>
								<a href="#" @click="showAddRoleDialog( role.id )"><span
									v-text="sanitized(labels.table_duplicate_role)"></span></a> |
								</span>
							<span class="delete" v-if="! role.current_user_has_role">
								<a href="#" v-if="! isDefault(role)" @click="showDeleteDialog( role.name, role.id )"><span
								v-text="sanitized(labels.table_delete_role)"></span></a>
								</span>
						</div>
					</td>
					<td>
						{{role.slug}}
					</td>
					<td>
						<a v-if="isDefault(role)" :href="changeDefaultURL">
							<span v-if="isDefault(role)" class="dashicons dashicons-yes"></span>
						</a>
					</td>
					<td>
						<a :href="usersURL + '?role=' + role.id">{{role.count}}</a>
					</td>
					<td>
						{{role.granted_count}}
					</td>
					<td>
						{{role.denied_count}}
					</td>
					<td class="align-middle">
						<router-link :to="{ name: 'role', params: { id: role.id }}" tag="button" type="submit" class="button"><span class="dashicons dashicons-admin-settings"></span> <span v-text="sanitized(labels.table_customize)"></span></router-link>
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
import DeleteDialog from './dialogs/dialog-delete-role'
import EditRoleDialog from './dialogs/dialog-edit-role'
import CreateRoleDialog from './dialogs/dialog-create-role'
import removeRoleByID from 'lodash.remove'
import findFormIndex from 'lodash.findindex'

export default {
	name: 'roles-list',
	components: {
		DeleteDialog,
		EditRoleDialog,
		CreateRoleDialog
	},
	data() {
		return {
			labels:    wpumRolesEditor.labels,
			pluginURL: wpumRolesEditor.pluginURL,
			usersURL: wpumRolesEditor.usersURL,
			changeDefaultURL: wpumRolesEditor.changeDefaultURL,
			loading:   false,
			roles:     '',
			showMessage: false,
			messageStatus: 'success',
			messageText: wpumRolesEditor.labels.success_message,
		}
	},
	created() {
		this.getRoles()
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
		isDefault( role ) {
			return role.default === true
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
			this.messageText = wpumRolesEditor.labels.success_message;
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
		getRoles() {

			this.loading = true

			axios.get( wpumRolesEditor.ajax, {
				params: {
					nonce:  wpumRolesEditor.getRolesNonce,
					action: 'wpum_get_roles'
				}
			})
			.then( response => {
				this.loading = false
				this.roles   = response.data.data
			})
			.catch( error => {
				this.loading = false
				console.log(error)
			})

		},
		/**
		 * Show the delete form modal.
		 */
		showDeleteDialog( role_name, role_id ) {
			this.$modal.show( DeleteDialog, {
				role: role_name,
				role_id: role_id,
				/**
				 * Pass a function to the component so we can
				 * then update the app status from the child component response.
				 */
				updateStatus:(status, id_or_message) => {
					if( status == 'error' ) {
						this.showError(id_or_message)
					} else {
						removeRoleByID(this.roles, {
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
		 * Show the edit role dialog.
		 */
		showEditFormDialog( role ) {
			this.$modal.show( EditRoleDialog , {
				role_id: role.id,
				role_name: role.name,
				/**
				 * Update the interface with the newly retrieve info from the backend.
				 * Show a success or error message depending on what happened.
				 */
				updateFormDetails: ( status, data_or_message ) => {
					if( status == 'error' ) {
						this.showError(data_or_message)
					} else {
						// Find object index of the updated group.
						const roleIndex = findFormIndex( this.forms , function(o) { return o.id == data_or_message.id })
						// Now update the interface content.
						this.roles[roleIndex].name = data_or_message.name
						// Show success message.
						this.showSuccess()
					}
				}
			},{
				height: '250px',
			})
		},
		showAddRoleDialog( role_id ) {
			this.$modal.show( CreateRoleDialog, {
				orig_role_id: role_id,
				addNewRole: ( status, data_or_message ) => {
					if ( status == 'error' ) {
						this.showError( data_or_message )
					} else {
						this.showSuccess()
						this.roles.push( data_or_message )
						this.$router.push({ name: 'role', params: { id: data_or_message.id }})
					}
				}
			}, { height: '250px' } )
		}
	}
}
</script>
