/*! WP User Manager - v2.4
 * https://wpusermanager.com
 * Copyright (c) 2020; * Licensed GPLv2+ */
window.loadResources=function(e){return new Promise(function(t,n){var o=0,r=function(){++o===e.length&&t()};e.forEach(function(e){var t=e.split("?")[0];/\.css/.test(t)?function(e,t){var n=document.createElement("link");n.rel="stylesheet",n.href=e,n.onload=t,document.head.appendChild(n)}(e,r):/\.js/.test(t)&&function(e,t){var n=document.createElement("script");n.src=e,n.onload=t,document.head.appendChild(n)}(e,r)})})}

jQuery( function( $ ) {
	function initFields() {
		$( '.wpum-multiselect:not(.wpum-clone-field)' ).select2( {
			theme: 'default'
		} );

		$( '.wpum-datepicker:not([readonly]):not(.wpum-clone-field)' ).flatpickr( {
			dateFormat: wpumFrontend.dateFormat
		} );
	}

	var repeater = {

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

			$('body').on( 'click', '.add-repeater-row', function() {
				var parent = $( this ).parents( 'fieldset' );
				self.addNewInstance( parent.get( 0 ).classList[ 0 ] );
			} );

			$('body').on( 'click', '.remove-repeater-row', function(e) {
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

	// load FilePond resources
	var resources = [
		'assets/css/vendor/filepond/filepond-preview.css',
		'assets/css/vendor/filepond/filepond.min.css',
		'assets/js/vendor/filepond/plugin/filepond-encode.min.js',
		'assets/js/vendor/filepond/plugin/filepond-preview.min.js',
		'assets/js/vendor/filepond/plugin/filepond-crop.min.js',
		'assets/js/vendor/filepond/plugin/filepond-resize.min.js',
		'assets/js/vendor/filepond/filepond.min.js',
	].map(function(resource) { return wpumFrontend.pluginurl + resource });

	loadResources(resources).then(function() {

		// register plugins
		FilePond.registerPlugin(
			FilePondPluginFileEncode,
			FilePondPluginImagePreview,
			FilePondPluginImageCrop,
			FilePondPluginImageResize,
		);

		var fields = [].slice.call(document.querySelectorAll('input[type="file"]'));
		var ponds = fields.map(function(field, index) {

			var files = [];

			if ( $( field ).data( 'file-poster' ) !== '' ) {
				files = [ {
					source: $( field ).data( 'file-poster' ),
					options: {
						type: 'local',
					}
				} ];
			}

			return FilePond.create( field, {
				credits: false,
				allowFilePoster: true,
				allowFileMetadata: true,
				fileMetadataObject: {
					fieldkey: $( field ).data( 'file-key' )
				},

				imageResizeTargetWidth: $( field ).data( 'file-width' ) ? $( field ).data( 'file-width' ) : null,
				imageResizeTargetHeight: $( field ).data( 'file-height' ) ? $( field ).data( 'file-height' ) : null,
				styleLoadIndicatorPosition: 'center bottom',
				styleProgressIndicatorPosition: 'center bottom',
				styleButtonRemoveItemPosition: 'center bottom',
				styleButtonProcessItemPosition: 'center bottom',
				stylePanelLayout: $( field ).data( 'file-layout' ) ? $( field ).data( 'file-layout' ) : '',
				server: {
					url: wpumFrontend.ajaxurl,
					process: {
						method: 'POST',
						ondata: ( formData ) => {
							formData.append( 'action', $( field ).data( 'file-action' ) );
							formData.append( 'key', $( field ).data( 'file-key' ) );
							formData.append( 'nonce', $( field ).data( 'file-nonce' ) );
							return formData;
						},
						onload: ( data ) => {
							const resp = JSON.parse( data );
							if ( resp ) {
								var uploaded = resp.data.is_loggedin == 0 ? '<input type="hidden" class="input-text" name="current_'+ $( field ).data( 'file-key' ) +'[url]" value="'+ resp.data.url +'" /><input type="hidden" class="input-text" name="current_'+ $( field ).data( 'file-key' ) +'[path]" value="'+ resp.data.file +'" />' : '<input type="hidden" class="input-text" name="current_'+ $( field ).data( 'file-key' ) +'" value="'+ resp.data.url +'" />';
								$( '#' + $( field ).data( 'file-key' ) ).closest( '.field' ).find( '.wpum-uploaded-files' ).html(`<div class="wpum-uploaded-file">${uploaded}</div>`);
							}
						},
					},
					load: '?action=wpum_load_profile_image&path_key='+ $( field ).data( 'file-posterkey' ) + '&',
					remove: (source, load, error) => {
						$.ajax({
							type: 'POST',
							url: wpumFrontend.ajaxurl,
							data: {
								key: $( field ).data( 'file-key' ),
								path_key: $( field ).data( 'file-posterkey' ),
								nonce: $( field ).data( 'file-nonce' ),
								action: 'wpum_delete_profile_image',
							},
							success: function (d) {
								$( field ).closest( 'field ' ).find( '.wpum-uploaded-files' ).html( '' );
								load();
							},
						});
					},
					revert: '?action=wpum_delete_profile_image&path_key='+ $( field ).data( 'file-posterkey' ) + '&key='+$( field ).data( 'file-key' )+'&nonce='+ $( field ).data( 'file-nonce' ),
				},
				files: files,
			});
		});
	});
} );
