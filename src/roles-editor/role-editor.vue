<template>
	<div class="wpum-registration-form">
			<h1 class="wp-heading-inline">
				<img :src="pluginURL + 'assets/images/logo.svg'" alt="WP User Manager">
				{{roleName}}
			</h1>
			<router-link to="/" class="page-title-action wpum-icon-button circular" :data-balloon="labels.page_back" data-balloon-pos="down"><span class="dashicons dashicons-arrow-left-alt"></span></router-link>
		<div class="optionskit-navigation-wrapper">
			<div class="wp-filter" id="optionskit-navigation">
				<ul class="filter-links">
					<li>
						<router-link :to="{ name: 'role', params: { id: this.roleID }}">{{labels.table_fields}}</router-link>
					</li>
				</ul>
			</div>
		</div>

<!--		<div class="widget-liquid-left">-->
<!--			<div id="widgets-right" class="wp-clearfix">-->

<!--				<div class="sidebars-column-3">-->
<!--					<div class="widgets-holder-wrap">-->

<!--						<wp-notice :type="messageStatus" alternative v-if="showMessage">{{messageContent}}</wp-notice>-->

<!--						<div class="widgets-sortables ui-droppable ui-sortable">-->
<!--							<div class="sidebar-name">-->
<!--								<h2>{{labels.editor_current_title}} <div class="spinner is-active" v-if="loading"></div></h2>-->
<!--							</div>-->
<!--							<div class="sidebar-description">-->
<!--								<p class="description">{{labels.editor_used_fields}}</p>-->
<!--							</div>-->
<!--							&lt;!&ndash; start fields list &ndash;&gt;-->
<!--							<draggable v-model="selectedFields" class="droppable-fields" :options="{group:'formFields', animation:150}" @sort="saveFields">-->
<!--								<div class="widget" v-for="(element, i) in selectedFields" :key="i">-->
<!--									<div class="widget-top">-->
<!--										<div class="widget-title ui-sortable-handle">-->
<!--											<h3><span :class="'dashicons ' + element.icon"></span> {{element.name}}</h3>-->
<!--										</div>-->
<!--									</div>-->
<!--								</div>-->
<!--							</draggable>-->
<!--							&lt;!&ndash; end fields list &ndash;&gt;-->
<!--							<div class="droppable-fields-after">-->
<!--								<template v-for="field in droppableFieldAfter">-->
<!--									<component :is="field" :key="field.name"></component>-->
<!--								</template>-->
<!--							</div>-->
<!--						</div>-->
<!--					</div>-->
<!--				</div>-->

<!--			</div>-->
<!--		</div>-->

	</div>
</template>

<script>
import Vue from 'vue'
import axios from 'axios'
import qs from 'qs'
import balloon from 'balloon-css'

export default {
	name: 'role-editor',
	data() {
		return {
			labels:              wpumRolesEditor.labels,
			pluginURL:           wpumRolesEditor.pluginURL,
			loading:             false,
			loadingSettings:     false,
			roleID:              '',
			roleName:            '...',
			showMessage:         false,
			showMessageSettings: false,
			messageStatus:       'success',
			messageContent:      ''
		}
	},
	created() {
		this.roleID = this.$route.params.id
		this.getRole()
	},
	methods: {
		/**
		 * Setup classes for the component based on the field type.
		 */
		classes (type) {
			return [
				'opk-field',
				type == 'text' ? 'regular-text' : ''
			];
		},
		/**
		 * Retrieve the role
		 */
		getRole() {
			this.loading = true

			axios.get( wpumRolesEditor.ajax, {
				params: {
					nonce:   wpumRolesEditor.getRoleNonce,
					action:  'wpum_get_role',
					role_id: this.roleID
				}
			})
			.then( response => {
				this.loading         = false
				this.roleName        = response.data.data.name
			})
			.catch( error => {
				this.loading = false
				console.log(error)
			})
		},
		/**
		 * Automatically hide a notice after it's displayed.
		*/
		resetNotice() {
			setTimeout( () => {
				this.showMessage = false
				this.showMessageSettings = false
			}, 3000)
		}
	}
}
</script>
