<template>
	<div class="media-modal-content wpum-dialog" id="edit-dialog">
		<button type="button" class="media-modal-close" @click="$emit('close')"><span class="media-modal-icon"><span class="screen-reader-text">Close panel</span></span></button>
		<div class="media-frame mode-select wp-core-ui">
			<div class="media-frame-title">
				<h1>{{labels.table_edit_group}}</h1>
			</div>
			<div class="media-frame-content">
				<form action="#" method="post" class="dialog-form">
					<label for="group-name">Group name <span class="dashicons dashicons-editor-help"></span></label>
					<input type="text" name="group-name" id="group-name" value="">
					<label for="group-description">Group description <span class="dashicons dashicons-editor-help"></span></label>
					<textarea name="group-description" id="group-description" cols="30" rows="4"></textarea>
				</form>
			</div>
			<div class="media-frame-toolbar">
				<div class="media-toolbar">
					<div class="media-toolbar-primary search-form">
						<div class="spinner is-active" v-if="loading"></div>
						<button type="button" class="button media-button button-primary button-large media-button-insert" v-text="labels.save" :disabled="loading" @click="deleteGroup()"></button>
					</div>
				</div>
			</div>
		</div>
	</div>
</template>

<script>
import axios from 'axios'
import qs from 'qs'

export default {
	name: 'edit-group-dialog',
	props: {
		group_id: '',
	},
	data() {
		return {
			loading: false,
			labels: wpumFieldsEditor.labels
		}
	},
}
</script>
