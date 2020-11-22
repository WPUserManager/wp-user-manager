<template>
	<div class="repeater-wrapper">
		<div v-if="state === 'add'">
			<dialog-create-field :group_id="$route.params.id.toString()" :addNewField="addedNewField" :parent="this.model.parent" />
		</div>
		<div v-if="state === 'list'">
			<a class="page-title-action wpum-icon-button" @click="state = 'add'">
				<span class="dashicons dashicons-plus-alt"></span> {{labels.fields_add_new}}
			</a>
			<table class="wp-list-table widefat fixed striped wpum-fields-groups-table">
				<thead>
					<tr>
						<th scope="col" class="order-column" :data-balloon="labels.table_drag_tooltip" data-balloon-pos="right" v-if="fields.length > 1"><span class="dashicons dashicons-menu"></span></th>
						<th scope="col" class="column-primary">{{labels.fields_name}}</th>
						<th scope="col" class="small-column">{{labels.fields_type}}</th>
						<th scope="col" class="small-column" :data-balloon="labels.fields_required_tooltip" data-balloon-pos="up">{{labels.fields_required}}</th>
						<th scope="col" class="small-column" :data-balloon="labels.fields_visibility_tooltip" data-balloon-pos="up">{{labels.fields_visibility}}</th>
						<th scope="col" class="small-column" :data-balloon="labels.fields_editable_tooltip" data-balloon-pos="up">{{labels.fields_editable}}</th>
					</tr>
				</thead>
				<tbody>
					<tr v-for="field in fields" :key="field.id">
						<td v-if="fields.length > 1"><span class="dashicons dashicons-menu"></span></td>
						<td>{{field.name}}</td>
						<td>{{field.type_nicename}}</td>
						<td><span class="dashicons dashicons-yes" v-if="field.required === true"></span></td>
						<td><span class="dashicons dashicons-yes" v-if="field.visibility === 'public'"></span></td>
						<td><span class="dashicons dashicons-yes" v-if="field.editable === 'public'"></span></td>
					</tr>
				</tbody>
			</table>
		</div>
	</div>
</template>
<script>

import VueFormGenerator from 'vue-form-generator'
import CreateField from './../dialogs/dialog-create-field'
import axios from 'axios'

export default {
	mixins: [ VueFormGenerator.abstractField ],
	components: {
		'dialog-create-field': CreateField
	},
	data(){
		return {
			labels:         wpumFieldsEditor.labels,
			fields: 		[],
			state:			'list',
			repeater:		null
		}
	},
	methods: {
		getFields(){
			this.$parent.loading = true

			axios.get( wpumFieldsEditor.ajax, {
				params: {
					group_id: this.$route.params.id,
					nonce: wpumFieldsEditor.get_fields_nonce,
					action: 'wpum_get_fields_from_group',
					parent_id: this.model.parent
				}
			})
			.then( response => {
				this.$parent.loading = false
				if ( typeof response.data.data.fields !== 'undefined' && response.data.data.fields.length > 0 ) {
					this.fields = response.data.data.fields
				}
			})
			.catch( error => {
				this.$parent.loading = false
				console.error(error);
			})
		},
		addedNewField(){
			this.getFields();
			this.state = 'list';
		}
	},
	mounted(){

	},
	created(){
		this.repeater = wpumFieldsEditor.fields_types.advanced.fields.find((field) => field.type === 'repeater')
		if(this.repeater){
			wpumFieldsEditor.fields_types.advanced.fields = wpumFieldsEditor.fields_types.advanced.fields.filter((field) => field.type !== 'repeater')
		}

		this.getFields()
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
	.repeater-wrapper >>> .wpum-fields-groups-table{
		margin-top: 10px;
	}
</style>
