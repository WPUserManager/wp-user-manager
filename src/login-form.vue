<template>
	<div id="wpum-login-form-wrapper">
		<div v-if="loading">loading</div>
		<div v-html="form"></div>
  	</div>
</template>

<script>
import axios from 'axios'
import sanitizeHTML from 'sanitize-html'

export default {
	name: 'wpum-login-form',
	data() {
		return {
			form: '',
			loading: true
		}
	},
	mounted() {
		this.createLoginForm()
	},
	methods: {
		createLoginForm() {
			axios.get( wpumRest.rest + '/get-form/login', {
				params: {
      				_wpnonce: wpumRest.nonce
    			}
			} )
			.then( response => {
				if( typeof response.data.form !== 'undefined' ) {
					this.loading = false
					this.form = sanitizeHTML(response.data.form, {
						allowedTags: sanitizeHTML.defaults.allowedTags.concat( wpumRest.html_tags ),
						allowedAttributes: false
					})
				}
			})
			.catch(function (error) {
				console.log(error);
			});

		}
	}
}
</script>
