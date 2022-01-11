<template>
	<div class="media-modal-content wpum-dialog" id="create-field-dialog">
		<button type="button" class="media-modal-close" @click="$emit('close')"><span class="media-modal-icon"><span class="screen-reader-text">Close panel</span></span></button>
		<div class="media-frame mode-select wp-core-ui">
			<div class="media-frame-title">
				<h1>{{labels.email_form_title}}</h1>
			</div>
			<div class="media-frame-content">
				<form action="post">
          <table class="form-table" style="margin-top:0px;">
              <tr>
                  <td scope="row">
                    <label for="email-name" :data-balloon="labels.email_name" data-balloon-pos="right"><span>{{labels.email_name}}</span> <span class="dashicons dashicons-editor-help"></span></label>
                    <input type="text" name="email-name" id="email-name" :disabled="loading" v-model="email_name">                    
                  </td>
                  <td scope="row">
                    <label for="email-description" :data-balloon="labels.email_description" data-balloon-pos="right"><span>{{labels.email_description}}</span> <span class="dashicons dashicons-editor-help"></span></label>
                    <input type="text" name="email-description" id="email-description" :disabled="loading" v-model="email_description">                    
                  </td>        
              </tr>            
              <tr>
                  <td scope="row">
                    <label for="email-subject" :data-balloon="labels.email_subject" data-balloon-pos="right"><span>{{labels.email_subject}}</span> <span class="dashicons dashicons-editor-help"></span></label>
                    <input type="text" name="email-subject" id="email-subject" :disabled="loading" v-model="email_subject">                    
                  </td>
                  <td scope="row">
                    <label for="email-heading" :data-balloon="labels.email_heading" data-balloon-pos="right"><span>{{labels.email_heading}}</span> <span class="dashicons dashicons-editor-help"></span></label>
                    <input type="text" name="email-heading" id="email-heading" :disabled="loading" v-model="email_heading">                    
                  </td>        
              </tr>
              <tr>
                  <td scope="row">
                    <label for="email-recipient" :data-balloon="labels.email_recipient" data-balloon-pos="right"><span>{{labels.email_recipient}}</span> <span class="dashicons dashicons-editor-help"></span></label><br>
                    <select v-model="email_recipient" @change="onChangeRecipient($event)"> 
                        <option value="admin">Admin Email</option>
                        <option value="user">User Email</option>
                        <option value="specific">Specific Email</option>
                    </select>
                    <input v-show="isSpecific" type="text" name="email-recipient-email" id="email-recipient-email" :disabled="loading" placeholder="Email address" v-model="email_recipient_email">               
                  </td>
                  <td></td>   
              </tr>
              <tr>
                  <td scope="row" colspan="2">
                    <label for="email-body" :data-balloon="labels.email_body" data-balloon-pos="right"><span>{{labels.email_body}}</span> <span class="dashicons dashicons-editor-help"></span></label>
                    <vue-editor v-model="email_body"></vue-editor>                  
                  </td>        
              </tr>                          
          </table>
          <div class="wpum-email-tags-list"><strong>Available email merge tags:</strong><br/><span v-html="labels.email_merge_tags"></span></div>
				</form>
			</div>
			<div class="media-frame-toolbar">
				<div class="media-toolbar">
					<div class="media-toolbar-primary search-form">
						<div class="spinner is-active" v-if="loading"></div>
						<button type="button" class="button media-button button-primary button-large media-button-insert" v-text="labels.email_save" :disabled="loading || ! canSubmit()" @click="SaveTemplate()"></button>
					</div>
				</div>
			</div>
		</div>
	</div>
</template>
<style scoped>
body.users_page_wpum-emails .media-modal-content {
  min-height: initial;
  background: #efefef;
}
body.users_page_wpum-emails .media-frame-title,
body.users_page_wpum-emails .media-frame-content,
body.users_page_wpum-emails .media-frame-toolbar {
  left: 0;
}

body.users_page_wpum-emails .dialog-form input,
body.users_page_wpum-emails .dialog-form textarea {
  display: block;
  width: 100%;
  margin-bottom: 15px;
  font-size: 13px !important;
}
body.users_page_wpum-emails .dialog-form input:last-child,
body.users_page_wpum-emails .dialog-form textarea:last-child {
  margin-bottom: 0;
}
#create-field-dialog form .hidden{
  display:none;
}
#create-field-dialog form #email-recipient-email{
  width: 60% !important;
  vertical-align: middle;
}
#create-field-dialog form select{
  min-height: 38px !important;
  width: 200px;
  line-height: 35px;  
  margin-bottom: 4px;
  font-size: 13px !important;
  display: inline-block;
}
#create-field-dialog .media-frame-content {
  padding: 0;
  top: 50px;
}
#create-field-dialog form {
  padding: 20px 15px;
  border-bottom: 1px solid #ddd;
}
#create-field-dialog .form-table td{
  padding: 0 5px;
}

#create-field-dialog form label {
  display: inline-block;
  font-weight: 500;
  margin-bottom: 0.4em;
  margin-top: 0.6em;
  font-size: 13px;
}
#create-field-dialog form input {
  padding: 3px 8px;
  font-size: 1.7em;
  line-height: 100%;
  height: 1.7em;
  width: 100%;
  outline: 0;
  margin: 0 0 3px;
  background-color: #fff;
}
#create-field-dialog ul.attachments {
  padding: 20px 10px;
}
#create-field-dialog ul.attachments .attachment {
  width: 16.6%;
  padding: 10px;
}
#create-field-dialog ul.attachments .dashicons {
  font-size: 48px;
  height: 48px;
  width: 48px;
  margin-top: -20px;
}
#create-field-dialog ul.attachments .check:hover .media-modal-icon {
  background-position: -21px 0;
}
#create-field-dialog ul.attachments .locked-type {
  position: absolute;
  top: 0;
  right: 0;
}
#create-field-dialog ul.attachments .locked-type span {
  margin-top: 20px;
  height: 20px;
  width: 20px;
  font-size: 30px;
  margin-right: 25px;
  color: #016afe;
}
#create-field-dialog button:disabled {
  cursor: not-allowed;
}
</style>
<script>
import axios from 'axios'
import qs from 'qs'
import { VueEditor } from "vue2-editor";

export default {
	name: 'dialog-create-email',
	components: {
		VueEditor
	},
	props: {
		group_id:    '',
		addNewField: '',
		parent: {
			type: Number,
			default: 0
		}
	},
	data() {
		return {
			loading: false,
			labels: wpumEmailsEditor.labels.email_form,
			email_recipient: 'admin',
      isSpecific: false,
      email_name: '',
      email_recipient_email: '',
      email_description: '',
      email_subject: '',
      email_heading: '',
      email_body: ''
		}
	},
	methods: {
		/**
		* Show/Hide email field.
		*/
		onChangeRecipient( e ) {
      this.isSpecific = e.target.value == 'specific' ? true : false;
		},
		/**
		 * Verify that the name and field type are selected
		 * before enabling the create button.
		 */
		canSubmit() {
			if( this.email_name && this.email_name.trim().length  ) {
				return true
			} else {
				return false
			}
		},
		/**
		 * Create the new field into the database.
		 */
		SaveTemplate() {
			this.loading = true

			// Make a call via ajax.
			axios.post( wpumEmailsEditor.ajax,
				qs.stringify({
					nonce: wpumEmailsEditor.create_field_nonce,
          email_description: this.email_description,
          email_subject: this.email_subject,
          email_heading: this.email_heading,
          email_body: this.email_body,
          email_name: this.email_name,
          email_recipient_email: this.email_recipient_email,
          email_recipient: this.email_recipient

				}),
				{
					params: {
						action: 'wpum_create_emailtemplate'
					},
				}
			)
			.then( response => {
        window.location.reload();
				this.$emit('close')
			})
			.catch( error => {
				this.loading = false
				this.$emit('close')
			})

		}
	}
}
</script>
