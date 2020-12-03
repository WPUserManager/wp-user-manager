<template>
	<div class="media-modal-content wpum-dialog" id="create-form-dialog">
		<button type="button" class="media-modal-close" @click="$emit('close')"><span class="media-modal-icon"><span class="screen-reader-text">Close panel</span></span></button>
		<div class="media-frame mode-select wp-core-ui">
			<div class="media-frame-title">
				<h1 v-if="! orig_role_id">{{labels.table_add_role}}</h1>
				<h1 v-if=" orig_role_id">{{labels.table_duplicate_role}} <span v-html="orig_role_id"></span> </h1>
			</div>
			<div class="media-frame-content">
				<form action="#" method="post" class="dialog-form">
					<label for="role-name" :data-balloon="labels.tooltip_role_name" data-balloon-pos="right"><span>{{labels.table_name}}</span> <span class="dashicons dashicons-editor-help"></span></label>
					<input type="text" name="role-name" id="role-name" value="" v-model="roleName">
				</form>
			</div>
			<div class="media-frame-toolbar">
				<div class="media-toolbar">
					<div class="media-toolbar-primary search-form">
						<div class="spinner is-active" v-if="loading"></div>
						<button type="button" class="button media-button button-primary button-large media-button-insert" v-text="! orig_role_id ? labels.create_role : labels.table_duplicate_role" :disabled="loading" @click="createRole()"></button>
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
	name: 'create-role-dialog',
	props: {
		orig_role_id: 0,
		addNewRole: ''
	},
	data() {
		return {
			loading: false,
			labels: wpumRolesEditor.labels,
			roleName: '',
		}
	},
	methods: {
		createRole() {
			this.loading = true

			axios.post( wpumRolesEditor.ajax,
				qs.stringify({
					nonce: wpumRolesEditor.createRoleNonce,
					role_name: this.roleName,
					orig_role_id: this.orig_role_id
				}),
				{
					params: {
						action: 'wpum_create_role'
					},
				}
			)
			.then( response => {
				this.loading = false
				this.addNewRole( 'success', response.data.data )
				this.$emit('close')
			})
			.catch( error => {
				this.loading = false
				this.addNewRole( 'error', error.response )
				this.$emit('close')
			})

		}
	}
}
</script>
