<template>
	<div id="wpum-emails-list">
		<h1>
			<img :src="url + 'assets/images/logo.svg'" alt="WP User Manager">
			{{labels.title}}
		</h1>

		<div class="notice notice-success is-dismissible" v-if="success">
			<p><strong v-text="sanitized(labels.success)"></strong></p>
		</div>

		<div class="notice notice-error is-dismissible" v-if="error">
			<p><strong v-text="sanitized(labels.error)"></strong></p>
		</div>

		<div class="wp-filter">
			<form action="" class="search-form search-plugins" v-on:submit.prevent="sendTestEmail">
				<label>
					<input name="email" :disabled="loading" v-model="test_email" class="wp-filter-search" :placeholder="labels.placeholder" type="text">
				</label>
				<a href="#" class="button" v-on:click="sendTestEmail" :disabled="loading"><span class="dashicons dashicons-email-alt"></span> <span v-text="sanitized(labels.send)" class="send-text"></span></a>
				<div class="spinner is-active" v-if="loading"></div>
			</form>
		</div>

		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th scope="col" class="icon-col"></th>
					<th scope="col" class="column-primary" v-text="sanitized(labels.email)"></th>
					<th scope="col" v-text="sanitized(labels.description)"></th>
					<th scope="col" v-text="sanitized(labels.recipients)"></th>
					<th scope="col" v-text="sanitized(labels.active)"></th>
					<th scope="col"></th>
				</tr>
			</thead>
			<tbody>
				<tr v-for="(email, index) in emails" :key="index">
					<td>
						<div :data-balloon="getTooltip( email.status )" data-balloon-pos="right">
							<span :class="getEmailStatusIcon( email.status )"></span>
						</div>
					</td>
					<td><a :href="getCustomizationURL( index )" v-text="sanitized(email.name)"></a></td>
					<td v-text="sanitized(email.description)"></td>
					<td v-text="sanitized(email.recipient)"></td>
					<td><toggle-button :disabled="email.disabled" v-model="email.enabled" color="#00be28" @change="emailEnabledChange( index, $event )" /> <div class="spinner is-active" v-if="emailkey === index" ></div></td>
					<td><a :href="getCustomizationURL( index )" class="button"><span class="dashicons dashicons-edit"></span> <span v-text="sanitized(labels.customize)"></span></a></td>

				</tr>
			</tbody>
			<tfoot>
				<tr>
					<th scope="col"></th>
					<th scope="col" class="column-primary" v-text="sanitized(labels.email)"></th>
					<th scope="col" v-text="sanitized(labels.description)"></th>
					<th scope="col" v-text="sanitized(labels.recipients)"></th>
					<th scope="col" v-text="sanitized(labels.active)"></th>
					<th scope="col"></th>
				</tr>
			</tfoot>
		</table>
  	</div>
</template>

<script>
import axios from 'axios'
import qs from 'qs'
import Sanitize from 'sanitize-html'
import balloon from 'balloon-css'

export default {
	name: 'emails-editor',
	data() {
		return {
			loading: false,
			success: false,
			error: false,
			placeholder: wpumEmailsEditor.placeholder,
			test_email: wpumEmailsEditor.default_email,
			emails: wpumEmailsEditor.emails,
			labels: wpumEmailsEditor.labels,
			url: wpumEmailsEditor.pluginURL,
			emailkey: '',
		}
	},
	methods: {
		/**
		 * Sanitize strings (needed because strings can be translated)
		 */
		sanitized( content ) {
			return Sanitize( content )
		},
		/**
		 * Determine classes for the icon to display within the table.
		 */
		getEmailStatusIcon( status ) {
			return [
                'dashicons',
				status == 'active' ? 'dashicons-yes' : false,
				status == 'manual' ? 'dashicons-arrow-right-alt' : false
            ];
		},
		/**
		 * Retrieve the correct tooltip text based on the status of the email.
		 */
		getTooltip( status ) {
			let tooltipText = this.sanitized( this.labels.tooltip_automatic )
			if( status == 'manual' ) {
				tooltipText = this.sanitized( this.labels.tooltip_manual )
			}
			return tooltipText
		},
		/**
		 * Retrieve the url for the customization button.
		 */
		getCustomizationURL( index ) {
			let previewURL = wpumEmailsEditor.url + qs.stringify({ wpum_email_customizer: true, email: this.sanitized(index) }, { addQueryPrefix: true })
			return wpumEmailsEditor.customizeurl + qs.stringify({
					'autofocus[panel]': this.sanitized(index),
					wpum_email_customizer: true,
					email: this.sanitized(index),
					url: previewURL
				}, { addQueryPrefix: true })
		},
		/**
		 * Send test email.
		 */
		sendTestEmail: function (event) {
			this.loading  = true
			this.error = false
			this.success = false

			axios.post( wpumEmailsEditor.ajax,
				qs.stringify({
					email: this.test_email,
					nonce: wpumEmailsEditor.nonce
				}),
				{
					params: {
						action: 'wpum_send_test_email'
					},
				}
			)
			.then( response => {

				if( response.data.success === true ) {
					this.loading = false
					this.error = false
					this.success = true
				}

				let self = this

				setInterval(function() {
					self.$data.success = false
				}, 4000)

			})
			.catch( response => {
				this.loading = false
				this.error   = true
				this.success = false
			});

		},
		emailEnabledChange: function ( key, event ) {
			this.emailkey = key
			axios.post( wpumEmailsEditor.ajax,
				qs.stringify({
					key: key,
					nonce: wpumEmailsEditor.nonce,
					enabled: event.value,
				}),
				{
					params: {
						action: 'wpum_enabled_email'
					},
				}
			)
			.then( response => {
				if( response.data.success === true ) {
					this.emailkey = ''
				}
			})
			.catch( response => {
				this.emailkey = ''
			});
		},
	}
}
</script>

<style lang="scss">
#wpum-emails-list table {
	margin-top: 1em;

	.icon-col {
		width: 20px;
	}

	a:not(.button) {
		font-weight: bold;
	}

	a.button {
		span {
			margin-top: 3px;
			margin-right: 3px;
		}
	}

	td {
		vertical-align: middle;
	}

	.dashicons-yes {
		background: #00be28;
		color: #fff;
		border-radius: 9999px;
		&:before {
			font-size: 18px;
		}
	}

}

#wpum-emails-list {
	h1 img {
		width: 26px;
		float: left;
		padding-right: 15px;
		margin-top: -6px;
	}
	.wp-filter {
		input[type="text"] {
			margin: 0;
			padding: 3px 5px;
			width: 280px;
			max-width: 100%;
			font-size: 16px;
			font-weight: 300;
			line-height: 1.5;
		}
		a.button {
			margin-top: 2px;
			margin-left: 3px;
			span {
				display: inline-block;
				margin-top: 3px;
				padding-right: 3px;
				&.send-text {
					display: inherit;
					margin: 0;
					padding: 0;
				}
			}
		}
		.search-form {
			float: none;
			margin: 10px 0 15px;
		}
	}
	.spinner {
		float: none;
		margin-top: -1px;
	}
}
</style>
