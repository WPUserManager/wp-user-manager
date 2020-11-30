<template>
	<div class="wpum-role">
		<h1 class="wp-heading-inline">
			<img :src="pluginURL + 'assets/images/logo.svg'" alt="WP User Manager">
			{{ roleName }}
		</h1>
		<router-link to="/" class="page-title-action wpum-icon-button circular" :data-balloon="labels.page_back"
					 data-balloon-pos="down"><span class="dashicons dashicons-arrow-left-alt"></span></router-link>
		<a class="page-title-action wpum-icon-button" @click="openCreateNewCapDialog()"><span class="dashicons dashicons-plus-alt"></span> {{labels.add_new_cap}}</a>
		<div class="wpum-role-wrapper">
			<div class="wpum-role-caps">
dsds
			</div>
			<div class="wpum-role-stats">
sdsdsdsd
			</div>
		</div>
	</div>
</template>

<script>
import Vue from 'vue'
import axios from 'axios'
import qs from 'qs'
import balloon from 'balloon-css'
import CreateFieldDialog from "../fields-editor/dialogs/dialog-create-field";
import PremiumDialog from "../fields-editor/dialogs/dialog-premium";

export default {
	name: 'role-editor',
	data() {
		return {
			labels:              wpumRolesEditor.labels,
			pluginURL:           wpumRolesEditor.pluginURL,
			loading:             false,
			loadingSettings:     false,
			roleID:              '',
			roleName:            '...',
			showMessage:         false,
			showMessageSettings: false,
			messageStatus:       'success',
			messageContent:      ''
		}
	},
	created() {
		this.roleID = this.$route.params.id
		this.getRole()
	},
	methods: {
		/**
		 * Setup classes for the component based on the field type.
		 */
		classes (type) {
			return [
				'opk-field',
				type == 'text' ? 'regular-text' : ''
			];
		},
		/**
		 * Retrieve the role
		 */
		getRole() {
			this.loading = true

			axios.get( wpumRolesEditor.ajax, {
				params: {
					nonce:   wpumRolesEditor.getRoleNonce,
					action:  'wpum_get_role',
					role_id: this.roleID
				}
			})
			.then( response => {
				this.loading         = false
				this.roleName        = response.data.data.name
			})
			.catch( error => {
				this.loading = false
				console.log(error)
			})
		},
		/**
		 * Open the create new field dialog. Only works when the premium addon is installed.
		 */
		openCreateNewCapDialog() {
			this.$modal.show( CreateCapDialog, {
				addNewCap: ( status, data_or_message ) => {
					if ( status == 'error' ) {
						this.showError( data_or_message )
					} else {
						this.showSuccess()
						this.getFields()

						const field_id = data_or_message.field_id
						const field_name = data_or_message.field_name
						const field_type = data_or_message.field_type
						const default_id = data_or_message.default_id
					}
				}
			}, { height: '80%', width: '70%' } )
		},
		/**
		 * Automatically hide a notice after it's displayed.
		*/
		resetNotice() {
			setTimeout( () => {
				this.showMessage = false
				this.showMessageSettings = false
			}, 3000)
		}
	}
}
</script>
