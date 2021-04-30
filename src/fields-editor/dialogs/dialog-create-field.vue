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

<script>
import axios from 'axios'
import qs from 'qs'
import FieldTypeBox from '../settings/field-type-box'

export default {
	name: 'dialog-create-field',
	components: {
		FieldTypeBox
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
			loading:         false,
			labels:          wpumFieldsEditor.labels,
			types:           wpumFieldsEditor.fields_types,
			selectedTypeTab: 'standard',
			newFieldName:    '',
			newFieldType:    ''
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
			axios.post( wpumFieldsEditor.ajax,
				qs.stringify({
					nonce: wpumFieldsEditor.create_field_nonce,
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
