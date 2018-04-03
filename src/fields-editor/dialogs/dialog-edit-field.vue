<template>
	<div class="media-modal-content wpum-dialog" id="edit-field-dialog">
		<button type="button" class="media-modal-close" @click="$emit('close')"><span class="media-modal-icon"><span class="screen-reader-text">Close panel</span></span></button>
		<div class="media-frame mode-select wp-core-ui">
			<div class="media-frame-menu">
				<div class="media-menu">
					<a class="media-menu-item">{{labels.field_edit_general}}</a>
					<a class="media-menu-item">{{labels.field_edit_privacy}}</a>
					<a class="media-menu-item">{{labels.field_edit_customization}}</a>
					<!-- for future use <div class="separator"></div> -->
				</div>
			</div>
			<div class="media-frame-title">
				<h1>{{labels.fields_page_title}} "{{field_name}}"</h1>
			</div>
			<div class="media-frame-content">

			</div>
			<div class="media-frame-toolbar">
				<div class="media-toolbar">
					<div class="media-toolbar-primary search-form">
						<div class="spinner is-active" v-if="loading"></div>
						<button type="button" class="button media-button button-primary button-large media-button-insert" :disabled="loading" @click="editField()">{{labels.save}}</button>
					</div>
				</div>
			</div>
		</div>
	</div>
</template>

<script>
export default {
	name: 'dialog-edit-field',
	props: {
		field_id: '',
		field_name: ''
	},
	data() {
		return {
			loading:   false,
			labels:    wpumFieldsEditor.labels,
			activeTab: '',
		}
	},
	methods: {
	}
}
</script>
