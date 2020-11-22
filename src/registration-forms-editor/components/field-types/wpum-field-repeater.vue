<template>
	<div class="widget-top">
		<div class="widget-title ui-sortable-handle">
			<h3><span :class="'dashicons ' + field.icon"></span> {{field.name}}</h3>
		</div>
		<div class="widget-content">
			<div class="widget" v-for="field in childFields" :key="field.id">
				<div class="widget-top">
					<h3><span :class="'dashicons ' + field.icon"></span> {{field.name}}</h3>
				</div>
			</div>
		</div>
	</div>
</template>

<script>
import axios from 'axios'

export default {
	name: 'wpum-field-repeater',
	props: [ 'field' ],
	data(){
		return {
			childFields: []
		}
	},
	created(){
		axios.get( wpumRegistrationFormsEditor.ajax, {
			params: {
				nonce: wpumRegistrationFormsEditor.getFormNonce,
				action: 'wpum_get_registration_form_field',
				parent_id: this.field.id
			}
		})
		.then( response => {
			this.$parent.loading = false
			if( response.data.data && response.data.data.fields ){
				this.childFields = response.data.data.fields
			}
		})
		.catch( error => {
			this.$parent.loading = false
			console.error(error);
		})
	}
}
</script>

<style scoped>
	>>> .widget-content {
		margin-top: 10px;
	}

	>>> .widget-content h3 {
		margin: 0;
		padding: 10px;
		font-size: 1em;
		line-height: 1.2em;
	}

	>>> .widget-content .widget {
		padding: 0 3em;
	}

	>>> .widget-content .widget-top:hover {
		border: 1px solid #ccd0d4 !important;
    	box-shadow: 0 1px 1px rgba(0,0,0,.04) !important;
	}
</style>
