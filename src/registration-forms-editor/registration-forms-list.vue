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
					<th scope="col" :data-balloon="labels.table_default_tooltip" data-balloon-pos="left">{{labels.table_default}}</th>
					<th scope="col">{{labels.table_role}}</th>
				</tr>
			</thead>
			<tbody>
				<tr class="no-items" v-if="loading">
					<td class="colspanchange" colspan="4">
						<div class="spinner is-active"></div>
					</td>
				</tr>
				<tr class="no-items" v-if="forms.length < 1 && ! loading"><td class="colspanchange" colspan="4"><strong>{{labels.table_not_found}}</strong></td></tr>
				<tr v-if="forms" v-for="(form, id) in forms" :key="id">
					<td>
						<strong>{{form.name}}</strong>
						<div class="row-actions">
							<span>
								<router-link :to="{ name: 'form', params: { id: id }}">{{labels.table_customize}}</router-link>
							</span>
						</div>
					</td>
					<td>
						{{form.count}}
					</td>
					<td>
						<span v-if="form.default === true" class="dashicons dashicons-yes"></span>
					</td>
					<td>
						{{form.role}}
					</td>
				</tr>
			</tbody>
		</table>

	</div>
</template>

<script>
import axios from 'axios'
import balloon from 'balloon-css'

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
		/**
		 * Retrieve forms on page load.
		*/
		this.getForms()
	},
	methods: {
		/**
		 * Retrieve the list of created registration forms.
		 */
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
				this.forms   = response.data.data
			})
			.catch( error => {
				this.loading = false
				console.log(error)
			})

		}
	}
}
</script>
