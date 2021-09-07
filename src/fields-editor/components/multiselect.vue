<template>
	<span class="opk-multiselect-field-wrapper">
        <multiselect
			v-model="selected"
			:options="options"
			:multiple="multiple"
			:selectLabel="selectLabel"
			:placeholder="placeholder"
			:SelectedLabel="SelectedLabel"
			:deselectLabel="deselectLabel"
			track-by="value"
			label="label"
			@input="triggerChange"
			:hide-seleccted="true"
        >
        </multiselect>
	</span>
</template>
<style lang="scss">
@import "vue-multiselect/dist/vue-multiselect.min.css";

.opk-form table {
    .multiselect__tags {
        color: #555;
        border-color: #ccc;
        background: #f7f7f7;
        box-shadow: 0 1px 0 #ccc;
        border-radius: 3px;
        &:hover {
            border-color: #999;
        }
    }
    .multiselect__input {
        border:none;
        background: transparent;
        box-shadow: none;
        padding: 0;
    }
    .opk-multiselect-field-wrapper {
        display: block;
        width: 25em;
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
	}
}
</style>
<script>
import Multiselect from 'vue-multiselect'
import { abstractField } from "vue-form-generator";

   export default {
		mixins: [ abstractField ],
		components: {
        	Multiselect
		},
		props: ['schema', 'value'],
		data () {
			return {
				selected: this.model.options,
				multiple: true,
				selectLabel: this.getLabel( 'selectLabel' ),
				placeholder: this.getLabel( 'placeholder' ),
				SelectedLabel: this.getLabel( 'SelectedLabel' ),
				deselectLabel: this.getLabel( 'deselectLabel' ),
				options: this.schema.values
			}
		},
		mounted() {
			/**
			 * Preselect any stored value for this field.
			 */
			const retrievedValue = this.model.options
			const fieldOptions   = this.schema.values
			const currentValue   = []

			if( retrievedValue instanceof Array ) {
				retrievedValue.forEach(function(entry) {
					let result = fieldOptions.filter(function( obj ) {
						return obj.value == entry
					})
					currentValue.push({
						label: result[0].label,
						value: result[0].value
					})
				})
				this.selected = currentValue
			} else if( retrievedValue == '' ) {
				this.selected = null
			} else {
				let result = fieldOptions.filter(function( obj ) {
					return obj.value == retrievedValue
				})
				currentValue.push({
					label: result[0].label,
					value: result[0].value
				})
				this.selected = currentValue
			}

		},
		methods: {

			getLabel( which ) {
				if( this.schema.labels ) {
					return this.schema.labels[which]
				} else {
					return this.schema.all_labels.multiselect[which]
				}
			},

			/**
			 * Trigger v-model change.
			 */
			triggerChange (value) {

				let savedValue = value
				this.clearValidationErrors()
				this.errors = []

				if ( this.multiple === true ) {
					savedValue = []
					value.forEach(function(element) {
						savedValue.push(element.value)
					})
				} else if( savedValue === null ) {
					savedValue = null
				} else {
					savedValue = savedValue.value
				}
				this.model.options = savedValue;
			},
			validate( calledParent ) {
				if ( this.disabled ) return true;

				this.clearValidationErrors()

				if ( this.schema.required && ! this.selected ) {
					this.errors.push( this.schema.errorText || 'Input is required' );
				}

				return this.errors;
			}
		}
   };
</script>
