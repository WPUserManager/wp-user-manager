<template>
	<div id="wpum-email-content-editor">
		<a href="#" class="button button-hero" id="wpum-email-editor-btn" @click="openEditor">
			<span class="dashicons dashicons-edit" v-if="!editorVisible"></span>
			<span class="dashicons dashicons-hidden" v-else></span>
			<span v-text="buttonLabel"></span>
		</a>
		<transition name="slide">
			<div v-dom-portal="'.wp-full-overlay'" :class="classes" v-if="editorVisible">
				<div class="inside">
					<textarea name="wpum-email-content" id="wpum-email-content" cols="30" rows="10" v-model="emailContent"></textarea>
				</div>
			</div>
		</transition>
  	</div>
</template>

<script>
export default {
	name: 'email-content-editor',
	computed: {
		/**
		 * Set the classes for the editor window container
		 */
		classes() {
			return [
				'wpum-editor-window',
			]
		}
	},
	data() {
		return {
			editorVisible: false,
			emailID: wpumCustomizeControls.selected_email_id,
			emailContent: wpumCustomizeControls.email_content,
			buttonLabel: wpumCustomizeControls.labels.open
		}
	},
	methods: {
		/**
		 * Open the editor window and initialize wp.editor.
		 */
		openEditor() {
			this.editorVisible = !this.editorVisible

			if( this.editorVisible ) {
				this.buttonLabel = wpumCustomizeControls.labels.close
				wp.editor.remove('wpum-email-content')
			} else {
				this.buttonLabel = wpumCustomizeControls.labels.open
			}

			let that = this
			setTimeout(
				() => {
					wp.editor.initialize('wpum-email-content', {
						tinymce: {
                            setup(ed) {
                                ed.on('change', function (ed, l) {
									that.updateLiveContent()
                            	});
                            }
                        },
					})
				},
			10)
		},
		/**
		 * Update the live preview content.
		 */
		updateLiveContent() {
			wp.customize( 'wpum_email[registration_confirmation][content]', function ( obj ) {
				obj.set( wp.editor.getContent('wpum-email-content') );
			} );
		}
	}
}
</script>

<style lang="scss">
#wpum-email-editor-btn {
	span.dashicons {
		margin-top: 12px;
		margin-right: 5px;
	}
}

.wpum-editor-window {
	border-top: solid 1px #ddd;
	position: absolute;
	height: 275px;
	right: 0;
	left: 0;
	z-index: 20;
	background: #f1f1f1;
	display: block;
	bottom: 0;
}

.slide-leave-active,
.slide-enter-active {
	transition: .2s;
}

.slide-enter {
	transform: translate3d(0, 100%, 0);
	visibility: visible;
}
.slide-leave-to {
	transform: translate3d(0, 0, 0);
}
</style>
