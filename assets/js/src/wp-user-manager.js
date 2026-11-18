jQuery( function( $ ) {
	function initFields() {
		$( '.wpum-multiselect:not(.wpum-clone-field)' ).each( function() {
			initSelect2( $( this ) );
		} );

		$( '.wpum-datepicker:not([readonly]):not(.wpum-clone-field)' ).flatpickr( {
			altFormat : wpumFrontend.dateFormat,
			altInput: true,
			dateFormat: "Y-m-d",
			// Flatpickr swaps itself for a native date input on touch devices unless
			// this is set, which ignores the site date format and leaves the field
			// inert on some mobile browsers. See issue #202.
			disableMobile: 'undefined' === typeof wpumFrontend.disableMobile ? true : !! wpumFrontend.disableMobile
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

			// Setup instances of the top level repeaters. Nested repeaters are
			// set up recursively by setupInstances().
			$( '.add-repeater-row' ).each( function() {
				var fieldSet = $( this ).parent( 'fieldset' );
				if ( !fieldSet.length || fieldSet.parents( '.fieldset-wpum_field_group' ).length ) {
					return;
				}

				var fieldGroup = fieldSet.find( '> .fieldset-wpum_field_group' ).not( '.fieldset-wpum_field_group-clone' );

				if ( fieldGroup.length ) {
					self.setupInstances( fieldSet, null );
					self.validateMaxRows( fieldSet );
				}
			} );

			$( '.fieldset-wpum_field_group-clone :input' ).not( ':button, :submit, :reset' ).each( function() {
				$( this ).addClass( 'wpum-clone-field' );
				if ( !$( this ).attr( 'data-clone' ) ) {
					$( this ).attr( 'data-clone', $( this ).attr( 'id' ) );
				}
				if ( !$( this ).attr( 'data-name' ) ) {
					$( this ).attr( 'data-name', $( this ).attr( 'name' ) );
				}
				$( this ).attr( 'id', '' )
				$( this ).attr( 'name', '' )
				$( this ).removeAttr( 'required' )
			} );

			self.form.on( 'click', '.add-repeater-row', function() {
				var fieldSet = $( this ).parent( 'fieldset' );
				self.addNewInstance( fieldSet );
				self.form.wpumConditionalFields({});
			} );

			self.form.on( 'click', '.remove-repeater-row', function(e) {
				e.preventDefault();
				var $row = $( this ).parent( '.fieldset-wpum_field_group' );
				var fieldSet = $row.parent( 'fieldset' );
				$row.remove();

				self.setupInstances( fieldSet, fieldSet.attr( 'data-parent-base' ) );
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
			this.addNewRepeaterRow( fieldSet );
			this.setupInstances( fieldSet, $( fieldSet ).attr( 'data-parent-base' ) );
			this.validateMaxRows( fieldSet );

			initFields();
		},

		resetInstance: function( name ) {
			this.repeaters[ name ] = 0;
		},

		addNewRepeaterRow: function( fieldSet ) {
			// Only the clone row that belongs to this repeater, not to a nested one.
			var repeater = $( fieldSet ).find( '> .fieldset-wpum_field_group-clone' ).last();
			if ( !repeater.length ) {
				return;
			}

			if ( !this.validateMaxRows( fieldSet ) ) {
				return;
			}

			var newRepeater = repeater.clone();
			newRepeater.removeClass( 'fieldset-wpum_field_group-clone' );
			newRepeater.find( ':input' ).not( ':button, :submit, :reset' ).val( '' ).prop( 'checked', false ).prop( 'selected', false ).removeClass( 'wpum-clone-field' ).trigger( 'change' );
			// Clone rows of nested repeaters inside the new row stay templates.
			newRepeater.find( '.fieldset-wpum_field_group-clone :input' ).not( ':button, :submit, :reset' ).addClass( 'wpum-clone-field' );
			// A nested repeater in the new row may have been disabled by the template.
			newRepeater.find( '.add-repeater-row' ).prop( 'disabled', false );
			newRepeater.insertBefore( repeater );
		},

		getRepeaterKey: function( fieldSet ) {
			var parentBase = $( fieldSet ).attr( 'data-parent-base' );
			var repeaterKey = $( fieldSet ).get( 0 ).classList[ 0 ].replace( 'fieldset-', '' );

			if ( parentBase ) {
				repeaterKey = parentBase + '[' + repeaterKey + ']';
			}

			return repeaterKey;
		},

		/**
		 * Rename the inputs of every row of a repeater, then recurse into any
		 * repeater nested in a row.
		 *
		 * Each input keeps its name relative to its own repeater in data-name
		 * (e.g. "inner[0][city]") and its original id in data-clone, so the full
		 * name (e.g. "outer[1][inner][0][city]") can be rebuilt from scratch
		 * every time rows are added or removed at any level.
		 */
		setupInstances: function( fieldSet, parentBase ) {
			var self = this;
			fieldSet = $( fieldSet );

			if ( parentBase ) {
				fieldSet.attr( 'data-parent-base', parentBase );
			} else {
				parentBase = null;
			}

			var repeaterRow = fieldSet.find( '> .fieldset-wpum_field_group' ).not( '.fieldset-wpum_field_group-clone' );

			if ( !repeaterRow.length ) {
				return;
			}

			var repeaterKey = self.getRepeaterKey( fieldSet );
			self.resetInstance( repeaterKey );

			repeaterRow.each( function( i ) {
				var row = $( this );

				// Conditional logic reads the row index from the child fieldsets.
				row.find( '> fieldset' ).attr( 'data-index', i );

				row.find( '> fieldset :input' )
					.not( row.find( '> fieldset > .fieldset-wpum_field_group :input' ) )
					.not( ':button, :submit, :reset' )
					.each( function() {
						var input = $( this );

						if ( !input.attr( 'data-name' ) ) {
							if ( !input.prop( 'name' ) ) {
								// Helper inputs such as the flatpickr alt input have no name.
								return;
							}
							input.attr( 'data-name', input.prop( 'name' ) );
						}
						if ( !input.attr( 'data-clone' ) && input.prop( 'id' ) ) {
							input.attr( 'data-clone', input.prop( 'id' ) );
						}

						var fieldName = input.attr( 'data-name' ).replace( /\[(.*?)\]/, '[' + i + ']' );

						if ( parentBase ) {
							// "inner[0][city]" becomes "outer[1][inner][0][city]".
							fieldName = parentBase + fieldName.replace( /^([^[]+)/, '[$1]' );
						}

						input.attr( 'name', fieldName );

						var originalId = input.attr( 'data-clone' );
						if ( originalId ) {
							var indexes = ( fieldName.match( /\[\d+\]/g ) || [] ).map( function( index ) {
								return index.replace( /\D/g, '' );
							} );
							var nonZero = indexes.some( function( index ) {
								return '0' !== index;
							} );
							// Single level rows keep their old ids: "field", "field_1", "field_2".
							var id = nonZero ? originalId + '_' + indexes.join( '_' ) : originalId;

							input.attr( 'id', id );
							input.closest( 'fieldset' ).find( 'label' ).attr( 'for', id );
						}
					} );

				self.increaseInstance( repeaterKey );

				// Recurse into nested repeaters in this row.
				row.find( '> fieldset > .add-repeater-row' ).each( function() {
					var nestedFieldSet = $( this ).parent( 'fieldset' );

					self.setupInstances( nestedFieldSet, repeaterKey + '[' + i + ']' );
					self.validateMaxRows( nestedFieldSet );
				} );
			} );
		},

		/**
		 * Whether another row can be added. Also disables the add button when
		 * the maximum is reached, and enables it again after a row is removed.
		 */
		validateMaxRows: function( fieldSet ) {
			var repeater = $( fieldSet ).find( '> .fieldset-wpum_field_group' ).not( '.fieldset-wpum_field_group-clone' );
			var addBtn = $( fieldSet ).find( '> .add-repeater-row' );
			var maxRows = parseInt( addBtn.data( 'max-row' ), 10 );

			if ( !maxRows || maxRows < 1 ) {
				return true;
			}

			var canAdd = repeater.length < maxRows;
			addBtn.prop( 'disabled', !canAdd );

			return canAdd;
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
