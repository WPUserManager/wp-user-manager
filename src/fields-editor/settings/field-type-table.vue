<template>
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
</template>
<script>

import VueFormGenerator from 'vue-form-generator'

export default {
	mixins: [ VueFormGenerator.abstractField ],
	data(){
		return {
			labels:         wpumFieldsEditor.labels,
			rows: 			[],
			columns: 		[]
		}
	},
	mounted(){

	}
}
</script>
