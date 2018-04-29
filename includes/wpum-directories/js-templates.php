<?php
/**
 * Holds all the Backbone js templates for the builder.
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

global $wp_widget_factory, $post;
$layouts = apply_filters( 'wpum-builder_panels_prebuilt_layouts', array() );
?>

<script type="text/template" id="wpum-builder-panels-builder">

	<div class="wpum-builder-panels-builder">

		<div class="wpum-builder-toolbar">

			<a class="wpum-tool-button wpum-widget-add" title="<?php esc_attr_e( 'Add Field' ) ?>">
				<span class="wpum-panels-icon wpum-panels-icon-add-widget"></span>
				<span class="wpum-button-text"><?php esc_html_e('Add Field') ?></span>
			</a>

			<a class="wpum-tool-button wpum-row-add" title="<?php esc_attr_e( 'Add Row' ) ?>">
				<span class="wpum-panels-icon wpum-panels-icon-add-row"></span>
				<span class="wpum-button-text"><?php esc_html_e('Add Row') ?></span>
			</a>

		</div>

		<div class="wpum-rows-container">

		</div>

		<div class="wpum-panels-welcome-message">
			<div class="wpum-message-wrapper">
				<?php
				printf(
					__( 'Add a %s, %s or %s to get started. Read our %s if you need help.' ),
					"<a href='#' class='wpum-tool-button wpum-widget-add'>" . __( 'Field' ) . "</a>",
					"<a href='#' class='wpum-tool-button wpum-row-add'>" . __( 'Row' ) . "</a>",
					"<a href='#' class='wpum-tool-button wpum-prebuilt-add'>" . __( 'Prebuilt Layout' ) . "</a>",
					"<a href='https://wpum-builder.com/page-builder/documentation/' target='_blank' rel='noopener noreferrer'>" . __( 'documentation' ) . "</a>"
				);
				?>
			</div>

		</div>

	</div>

</script>

<script type="text/template" id="wpum-builder-panels-builder-row">
	<div class="wpum-row-container ui-draggable wpum-row-color-{{%= rowColorLabel %}}">

		<div class="wpum-row-toolbar">
			{{% if( rowLabel ) { %}}
			<h3 class="wpum-row-label">{{%= rowLabel %}}</h3>
			{{% } %}}
			<span class="wpum-row-move wpum-tool-button"><span class="wpum-panels-icon wpum-panels-icon-move"></span></span>

			<span class="wpum-dropdown-wrapper">
				<a class="wpum-row-settings wpum-tool-button"><span class="wpum-panels-icon wpum-panels-icon-settings"></span></a>

				<div class="wpum-dropdown-links-wrapper">
					<ul>
						<li><a class="wpum-row-settings"><?php _e('Edit Row') ?></a></li>
						<li><a class="wpum-row-duplicate"><?php _e('Duplicate Row') ?></a></li>
						<li><a class="wpum-row-delete wpum-needs-confirm" data-confirm="<?php esc_attr_e('Are you sure?') ?>"><?php _e('Delete Row') ?></a></li>
					</ul>
					<div class="wpum-pointer"></div>
				</div>
			</span>
		</div>

		<div class="wpum-cells">

		</div>

	</div>
</script>

<script type="text/template" id="wpum-builder-panels-builder-cell">
	<div class="cell">
		<div class="resize-handle"></div>
		<div class="cell-wrapper widgets-container">
		</div>
	</div>
</script>

<script type="text/template" id="wpum-builder-panels-builder-widget">
	<div class="wpum-widget ui-draggable">
		<div class="wpum-widget-wrapper">
			<div class="title">
				<h4>{{%= title %}}</h4>
				<span class="actions">
					<a class="widget-duplicate"><?php _e('Duplicate') ?></a>
					<a class="widget-delete"><?php _e('Delete') ?></a>
				</span>
			</div>
			<small class="description">{{%= description %}}</small>
		</div>
	</div>
</script>

<script type="text/template" id="wpum-builder-panels-dialog">
	<div class="wpum-panels-dialog">

		<div class="wpum-overlay"></div>

		<div class="wpum-title-bar {{% if ( dialogIcon ) print( 'wpum-has-icon' ) %}}">
			{{% if ( ! _.isEmpty( dialogIcon ) ) { %}}
				<div class="wpum-panels-icon wpum-panels-icon-{{%- dialogIcon %}}" />
			{{% } %}}
			<h3 class="wpum-title{{% if ( editableLabel ) print(' wpum-title-editable')%}}"
			    {{% if ( editableLabel ) print('contenteditable="true" spellcheck="false" tabIndex="1"')%}}
				>{{%= title %}}</h3>
			<a class="wpum-previous wpum-nav"><span class="wpum-dialog-icon"></span></a>
			<a class="wpum-next wpum-nav"><span class="wpum-dialog-icon"></span></a>
			<a class="wpum-close"><span class="wpum-dialog-icon"></span></a>
		</div>

		<div class="wpum-toolbar">
			<div class="wpum-status">{{% if(typeof status != 'undefined') print(status); %}}</div>
			<div class="wpum-buttons">
				{{%= buttons %}}
			</div>
		</div>

		<div class="wpum-content panel-dialog">
			{{%= content %}}
		</div>

	</div>
</script>

<script type="text/template" id="wpum-builder-panels-dialog-builder">
	<div class="dialog-data">

		<h3 class="title"><?php _e('Page Builder') ?></h3>

		<div class="content">
			<div class="wpum-builder-panels-builder">

			</div>
		</div>

		<div class="buttons">
			<input type="button" class="button-primary wpum-close" value="<?php esc_attr_e('Done') ?>" />
		</div>

	</div>
</script>


<script type="text/template" id="wpum-builder-panels-dialog-tab">
	<li><a href="{{% if(typeof tab != 'undefined') { print ( '#' + tab ); } %}}">{{%= title %}}</a></li>
</script>

<script type="text/template" id="wpum-builder-panels-dialog-widgets">
	<div class="dialog-data">

		<h3 class="title"><?php printf( __('Add New Field %s'), '<span class="current-tab-title"></span>' ) ?></h3>

		<div class="left-sidebar">

			<input type="text" class="wpum-sidebar-search" placeholder="<?php esc_attr_e('Search Fields') ?>" />

			<ul class="wpum-sidebar-tabs">
			</ul>

		</div>

		<div class="content">
			<ul class="widget-type-list"></ul>
		</div>

		<div class="buttons">
			<input type="button" class="button-primary wpum-close" value="<?php esc_attr_e('Close') ?>" />
		</div>

	</div>
</script>

<script type="text/template" id="wpum-builder-panels-dialog-widgets-widget">
	<li class="widget-type">
		<div class="widget-type-wrapper">
			<h3>{{%= title %}}</h3>
			<small class="description">{{%= description %}}</small>
		</div>
	</li>
</script>

<script type="text/template" id="wpum-builder-panels-dialog-widget">
	<div class="dialog-data">

		<h3 class="title"><span class="widget-name"></span></h3>

		<div class="right-sidebar"></div>

		<div class="content">

			<div class="widget-form">
			</div>

		</div>

		<div class="buttons">
			<div class="action-buttons">
				<a class="wpum-delete"><?php _e('Delete') ?></a>
				<a class="wpum-duplicate"><?php _e('Duplicate') ?></a>
			</div>

			<input type="button" class="button-primary wpum-close" value="<?php esc_attr_e('Done') ?>" />
		</div>

	</div>
</script>

<script type="text/template" id="wpum-builder-panels-dialog-widget-sidebar-widget">
	<div class="wpum-widget">
		<h3>{{%= title %}}</h3>
		<small class="description">
			{{%= description %}}
		</small>
	</div>
</script>

<script type="text/template" id="wpum-builder-panels-dialog-row">
	<div class="dialog-data">

		<h3 class="title">
			{{%= title %}}
		</h3>

		<div class="right-sidebar"></div>

		<div class="content">

			<div class="row-set-form">
				<?php
				$cells_field = apply_filters('wpum-builder_panels_row_column_count_input', '<input type="number" min="1" max="12" name="cells" class="wpum-row-field" value="2" />');
				$ratios = apply_filters('wpum-builder_panels_column_ratios', array(
					'Even' => 1,
					'Golden' => 0.61803398,
					'Halves' => 0.5,
					'Thirds' => 0.33333333,
					'Diagon' => 0.41421356,
					'Hecton' => 0.73205080,
					'Hemidiagon' => 0.11803398,
					'Penton' => 0.27201964,
					'Trion' => 0.15470053,
					'Quadriagon' => 0.207,
					'Biauron' => 0.30901699,
					'Bipenton' => 0.46,
				) );
				$ratio_field = '<select name="ratio" class="wpum-row-field">';
				foreach( $ratios as $name => $value ) {
					$ratio_field .= '<option value="' . esc_attr($value) .  '">' . esc_html($name . ' (' . round($value, 3) . ')') . '</option>';
				}
				$ratio_field .= '</select>';

				$direction_field = '<select name="ratio_direction" class="wpum-row-field">';
				$direction_field .= '<option value="right">' . esc_html__('Left to Right') . '</option>';
				$direction_field .= '<option value="left">' . esc_html__('Right to Left') . '</option>';
				$direction_field .= '</select>';

				printf(
					preg_replace(
						array(
							'/1\{ *(.*?) *\}/',
						),
						array(
							'<strong>$1</strong>',
						),
						__('1{Set row layout}: %1$s columns with a ratio of %2$s going from %3$s')
					),
					$cells_field,
					$ratio_field,
					$direction_field
				);
				echo '<button class="button-secondary set-row">' . esc_html__('Set') . '</button>';
				?>
			</div>

			<div class="row-preview">

			</div>

		</div>

		<div class="buttons">
			{{% if( dialogType == 'edit' ) { %}}
				<div class="action-buttons">
					<a class="wpum-delete"><?php _e('Delete') ?></a>
					<a class="wpum-duplicate"><?php _e('Duplicate') ?></a>
				</div>
			{{% } %}}

			{{% if( dialogType == 'create' ) { %}}
				<input type="button" class="button-primary wpum-insert" value="<?php esc_attr_e('Insert') ?>" />
			{{% } else { %}}
				<input type="button" class="button-primary wpum-save" value="<?php esc_attr_e('Done') ?>" />
			{{% } %}}
		</div>

	</div>
</script>

<script type="text/template" id="wpum-builder-panels-dialog-row-cell-preview">
	<div class="preview-cell" style="width: {{%- weight*100 %}}%">
		<div class="preview-cell-in">
			<div class="preview-cell-weight">{{% print(Math.round(weight * 1000) / 10) %}}</div>
		</div>
	</div>
</script>

<script type="text/template" id="wpum-builder-panels-context-menu">
	<div class="wpum-panels-contextual-menu"></div>
</script>

<script type="text/template" id="wpum-builder-panels-context-menu-section">
	<div class="wpum-section">
		<h5>{{%- settings.sectionTitle %}}</h5>

		{{% if( settings.search ) { %}}
			<div class="wpum-search-wrapper">
				<input type="text" placeholder="{{%- settings.searchPlaceholder %}}" />
			</div>
		{{% } %}}
		<ul class="wpum-items">
			{{% for( var k in items ) { %}}
				<li data-key="{{%- k %}}" class="wpum-item {{% if( !_.isUndefined( items[k].confirm ) && items[k].confirm ) { print( 'wpum-confirm' ); } %}}">{{%= items[k][settings.titleKey] %}}</li>
			{{% } %}}
		</ul>
		{{% if( settings.search ) { %}}
		<div class="wpum-no-results">
			<?php _e('No Results') ?>
		</div>
		{{% } %}}
	</div>
</script>
