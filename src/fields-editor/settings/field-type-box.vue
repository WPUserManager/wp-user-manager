<template>
	<li :class="selected" @click="enable()">
		<div class="attachment-preview js--select-attachment">
			<div class="thumbnail">
				<div class="table-fixed">
					<div class="table-cell">
						<span :class="classes"></span>
					</div>
				</div>
			</div>
			<div class="filename">
				<div>{{name}}</div>
			</div>
		</div>
		<button type="button" class="check" tabindex="-1">
			<span class="media-modal-icon"></span>
			<span class="screen-reader-text">Deselect</span>
		</button>
	</li>
</template>

<script>
export default {
	name: 'field-type-box',
	props: {
		name: '',
		icon: '',
		type: '',
		enabled: false
	},
	computed: {
		classes() {
			return [
				'dashicons',
				this.icon,
			]
		},
		selected() {
			return [
				'attachment save-ready',
				this.enabled ? 'details selected' : ''
			]
		}
	},
	methods: {
		enable(event) {
			this.$emit('click', event);
		},
	}
}
</script>
