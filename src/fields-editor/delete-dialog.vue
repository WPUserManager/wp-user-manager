<template>
	<div class="media-modal-content" id="delete-dialog">
		<button type="button" class="media-modal-close" @click="$emit('close')"><span class="media-modal-icon"><span class="screen-reader-text">Close panel</span></span></button>
		<div class="media-frame mode-select wp-core-ui">
			<div class="media-frame-title">
				<h1><span class="dashicons dashicons-warning"></span> {{labels.confirm_delete}}</h1>
			</div>
			<div class="media-frame-content">
				<p>{{labels.modal_group_delete}} <strong>{{group}}</strong>. {{labels.modal_delete}}</p>
			</div>
			<div class="media-frame-toolbar">
				<div class="media-toolbar">
					<div class="media-toolbar-primary search-form">
						<div class="spinner is-active" v-if="loading"></div>
						<button type="button" class="button media-button button-primary button-large media-button-insert" v-text="labels.table_delete_group" :disabled="loading" @click="deleteGroup()"></button>
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
	name: 'delete-dialog',
	props: {
		group: '',
		group_id: '',
		updateStatus: ''
	},
	data() {
		return {
			loading: false,
			labels: wpumFieldsEditor.labels
		}
	},
	methods: {
		/**
		 * Delete a fields group from the database.
		 * Only works when the custom fields addon is active. 
		 */
		deleteGroup() {
			this.loading = true

			axios.post( wpumFieldsEditor.ajax,
				qs.stringify({
					nonce: wpumFieldsEditor.nonce,
					group_id: this.group_id
				}),
				{
					params: {
						action: 'wpum_delete_field_group'
					},
				}
			)
			.then( response => {
				this.loading = false
				this.updateStatus('success')
				this.$emit('close')
			})
			.catch( error => {
				this.loading = false
				this.updateStatus('error', error.response.data)
				this.$emit('close')
			})
		}
	}
}
</script>

<style lang="scss">
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
		color: red;
	}
}

.media-frame-content {
	top: 50px;
	padding: 10px 16px;
	font-size: 13px;
	line-height: 1.6em;
}

#delete-dialog {
	.spinner {
		float: none;
		margin-top: 20px;
	}
}

</style>
