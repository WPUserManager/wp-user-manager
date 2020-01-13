<template>
	<textarea
		:id="fieldId"
		:class="schema.fieldClasses"
		rows="10"
		cols="50"
		:disabled="disabled"
		:maxlength="schema.max"
		:minlength="schema.min"
		:placeholder="schema.placeholder"
		:readonly="schema.readonly"
		:required="schema.required"
		:rows="schema.rows || 2"
		:name="schema.inputName"
		v-attributes="'input'"
	>{{ value }}</textarea>
</template>

<script>
	import { abstractField } from "vue-form-generator";

	var lodash = _.noConflict()

	export default {
		mixins: [ abstractField ],
		computed: {
			fieldId() {
				return this.getFieldID( this.schema );
			}
		},
		mounted() {
			var self = this;
			wp.editor.initialize( this.fieldId, {
				tinymce: {
					setup: function (ed) {
						ed.on('change', function (ed, l) {
							self.handleChange();
						})
						ed.on('keyup', function (ed, l) {
							self.handleInput();
						})
					},
					wpautop: true,
					plugins: 'charmap colorpicker compat3x directionality fullscreen hr image lists media paste tabfocus textcolor wordpress wpautoresize wpdialogs wpeditimage wpemoji wpgallery wplink wptextpattern wpview',
					toolbar1: 'formatselect bold italic | bullist numlist | blockquote | alignleft aligncenter alignright | link unlink | spellchecker'
				}
			} );
		},
		beforeDestroy() {
			wp.editor.remove(this.fieldId);
		},

		methods: {
			handleInput() {
				const content = wp.editor.getContent( this.fieldId );
				this.value = content;
				this.$emit('input', content)
			},

			handleChange() {
				this.$emit('change', this.value)
			},
		}
	}
</script>