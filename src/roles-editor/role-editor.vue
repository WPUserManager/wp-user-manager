<template>
	<div class="wpum-role">
		<h1 class="wp-heading-inline">
			<img :src="pluginURL + 'assets/images/logo.svg'" alt="WP User Manager">
			{{ roleName }}
		</h1>
		<router-link to="/" class="page-title-action wpum-icon-button circular" :data-balloon="labels.page_back"
					 data-balloon-pos="down"><span class="dashicons dashicons-arrow-left-alt"></span></router-link>
		<a v-if="! loading" class="page-title-action wpum-icon-button" @click="openCreateNewCapDialog()"><span
			class="dashicons dashicons-plus-alt"></span> {{ labels.add_new_cap }}</a>
		<span v-if="! loading" class="cap-counts">
			Granted: {{ grantedCount }} Denied: {{ deniedCount }}
		</span>

		<wp-notice :type="messageStatus" alternative v-if="showMessageSettings">{{ messageContent }}</wp-notice>
		<v-dialog/>
		<modals-container/>

		<div class="spinner is-active" style="margin-top: 20px; margin-left: 0" v-if="loading"></div>
		<div v-if="! loading" class="wpum-role-wrapper">
			<div class="wpum-role-caps">
				<div class="wpum-role-caps-wrapper">
					<ul id="wpum-cap-nav" v-if="! loading">
						<li v-for="group in groups" :class="activeTab == group.name ? 'active' : ''" @click.prevent="setActiveTabName(group.name)">
							<a :href="'#tab-' + group.name" aria-selected="true"
							   @click.prevent="setActiveTabName(group.name)">
								<i :class="'dashicons ' + group.icon "></i>
								<span class="label">{{ group.label }}</span></a>
						</li>
						<li @click.prevent="setActiveTabName('all')">
							<a href="#tab-all" aria-selected="true" @click.prevent="setActiveTabName('all')">
								<i class="dashicons dashicons-wordpress"></i>
								<span class="label">All</span></a>
						</li>
					</ul>

					<div class="wpum-caps">
						<form action="post" @submit.prevent="saveRole()" class="opk-form">

							<div v-for="group in groups" :id="'group-' + group.name" class="cap-group"
								 v-if="displayContents(group.name)">
								<table class="wp-list-table widefat">
									<thead>
									<tr>
										<th class="column-cap">Capability</th>
										<th class="column-grant">Grant</th>
										<th class="column-deny">Deny</th>
									</tr>
									</thead>

									<tfoot>
									<tr>
										<th class="column-cap">Capability</th>
										<th class="column-grant">Grant</th>
										<th class="column-deny">Deny</th>
									</tr>
									</tfoot>

									<tbody>
									<tr class="group-role-caps" v-for="cap in group.caps">
										<td class="column-cap">
											<strong>{{ cap }}</strong>
										</td>

										<td class="column-grant">
										<span
											class="screen-reader-text">Grant <code>{{ cap }}</code> capability</span>
											<input type="checkbox" :value="cap" v-model="role_caps"
												   @change="grantClick($event)">
										</td>

										<td class="column-deny">
										<span
											class="screen-reader-text">Deny <code>{{ cap }}</code> capability</span>
											<input type="checkbox" :value="cap" v-model="role_denied_caps"
												   @change="denyClick($event)">
										</td>
									</tr>
									</tbody>
								</table>
							</div>
							<div id="group-all" class="cap-group"
								 v-if="displayContents('all')">
								<table class="wp-list-table widefat">
									<thead>
									<tr>
										<th class="column-cap">Capability</th>
										<th class="column-grant">Grant</th>
										<th class="column-deny">Deny</th>
									</tr>
									</thead>

									<tfoot>
									<tr>
										<th class="column-cap">Capability</th>
										<th class="column-grant">Grant</th>
										<th class="column-deny">Deny</th>
									</tr>
									</tfoot>

									<tbody>
									<tr class="group-role-caps" v-for="cap in capabilities">
										<td class="column-cap">
											<strong>{{ cap }}</strong>
										</td>

										<td class="column-grant">
										<span
											class="screen-reader-text">Grant <code>{{ cap }}</code> capability</span>
											<input type="checkbox" :value="cap" v-model="role_caps"
												   @change="grantClick($event)">
										</td>

										<td class="column-deny">
										<span
											class="screen-reader-text">Deny <code>{{ cap }}</code> capability</span>
											<input type="checkbox" :value="cap" v-model="role_denied_caps"
												   @change="denyClick($event)">
										</td>
									</tr>
									</tbody>
								</table>
							</div>
							<input type="submit" class="button button-primary button-large" v-model="labels.save"
								   :disabled="loading || loadingSettings">
							<div class="spinner is-active" style="margin-top: 20px; margin-left: 0" v-if="loadingSettings"></div>
						</form>
					</div>

				</div>
			</div>
		</div>
	</div>
</template>

<script>
import axios from 'axios'
import qs from 'qs'
import CreateCapDialog from "../roles-editor/dialogs/dialog-create-cap";

export default {
	name: 'role-editor',
	components: {
		CreateCapDialog
	},
	data() {
		return {
			labels: wpumRolesEditor.labels,
			pluginURL: wpumRolesEditor.pluginURL,
			loading: false,
			loadingSettings: false,
			roleID: '',
			roleName: '...',
			role: [],
			capabilities: [],
			is_editable: false,
			role_caps: [],
			role_denied_caps: [],
			groups: [],
			showMessage: false,
			showMessageSettings: false,
			messageStatus: 'success',
			messageContent: '',
			activeTab: 'general',
		}
	},
	computed: {
		grantedCount() {
			return this.role_caps.length;
		},
		deniedCount() {
			return this.role_denied_caps.length;
		},
	},
	created() {
		this.roleID = this.$route.params.id
		this.getRole()
	},
	methods: {
		/**
		 * Setup classes for the component based on the field type.
		 */
		classes( type ) {
			return [
				'opk-field',
				type == 'text' ? 'regular-text' : ''
			];
		},
		setActiveTabName( name ) {
			this.activeTab = name;
		},
		displayContents( name ) {
			return this.activeTab === name;
		},
		grantClick( event ) {
			if ( !event.srcElement.checked ) {
				return;
			}

			let cap = event.srcElement.value;
			this.role_denied_caps = this.role_denied_caps.filter( name => name !== cap );
		},
		denyClick( event ) {
			if ( !event.srcElement.checked ) {
				return;
			}

			let cap = event.srcElement.value;
			this.role_caps = this.role_caps.filter( name => name !== cap );
		},
		/**
		 * Retrieve the role
		 */
		getRole() {
			this.loading = true

			axios.get( wpumRolesEditor.ajax, {
				params: {
					nonce: wpumRolesEditor.getRoleNonce,
					action: 'wpum_get_role',
					role_id: this.roleID
				}
			} )
				.then( response => {
					this.loading = false
					this.roleName = response.data.data.name
					this.role = response.data.data.role
					this.capabilities = Object.values( response.data.data.capabilities )
					this.role_caps = Object.values( response.data.data.role.granted_caps )
					this.role_denied_caps = Object.values( response.data.data.role.denied_caps )
					this.is_editable = response.data.data.is_editable
					this.groups = response.data.data.groups
				} )
				.catch( error => {
					this.loading = false
					console.log( error )
				} )
		},
		/**
		 * Save settings to the form.
		 */
		saveRole() {
			this.loadingSettings = true

			axios.post( wpumRolesEditor.ajax,
				qs.stringify( {
					nonce: wpumRolesEditor.saveRoleNonce,
					role_id: this.roleID,
					granted_caps: this.role_caps,
					denied_caps: this.role_denied_caps
				} ),
				{
					params: {
						action: 'wpum_save_role'
					},
				}
			)
				.then( response => {
					this.loadingSettings = false
					this.showMessageSettings = true
					this.messageStatus = 'success'
					this.messageContent = wpumRolesEditor.labels.cap_success
					this.resetNotice()
				} )
				.catch( error => {
					this.loadingSettings = false
					this.showMessageSettings = true
					this.messageStatus = 'error'
					this.messageContent = wpumRolesEditor.labels.error
					this.resetNotice()
					console.log( error )
				} )

		},
		/**
		 * Open the create new field dialog. Only works when the premium addon is installed.
		 */
		openCreateNewCapDialog() {
			let that = this;
			this.$modal.show( CreateCapDialog, {
				addNewCap: ( status, capability ) => {
					that.capabilities.push( capability )
					that.role_caps.push( capability )

					let general = Object.values( that.groups[ 'general' ].caps );
					general.unshift( capability );
					that.groups[ 'general' ].caps = general;

					let custom = Object.values( that.groups[ 'custom' ].caps );
					custom.unshift( capability );
					that.groups[ 'custom' ].caps = general;
				}
			}, { height: '200px', width: '400px' } )
		},
		/**
		 * Automatically hide a notice after it's displayed.
		 */
		resetNotice() {
			setTimeout( () => {
				this.showMessage = false
				this.showMessageSettings = false
			}, 3000 )
		}
	}
}
</script>
