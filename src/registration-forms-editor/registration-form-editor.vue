<template>
	<div class="wpum-registration-form">
			<h1 class="wp-heading-inline">
				<img :src="pluginURL + 'assets/images/logo.svg'" alt="WP User Manager">
				{{formName}}
			</h1>
			<router-link to="/" class="page-title-action wpum-icon-button circular" :data-balloon="labels.page_back" data-balloon-pos="down"><span class="dashicons dashicons-arrow-left-alt"></span></router-link>
		<div class="optionskit-navigation-wrapper">
			<div class="wp-filter" id="optionskit-navigation">
				<ul class="filter-links">
					<li v-for="section in sections" :key="section.path">
						<router-link :to="{ name: section.name, params: { id: formID }}">{{section.label}}</router-link>
					</li>
				</ul>
			</div>
		</div>

		<div class="widget-liquid-right">
			<div id="widgets-left">
				<div id="available-widgets-d" class="widgets-holder-wrap ui-droppable">
					<div class="sidebar-name">
						<h2>{{labels.editor_available_title}}</h2>
					</div>
					<div class="sidebar-description">
						<p class="description">{{labels.editor_available_desc}}</p>
					</div>

					<draggable v-model="availableFields" class="dragArea available-fields-holder" :options="{group:'formFields', sort:false, animation:150}">
						<div class="widget ui-draggable" v-for="element in availableFields" :key="element.id">
							<div class="widget-top">
								<div class="widget-title ui-draggable-handle">
									<h3><span :class="'dashicons ' + element.icon"></span> {{element.name}}</h3>
								</div>
							</div>
						</div>
      				</draggable>

				</div>
			</div>
		</div>

		<div class="widget-liquid-left">
			<div id="widgets-right" class="wp-clearfix">

				<div class="sidebars-column-3">
					<div class="widgets-holder-wrap">

						<wp-notice :type="messageStatus" alternative v-if="showMessage">{{messageContent}}</wp-notice>

						<div class="widgets-sortables ui-droppable ui-sortable">
							<div class="sidebar-name">
								<h2>{{labels.editor_current_title}} <div class="spinner is-active" v-if="loading"></div></h2>
							</div>
							<div class="sidebar-description">
								<p class="description">{{labels.editor_used_fields}}</p>
							</div>
							<!-- start fields list -->
							<draggable v-model="selectedFields" class="droppable-fields" :options="{group:'formFields', animation:150}" @sort="saveFields">
								<div class="widget" v-for="(element, i) in selectedFields" :key="i">
									<component v-if="isComponentAvailable(element.type)" :is="`wpum-field-${element.type}`" :field="element"></component>
									<div class="widget-top" v-else>
										<div class="widget-title ui-sortable-handle">
											<h3><span :class="'dashicons ' + element.icon"></span> {{element.name}}</h3>
										</div>
									</div>
								</div>
							</draggable>
							<!-- end fields list -->
							<div class="droppable-fields-after">
								<template v-for="field in droppableFieldAfter">
									<component :is="field" :key="field.name"></component>
								</template>

								<p v-if="! isAddonInstalled" style="text-align:right">
									<button class="button" @click="showRegistrationAddonDialog('step')">Add Step</button>
									<button class="button" @click="showRegistrationAddonDialog('html')">Add HTML</button>

									<modals-container/>
								</p>
							</div>
						</div>
					</div>
				</div>

			</div>
		</div>

	</div>
</template>

<script>
import Vue from 'vue'
import axios from 'axios'
import qs from 'qs'
import balloon from 'balloon-css'
import draggable from 'vuedraggable'
import hooks from './../hooks'
import PremiumFormsDialog from "./dialogs/dialog-premium";

export default {
	name: 'registration-form-editor',
	components: {
		draggable
	},
	data() {
		return {
			labels:              wpumRegistrationFormsEditor.labels,
			pluginURL:           wpumRegistrationFormsEditor.pluginURL,
			isAddonInstalled: 	 wpumRegistrationFormsEditor.is_addon_installed,
			loading:             false,
			loadingSettings:     false,
			formID:              '',
			formName:            '...',
			availableFields:     [],
			selectedFields:      [],
			settings:            {},
			settingsModel:      {},
			showMessage:         false,
			showMessageSettings: false,
			messageStatus:       'success',
			messageContent:      '',
			fields:				 {},
			sections:         []
		}
	},
	computed: {
		droppableFieldAfter(){
			return hooks.applyFilters('droppableFieldAfter', []);
		}
	},
	created() {
		// Grab the form id from the router.
		this.formID = this.$route.params.id
		// Retrieve the selected form.
		this.getForm()
		this.$router.options.routes.forEach(route => {
			if (route.meta && route.meta.label) {
				this.sections.push({
					name:  route.name,
					path:  route.path,
					label: route.meta.label
				});
			}
		})
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
		 * Sets the name of the component to retrieve.
		 */
		getFieldComponentName ( type ) {
			return 'formit-'+type
		},
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
				this.loading         = false
				this.formName        = response.data.data.name
				this.availableFields = response.data.data.available_fields
				this.selectedFields  = response.data.data.stored_fields
			})
			.catch( error => {
				this.loading = false
				console.log(error)
			})
		},
		/**
		 * Automatically hide a notice after it's displayed.
		*/
		resetNotice() {
			setTimeout( () => {
				this.showMessage = false
				this.showMessageSettings = false
			}, 3000)
		},
		/**
		 * Save fields to the form.
		 */
		saveFields() {

			this.loading = true

			axios.post( wpumRegistrationFormsEditor.ajax,
				qs.stringify({
					nonce:   wpumRegistrationFormsEditor.saveFormNonce,
					action:  'wpum_save_registration_form',
					form_id: this.formID,
					fields:  this.selectedFields
				}),
				{
					params: {
						action: 'wpum_save_registration_form'
					},
				}
			)
			.then( response => {
				this.loading        = false
				this.showMessage    = true
				this.messageStatus  = 'success'
				this.messageContent =  wpumRegistrationFormsEditor.labels.success
				this.resetNotice()
			})
			.catch( error => {
				this.loading = false
				this.showMessage    = true
				this.messageStatus  = 'error'
				this.messageContent =  wpumRegistrationFormsEditor.labels.error
				this.resetNotice()
				console.log(error)
			})

		},
		isComponentAvailable( type ){
			return !!this.$root.$options.components['wpum-field-' + type];
		},
		/**
		 * Show the add new form modal or show the premium dialog if the addon isn't installed.
		 */
		showRegistrationAddonDialog(type) {
				this.$modal.show( PremiumFormsDialog, {type: type},{ height: '220px' })
		}
	}
}
</script>
