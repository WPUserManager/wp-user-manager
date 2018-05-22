<template>
	<div class="media-modal-content wpum-dialog" id="delete-dialog">
		<button type="button" class="media-modal-close" @click="$emit('close')"><span class="media-modal-icon"><span class="screen-reader-text">Close panel</span></span></button>
		<div class="media-frame mode-select wp-core-ui">
			<div class="media-frame-title">
				<h1><span class="dashicons dashicons-warning delete"></span> {{labels.confirm_delete}}</h1>
			</div>
			<div class="media-frame-content">
				<p>{{labels.fields_delete_1}} <strong>{{field_name}}</strong></p>
				<p>{{labels.fields_delete_2}}</p>
			</div>
			<div class="media-frame-toolbar">
				<div class="media-toolbar">
					<div class="media-toolbar-primary search-form">
						<div class="spinner is-active" v-if="loading"></div>
						<button type="button" class="button media-button button-primary button-large media-button-insert" :disabled="loading" @click="deleteField()">{{labels.fields_delete}}</button>
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
	name: 'delete-field-dialog',
	props: {
		field_id: '',
		field_name: '',
		updateStatus: '',
	},
	data() {
		return {
			loading: false,
			labels: wpumFieldsEditor.labels
		}
	},
	methods: {
		/**
		 * Delete a field from the database.
		 * Then tell the parent component what happened.
		 */
		deleteField() {

			this.loading = true

			axios.post( wpumFieldsEditor.ajax,
				qs.stringify({
					nonce: wpumFieldsEditor.delete_field_nonce,
					field_id: this.field_id
				}),
				{
					params: {
						action: 'wpum_delete_field'
					},
				}
			)
			.then( response => {
				this.loading = false
				this.updateStatus('success', response.data.data.field_id )
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
