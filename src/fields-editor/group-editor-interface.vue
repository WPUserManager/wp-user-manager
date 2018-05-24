<template>
	<section id="wpum-fields-editor-wrapper">

		<h1 class="wp-heading-inline">
			<img :src="pluginURL + 'assets/images/logo.svg'" alt="WP User Manager">
			{{pageTitle}}
		</h1>
		<a href="#" class="page-title-action" id="wpum-add-field-group" @click="showAddGroupDialog()"><span class="dashicons dashicons-plus-alt"></span> <span v-text="sanitized(labels.table_add_group)"></span></a>
		<div class="spinner is-active" v-if="loading"></div>
		<br/><br/>

		<wp-notice :type="messageStatus" v-if="showMessage">
			<strong>{{sanitized(messageText)}}</strong>
		</wp-notice>

		<v-dialog/>
		<modals-container/>

		<table class="wp-list-table widefat fixed striped wpum-fields-groups-table">
			<thead>
				<tr>
					<th scope="col" class="order-column" :data-balloon="sanitized(labels.table_drag_tooltip)" data-balloon-pos="right" v-if="groups.length > 1"><span class="dashicons dashicons-menu"></span></th>
					<th scope="col" class="column-primary" v-text="sanitized(labels.table_name)"></th>
					<th scope="col" v-text="sanitized(labels.table_desc)"></th>
					<th scope="col" class="small-column" v-text="sanitized(labels.table_default)" :data-balloon="sanitized(labels.table_default_tooltip)" data-balloon-pos="up"></th>
					<th scope="col" class="small-column" v-text="sanitized(labels.table_fields)"></th>
					<th scope="col" v-text="sanitized(labels.table_actions)"></th>
				</tr>
			</thead>
				<draggable v-model="groups" :element="'tbody'" :options="{handle:'.order-anchor'}" @start="onSortingStart" @end="onSortingEnd">
					<tr v-for="group in groups" :key="group.id">
						<td class="order-anchor align-middle" v-if="groups.length > 1">
							<span class="dashicons dashicons-menu"></span>
						</td>
						<td class="column-username has-row-actions column-primary">
							<strong>
								<router-link :to="{ name: 'group', params: { id: group.id }}">{{group.name}}</router-link></strong><br>
							<div class="row-actions">
								<span>
									<a href="#" @click="showEditGroupDialog( group )" v-text="sanitized(labels.table_edit_group)"></a>
								</span>
							</div>
							<button type="button" class="toggle-row">
								<span class="screen-reader-text">Show more details</span>
							</button>
						</td>
						<td data-colname="Start Date" v-html="sanitized(group.description)"></td>
						<td data-colname="End Date">
							<span class="dashicons dashicons-yes" v-if="isDefault(group.id)"></span>
						</td>
						<td data-colname="End Date">{{group.fields}}</td>
						<td class="align-middle">
							<router-link :to="{ name: 'group', params: { id: group.id }}" tag="button" type="submit" class="button"><span class="dashicons dashicons-admin-settings"></span> <span v-text="sanitized(labels.table_edit_fields)"></span></router-link>
							<button type="submit" class="button delete-btn" v-if="! isDefault(group.id)" @click="showDeleteDialog( group.name, group.id )"><span class="dashicons dashicons-trash"></span> <span v-text="sanitized(labels.table_delete_group)"></span></button>
						</td>
					</tr>
				</draggable>
		</table>

	</section>
</template>

<script>
import axios from 'axios'
import qs from 'qs'
import Sanitize from 'sanitize-html'
import draggable from 'vuedraggable'
import balloon from 'balloon-css'
import DeleteDialog from './dialogs/dialog-delete-group'
import EditGroupDialog from './dialogs/dialog-edit-group'
import PremiumDialog from './dialogs/dialog-premium'
import CreateGroupDialog from './dialogs/dialog-create-group'
import findGroupIndex from 'lodash.findindex'
import removeGroupByID from 'lodash.remove'

export default {
	name: 'editor-interface',
	components: {
		draggable,
		DeleteDialog,
		EditGroupDialog
	},
	data() {
		return {
			addonInstalled: wpumFieldsEditor.is_addon_installed,
			pageTitle: wpumFieldsEditor.page_title,
			labels: wpumFieldsEditor.labels,
			groups: wpumFieldsEditor.groups,
			loading: false,
			showMessage: false,
			messageStatus: 'success',
			messageText: wpumFieldsEditor.success_message,
			pluginURL: wpumFieldsEditor.pluginURL
		}
	},
	methods: {
		/**
		 * Sanitize strings (needed because strings can be translated)
		 */
		sanitized( content ) {
			return Sanitize( content )
		},
		/**
		 * Determine if the field group is the default one or not.
		 * Needed to check wether we can delete it or not.
		 */
		isDefault( group_id ) {
			return group_id === '1' ? true : false
		},
		/**
		 * Tell the app sorting of the table has started.
		 */
		onSortingStart( event ) {
			this.loading = true
			console.log(event)
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
			this.loading = false
			this.showMessage = true
			this.messageStatus = 'success'
			this.messageText = wpumFieldsEditor.success_message
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
		 * Update the database when the sorting is finished.
		 */
		onSortingEnd( event ) {
			axios.post( wpumFieldsEditor.ajax,
				qs.stringify({
					nonce: wpumFieldsEditor.nonce,
					groups: this.groups
				}),
				{
					params: {
						action: 'wpum_update_fields_groups_order'
					},
				}
			)
			.then( response => {
				this.showSuccess()
			})
			/**
			 * Show error message and update the message text with
			 * the one retrieved from the backend.
			 *
			 * Then automatically hide the message after 3 seconds.
			 */
			.catch( error => {
				this.showError( error.response.data )
			})
		},
		/**
		 * Show the delete group modal.
		 */
		showDeleteDialog( group_name, group_id ) {
			this.$modal.show( DeleteDialog , {
				group: group_name,
				group_id: group_id,
				/**
				 * Pass a function to the component so we can
				 * then update the app status from the child component response.
				 */
				updateStatus:(status, id_or_message) => {
					if( status == 'error' ) {
						this.showError(id_or_message)
					} else {
						removeGroupByID(this.groups, {
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
		 * Show the edit group dialog.
		 */
		showEditGroupDialog( group ) {
			this.$modal.show( EditGroupDialog , {
				group_id: group.id,
				group_name: group.name,
				group_desc: group.description,
				/**
				 * Update the interface with the newly retrieve info from the backend.
				 * Show a success or error message depending on what happened.
				 */
				updateGroupDetails: ( status, data_or_message ) => {
					if( status == 'error' ) {
						this.showError(data_or_message)
					} else {
						// Find object index of the updated group.
						const groupIndex = findGroupIndex( this.groups , function(o) { return o.id == data_or_message.id })
						// Now update the interface content.
						this.groups[groupIndex].name = data_or_message.name
						this.groups[groupIndex].description = data_or_message.description
						// Show success message.
						this.showSuccess()
					}
				}
			},{
				height: '350px',
			})
		},
		/**
		 * Show the add new group modal or show the premium dialog if the addon isn't installed.
		 */
		showAddGroupDialog() {
			if( wpumFieldsEditor.is_addon_installed ) {
				this.$modal.show( CreateGroupDialog, {
					addNewGroup: ( status, data_or_message ) => {
						if( status == 'error' ) {
							this.showError(data_or_message)
						} else {
							this.showSuccess()
							this.groups.push(data_or_message)
						}
					}
				},{ height: '350px' })
			} else {
				this.$modal.show( PremiumDialog, {},{ height: '220px' })
			}
		}
	}
}
</script>
