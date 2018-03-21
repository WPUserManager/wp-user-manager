<template>
	<section id="wpum-fields-editor-wrapper">

		<h1 class="wp-heading-inline" v-text="sanitized(pageTitle)"></h1>
		<a href="#" class="page-title-action" id="wpum-add-field-group"><span class="dashicons dashicons-plus-alt"></span> <span v-text="sanitized(labels.table_add_group)"></span></a>

		<br/><br/>

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
				<tr>
					<td class="order-anchor">
						<span class="dashicons dashicons-menu"></span>
					</td>
					<td class="column-username has-row-actions column-primary" data-colname="Event">
						<strong><a href="#">WordCamp Philly</a></strong><br>
						<div class="row-actions">
							<span>
								<a href="#" v-text="sanitized(labels.table_edit_group)"></a>
							</span>
						</div>
						<button type="button" class="toggle-row">
							<span class="screen-reader-text">Show more details</span>
						</button>
					</td>
					<td data-colname="Start Date">2016 </td>
					<td data-colname="End Date">2017</td>
					<td data-colname="End Date">2017</td>
					<td>
						<button type="submit" class="button"><span class="dashicons dashicons-admin-settings"></span> <span v-text="sanitized(labels.table_edit_fields)"></span></button>
						<button type="submit" class="button delete-btn"><span class="dashicons dashicons-trash"></span> <span v-text="sanitized(labels.table_delete_group)"></span></button>
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
			labels: wpumFieldsEditor.labels
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
			return group_id === 1 ? true : false
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
}

</style>

