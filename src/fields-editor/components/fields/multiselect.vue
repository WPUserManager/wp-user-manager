<template>
	<Multiselect
		:multiple="isMultiple"
		:options="schema.options || []"
		v-model="selected"
		track-by="value"
		label="label"
		@input="triggerChange"
	/>
</template>

<style lang="scss">
@import "vue-multiselect/dist/vue-multiselect.min.css";

.field-multiselect {
	.multiselect {
		font-size: inherit;

		.multiselect__element {
			margin-bottom: 0;
		}

		.multiselect__tags {
			color: #555;
			border-color: #8c8f94;
			border-radius: 3px;
			&:hover {
				border-color: #999;
			}
		}
		.multiselect__input {
			font-size: inherit;
			border:none !important;
			background: transparent;
			box-shadow: none !important;
			padding: 0;
			outline: none !important;
		}
		.multiselect__content-wrapper {
			box-shadow: 0 3px 5px rgba(0,0,0,.2);
			border: 1px solid #ddd;
			border-radius: 0;
			margin-top: 5px;
		}
		.multiselect__option--highlight {
			background: #0073aa;
			color: #fff;
			&:after {
				background: #0073aa;
				color: #fff;
			}
		}
		.multiselect__tags-wrap {
			.multiselect__tag {
				background: #0085ba;
				border-radius: 3px;
				margin-bottom: 3px;
				padding: 5px 30px 5px 10px;
				i {
					border-radius: 0;
					&:hover {
						background: #dd3e3e;
					}
				}
			}
		}
		.multiselect__spinner {
			background: #f7f7f7;
			&:before,
			&:after {
				border-color: #0085ba transparent transparent
			}
		}
		.multiselect__single {
			background: transparent;
			font-size: inherit;
		}
	}
}
</style>

<script>
import Multiselect from 'vue-multiselect'

export default {
	props: {
		schema: {},
		model: {},
		formOptions: {},
		disabled: false
	},
	components: {
		Multiselect
	},
	data() {
		return {
			selected: null,
			isMultiple: this.schema.multiple
		}
	},
	methods: {
		clearValidationErrors(){

		},
		validate(){

		},
		triggerChange (value) {

			let savedValue = value

			if ( this.isMultiple === true ) {
				savedValue = []
				value.forEach(function(element) {
					savedValue.push(element.value)
				})
			} else if( savedValue === null ) {
				savedValue = null
			} else {
				savedValue = savedValue.value
			}

			if( this.schema.model ){
				this.$set( this.model, this.schema.model, savedValue )
			}

            this.$emit('input', savedValue )
        }
	},
	created() {
		let currentValue = this.model[this.schema.model];

		if( currentValue instanceof Array ){
			currentValue = currentValue.map( role => this.schema.options.find( option => option.value == role ) ).filter( role => !!role )
		}else if( typeof currentValue === 'string' ){
			currentValue = this.schema.options.find( option => option.value == currentValue )
		}

		this.selected = currentValue;
	}
}
</script>
