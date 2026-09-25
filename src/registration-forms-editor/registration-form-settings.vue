<template>
	<div class="wpum-registration-form">
		<h1 class="wp-heading-inline">
			<img :src="pluginURL + 'assets/images/logo.svg'" alt="WP User Manager">
			{{formName}}
		</h1>
		<router-link to="/" class="page-title-action wpum-icon-button circular" :data-balloon="labels.page_back" data-balloon-pos="down">
			<span class="dashicons dashicons-arrow-left-alt"></span></router-link>
		<div class="optionskit-navigation-wrapper">
			<div class="wp-filter" id="optionskit-navigation">
				<ul class="filter-links">
					<li v-for="section in sections" :key="section.path">
						<router-link :to="{ name: section.name, params: { id: formID }}">{{section.label}}</router-link>
					</li>
				</ul>
			</div>
		</div>

		<wp-notice :type="messageStatus" alternative v-if="showMessageSettings">{{messageContent}}</wp-notice>
		<form action="post" @submit.prevent="saveSettings()" class="opk-form">
			<div class="optionskit-form-wrapper">
				<table class="form-table">
					<tr class="registration-form-setting" v-for="field in settings" :key="field.id" v-show="! field.toggle || settingsModel[field.toggle.key] == field.toggle.value">
						<th scope="row">
							<label :for="field.id">{{field.name}}</label>
						</th>
						<td>
							<component v-bind:is="getFieldComponentName(field.type)" :field="field" :class="classes(field.type)" v-model="settingsModel[field.id]" :disabled="loading || loadingSettings"></component>
							<p class="description" v-if="field.desc">{{field.desc}}</p>
						</td>
					</tr>
				</table>
				<div class="spinner is-active" v-if="loading"></div>
				<input type="submit" class="button button-primary button-large" v-model="labels.save" :disabled="loading || loadingSettings">
				<div class="spinner is-active" v-show="loadingSettings"></div>
			</div>
		</form>
	</div>
</template>

<script>
	import axios from 'axios'
	import qs from 'qs'
	export default {
		name: 'registration-form-settings',
		components: { },
		data() {
			return {
				labels: wpumRegistrationFormsEditor.labels,
				pluginURL: wpumRegistrationFormsEditor.pluginURL,
				loading: false,
				loadingSettings: false,
				formID: '',
				formName: '...',
				settings: {},
				settingsModel: {},
				showMessage: false,
				showMessageSettings: false,
				messageStatus: 'success',
				messageContent: '',
				sections:		[]
			}
		},
		created() {
			// Grab the form id from the router.
			this.formID = this.$route.params.id			
			this.$router.options.routes.forEach(route => {
				if (route.meta && route.meta.label) {
					this.sections.push({
						name:  route.name,
						path:  route.path,
						label: route.meta.label
					});
				}
			})

			// Retrieve the selected form.
			this.getForm()
		},
		methods: {
			/**
			 * Setup classes for the component based on the field type.
			 */
			classes( type ) {
				return [
					'opk-field',
					type == 'text' ? 'regular-text' : ''
				];
			},
			/**
			 * Sets the name of the component to retrieve.
			 */
			getFieldComponentName( type ) {
				return 'formit-' + type
			},
			/**
			 * Retrieve the registration form from the db.
			 */
			getForm() {
				this.loading = true
				axios.get( wpumRegistrationFormsEditor.ajax, {
					params: {
						nonce: wpumRegistrationFormsEditor.getFormNonce,
						action: 'wpum_get_registration_form',
						form_id: this.formID
					}
				} )
					.then( response => {
						this.loading = false
						this.formName = response.data.data.name
						this.settings = response.data.data.settings[this.$route.meta.key],
						this.settingsModel = response.data.data.settings_model
					} )
					.catch( error => {
						this.loading = false
						console.log( error )
					} )
			},
			/**
			 * Automatically hide a notice after it's displayed.
			 */
			resetNotice() {
				setTimeout( () => {
					this.showMessage = false
					this.showMessageSettings = false
				}, 3000 )
			},
			/**
			 * Save settings to the form.
			 */
			saveSettings() {

				this.loadingSettings = true

				axios.post( wpumRegistrationFormsEditor.ajax,
					qs.stringify( {
						nonce: wpumRegistrationFormsEditor.saveFormSettingsNonce,
						form_id: this.formID,
						settings_model: this.settingsModel
					} ),
					{
						params: {
							action: 'wpum_save_registration_form_settings'
						},
					}
				)
					.then( response => {
						this.loadingSettings = false
						this.showMessageSettings = true
						this.messageStatus = 'success'
						this.messageContent = wpumRegistrationFormsEditor.labels.success
						this.resetNotice()
					} )
					.catch( error => {
						this.loadingSettings = false
						this.showMessageSettings = true
						this.messageStatus = 'error'
						this.messageContent = wpumRegistrationFormsEditor.labels.error
						this.resetNotice()
						console.log( error )
					} )

			}
		}
	}
</script>
