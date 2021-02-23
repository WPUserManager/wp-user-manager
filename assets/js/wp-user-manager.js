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

	removeProfileImage = function(type, nonce){
		$.post( wpumFrontend.ajaxurl, {
			type: type,
			nonce: $('#account_update_nonce').val(),
			action: 'wpum_delete_profile_image',
		},
		function( data, status ){

		});
	}

	// load FilePond resources
	var resources = [
		'plugin/filepond-encode.min.js',
		'plugin/filepond-preview.css',
		'plugin/filepond-preview.min.js',
		'plugin/filepond-crop.min.js',
		'plugin/filepond-resize.min.js',
		'filepond.min.css',
		'filepond.min.js',
	].map(function(resource) { return wpumFrontend.pluginurl + 'assets/js/vendor/filepond/' + resource });

	loadResources(resources).then(function() {

		// register plugins
		FilePond.registerPlugin(
			FilePondPluginFileEncode,
			FilePondPluginImagePreview,
			FilePondPluginImageCrop,
			FilePondPluginImageResize,
		);

		const defaultCover = $('.fieldset-user_cover .wpum-uploaded-files input');
		const FilePondCover = FilePond.create( $('.fieldset-user_cover input[type="file"]')[0], {
			stylePanelLayout: 'compact square',
			server: {
				url: wpumFrontend.ajaxurl,
				process: {
					method: 'POST',
					ondata: (formData) => {
						formData.append( 'action', 'wpum_upload_profile_image' );
						formData.append( 'key', 'user_cover');
						formData.append( 'nonce', $( '#account_update_nonce' ).val() );
						return formData;
					}
				},
				load: '?action=wpum_load_profile_image&type=user_cover_path&',
			}
		});

		if ( defaultCover.length > 0 ) {
			FilePondCover.files = [ {
				source: defaultCover.val(),
				options: {
					type: 'local',
					metadata: {
						poster: defaultCover.length > 0 ? defaultCover.val() : null
					}
				}
			} ];
		}

		const defaultAvatar = $('.fieldset-user_avatar .wpum-uploaded-files input');
		const FilePondAvatar = FilePond.create( $('.fieldset-user_avatar input[type="file"]')[0], {
			imageResizeTargetWidth: 250,
			imageResizeTargetHeight: 250,
			styleLoadIndicatorPosition: 'center bottom',
			styleProgressIndicatorPosition: 'center bottom',
			styleButtonRemoveItemPosition: 'center bottom',
			styleButtonProcessItemPosition: 'center bottom',
			stylePanelLayout: 'compact circle',
			server: {
				url: wpumFrontend.ajaxurl,
				process: {
					method: 'POST',
					ondata: (formData) => {
						formData.append( 'action', 'wpum_upload_profile_image' );
						formData.append( 'key', 'user_avatar' );
						formData.append( 'nonce', $( '#account_update_nonce' ).val() );
						return formData;
					}
				},
				load: defaultAvatar.length > 0 ? '?action=wpum_load_profile_image&type=current_user_avatar_path&' : null,
			}
		});

		if ( defaultAvatar.length > 0 ) {
			FilePondAvatar.files = [ {
				source: defaultAvatar.val(),
				options: {
					type: 'local',
					metadata: {
						poster: defaultCover.length > 0 ? defaultCover.val() : null
					}
				}
			} ];
		}

		const filePondCover = document.querySelector('.fieldset-user_cover .filepond--root');
		filePondCover.addEventListener('FilePond:removefile', e => {
			$('.fieldset-user_cover .wpum-uploaded-files').html('');
			removeProfileImage('user_cover', '')
		});

		const filePondAvatar = document.querySelector('.fieldset-user_avatar .filepond--root');
		filePondAvatar.addEventListener('FilePond:removefile', e => {
			$('.fieldset-user_avatar .wpum-uploaded-files').html('');
			removeProfileImage('current_user_avatar', '')
		});

	});

} );
