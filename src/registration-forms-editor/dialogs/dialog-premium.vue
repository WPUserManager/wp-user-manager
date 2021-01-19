<template>
	<div class="media-modal-content wpum-dialog" id="premium-dialog">
		<button type="button" class="media-modal-close" @click="$emit('close')"><span class="media-modal-icon"><span class="screen-reader-text">Close panel</span></span></button>
		<div class="media-frame mode-select wp-core-ui">
			<div class="media-frame-title">
				<h1><span class="dashicons dashicons-warning"></span> Premium addon required</h1>
			</div>
			<div class="media-frame-content">
				<p v-html="labels['premium_addon' + type]"></p>
			</div>
			<div class="media-frame-toolbar">
				<div class="media-toolbar">
					<div class="media-toolbar-primary search-form">
						<a :href="wpumRegistrationFormsEditor.addon_url" target="_blank" class="button media-button button-primary button-large media-button-insert">{{labels.purchase}}</a>
					</div>
				</div>
			</div>
		</div>
	</div>
</template>

<script>
export default {
	name: 'premium-dialog',
	props: {
		type: {
			type: String,
			default: ''
		},
	},
	data() {
		return {
			labels: wpumRegistrationFormsEditor.labels,
			wpumRegistrationFormsEditor: wpumRegistrationFormsEditor
		}
	},
}
</script>
