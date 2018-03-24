<template>
	<div class="media-modal-content wpum-dialog" id="create-group-dialog">
		<button type="button" class="media-modal-close" @click="$emit('close')"><span class="media-modal-icon"><span class="screen-reader-text">Close panel</span></span></button>
		<div class="media-frame mode-select wp-core-ui">
			<div class="media-frame-title">
				<h1>{{labels.table_add_group}}</h1>
			</div>
			<div class="media-frame-content">
				<form action="#" method="post" class="dialog-form">
					<label for="group-name" :data-balloon="labels.tooltip_group_name" data-balloon-pos="right"><span>{{labels.table_name}}</span> <span class="dashicons dashicons-editor-help"></span></label>
					<input type="text" name="group-name" id="group-name" value="" v-model="groupName">
					<label for="group-description" :data-balloon="labels.tooltip_group_description" data-balloon-pos="right"><span>{{labels.table_desc}}</span> <span class="dashicons dashicons-editor-help"></span></label>
					<textarea name="group-description" id="group-description" cols="30" rows="4" v-model="groupDescription"></textarea>
				</form>
			</div>
			<div class="media-frame-toolbar">
				<div class="media-toolbar">
					<div class="media-toolbar-primary search-form">
						<div class="spinner is-active" v-if="loading"></div>
						<button type="button" class="button media-button button-primary button-large media-button-insert" v-text="labels.create_group" :disabled="loading" @click="createGroup()"></button>
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
	name: 'create-group-dialog',
	props: {
		addNewGroup: ''
	},
	data() {
		return {
			loading: false,
			labels: wpumFieldsEditor.labels,
			groupName: '',
			groupDescription: '',
		}
	},
	methods: {
		/**
		 * Create a new fields group via ajax.
		 * Only works when the custom fields addon is installed.
		 */
		createGroup() {

			this.loading = true

			// Make a call via ajax.
			axios.post( wpumFieldsEditor.ajax,
				qs.stringify({
					nonce: wpumFieldsEditor.nonce,
					group_name: this.groupName,
					group_description: this.groupDescription
				}),
				{
					params: {
						action: 'wpum_create_fields_group'
					},
				}
			)
			.then( response => {
				this.loading = false
				this.addNewGroup( 'success', response.data.data )
				this.$emit('close')
			})
			.catch( error => {
				this.loading = false
				this.addNewGroup( 'error', error.response.data )
				this.$emit('close')
			})

		}
	}
}
</script>
