<template>
	<div>
		<h1 class="wp-heading-inline">
			<img :src="pluginURL + 'assets/images/logo.svg'" alt="WP User Manager">
			{{formName}}
		</h1>
		<router-link to="/" class="page-title-action wpum-icon-button circular" :data-balloon="labels.page_back" data-balloon-pos="down"><span class="dashicons dashicons-arrow-left-alt"></span></router-link>

		<br/><br/>

	</div>
</template>

<script>
import axios from 'axios'
import balloon from 'balloon-css'

export default {
	name: 'registration-form-editor',
	data() {
		return {
			labels:    wpumRegistrationFormsEditor.labels,
			pluginURL: wpumRegistrationFormsEditor.pluginURL,
			loading:   false,
			formID:    '',
			formName:  '...',
			availableFields: [],
			selectedFields: []
		}
	},
	created() {
		// Grab the form id from the router.
		this.formID = this.$route.params.id
		// Retrieve the selected form.
		this.getForm()
	},
	methods: {
		/**
		 * Retrieve the registration form from the db.
		 */
		getForm() {
			this.loading = true

			axios.get( wpumRegistrationFormsEditor.ajax, {
				params: {
					nonce:   wpumRegistrationFormsEditor.getFormNonce,
					action:  'wpum_get_registration_form',
					form_id: this.formID
				}
			})
			.then( response => {

				this.loading = false

				this.formName = response.data.data.name

			})
			.catch( error => {
				this.loading = false
				console.log(error)
			})
		}
	}
}
</script>
