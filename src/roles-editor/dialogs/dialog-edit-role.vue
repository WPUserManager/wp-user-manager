<template>
	<div class="media-modal-content wpum-dialog" id="edit-role">
		<button type="button" class="media-modal-close" @click="$emit('close')"><span class="media-modal-icon"><span class="screen-reader-text">Close panel</span></span></button>
		<div class="media-frame mode-select wp-core-ui">
			<div class="media-frame-title">
				<h1>{{labels.table_edit_role}}</h1>
			</div>
			<div class="media-frame-content">
				<form action="#" method="post" class="dialog-form">
					<label for="role-name" :data-balloon="labels.tooltip_form_name" data-balloon-pos="right"><span>{{labels.table_name}}</span> <span class="dashicons dashicons-editor-help"></span></label>
					<input type="text" name="role-name" id="role-name" value="" v-model="roleName">
				</form>
			</div>
			<div class="media-frame-toolbar">
				<div class="media-toolbar">
					<div class="media-toolbar-primary search-form">
						<div class="spinner is-active" v-if="loading"></div>
						<button type="button" class="button media-button button-primary button-large media-button-insert" v-text="labels.save" :disabled="loading" @click="updateRole()"></button>
					</div>
				</div>
			</div>
		</div>
	</div>
</template>

<script>
import axios from 'axios'
import qs from 'qs'

export default {
	name: 'edit-role-dialog',
	props: {
		role_id: '',
		role_name: '',
		updateRoleDetails: '',
	},
	data() {
		return {
			loading: false,
			labels: wpumRolesEditor.labels,
			roleName: ''
		}
	},
	mounted() {
		this.roleName = this.role_name
	},
	methods: {
		updateRole() {
			this.loading = true

			// Make a call via ajax.
			axios.post( wpumRolesEditor.ajax,
				qs.stringify({
					nonce: wpumRolesEditor.nonce,
					role_id: this.form_id,
					role_name: this.role_name,
				}),
				{
					params: {
						action: 'wpum_update_role'
					},
				}
			)
				.then( response => {
					this.loading = false
					this.updateRoleDetails( 'success', response.data.data )
					this.$emit('close')
				})
				.catch( error => {
					this.loading = false
					this.updateRoleDetails( 'error', error.response.data )
					this.$emit('close')
				})

		}
	}
}
</script>
