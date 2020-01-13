<template>
	<div class="media-modal-content wpum-dialog" id="edit-dialog">
		<button type="button" class="media-modal-close" @click="$emit('close')"><span class="media-modal-icon"><span class="screen-reader-text">Close panel</span></span></button>
		<div class="media-frame mode-select wp-core-ui">
			<div class="media-frame-title">
				<h1>{{labels.table_edit_form}}</h1>
			</div>
			<div class="media-frame-content">
				<form action="#" method="post" class="dialog-form">
					<label for="form-name" :data-balloon="labels.tooltip_form_name" data-balloon-pos="right"><span>{{labels.table_name}}</span> <span class="dashicons dashicons-editor-help"></span></label>
					<input type="text" name="form-name" id="form-name" value="" v-model="formName">
				</form>
			</div>
			<div class="media-frame-toolbar">
				<div class="media-toolbar">
					<div class="media-toolbar-primary search-form">
						<div class="spinner is-active" v-if="loading"></div>
						<button type="button" class="button media-button button-primary button-large media-button-insert" v-text="labels.save" :disabled="loading" @click="updateForm()"></button>
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
	name: 'edit-form-dialog',
	props: {
		form_id: '',
		form_name: '',
		updateFormDetails: '',
	},
	data() {
		return {
			loading: false,
			labels: wpumRegistrationFormsEditor.labels,
			formName: ''
		}
	},
	/**
	 * Update the data with the form name and description coming from the props.
	 * Data will then be sent back to the db when updated and also back to the parent component.
	 */
	mounted() {
		this.formName = this.form_name
	},
	methods: {
		/**
		 * Update the form details within the database and send back the data to the interface.
		 * The interface is the parent component.
		 */
		updateForm() {
			this.loading = true

			// Make a call via ajax.
			axios.post( wpumRegistrationFormsEditor.ajax,
				qs.stringify({
					nonce: wpumRegistrationFormsEditor.nonce,
					form_id: this.form_id,
					form_name: this.formName,
				}),
				{
					params: {
						action: 'wpum_update_registration_form'
					},
				}
			)
			.then( response => {
				this.loading = false
				this.updateFormDetails( 'success', response.data.data )
				this.$emit('close')
			})
			.catch( error => {
				this.loading = false
				this.updateFormDetails( 'error', error.response.data )
				this.$emit('close')
			})

		}
	}
}
</script>
