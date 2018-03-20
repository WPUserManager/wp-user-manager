<template>

	<section id="wpum-fields-editor-wrapper">
		<h1 v-text="sanitized(pageTitle)"></h1>

		<groups-selector></groups-selector>

	</section>

</template>

<script>
import Sanitize from 'sanitize-html'
import GroupsSelector from './groups-selector'

export default {
	name: 'editor-interface',
	components: {
		GroupsSelector
	},
	data() {
		return {
			pageTitle: wpumFieldsEditor.page_title
		}
	},
	methods: {
		/**
		 * Sanitize strings (needed because strings can be translated)
		 */
		sanitized( content ) {
			return Sanitize( content )
		},
	}
}
</script>
