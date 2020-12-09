/*! WP User Manager - v2.3.11
 * https://wpusermanager.com
 * Copyright (c) 2020; * Licensed GPLv2+ */
jQuery(document).ready(function ($) {
	$(document.body).on('click', '.wpum-remove-uploaded-file', function () {
		$(this).closest('.wpum-uploaded-file').remove();
		return false;
	});
	$('.wpum-multiselect').select2({
		theme: 'default'
	});
	$('.wpum-datepicker:not([readonly])').flatpickr({
		dateFormat: wpumFrontend.dateFormat
	});

	var repeater = {

		form:		$('form'),
		repeaters:  {},

		init: function(){
			var self = this;

			$('.add-repeater-row').each( function(){
				var parent 	 = $(this).parents('fieldset');
				var repeater = parent.find('.fieldset-wpum_field_group');

				if( repeater.length ){
					var name = parent.get(0).classList[0];
					self.increaseInstance( name );
					self.validateMaxRows( name );
				}
			});

			self.form.on( 'click', '.add-repeater-row', function(){
				var parent =  $(this).parents('fieldset');
				self.addNewInstance( parent.get(0).classList[0] );
			});
		},

		increaseInstance: function( name ){
			if( !this.repeaters[ name ] ){
				this.resetInstance( name );
			}

			this.repeaters[name]++;
		},

		addNewInstance: function( name ){
			this.addNewRepeaterRow( name );
			this.setupInstances( name );
		},

		resetInstance: function( name ){
			this.repeaters[name] = 0;
		},

		addNewRepeaterRow: function( name ){
			var repeater = $( '.' + name ).find('.fieldset-wpum_field_group').last();
			if( !repeater.length ){
				return;
			}

			if( !this.validateMaxRows( name ) ){
				return;
			}

			var newRepeater = repeater.clone();
			newRepeater.find( ':input' ).not(':button, :submit, :reset').val('').prop('checked', false).prop('selected', false).trigger('change');

			newRepeater.insertAfter( repeater );
		},

		setupInstances: function( name ){
			var repeaterRow = $( '.' + name ).find('.fieldset-wpum_field_group');
			var self		= this;

			if( !repeaterRow.length ){
				return;
			}

			self.resetInstance( name );

			repeaterRow.each( function( i ){

				$(this).find( ':input' ).each( function(){

					$(this).attr(
						'name',
						$(this).prop( 'name' ).replace(
							new RegExp(/\[(.*?)\]/),
							function(){
								return '[' + i + ']';
							}
						)
					);

					if( i > 0 ){
						var id = $(this).prop( 'id' ) + '_' + i;
						$(this).attr( 'id', id );
						$(this).closest( 'fieldset' ).find( 'label' ).attr( 'for', id );
					}
				});
				self.increaseInstance( name );
			});
		},

		validateMaxRows: function( name ){
			var parent   = $( '.' + name );
			var repeater = parent.find('.fieldset-wpum_field_group');
			var addBtn	 = parent.find('.add-repeater-row');
			var maxRows  = addBtn.data('max-row');
			if( !maxRows || parseInt( maxRows ) < 1 ){
				return true;
			}

			if( repeater.length < parseInt( maxRows ) ){
				return true;
			}

			addBtn.attr('disabled', true);

			return repeater.length < parseInt( maxRows );
		}
	}

	repeater.init();
});
