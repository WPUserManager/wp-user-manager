<template>
	<section id="wpum-fields-editor-list">
		<h1 class="wp-heading-inline">{{labels.fields_page_title}} "{{group_name}}"</h1>
		<router-link to="/" class="page-title-action wpum-icon-button circular" :data-balloon="labels.fields_go_back" data-balloon-pos="down"><span class="dashicons dashicons-arrow-left-alt"></span></router-link>
		<a href="#" class="page-title-action wpum-icon-button" @click="openCreateNewFieldDialog()"><span class="dashicons dashicons-plus-alt"></span> {{labels.fields_add_new}}</a>

		<v-dialog/>
		<modals-container/>

		<br/>

		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th scope="col" class="order-column" :data-balloon="labels.table_drag_tooltip" data-balloon-pos="right"><span class="dashicons dashicons-menu"></span></th>
					<th scope="col" class="column-primary">{{labels.fields_name}}</th>
					<th scope="col">{{labels.fields_type}}</th>
					<th scope="col">{{labels.fields_required}}</th>
					<th scope="col">{{labels.fields_visibility}}</th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td class="order-anchor align-middle">
						<span class="dashicons dashicons-menu"></span>
					</td>
					<td class="column-primary">
						Field name
						<div class="row-actions">
							<span>
								<a href="#">Edit field</a> | <a href="#">Delete field</a>
							</span>
						</div>
					</td>
					<td>
						Text
					</td>
					<td>
						no
					</td>
					<td>
						more
					</td>
				</tr>
			</tbody>
		</table>

	</section>
</template>

<script>
import balloon from 'balloon-css'
import findGroupIndex from 'lodash.findindex'
import PremiumDialog from './dialogs/dialog-premium'

export default {
	name: 'fields-editor-interface',
	data() {
		return {
			addonInstalled: wpumFieldsEditor.is_addon_installed,
			labels:         wpumFieldsEditor.labels,
			group_id:       '',
			group_name:     '',
		}
	},
	/**
	 * Detect the selected group to edit and retrieve group id and group name.
	 */
	created() {
		const group_id      = this.$route.params.id.toString()
		const selectedGroup = findGroupIndex( wpumFieldsEditor.groups, function(o) { return o.id == group_id })
		this.group_id       = group_id
		this.group_name     = wpumFieldsEditor.groups[selectedGroup].name
	},
	methods: {
		/**
		 * Open the create new field dialog. Only works when the premium addon is installed.
		 */
		openCreateNewFieldDialog() {
			if( wpumFieldsEditor.is_addon_installed ) {

			} else {
				this.$modal.show( PremiumDialog, {},{ height: '220px' })
			}
		}
	}
}
</script>

<style lang="scss">
.wpum-icon-button {
	&.circular {
		padding: 4px !important;
		border-radius: 9999px !important;
	}
	span.dashicons {
		width: 16px;
		height: 16px;
		font-size: 16px;
		vertical-align: inherit;
		position: relative;
		top: 3px;
	}
}
</style>
