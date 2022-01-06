<template>
	<div class="media-modal-content wpum-dialog" id="create-field-dialog">
		<button type="button" class="media-modal-close" @click="$emit('close')"><span class="media-modal-icon"><span class="screen-reader-text">Close panel</span></span></button>
		<div class="media-frame mode-select wp-core-ui">
			<div class="media-frame-title">
				<h1>{{labels.fields_add_new}}</h1>
			</div>
			<!-- Field types navigation -->
			<div class="media-frame-router">
				<div class="media-router">
					<a
						v-for="(type, type_id) in types"
						:key="type_id"
						:class="getActiveTypeTabClasses( type_id )"
						@click="activateTypeTab( type_id )"
						v-if="!isDefault(type_id)"
						>
						{{type.group_name}}
					</a>
				</div>
			</div>
			<div class="media-frame-content">
				<form action="post">
					<label for="field-name">
						{{labels.field_new_name}}
					</label>
					<input type="text" name="field-name" id="field-name" :disabled="loading" v-model="newFieldName" :placeholder="labels.field_new_placeholder">
				</form>
				<!-- loop available fields within the selected tab -->
				<div
					v-for="(type, type_id) in types"
					:key="type_id"
					v-if="selectedTypeTab == type_id && !isDefault(type_id)"
					class="types-wrapper">
						<ul class="attachments">
							<field-type-box
								v-for="(field, index) in type.fields" :key="index"
								:name="field.name"
								:icon="field.icon"
								:type="field.type"
								:locked="field.locked"
								:min_version="field.min_addon_version"
								:enabled="isTypeSelected(field.type)"
								@click="selectFieldType(field.type)"
							></field-type-box>
						</ul>
				</div>
				<!-- end fields loop -->
			</div>
			<div class="media-frame-toolbar">
				<div class="media-toolbar">
					<div class="media-toolbar-primary search-form">
						<div class="spinner is-active" v-if="loading"></div>
						<button type="button" class="button media-button button-primary button-large media-button-insert" v-text="labels.fields_create" :disabled="loading || ! canSubmit()" @click="createField()"></button>
					</div>
				</div>
			</div>
		</div>
	</div>
</template>
<style scoped>
body.users_page_wpum-emails .wp-heading-inline img {
  width: 26px;
  float: left;
  padding-right: 15px;
  margin-top: -6px;
}
body.users_page_wpum-emails #wpum-add-field-group span.dashicons, body.users_page_wpum-emails #wpum-add-form span.dashicons, body.users_page_wpum-emails #wpum-add-role span.dashicons{
  width: 16px;
  height: 16px;
  font-size: 16px;
  vertical-align: inherit;
  position: relative;
  top: 3px;
  margin-right: 2px;
  color: #0071a1;
}
body.users_page_wpum-emails .wpum-icon-button.circular {
  padding: 4px !important;
  border-radius: 9999px !important;
}
body.users_page_wpum-emails .wpum-icon-button span.dashicons  {
  width: 16px;
  height: 16px;
  font-size: 16px;
  vertical-align: inherit;
  position: relative;
  top: 3px;
}
body.users_page_wpum-emails .order-column  {
  width: 30px;
  border-right: 1px solid #e1e1e1;
  text-align: center !important;
}
body.users_page_wpum-emails .order-anchor  {
  border-right: 1px solid #e1e1e1;
  text-align: center !important;
}
body.users_page_wpum-emails .small-column {
  width: 100px;
}
body.users_page_wpum-emails .wpum-registration-forms-list .delete-btn, body.users_page_wpum-emails .wpum-roles-list .delete-btn {
  margin-top: 5px;
}
body.users_page_wpum-emails .wpum-registration-forms-list .row-actions > span:not(:first-child), body.users_page_wpum-emails .wpum-roles-list .row-actions > span:not(:first-child) {
  padding-left: 3px;
  margin-left: 3px;
  border-left: 2px solid currentColor;
}
body.users_page_wpum-emails .wpum-fields-groups-table tbody tr:hover, body.users_page_wpum-emails .wpum-registration-forms-table tbody tr:hover, body.users_page_wpum-emails .wpum-roles-table tbody tr:hover {
  background: #f5f5f5;
}
body.users_page_wpum-emails .wpum-fields-groups-table .button, body.users_page_wpum-emails .wpum-registration-forms-table .button, body.users_page_wpum-emails .wpum-roles-table .button {
  margin: 3px 5px 3px 0;
  max-width: 100%;
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
}
body.users_page_wpum-emails .wpum-fields-groups-table .button:last-child, body.users_page_wpum-emails .wpum-registration-forms-table .button:last-child, body.users_page_wpum-emails .wpum-roles-table .button:last-child {
  margin-right: 0;
}
body.users_page_wpum-emails .wpum-fields-groups-table .button span.dashicons, body.users_page_wpum-emails .wpum-registration-forms-table .button span.dashicons, body.users_page_wpum-emails .wpum-roles-table .button span.dashicons {
  position: relative;
  top: 3px;
  margin-right: 3px;
}
body.users_page_wpum-emails .wpum-fields-groups-table .button.delete-btn:hover span.dashicons, body.users_page_wpum-emails .wpum-registration-forms-table .button.delete-btn:hover span.dashicons, body.users_page_wpum-emails .wpum-roles-table .button.delete-btn:hover span.dashicons {
  color: red;
}
body.users_page_wpum-emails .wpum-fields-groups-table .dashicons-yes, body.users_page_wpum-emails .wpum-registration-forms-table .dashicons-yes, body.users_page_wpum-emails .wpum-roles-table .dashicons-yes {
  color: green;
}
body.users_page_wpum-emails .wpum-fields-groups-table .align-middle, body.users_page_wpum-emails .wpum-registration-forms-table .align-middle, body.users_page_wpum-emails .wpum-roles-table .align-middle {
  vertical-align: middle;
}
body.users_page_wpum-emails .wpum-fields-groups-table td, body.users_page_wpum-emails .wpum-registration-forms-table td, body.users_page_wpum-emails .wpum-roles-table td {
  vertical-align: middle;
  padding: 12px 10px;
}
body.users_page_wpum-emails .wpum-fields-groups-table .spinner, body.users_page_wpum-emails .wpum-registration-forms-table .spinner, body.users_page_wpum-emails .wpum-roles-table .spinner {
  margin: 0;
  float: none !important;
}
body.users_page_wpum-emails #wpum-fields-editor-wrapper .vue-wp-notice {
  margin-right: 0;
  margin-bottom: 20px;
}
body.users_page_wpum-emails .sortable-ghost {
  background: #fffecc;
}
body.users_page_wpum-emails .sortable-chosen {
  background: #fffecc;
}
body.users_page_wpum-emails .spinner {
  float: none;
  margin-top: -8px;
}
body.users_page_wpum-emails .dashicons-menu:hover {
  cursor: move;
  color: #0073aa;
}
body.users_page_wpum-emails .v--modal-overlay {
  background: rgba(0, 0, 0, 0.7);
  z-index: 99999;
}
body.users_page_wpum-emails .v--modal {
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.7);
  background: #fcfcfc;
  border-radius: 0;
}
body.users_page_wpum-emails .media-modal-content {
  min-height: initial;
  background: #efefef;
}
body.users_page_wpum-emails .media-frame-title,
body.users_page_wpum-emails .media-frame-content,
body.users_page_wpum-emails .media-frame-toolbar {
  left: 0;
}
body.users_page_wpum-emails .media-frame-title .dashicons {
  display: inline-block;
  margin-top: 16px;
  margin-right: 10px;
}
body.users_page_wpum-emails .media-frame-title .dashicons.dashicons-warning {
  color: green;
}
body.users_page_wpum-emails .media-frame-content {
  top: 50px;
  padding: 10px 16px;
  font-size: 13px;
  line-height: 1.6em;
}
body.users_page_wpum-emails .wpum-dialog .spinner {
  float: none;
  margin-top: 20px !important;
}
body.users_page_wpum-emails .dialog-form {
  padding-top: 10px;
}
body.users_page_wpum-emails .dialog-form label {
  display: inline-block;
  font-weight: bold;
  color: #000;
  margin-bottom: 5px;
}
body.users_page_wpum-emails .dialog-form input,
body.users_page_wpum-emails .dialog-form textarea {
  display: block;
  width: 100%;
  margin-bottom: 15px;
  font-size: 13px !important;
}
body.users_page_wpum-emails .dialog-form input:last-child,
body.users_page_wpum-emails .dialog-form textarea:last-child {
  margin-bottom: 0;
}
body.users_page_wpum-emails #wpum-fields-editor-list .vue-wp-notice {
  margin-right: 0 !important;
  margin-top: 20px;
}

#create-field-dialog .media-frame-router {
  left: 10px;
}
#create-field-dialog .media-frame-content {
  top: 84px;
  padding: 0;
}
#create-field-dialog .media-router a:hover {
  cursor: pointer;
}
#create-field-dialog form {
  padding: 20px 15px;
  border-bottom: 1px solid #ddd;
}
#create-field-dialog form label {
  display: block;
  font-weight: 500;
  margin-bottom: 0.5em;
  font-size: 18px;
}
#create-field-dialog form input {
  padding: 3px 8px;
  font-size: 1.7em;
  line-height: 100%;
  height: 1.7em;
  width: 100%;
  outline: 0;
  margin: 0 0 3px;
  background-color: #fff;
}
#create-field-dialog ul.attachments {
  padding: 20px 10px;
}
#create-field-dialog ul.attachments .attachment {
  width: 16.6%;
  padding: 10px;
}
#create-field-dialog ul.attachments .dashicons {
  font-size: 48px;
  height: 48px;
  width: 48px;
  margin-top: -20px;
}
#create-field-dialog ul.attachments .check:hover .media-modal-icon {
  background-position: -21px 0;
}
#create-field-dialog ul.attachments .locked-type {
  position: absolute;
  top: 0;
  right: 0;
}
#create-field-dialog ul.attachments .locked-type span {
  margin-top: 20px;
  height: 20px;
  width: 20px;
  font-size: 30px;
  margin-right: 25px;
  color: #016afe;
}
#create-field-dialog button:disabled {
  cursor: not-allowed;
}

.table-fixed {
  display: table;
  height: 100%;
  table-layout: fixed;
  width: 100%;
}
.table-fixed .table-cell {
  display: table-cell;
  height: 100%;
  text-align: center;
  vertical-align: middle;
  width: 100%;
}

#edit-field-dialog.media-modal-content {
  background: #f3f3f3;
}
#edit-field-dialog .media-frame-title,
#edit-field-dialog .media-frame-content {
  left: 200px;
  padding: 0;
}
#edit-field-dialog .media-frame-title .spinner,
#edit-field-dialog .media-frame-content .spinner {
  margin-left: 20px;
}
#edit-field-dialog .media-frame-title .vue-wp-notice,
#edit-field-dialog .media-frame-content .vue-wp-notice {
  margin-top: 0 !important;
}
#edit-field-dialog .vue-form-generator .form-group {
  padding: 20px 20px;
  border-bottom: 1px solid #f3f3f3;
}
#edit-field-dialog .vue-form-generator .form-group label {
  display: block;
  font-weight: 600;
  margin-bottom: 0.4em;
}
#edit-field-dialog .vue-form-generator .form-group input[type=text],
#edit-field-dialog .vue-form-generator .form-group textarea {
  box-sizing: border-box;
  width: 100%;
  font-size: 13px;
}
#edit-field-dialog .vue-form-generator .form-group select {
  width: 100%;
  height: 30px;
  font-size: 13px;
}
#edit-field-dialog .vue-form-generator .form-group select option:first-child {
  display: none;
}
#edit-field-dialog .vue-form-generator .form-group.field-checkbox .field-wrap {
  float: left;
}
#edit-field-dialog .vue-form-generator .form-group .hint {
  font-size: 12px;
  color: #717171;
}
#edit-field-dialog .vue-form-generator .form-group.error {
  margin: 0;
  border: none;
  border-bottom: 1px solid #f3f3f3;
  box-shadow: none;
}
#edit-field-dialog .vue-form-generator .form-group .errors {
  background: #fbeaea;
  border-left: 4px solid #dc3232;
  margin: 5px 0px 2px 0;
  padding: 1px 12px;
}
#edit-field-dialog .vue-form-generator .form-group .errors span {
  display: block;
  margin: 0.5em 0;
  padding: 2px;
  font-weight: bold;
}
</style>
<script>
import axios from 'axios'
import qs from 'qs'
//import FieldTypeBox from '../settings/field-type-box'

export default {
	name: 'dialog-create-field',
	components: {
		//FieldTypeBox
	},
	props: {
		group_id:    '',
		addNewField: '',
		parent: {
			type: Number,
			default: 0
		}
	},
	data() {
		return {
			loading: false,
			labels: wpumEmailsEditor.labels,
			groupName: '',
			groupDescription: '',
			htmlForEditor: ""
		}
	},
	methods: {
		/**
		 * Hide the default group from the UI.
		 */
		isDefault( type_id ) {
			return type_id == 'default' ? true : false
		},
		/**
		 * Update the selected field type tab on click.
		 */
		activateTypeTab( tab_id ) {
			this.selectedTypeTab = tab_id
		},
		/**
		 * Setup the css classes for the fields types navigation.
		 * Add an active status if the tab is activated.
		 */
		getActiveTypeTabClasses( tab_id ) {
			return [
				'media-menu-item',
				tab_id == this.selectedTypeTab ? 'active': false
			]
		},
		/**
		 * Verify if the field type is currently selected
		 * so we can toggle the appropriate UI status.
		 */
		isTypeSelected( type ) {
			return this.newFieldType == type ? true : false
		},
		/**
		 * Save the selected field type.
		 */
		selectFieldType( type ) {
			this.newFieldType = type
		},
		/**
		 * Verify that the name and field type are selected
		 * before enabling the create button.
		 */
		canSubmit() {
			if( this.newFieldName && this.newFieldName.trim().length && this.newFieldType && this.newFieldType.trim().length ) {
				return true
			} else {
				return false
			}
		},
		/**
		 * Create the new field into the database.
		 */
		createField() {
			this.loading = true

			// Make a call via ajax.
			axios.post( wpumEmailsEditor.ajax,
				qs.stringify({
					nonce: wpumEmailsEditor.create_field_nonce,
					field_name: this.newFieldName,
					field_type: this.newFieldType,
					group_id: this.group_id,
					parent: this.parent
				}),
				{
					params: {
						action: 'wpum_create_field'
					},
				}
			)
			.then( response => {
				this.loading = false
				this.addNewField( 'success', response.data.data )
				this.$emit('close')
			})
			.catch( error => {
				this.loading = false
				this.addNewField( 'error', error.response.data )
				this.$emit('close')
			})

		}
	}
}
</script>
