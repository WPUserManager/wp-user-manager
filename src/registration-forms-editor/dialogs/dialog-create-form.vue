<template>
	<div class="media-modal-content wpum-dialog" id="create-form-dialog">
		<button type="button" class="media-modal-close" @click="$emit('close')"><span class="media-modal-icon"><span class="screen-reader-text">Close panel</span></span></button>
		<div class="media-frame mode-select wp-core-ui">
			<div class="media-frame-title">
				<h1>{{labels.table_add_form}}</h1>
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
						<button type="button" class="button media-button button-primary button-large media-button-insert" v-text="labels.create_form" :disabled="loading" @click="createForm()"></button>
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
	name: 'create-form-dialog',
	props: {
		addNewForm: ''
	},
	data() {
		return {
			loading: false,
			labels: wpumRegistrationFormsEditor.labels,
			formName: '',
		}
	},
	methods: {
		/**
		 * Create a new form via ajax.
		 * Only works when the Registration Forms addon is installed.
		 */
		createForm() {

			this.loading = true

			// Make a call via ajax.
			axios.post( wpumRegistrationFormsEditor.ajax,
				qs.stringify({
					nonce: wpumRegistrationFormsEditor.nonce,
					form_name: this.formName
				}),
				{
					params: {
						action: 'wpum_create_registration_form'
					},
				}
			)
			.then( response => {
				this.loading = false
				this.addNewForm( 'success', response.data.data )
				this.$emit('close')
			})
			.catch( error => {
				this.loading = false
				this.addNewForm( 'error', error.response )
				this.$emit('close')
			})

		}
	}
}
</script>
