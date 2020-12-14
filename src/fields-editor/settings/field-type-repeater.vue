<template>
	<div class="repeater-wrapper">
		<div v-if="state === 'add'">
			<dialog-create-field :group_id="$route.params.id.toString()" :addNewField="addedNewField" :parent="this.model.parent" />
		</div>
		<div v-else>
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
						<th scope="col">{{labels.table_actions}}</th>
					</tr>
				</thead>
				<tbody v-if="loading">
					<tr>
						<td :colspan="fields.length > 1 ? 7 : 6"><div class="spinner is-active"></div></td>
					</tr>
				</tbody>
				<draggable v-else v-model="fields" :element="'tbody'" :options="{handle:'.order-anchor', animation:150}" @end="onSortingEnd">
					<tr v-for="field in fields" :key="field.id">
						<td v-if="fields.length > 1" class="order-anchor align-middle"><span class="dashicons dashicons-menu"></span></td>
						<td>{{field.name}}</td>
						<td>{{field.type_nicename}}</td>
						<td><span class="dashicons dashicons-yes" v-if="field.required === true"></span></td>
						<td>
							<span class="dashicons dashicons-yes" v-if="field.visibility == 'public'"></span>
							<span class="dashicons dashicons-hidden" v-else></span>
						</td>
						<td>
							<span class="dashicons dashicons-yes" v-if="field.editable == 'public'"></span>
							<span class="dashicons dashicons-lock" v-else></span>
						</td>
						<td class="align-middle">
							<button class="button" @click="openEditFieldDialog( field.id, field.name, field.type, field.default_id )"><span class="dashicons dashicons-edit"></span> {{labels.fields_edit}}</button>
							<button class="button delete-btn" @click="openDeleteFieldDialog( field.id, field.name )"><span class="dashicons dashicons-trash"></span> {{labels.fields_delete}}</button>
						</td>
					</tr>
				</draggable>
			</table>
		</div>
	</div>
</template>
<script>

import VueFormGenerator from 'vue-form-generator'
import CreateField from './../dialogs/dialog-create-field'
import draggable from 'vuedraggable'
import axios from 'axios'
import qs from 'qs'
import EditFieldDialog from './../dialogs/dialog-edit-field'
import DeleteFieldDialog from './../dialogs/dialog-delete-field'

export default {
	mixins: [ VueFormGenerator.abstractField ],
	components: {
		'dialog-create-field': CreateField,
		'draggable': draggable
	},
	data(){
		return {
			labels:         wpumFieldsEditor.labels,
			clonedLabels:   Object.assign( {}, wpumFieldsEditor.labels ),
			fields: 		[],
			state:			'list',
			repeater:		null,
			editField:		{},
			loading:		true
		}
	},
	methods: {
		getFields(){
			this.$parent.loading = true

			axios.get( wpumFieldsEditor.ajax, {
				params: {
					group_id: this.model.group,
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
				this.loading = false;
			})
			.catch( error => {
				this.$parent.loading = false
				console.error(error);
				this.loading = false;
			})
		},
		addedNewField( status, data ){

			this.state = 'list';

			if( status !== 'error' ){
				this.getFields();
				this.openEditFieldDialog( data.field_id, data.field_name, data.field_type, data.default_id )
			}
		},
		openEditFieldDialog( id, name, type, primary_id ) {

			this.$modal.show( EditFieldDialog, {
				field_id: id,
				field_name: name,
				field_type: type,
				primary_id: primary_id,
				updateStatus:(status) => {
					this.getFields();
				}
			},{ height: '80%', width: type === 'repeater' ? '80%' : '60%', clickToClose: false })
		},
		openDeleteFieldDialog( id, name ) {
			this.$modal.show( DeleteFieldDialog, {
				field_id: id,
				field_name: name,
				updateStatus:(status, id_or_message) => {
					this.getFields();
				}
			},{ height: '230px' })
		},
		onSortingEnd(){

			this.$parent.$parent.$parent.loading = true

			axios.post( wpumFieldsEditor.ajax,
				qs.stringify({
					nonce: wpumFieldsEditor.nonce,
					fields: this.fields
				}),
				{
					params: {
						action: 'wpum_update_fields_order'
					},
				}
			)
			.then( response => {
				this.$parent.$parent.$parent.loading = false
			})
			.catch( error => {
				this.$parent.$parent.$parent.loading = false
			})
		}
	},
	mounted(){
		this.labels.fields_add_new = this.labels.repeater_fields_add_new;
		this.labels.fields_create = this.labels.repeater_fields_create;
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
		this.labels.fields_add_new = this.clonedLabels.fields_add_new;
		this.labels.fields_create  = this.clonedLabels.fields_create;
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
