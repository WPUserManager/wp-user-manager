<template>
	<section id="wpum-fields-editor-list">
		<h1 class="wp-heading-inline">{{labels.fields_page_title}} "{{group_name}}"</h1>
		<router-link to="/" class="page-title-action wpum-icon-button circular" :data-balloon="labels.fields_go_back" data-balloon-pos="down"><span class="dashicons dashicons-arrow-left-alt"></span></router-link>
		<a href="#" class="page-title-action wpum-icon-button"><span class="dashicons dashicons-plus-alt"></span> {{labels.fields_add_new}}</a>
	</section>
</template>

<script>
import balloon from 'balloon-css'
import findGroupIndex from 'lodash.findindex'

export default {
	name: 'fields-editor-interface',
	data() {
		return {
			addonInstalled: wpumFieldsEditor.is_addon_installed,
			labels:         wpumFieldsEditor.labels,
			group_id:       '',
			group_name:     '',
		}
	},
	/**
	 * Detect the selected group to edit and retrieve group id and group name.
	 */
	created() {
		const group_id      = this.$route.params.id.toString()
		const selectedGroup = findGroupIndex( wpumFieldsEditor.groups, function(o) { return o.id == group_id })
		this.group_id       = group_id
		this.group_name     = wpumFieldsEditor.groups[selectedGroup].name
	}
}
</script>

<style lang="scss">
.wpum-icon-button {
	&.circular {
		padding: 4px !important;
		border-radius: 9999px !important;
	}
	span.dashicons {
		width: 16px;
		height: 16px;
		font-size: 16px;
		vertical-align: inherit;
		position: relative;
		top: 3px;
	}
}
</style>
