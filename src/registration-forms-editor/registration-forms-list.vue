<template>
	<div class="wpum-registration-forms-list">

		<h1 class="wp-heading-inline">
			<img :src="pluginURL + 'assets/images/logo.svg'" alt="WP User Manager">
			{{labels.page_title}}
		</h1>

		<br/><br/>

		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th scope="col" class="column-primary">{{labels.table_name}}</th>
					<th scope="col">{{labels.table_fields}}</th>
					<th scope="col">{{labels.table_default}}</th>
					<th scope="col">{{labels.table_role}}</th>
				</tr>
			</thead>
			<tbody>
				<tr class="no-items" v-if="loading">
					<td class="colspanchange" colspan="4">
						<div class="spinner is-active"></div>
					</td>
				</tr>
			</tbody>
		</table>

	</div>
</template>

<script>
import axios from 'axios'
import qs from 'qs'

export default {
	name: 'registration-forms-list',
	data() {
		return {
			labels:    wpumRegistrationFormsEditor.labels,
			pluginURL: wpumRegistrationFormsEditor.pluginURL,
			loading:   false,
			forms:     ''
		}
	},
	created() {
		this.getForms()
	},
	methods: {
		getForms() {

			this.loading = true

			axios.get( wpumRegistrationFormsEditor.ajax, {
				params: {
					nonce:  wpumRegistrationFormsEditor.getFormsNonce,
					action: 'wpum_get_registration_forms'
				}
			})
			.then( response => {
				this.loading = false
				console.log(response)
			})
			.catch( error => {
				this.loading = false
				console.log(error)
			})

		}
	}
}
</script>
