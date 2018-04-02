<template>
	<div class="media-modal-content wpum-dialog" id="create-field-dialog">
		<button type="button" class="media-modal-close" @click="$emit('close')"><span class="media-modal-icon"><span class="screen-reader-text">Close panel</span></span></button>
		<div class="media-frame mode-select wp-core-ui">
			<div class="media-frame-title">
				<h1>{{labels.fields_add_new}}</h1>
			</div>
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
				<div v-for="(type, type_id) in types" :key="type_id" v-if="selectedTypeTab == type_id">
					{{type_id}}
				</div>
			</div>
			<div class="media-frame-toolbar">
				<div class="media-toolbar">
					<div class="media-toolbar-primary search-form">
						<div class="spinner is-active" v-if="loading"></div>
						<button type="button" class="button media-button button-primary button-large media-button-insert" v-text="labels.fields_create" :disabled="loading" @click="createField()"></button>
					</div>
				</div>
			</div>
		</div>
	</div>
</template>

<script>
export default {
	name: 'dialog-create-field',
	data() {
		return {
			loading: false,
			labels: wpumFieldsEditor.labels,
			types: wpumFieldsEditor.fields_types,
			selectedTypeTab: 'default',
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
		}
	}
}
</script>
