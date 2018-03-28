<template>
	<section id="wpum-fields-editor-list">
		<h1 class="wp-heading-inline">{{labels.fields_page_title}} "{{group_name}}"</h1>
		<router-link to="/" class="page-title-action wpum-icon-button circular" :data-balloon="labels.fields_go_back" data-balloon-pos="down"><span class="dashicons dashicons-arrow-left-alt"></span></router-link>
		<a href="#" class="page-title-action wpum-icon-button" @click="openCreateNewFieldDialog()"><span class="dashicons dashicons-plus-alt"></span> {{labels.fields_add_new}}</a>

		<v-dialog/>
		<modals-container/>

		<wp-notice :type="messageStatus" v-if="showMessage">
			<strong>{{messageText}}</strong>
		</wp-notice>

		<br/>

		<table class="wp-list-table widefat fixed striped wpum-fields-groups-table">
			<thead>
				<tr>
					<th scope="col" class="order-column" :data-balloon="labels.table_drag_tooltip" data-balloon-pos="right" v-if="fields > 1"><span class="dashicons dashicons-menu"></span></th>
					<th scope="col" class="column-primary">{{labels.fields_name}}</th>
					<th scope="col" class="small-column">{{labels.fields_type}}</th>
					<th scope="col" class="small-column" :data-balloon="labels.fields_required_tooltip" data-balloon-pos="up">{{labels.fields_required}}</th>
					<th scope="col" class="small-column" :data-balloon="labels.fields_default_tooltip" data-balloon-pos="up">{{labels.table_default}}</th>
					<th scope="col" class="small-column" :data-balloon="labels.fields_visibility_tooltip" data-balloon-pos="up">{{labels.fields_visibility}}</th>
					<th scope="col" class="small-column" :data-balloon="labels.fields_editable_tooltip" data-balloon-pos="up">{{labels.fields_editable}}</th>
					<th scope="col">{{labels.table_actions}}</th>
				</tr>
			</thead>
			<tbody>
				<tr v-if="fields && !loading" v-for="field in fields" :key="field.id">
					<td class="order-anchor align-middle" v-if="fields > 1">
						<span class="dashicons dashicons-menu"></span>
					</td>
					<td class="column-primary">
						<a href="">
							<strong>{{field.name}}</strong>
						</a>
					</td>
					<td>
						{{field.type_nicename}}
					</td>
					<td>
						<span class="dashicons dashicons-yes" v-if="isRequired(field.required)"></span>
					</td>
					<td>
						<span class="dashicons dashicons-yes" v-if="isDefault(field.default)"></span>
					</td>
					<td>
						<span class="dashicons dashicons-yes" v-if="field.visibility == 'public'"></span>
						<span class="dashicons dashicons-hidden" v-else></span>
					</td>
					<td>
						<span class="dashicons dashicons-yes" v-if="field.editable == 'public'"></span>
						<span class="dashicons dashicons-lock" v-else></span>
					</td>
					<td class="align-middle">
						<button type="submit" class="button"><span class="dashicons dashicons-edit"></span> {{labels.fields_edit}}</button>
						<button type="submit" class="button delete-btn" @click="openDeleteFieldDialog( field.id, field.name )"><span class="dashicons dashicons-trash"></span> {{labels.fields_delete}}</button>
					</td>
				</tr>
				<tr class="no-items" v-if="fields < 1 && ! loading"><td class="colspanchange" colspan="7"><strong>{{labels.fields_not_found}}</strong></td></tr>
				<tr class="no-items" v-if="loading">
					<td class="colspanchange" colspan="7">
						<div class="spinner is-active"></div>
					</td>
				</tr>
			</tbody>
		</table>

	</section>
</template>

<script>
import axios from 'axios'
import balloon from 'balloon-css'
import findGroupIndex from 'lodash.findindex'
import PremiumDialog from './dialogs/dialog-premium'
import DeleteFieldDialog from './dialogs/dialog-delete-field'
import removeFieldByID from 'lodash.remove'

export default {
	name: 'fields-editor-interface',
	data() {
		return {
			addonInstalled: wpumFieldsEditor.is_addon_installed,
			labels:         wpumFieldsEditor.labels,
			group_id:       '',
			group_name:     '',
			fields:         [],
			loading:        false,
			showMessage:    false,
			messageStatus:  'success',
			messageText:    wpumFieldsEditor.success_message,
		}
	},
	/**
	 * Detect the selected group to edit and retrieve group id and group name.
	 */
	created() {
		const group_id      = this.$route.params.id.toString()
		const selectedGroup = findGroupIndex( wpumFieldsEditor.groups, function(o) { return o.id == group_id })
		this.group_id       = group_id
		this.group_name     = wpumFieldsEditor.groups[selectedGroup].name
		// Load fields from the database.
		this.getFields()
	},
	methods: {
		/**
		 * Open the create new field dialog. Only works when the premium addon is installed.
		 */
		openCreateNewFieldDialog() {
			if( wpumFieldsEditor.is_addon_installed ) {

			} else {
				this.$modal.show( PremiumDialog, {},{ height: '220px' })
			}
		},
		/**
		 * Determine if the field is a required one or not.
		 */
		isRequired( is_required ) {
			return is_required === true ? true : false
		},
		/**
		 * Determine if the field is a default one or not.
		 */
		isDefault( is_default ) {
			return is_default === true ? true : false
		},
		/**
		 * Show the success status for the editor.
		 *
		 * - Disable loading spinner.
		 * - Enable message.
		 * - Set message status to success.
		 * - Inject the status message.
		 */
		showSuccess() {
			this.loading = false
			this.showMessage = true
			this.messageStatus = 'success'
			this.messageText = wpumFieldsEditor.success_message
			this.resetMessages()
		},
		/**
		 * Show an error message within the app.
		 *
		 * - Disable loading spinner.
		 * - Enable message.
		 * - Set message status to error
		 * - Inject the message from the server side.
		 */
		showError( message ) {
			this.loading = false
			this.showMessage = true
			this.messageStatus = 'error'
			this.messageText = message
			this.resetMessages()
		},
		/**
		 * Automatically hide the admin notice after 4 seconds.
		 */
		resetMessages() {
			let self = this
			setInterval(function() {
				self.$data.showMessage = false
			}, 4000)
		},
		/**
		 * Load fields from the database.
		 */
		getFields() {

			this.loading = true

			axios.get( wpumFieldsEditor.ajax, {
				params: {
					group_id: this.group_id,
					nonce: wpumFieldsEditor.get_fields_nonce,
					action: 'wpum_get_fields_from_group'
				}
			})
			.then( response => {
				this.loading = false
				if ( typeof response.data.data.fields !== 'undefined' && response.data.data.fields.length > 0 ) {
					this.fields = response.data.data.fields
				}
			})
			.catch( error => {
				this.loading = false
				console.error(error);
			})

		},
		/**
		 * Show the dialog asking the user to delete a field.
		 */
		openDeleteFieldDialog( id, name ) {
			this.$modal.show( DeleteFieldDialog, {
				field_id: id,
				field_name: name,
				/**
				 * Pass a function to the component so we can
				 * then update the app status from the child component response.
				 */
				updateStatus:(status, id_or_message) => {
					if( status == 'error' ) {
						this.showError(id_or_message)
					} else {
						removeFieldByID(this.fields, {
							id: id_or_message.data.data.field_id
						})
						this.showSuccess()
					}
				}
			},{ height: '230px' })
		}
	}
}
</script>

<style lang="scss">
.wpum-icon-button {
	&.circular {
		padding: 4px !important;
		border-radius: 9999px !important;
	}
	span.dashicons {
		width: 16px;
		height: 16px;
		font-size: 16px;
		vertical-align: inherit;
		position: relative;
		top: 3px;
	}
}

.order-column {
	width: 30px;
	border-right: 1px solid #e1e1e1;
	text-align: center !important;
}

.order-anchor {
	border-right: 1px solid #e1e1e1;
	text-align: center !important;
}

.small-column {
	width: 100px;
}

.wpum-fields-groups-table {
	.button {
		margin-right: 5px;
		&:last-child {
			margin-right: 0;
		}
		span.dashicons {
			position: relative;
			top: 3px;
			margin-right: 3px;
		}
		&.delete-btn {
			&:hover {
				span.dashicons {
					color: red;
				}
			}
		}
	}

	.dashicons-yes {
		color: green;
	}

	td {
		vertical-align: middle;
		padding: 12px 10px;
	}

	.spinner {
		margin: 0;
		float:none !important;
	}

}
.v--modal-overlay {
	background: rgba(0, 0, 0, 0.7);
	z-index: 9999;
}

.v--modal {
	box-shadow: 0 5px 15px rgba(0,0,0,.7);
	background: #fcfcfc;
	border-radius: 0;
}

.media-modal-content {
	min-height: initial;
	background: #efefef;
}

.media-frame-title,
.media-frame-content,
.media-frame-toolbar {
	left: 0;
}

.media-frame-title {
	.dashicons {
		display: inline-block;
		margin-top: 16px;
		margin-right: 10px;
		&.dashicons-warning {
			color: green;
			&.delete {
				color: red;
			}
		}
	}
}

.media-frame-content {
	top: 50px;
	padding: 10px 16px;
	font-size: 13px;
	line-height: 1.6em;
}

.wpum-dialog {
	.spinner {
		float: none;
		margin-top: 20px;
	}
}

.dialog-form {
	padding-top: 10px;
	label {
		display: inline-block;
		font-weight: bold;
		color: #000;
		margin-bottom: 5px;
	}

	input, textarea {
		display: block;
		width: 100%;
		margin-bottom: 15px;
		font-size: 13px !important;
		&:last-child {
			margin-bottom: 0;
		}
	}
}

.vue-wp-notice {
	margin-right: 0 !important;
	margin-top: 20px !important;
}

</style>
