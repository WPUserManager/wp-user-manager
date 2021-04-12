/*! WP User Manager - v2.5
 * https://wpusermanager.com
 * Copyright (c) 2021; * Licensed GPLv2+ */
jQuery( function( $ ) {
	function initFields() {
		$( '.wpum-multiselect:not(.wpum-clone-field)' ).each( function() {
			var args = {
				theme: 'default'
			};
			var placeholder = $( this ).attr( 'placeholder' );
			if ( placeholder ) {
				args[ 'placeholder' ] = placeholder;
			}
			$( this ).select2( args );
		} );

		$( '.wpum-datepicker:not([readonly]):not(.wpum-clone-field)' ).flatpickr( {
			dateFormat: wpumFrontend.dateFormat
		} );
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


	$( document ).ready( function() {
		$( document.body ).on( 'click', '.wpum-remove-uploaded-file', function() {
			$( this ).closest( '.wpum-uploaded-file' ).remove();
			return false;
		} );


		repeater.init();
		initFields();
	} );

} );
