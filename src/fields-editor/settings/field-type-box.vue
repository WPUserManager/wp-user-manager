<template>
	<li :class="selected" @click="enable()">
		<div class="attachment-preview js--select-attachment" :style="divStyle" :title="divTitle">
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
		<div class="locked-type" v-if="locked">
			<span class="dashicons-lock dashicons"></span>
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
		enabled: false,
		locked: false,
		min_version: false,
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
		},
		divStyle() {
			return this.locked ? 'opacity: 40%' : ''
		},
		divTitle() {
			return this.locked ? 'Requires the Custom Fields addon version ' + this.min_version + ' or higher' : ''
		}
	},
	methods: {
		enable(event) {
			if ( ! this.locked ) {
				this.$emit( 'click', event );
			}
		},
	}
}
</script>
