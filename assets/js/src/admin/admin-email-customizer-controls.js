/* global wp, jQuery, console, wpumCustomizeControls */
/* eslint consistent-this: [ "error", "control" ] */
/* eslint no-magic-numbers: ["error", { "ignore": [0,1] }] */
/* eslint complexity: ["error", 8] */
(function (wp, $) {
	'use strict'

	/**
	 * Generate an array of email tags which is used for the merge tags menu of the editor.
	 */
	function wpumEditorGetEmailTags(editor) {
		if (!editor) {
			return false
		}
		var $mergeTagsMenu = []
		wpumCustomizeControls.mergeTags.forEach(function (tag) {
			$mergeTagsMenu.push({
				text: tag.name,
				onclick: function () {
					editor.insertContent('{' + tag.tag + '}')
				}
			})
		})
		return $mergeTagsMenu
	}

	/**
	 * Update live preview when content changes.
	 */
	function wpumEditorUpdatePreview() {
		var $emailID = wpumCustomizeControls.selected_email_id
		wp.customize('wpum_email[' + $emailID + '][content]', function (obj) {
			obj.set(wp.editor.getContent('wpum-mail-content-editor'))
		})
	}

	/**
	 * Hook into the customizer
	 */
	wp.customize.bind('ready', function () {
		// Email editor controller.
		var $editorButton = $('#wpum-email-editor-btn')
		var $editorContainer = $('#wpum-editor-window')
		var $editorButtonIcon = $editorButton.find('span.dashicons')
		var $editorToolbarAdded = false
		var $editorActive = false
		var $editorIframe = $('#customize-preview')

		// ============== Trigger for the email list ==============
		var $mergeTagsButton = $('#wpum-display-tags-btn')
		var $mergeTagsList = $('.wpum-email-tags-list')

		$mergeTagsButton.click(function (e) {
			var $this = $(e.currentTarget)
			e.preventDefault()
			$this.toggleClass('active')
			$mergeTagsList.slideToggle('fast')
		})

		// Move the editor window within the preview frame.
		$editorContainer.appendTo('.wp-full-overlay')

		// Don't ask me why but elements are duplicated and I'm tired of trying to figure this out.
		// $('.wp-full-overlay').find('#wpum-editor-window').remove()

		$editorButton.click(function (e) {
			var $this = $(e.currentTarget)
			e.preventDefault()
			$this.toggleClass('active')

			// Make the iframe scrollable.
			$editorIframe.toggleClass('scroll-frame')

			// Toggle the editor instance if it was already created.
			if ($editorActive === true) {
				wp.editor.remove('wpum-mail-content-editor')
			}

			$editorActive = !$editorActive // Flag the editor as active or inactive.

			// Toggle button text depending on the status.
			$this.find('span:not(.dashicons)').text(function (i, v) {
				return v === wpumCustomizeControls.labels.open ? wpumCustomizeControls.labels.close : wpumCustomizeControls.labels.open
			})

			// Toggle the class within the button
			if ($editorActive === true) {
				$editorButtonIcon.removeClass('dashicons-edit').addClass('dashicons-hidden')
			} else {
				$editorButtonIcon.removeClass('dashicons-hidden').addClass('dashicons-edit')
			}

			// Toggle the editor area.
			$editorContainer.toggleClass('is-active')

			// Determine wether the editor was already loaded and if not add the new tooldbar button.
			if ($editorToolbarAdded === false) {
				$(document).on('tinymce-editor-setup', function (event, editor) {
					editor.settings.toolbar1 += ',wpumEmailTags'
					editor.addButton('wpumEmailTags', {
						text: wpumCustomizeControls.labels.addMerge,
						icon: false,
						type: 'menubutton',
						tooltip: wpumCustomizeControls.labels.addMergeTooltip,
						menu: wpumEditorGetEmailTags(editor)
					})
					$editorToolbarAdded = true
				})
			}
			// Initialize the editor.
			wp.editor.initialize('wpum-mail-content-editor', {
				tinymce: {
					setup: function (ed) {
						ed.on('change', function (ed, l) {
							wpumEditorUpdatePreview()
						})
						ed.on('keyup', function (ed, l) {
							wpumEditorUpdatePreview()
						})
					}
				}
			})
		})

		// ===== Close the editor if switching section =====
		wpumCustomizeControls.sections.forEach(function (sectionID) {
			wp.customize.section(sectionID, function (section) {
				section.expanded.bind(function (isExpanding) {
					if (isExpanding === false && $editorButton.hasClass('active')) {
						$editorButton.click()
					}
				})
			})
		})
	})
})(window.wp, jQuery)
