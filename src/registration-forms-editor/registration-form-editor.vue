<template>
	<div>
		<h1 class="wp-heading-inline">
			<img :src="pluginURL + 'assets/images/logo.svg'" alt="WP User Manager">
			{{formName}}
		</h1>
		<router-link to="/" class="page-title-action wpum-icon-button circular" :data-balloon="labels.page_back" data-balloon-pos="down"><span class="dashicons dashicons-arrow-left-alt"></span></router-link>

		<br/><br/>

	  	<div class="widget-liquid-left">
			<div id="widgets-left">
				<div id="available-widgets-d" class="widgets-holder-wrap ui-droppable">
					<div class="sidebar-name">
						<h2>{{labels.editor_available_title}} <div class="spinner is-active" v-if="loading"></div></h2>
					</div>
					<div class="sidebar-description">
						<p class="description">{{labels.editor_available_desc}}</p>
					</div>

					<draggable v-model="availableFields" class="dragArea available-fields-holder" :options="{group:'formFields', sort:false, animation:150}">
						<div class="widget ui-draggable" v-for="element in availableFields" :key="element.name">
							<div class="widget-top">
								<div class="widget-title ui-draggable-handle">
									<h3>{{element.name}}</h3>
								</div>
							</div>
						</div>
      				</draggable>

				</div>
			</div>
		</div>

		<div class="widget-liquid-right">
			<div id="widgets-right" class="wp-clearfix">

				<div class="sidebars-column-1">
					<div class="widgets-holder-wrap">
						<div class="widgets-sortables ui-droppable ui-sortable">
							<div class="sidebar-name">
								<h2>{{formName}}
									<div class="spinner is-active" v-if="loading"></div>
								</h2>
							</div>
							<div class="sidebar-description">
								<p class="description">{{labels.editor_used_fields}}</p>
							</div>
							<!-- start fields list -->
							<draggable v-model="selectedFields" class="droppable-fields" :options="{group:'formFields', animation:150}" @sort="saveFields">
								<div class="widget" v-for="element in selectedFields" :key="element.name">
									<div class="widget-top">
										<div class="widget-title ui-sortable-handle">
											<h3>{{element.name}}</h3>
										</div>
									</div>
								</div>
							</draggable>
							<!-- end fields list -->
						</div>
					</div>
				</div>

			</div>
		</div>

	</div>
</template>

<script>
import axios from 'axios'
import balloon from 'balloon-css'
import draggable from 'vuedraggable'

export default {
	name: 'registration-form-editor',
	components: {
		draggable
	},
	data() {
		return {
			labels:          wpumRegistrationFormsEditor.labels,
			pluginURL:       wpumRegistrationFormsEditor.pluginURL,
			loading:         false,
			formID:          '',
			formName:        '...',
			availableFields: [],
			selectedFields:  [],
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
				this.loading  = false
				this.formName = response.data.data.name
				this.availableFields = response.data.data.available_fields
			})
			.catch( error => {
				this.loading = false
				console.log(error)
			})
		},
		saveFields() {
			this.loading = true
			console.log('test ')
		}
	}
}
</script>
