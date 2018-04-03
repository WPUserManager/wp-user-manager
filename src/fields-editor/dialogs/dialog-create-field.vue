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
						>
						{{type.group_name}}
					</a>
				</div>
			</div>
			<div class="media-frame-content">
				<form action="post">
					<label for="field-name">
						Field name
					</label>
					<input type="text" name="field-name" id="field-name" v-model="newFieldName" placeholder="Enter a name for this field">
				</form>
				<!-- loop available fields within the selected tab -->
				<div v-for="(type, type_id) in types" :key="type_id" v-if="selectedTypeTab == type_id" class="types-wrapper">
					<ul class="attachments">
						<field-type-box
							v-for="(field, index) in type.fields" :key="index"
							:name="field.name"
							:icon="field.icon"
							:type="field.type"
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
import FieldTypeBox from '../settings/field-type-box'
export default {
	name: 'dialog-create-field',
	components: {
		FieldTypeBox
	},
	data() {
		return {
			loading: false,
			labels: wpumFieldsEditor.labels,
			types: wpumFieldsEditor.fields_types,
			selectedTypeTab: 'standard',
			newFieldName: '',
		}
	},
	methods: {
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
		 * Verify that the name and field type are selected
		 * before enabling the create button.
		 */
		canSubmit() {
			if( this.newFieldName && this.newFieldName.trim().length ) {
				return true
			} else {
				return false
			}
		}
	}
}
</script>
