<template>
	<section id="wpum-fields-editor-wrapper">

		<h1 class="wp-heading-inline" v-text="sanitized(pageTitle)"></h1>
		<a href="#" class="page-title-action" id="wpum-add-field-group"><span class="dashicons dashicons-plus-alt"></span> <span v-text="sanitized(labels.table_add_group)"></span></a>

		<br/><br/>

		<wp-notice type="success">Notice message goes here.</wp-notice>

		<table class="wp-list-table widefat fixed striped wpum-fields-groups-table">
			<thead>
				<tr>
					<th scope="col" class="order-column"><span class="dashicons dashicons-menu"></span></th>
					<th scope="col" class="column-primary" v-text="sanitized(labels.table_name)"></th>
					<th scope="col" v-text="sanitized(labels.table_desc)"></th>
					<th scope="col" class="small-column" v-text="sanitized(labels.table_default)"></th>
					<th scope="col" class="small-column" v-text="sanitized(labels.table_fields)"></th>
					<th scope="col" v-text="sanitized(labels.table_actions)"></th>
				</tr>
			</thead>
			<tbody>
				<tr v-for="group in groups" :key="group.id">
					<td class="order-anchor align-middle">
						<span class="dashicons dashicons-menu"></span>
					</td>
					<td class="column-username has-row-actions column-primary" data-colname="Event">
						<strong><a href="#">{{group.name}}</a></strong><br>
						<div class="row-actions">
							<span>
								<a href="#" v-text="sanitized(labels.table_edit_group)"></a>
							</span>
						</div>
						<button type="button" class="toggle-row">
							<span class="screen-reader-text">Show more details</span>
						</button>
					</td>
					<td data-colname="Start Date">{{group.description}}</td>
					<td data-colname="End Date">
						<span class="dashicons dashicons-yes" v-if="isDefault(group.id)"></span>
					</td>
					<td data-colname="End Date">{{group.fields}}</td>
					<td class="align-middle">
						<button type="submit" class="button"><span class="dashicons dashicons-admin-settings"></span> <span v-text="sanitized(labels.table_edit_fields)"></span></button>
						<button type="submit" class="button delete-btn" v-if="! isDefault(group.id)"><span class="dashicons dashicons-trash"></span> <span v-text="sanitized(labels.table_delete_group)"></span></button>
					</td>
				</tr>
			</tbody>

		</table>

	</section>
</template>

<script>
import Sanitize from 'sanitize-html'
import GroupsSelector from './groups-selector'

export default {
	name: 'editor-interface',
	components: {
		GroupsSelector
	},
	data() {
		return {
			addonInstalled: wpumFieldsEditor.is_addon_installed,
			pageTitle: wpumFieldsEditor.page_title,
			labels: wpumFieldsEditor.labels,
			groups: wpumFieldsEditor.groups
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
		 * Determine if the field group is the default one or not.
		 * Needed to check wether we can delete it or not.
		 */
		isDefault( group_id ) {
			return group_id === '1' ? true : false
		}
	}
}
</script>

<style lang="scss">
#wpum-add-field-group {
	span.dashicons {
		width: 16px;
		height: 16px;
		font-size: 16px;
		vertical-align: inherit;
		position: relative;
		top: 3px;
		margin-right: 2px;
	}
}

.order-column {
	width: 30px;
	border-right: 1px solid #e1e1e1;
	text-align: center !important;
}

.order-anchor {
	border-right: 1px solid #e1e1e1;
	text-align: center !important;
}

.small-column {
	width: 100px;
}

.wpum-fields-groups-table {
	.button {
		margin-right: 5px;
		&:last-child {
			margin-right: 0;
		}
		span.dashicons {
			position: relative;
			top: 3px;
			margin-right: 3px;
		}
		&.delete-btn {
			&:hover {
				span.dashicons {
					color: red;
				}
			}
		}
	}

	.dashicons-yes {
		color: green;
	}

	.align-middle {
		vertical-align: middle;
	}

	tr:hover {
		background: #e7f7ff
	}

}

#wpum-fields-editor-wrapper {
	.vue-wp-notice {
		margin-right: 0;
		margin-bottom: 20px;
	}
}

</style>

