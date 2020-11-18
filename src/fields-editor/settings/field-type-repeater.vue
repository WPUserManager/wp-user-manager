<template>
	<div class="repeater-wrapper">
		<div v-if="state === 'add'">
			<dialog-create-field :group_id="$route.params.id.toString()" :addNewField="() => console.log('hello')" />
		</div>
		<div v-if="state === 'list'">
			<a class="page-title-action wpum-icon-button" @click="state = 'add'">
				<span class="dashicons dashicons-plus-alt"></span> {{labels.fields_add_new}}
			</a>
			<table class="wp-list-table widefat fixed striped wpum-fields-groups-table">
				<thead>
					<tr>
						<th scope="col" class="order-column" :data-balloon="labels.table_drag_tooltip" data-balloon-pos="right" v-if="rows.length > 1"><span class="dashicons dashicons-menu"></span></th>
						<th scope="col" class="column-primary">{{labels.fields_name}}</th>
						<th scope="col" class="small-column">{{labels.fields_type}}</th>
						<th scope="col" class="small-column" :data-balloon="labels.fields_required_tooltip" data-balloon-pos="up">{{labels.fields_required}}</th>
						<th scope="col" class="small-column" :data-balloon="labels.fields_visibility_tooltip" data-balloon-pos="up">{{labels.fields_visibility}}</th>
						<th scope="col" class="small-column" :data-balloon="labels.fields_editable_tooltip" data-balloon-pos="up">{{labels.fields_editable}}</th>
					</tr>
				</thead>
				<tbody>

				</tbody>
			</table>
		</div>
	</div>
</template>
<script>

import VueFormGenerator from 'vue-form-generator'
import CreateField from './../dialogs/dialog-create-field'

export default {
	mixins: [ VueFormGenerator.abstractField ],
	components: {
		'dialog-create-field': CreateField
	},
	data(){
		return {
			labels:         wpumFieldsEditor.labels,
			rows: 			[],
			columns: 		[],
			state:			'list',
			repeater:		null
		}
	},
	mounted(){

	},
	created(){
		this.repeater = wpumFieldsEditor.fields_types.advanced.fields.find((field) => field.type === 'repeater')
		if(this.repeater){
			wpumFieldsEditor.fields_types.advanced.fields = wpumFieldsEditor.fields_types.advanced.fields.filter((field) => field.type !== 'repeater')
		}
	},
	destroyed(){
		if(this.repeater){
			wpumFieldsEditor.fields_types.advanced.fields.push(this.repeater)
		}
	}
}
</script>

<style scoped>
	.repeater-wrapper >>> .media-frame-title,
	.repeater-wrapper >>> .media-frame-content {
		left: 0 !important;
	}
	.repeater-wrapper >>> #create-field-dialog .media-modal-close {
		display: none;
	}
</style>
