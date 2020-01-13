<template>
	<div class="media-modal-content wpum-dialog" id="delete-dialog">
		<button type="button" class="media-modal-close" @click="$emit('close')"><span class="media-modal-icon"><span class="screen-reader-text">Close panel</span></span></button>
		<div class="media-frame mode-select wp-core-ui">
			<div class="media-frame-title">
				<h1><span class="dashicons dashicons-warning"></span> {{labels.confirm_delete}}</h1>
			</div>
			<div class="media-frame-content">
				<p>{{labels.modal_form_delete}} <strong>{{form}}</strong>. {{labels.modal_delete}}</p>
			</div>
			<div class="media-frame-toolbar">
				<div class="media-toolbar">
					<div class="media-toolbar-primary search-form">
						<div class="spinner is-active" v-if="loading"></div>
						<button type="button" class="button media-button button-primary button-large media-button-insert" v-text="labels.table_delete_form" :disabled="loading" @click="deleteForm()"></button>
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
	name: 'delete-dialog',
	props: {
		form: '',
		form_id: '',
		updateStatus: ''
	},
	data() {
		return {
			loading: false,
			labels: wpumRegistrationFormsEditor.labels
		}
	},
	methods: {
		/**
		 * Delete a fields form from the database.
		 * Only works when the custom fields addon is active.
		 */
		deleteForm() {
			this.loading = true

			axios.post( wpumRegistrationFormsEditor.ajax,
				qs.stringify({
					nonce: wpumRegistrationFormsEditor.delete_form_nonce,
					form_id: this.form_id
				}),
				{
					params: {
						action: 'wpum_delete_registration_form'
					},
				}
			)
			.then( response => {
				this.loading = false
				this.updateStatus('success', response )
				this.$emit('close')
			})
			.catch( error => {
				this.loading = false
				this.updateStatus('error', error.response.data)
				this.$emit('close')
			})
		}
	}
}
</script>
