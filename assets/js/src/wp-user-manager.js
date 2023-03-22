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

			$( '.add-repeater-row' ).each( function() {
				var parent = $( this ).parents( 'fieldset' );
				var repeater = parent.find( '.fieldset-wpum_field_group' ).not('.fieldset-wpum_field_group-clone' );

				if ( repeater.length ) {
					var name = parent.get( 0 ).classList[ 0 ];
					self.increaseInstance( name );
					self.validateMaxRows( name );
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
				var parent = $( this ).parents( 'fieldset' );
				self.addNewInstance( parent.get( 0 ).classList[ 0 ] );
				self.form.wpumConditionalFields({});
			} );

			self.form.on( 'click', '.remove-repeater-row', function(e) {
				e.preventDefault();
				var parent = $( this ).parents( 'fieldset' );
				var $row = $( this ).parents( '.fieldset-wpum_field_group' );
				$row.remove();
				self.setupInstances( parent.get( 0 ).classList[ 0 ] );
			} );
		},

		increaseInstance: function( name ) {
			if ( !this.repeaters[ name ] ) {
				this.resetInstance( name );
			}

			this.repeaters[ name ]++;
		},

		addNewInstance: function( name ) {
			this.addNewRepeaterRow( name );
			this.setupInstances( name );
			initFields();
		},

		resetInstance: function( name ) {
			this.repeaters[ name ] = 0;
		},

		addNewRepeaterRow: function( name ) {
			var repeater = $( '.' + name ).find( '.fieldset-wpum_field_group-clone' ).last();
			if ( !repeater.length ) {
				return;
			}

			if ( !this.validateMaxRows( name ) ) {
				return;
			}

			var newRepeater = repeater.clone();
			newRepeater.removeClass('fieldset-wpum_field_group-clone');
			newRepeater.find( ':input' ).not( ':button, :submit, :reset' ).val( '' ).prop( 'checked', false ).prop( 'selected', false ).removeClass( 'wpum-clone-field' ).trigger( 'change' );
			newRepeater.insertBefore( repeater );
		},

		setupInstances: function( name ) {
			var repeaterRow = $( '.' + name ).find( '.fieldset-wpum_field_group' ).not( '.fieldset-wpum_field_group-clone' );
			var self = this;

			if ( !repeaterRow.length ) {
				return;
			}

			self.resetInstance( name );

			repeaterRow.each( function( i ) {
				$( this ).find('fieldset').attr('data-index', i);
				$( this ).find( ':input' ).each( function() {
					var name = '';
					if ( $( this ).attr( 'data-name' ) ) {
						name = $( this ).attr( 'data-name' );
					} else {
						name = $( this ).prop( 'name' );
					}

					$( this ).attr(
						'name',
						name.replace(
							new RegExp( /\[(.*?)\]/ ),
							function() {
								return '[' + i + ']';
							}
						)
					);

					if ( i > 0 ) {
						var clone_id = '';
						if ( $( this ).attr( 'data-clone' ) ) {
							clone_id = $( this ).attr( 'data-clone' );
						} else {
							clone_id = $(this).prop( 'id' );
						}

						var id = clone_id + '_' + i;
						$( this ).attr( 'id', id );
						$( this ).closest( 'fieldset' ).find( 'label' ).attr( 'for', id );
					}
				} );
				self.increaseInstance( name );
			} );
		},

		validateMaxRows: function( name ) {
			var parent = $( '.' + name );
			var repeater = parent.find( '.fieldset-wpum_field_group' ).not( '.fieldset-wpum_field_group-clone' );
			var addBtn = parent.find( '.add-repeater-row' );
			var maxRows = addBtn.data( 'max-row' );
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
					$( this ).find( "input" ).prop( "required", validRule );
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
