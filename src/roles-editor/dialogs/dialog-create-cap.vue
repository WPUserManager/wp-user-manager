<template>
	<div class="media-modal-content wpum-dialog" id="create-cap-dialog">
		<button type="button" class="media-modal-close" @click="$emit('close')"><span class="media-modal-icon"><span class="screen-reader-text">Close panel</span></span></button>
		<div class="media-frame mode-select wp-core-ui">
			<div class="media-frame-title">
				<h1>{{labels.table_add_cap}}</h1>
			</div>
			<div class="media-frame-content">
				<form action="#" method="post" class="dialog-form">
					<label for="cap-name" :data-balloon="labels.tooltip_cap_name" data-balloon-pos="right"><span>{{labels.table_cap_name}}</span> <span class="dashicons dashicons-editor-help"></span></label>
					<input type="text" name="cap-name" id="cap-name" value="" v-model="capName">
				</form>
			</div>
			<div class="media-frame-toolbar">
				<div class="media-toolbar">
					<div class="media-toolbar-primary search-form">
						<div class="spinner is-active" v-if="loading"></div>
						<button type="button" class="button media-button button-primary button-large media-button-insert" v-text="labels.create_cap" :disabled="loading" @click="createCap()"></button>
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
	name: 'create-cap-dialog',
	props: {
		addNewCap: ''
	},
	data() {
		return {
			loading: false,
			labels: wpumRolesEditor.labels,
			capName: '',
		}
	},
	methods: {
		createCap() {
			this.addNewCap( 'success', this.capName )
			this.$emit( 'close' )
		}
	}
}
</script>
