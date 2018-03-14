<template>
	<div id="wpum-email-content-editor">
		<a href="#" class="button button-hero" id="wpum-email-editor-btn" @click="openEditor">
			<span class="dashicons dashicons-edit"></span>
			Open email content editor
		</a>
		<transition name="slide">
			<div v-dom-portal="'.wp-full-overlay'" :class="classes" v-if="editorVisible">
				<div class="inside">
					<textarea name="wpum-email-content" id="wpum-email-content" cols="30" rows="10"></textarea>
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
			emailID: wpumCustomizeControls.selected_email_id
		}
	},
	methods: {
		/**
		 * Open the editor window and initialize wp.editor.
		 */
		openEditor() {
			this.editorVisible = true
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
			1)
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
	span {
		margin-top: 12px;
		margin-right: 5px;
	}
}

.wpum-editor-window {
	border-top: solid 1px #ddd;
	position: absolute;
	height: 300px;
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
