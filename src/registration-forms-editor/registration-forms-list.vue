<template>
	<div class="wpum-registration-forms-list">
		<h1 class="wp-heading-inline">
			<img :src="pluginURL + 'assets/images/logo.svg'" alt="WP User Manager">
			{{labels.page_title}}
		</h1>
	</div>
</template>

<script>
export default {
	name: 'registration-forms-list',
	data() {
		return {
			labels: wpumRegistrationFormsEditor.labels,
			pluginURL: wpumRegistrationFormsEditor.pluginURL
		}
	}
}
</script>
