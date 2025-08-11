jQuery( function( $ ) {
	function initFields() {
		$( '.wpum-multiselect:not(.wpum-clone-field)' ).each( function() {
			initSelect2( $( this ) );
		} );

		$( '.wpum-datepicker:not([readonly]):not(.wpum-clone-field)' ).flatpickr( {
			altFormat : wpumFrontend.dateFormat,
			altInput: true,
			dateFormat: "Y-m-d"
		} );
	}

	function initSelect2( field ) {
		var args = {
			theme: 'default'
		};
		var placeholder = field.attr( 'placeholder' );
		if ( placeholder ) {
			args[ 'placeholder' ] = placeholder;
		}
		field.select2( args );
	}

	var repeater = {

		form: $( 'form' ),
		repeaters: {},

		init: function() {
			var self = this;

			// Setup instances to first level repeaters
			$('form > fieldset > .add-repeater-row').each(function () {
				var fieldSet = $(this).closest( 'fieldset' );
				var fieldGroup = $(fieldSet).find( ' > .fieldset-wpum_field_group' ).not('.fieldset-wpum_field_group-clone' );
				
				if ( fieldGroup.length ) {
					self.setupInstances(fieldSet, null);

					var repeaterKey = self.getRepeaterKey(fieldSet);

					self.increaseInstance( repeaterKey );
					self.validateMaxRows( fieldSet );
				}
			} );

			$( '.fieldset-wpum_field_group-clone :input' ).not( ':button, :submit, :reset' ).each( function() {
				$( this ).addClass( 'wpum-clone-field' );
				$( this ).attr( 'data-clone', $( this ).attr( 'id' ) );
				$( this ).attr( 'data-name', $( this ).attr( 'name' ) );
				$( this ).attr( 'id', '' )
				$( this ).attr( 'name', '' )
				$( this ).removeAttr( 'required' )
			} );

			self.form.on( 'click', '.add-repeater-row', function() {
				var fieldSet = $(this).parent('fieldset');
				// Setup new instance based on the parent fieldset
				self.addNewInstance( fieldSet );
				self.form.wpumConditionalFields({});
			} );

			self.form.on( 'click', '.remove-repeater-row', function(e) {
				e.preventDefault();
				var fieldSet = $(this).closest('fieldset');
				var parentBase = $(fieldSet).attr('data-parent-base');
				var $row = $( this ).parent( '.fieldset-wpum_field_group' );
				$row.remove();

				self.setupInstances( fieldSet, parentBase );
				self.validateMaxRows( fieldSet );
			} );
		},

		increaseInstance: function( name ) {
			if ( !this.repeaters[ name ] ) {
				this.resetInstance( name );
			}

			this.repeaters[ name ]++;
		},

		addNewInstance: function( fieldSet ) {
			this.addNewRepeaterRow(fieldSet);
			
			var parentBase = $(fieldSet).attr('data-parent-base');
			this.setupInstances(fieldSet, parentBase);

			initFields();
		},

		resetInstance: function( name ) {
			this.repeaters[ name ] = 0;
		},

		addNewRepeaterRow: function (fieldSet) {
			// Get repeater from the immediate child
			var repeater = $(fieldSet).find(' > .fieldset-wpum_field_group-clone').last();
			if ( !repeater.length ) {
				return;
			}

			if ( !this.validateMaxRows( fieldSet ) ) {
				return;
			}

			var newRepeater = repeater.clone();
			newRepeater.removeClass('fieldset-wpum_field_group-clone');
			newRepeater.find( ':input' ).not( ':button, :submit, :reset' ).val( '' ).prop( 'checked', false ).prop( 'selected', false ).removeClass( 'wpum-clone-field' ).trigger( 'change' );
			newRepeater.insertBefore( repeater );
		},

		getRepeaterKey: function(fieldSet) {
			var parentBase = $(fieldSet).attr('data-parent-base');
			var repeaterKey = $(fieldSet).get(0).classList[0];
			repeaterKey = repeaterKey.replace('fieldset-', '');

			if (parentBase) {
				repeaterKey = parentBase + '[' + repeaterKey + ']';
			}

			return repeaterKey;
		},

		setupInstances: function (fieldSet, parentBase) {
			if (typeof parentBase === 'undefined' || parentBase === null) {
				parentBase = null;
			}

			var repeaterRow = $( fieldSet ).find( ' > .fieldset-wpum_field_group' ).not( '.fieldset-wpum_field_group-clone' );
			var self = this;

			if ( !repeaterRow.length ) {
				return;
			}

			// Apply parentBase to the fieldset to make it available later
			if (parentBase) {
				$(fieldSet).attr('data-parent-base', parentBase);
			}

			var repeaterKey = self.getRepeaterKey(fieldSet);
			self.resetInstance(repeaterKey);

			repeaterRow.each(function (i) {
				$(fieldSet).attr('data-index', i);
				$(this)
					.find('> fieldset > .field :input')
					// Exclude sub repeater fields
					.not($(this).find('> fieldset > .fieldset-wpum_field_group :input'))
					.each(function() {
						var fieldName = $(this).attr('data-name') || $(this).prop('name');
						fieldName = fieldName.replace(/\[(.*?)\]/, '[' + i + ']');

						// Prepend parentBase if available and does not already have a parentBase
						if (parentBase && !fieldName.includes(parentBase)) {
							// Wrap the base key (before the first "[") in brackets
							fieldName = fieldName.replace(/^([^[]+)/, '[$1]');
							fieldName = parentBase + fieldName;
						}

						$(this).attr(
							'name',
							fieldName
						);

						if (i > 0) {
							var clone_id = $(this).attr('data-clone') || $(this).prop('id');
							var id = clone_id + '_' + i;
							$(this).attr('id', id);
							$(this).closest('fieldset').find('label').attr('for', id);
						}
				});

				self.increaseInstance(repeaterKey);

				// Recurse into subrepeaters
				$(this)
					.find('> fieldset > .add-repeater-row')
					.each(function () {
						var fieldSet = $(this).closest('fieldset');
						var currentParentBase = repeaterKey + '[' + i + ']';

						self.setupInstances(fieldSet, currentParentBase);
						self.validateMaxRows(fieldSet);
				});
			});
		},
		validateMaxRows: function( fieldSet ) {
			var repeater = $(fieldSet).find( ' > .fieldset-wpum_field_group' ).not( '.fieldset-wpum_field_group-clone' );
			var addBtn = $(fieldSet).find( ' > .add-repeater-row' );
			var maxRows = addBtn.data('max-row');
			const repeaterKey = this.getRepeaterKey(fieldSet);

			if ( !maxRows || parseInt( maxRows ) < 1 ) {
				return true;
			}

			if ( repeater.length < parseInt( maxRows ) ) {
				return true;
			}

			addBtn.attr( 'disabled', true );

			return repeater.length < parseInt( maxRows );
		}
	}

	$.wpumConditionalFields = function( element, options ){

		var form = $(element),
			self = this;

		this.init = function(){

			this.validateFields();

			form.find(':input').on( 'input change', function(){
				self.validateFields( $(this).parents('fieldset') );
			});
		}

		this.validateField = function(element){
			var rules = element.data('condition');
			element.toggle( this.validateRules(rules) );
		}

		this.validateFields = function(){
			form.find('fieldset[data-condition]').each(function(){
				window.fieldsetIndex = $(this).data("index");
				var rules = $(this).data('condition');
				var validRule = self.validateRules(rules);
				$(this).toggle( validRule );

				if ( $( this ).find( 'select' ).hasClass( 'wpum-multiselect' ) ) {
					initSelect2( $( this ).find( 'select' ) );
				}
				if ( $(this).find('.field').hasClass('required-field') ) {
					$( this ).find( "input" ).not('.input-checkboxes').prop( "required", validRule );
					$( this ).find( "select" ).prop( "required", validRule );
					$( this ).find( "textarea" ).prop( "required", validRule );
				}
			});
		}

		this.validateRules = function(rules){
			return rules.some(function(andRules){
				return andRules.every(self.validateRule);
			})
		}

		this.validateRule = function(rule){
			return self.hasOwnProperty(self.ruleMethodName(rule.condition)) ? self[self.ruleMethodName(rule.condition)](rule) : false;
		}

		this.ruleMethodName = function(rule){
			return rule.replace(/([-_][a-z])/ig, function($1){
				return $1.toUpperCase()
					.replace('-', '')
					.replace('_', '');
			});
		}

		this.getValue = function(rule){
			var el = $('[name^="'+rule.field+'"]');
			if (el.length === 0) { // Check repeater fields
				var index = window.fieldsetIndex ? window.fieldsetIndex : 0;
				el = $('[name^="'+rule.parent+'['+index+']['+rule.field+']"]');
			}

			if( el.length ){
				if( el.is('[type="radio"]') ){
					return el.filter(':checked').val();
				}else if( el.is('[type="checkbox"]') ){
					return el.filter(':checked').map(function(){
						return $(this).val();
					}).toArray();
				}else{
					return el.first().val();
				}
			}
		}

		this.hasValue = function(rule){
			var value = this.getValue(rule);
			return $.isArray(value) ? value.length : value && $.trim(value) !== '';
		}

		this.hasNoValue = function(rule){
			var value = this.getValue(rule);
			return $.isArray(value) ? !value.length : !value || value === '';
		}

		this.valueContains = function(rule){
			var value = this.getValue(rule);
			return $.isArray(value) ? value.includes(rule.value) : value && value.toLowerCase().indexOf(rule.value.toLowerCase())  > -1;
		}

		this.valueEquals = function(rule){
			var value = this.getValue(rule);
			return $.isArray(value) ? value.includes(rule.value) : value && value.toLowerCase() === rule.value.toLowerCase();
		}

		this.valueNotEquals = function(rule){
			var value = this.getValue(rule);
			return $.isArray(value) ? !value.includes(rule.value) : value && value.toLowerCase() !== rule.value.toLowerCase();
		}

		this.valueGreater = function(rule){
			var value = this.getValue(rule);
			return parseFloat(value) > parseFloat(rule.value);
		}

		this.valueLess = function(rule){
			var value = this.getValue(rule);
			return parseFloat(value) < parseFloat(rule.value);
		}

		this.init();
	}

	$.fn.wpumConditionalFields = function( options ) {
		new $.wpumConditionalFields(this, options);
	};


	$( document ).ready( function() {
		$( document.body ).on( 'click', '.wpum-remove-uploaded-file', function() {
			$( this ).closest( '.wpum-uploaded-file' ).remove();
			return false;
		} );


		$('.wpum-registration-form, .wpum-account-form, .wpum-custom-account-form').wpumConditionalFields({});

		repeater.init();
		initFields();
	} );

} );
