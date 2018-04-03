<template>
	<li class="attachment save-ready">
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
	</li>
</template>

<script>
export default {
	name: 'field-type-box',
	props: {
		name: '',
		icon: '',
		type: ''
	},
	computed: {
		classes() {
			return [
				'dashicons',
				this.icon
			]
		}
	}
}
</script>
