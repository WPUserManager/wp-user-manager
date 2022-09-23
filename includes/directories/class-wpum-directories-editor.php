<?php
/**
 * Handles all registration of users directories.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WPUM\Carbon_Fields\Container;
use WPUM\Carbon_Fields\Field;

/**
 * The class that handles the user directories.
 */
class WPUM_Directories_Editor {

	/**
	 * Holds the layout builder object.
	 *
	 * @var object
	 */
	protected $builder;

	/**
	 * Initialisation
	 */
	public function init() {
		add_action( 'init', array( $this, 'register_post_type' ), 0 );
		add_action( 'carbon_fields_register_fields', array( $this, 'maybe_register_directory_settings' ) );
		add_action( 'admin_footer', array( $this, 'css' ) );

		if ( is_admin() ) {
			add_filter( 'manage_edit-wpum_directory_columns', array( $this, 'post_type_columns' ) );
			add_action( 'manage_wpum_directory_posts_custom_column', array( $this, 'post_type_columns_content' ), 2, 2 );
			add_filter( 'post_row_actions', array( $this, 'remove_action_rows' ), 10, 2 );
			add_filter( 'post_updated_messages', array( $this, 'post_updated_messages' ) );
			add_filter( 'bulk_post_updated_messages', array( $this, 'bulk_post_updated_messages' ) );
		}
	}

	/**
	 * Register the directory post type.
	 *
	 * @return void
	 */
	public function register_post_type() {

		$labels = array(
			'name'                  => _x( 'Users Directories', 'Post Type General Name', 'wp-user-manager' ),
			'singular_name'         => _x( 'Directory', 'Post Type Singular Name', 'wp-user-manager' ),
			'menu_name'             => __( 'Users Directories', 'wp-user-manager' ),
			'name_admin_bar'        => __( 'Directory', 'wp-user-manager' ),
			'archives'              => __( 'Directory Archives', 'wp-user-manager' ),
			'attributes'            => __( 'Directory Attributes', 'wp-user-manager' ),
			'parent_item_colon'     => __( 'Parent Directory:', 'wp-user-manager' ),
			'all_items'             => __( 'Directories', 'wp-user-manager' ),
			'add_new_item'          => __( 'Add new directory', 'wp-user-manager' ),
			'add_new'               => __( 'Add New', 'wp-user-manager' ),
			'new_item'              => __( 'New Directory', 'wp-user-manager' ),
			'edit_item'             => __( 'Edit Directory', 'wp-user-manager' ),
			'update_item'           => __( 'Update Directory', 'wp-user-manager' ),
			'view_item'             => __( 'View Directory', 'wp-user-manager' ),
			'view_items'            => __( 'View Directories', 'wp-user-manager' ),
			'search_items'          => __( 'Search Directory', 'wp-user-manager' ),
			'not_found'             => __( 'Not found', 'wp-user-manager' ),
			'not_found_in_trash'    => __( 'Not found in Trash', 'wp-user-manager' ),
			'featured_image'        => __( 'Featured Image', 'wp-user-manager' ),
			'set_featured_image'    => __( 'Set featured image', 'wp-user-manager' ),
			'remove_featured_image' => __( 'Remove featured image', 'wp-user-manager' ),
			'use_featured_image'    => __( 'Use as featured image', 'wp-user-manager' ),
			'insert_into_item'      => __( 'Insert into directory', 'wp-user-manager' ),
			'uploaded_to_this_item' => __( 'Uploaded to this directory', 'wp-user-manager' ),
			'items_list'            => __( 'Directories list', 'wp-user-manager' ),
			'items_list_navigation' => __( 'Directories list navigation', 'wp-user-manager' ),
			'filter_items_list'     => __( 'Filter directories list', 'wp-user-manager' ),
		);
		$args   = array(
			'label'               => __( 'Directory', 'wp-user-manager' ),
			'labels'              => $labels,
			'supports'            => array( 'title' ),
			'hierarchical'        => false,
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => 'users.php',
			'menu_position'       => 5,
			'show_in_admin_bar'   => false,
			'show_in_nav_menus'   => false,
			'can_export'          => true,
			'has_archive'         => false,
			'exclude_from_search' => true,
			'publicly_queryable'  => true,
			'capability_type'     => 'page',
			'show_in_rest'        => true,
		);
		register_post_type( 'wpum_directory', $args );

	}

	/**
	 * Get post type for page request (in admin)
	 *
	 * @return string
	 */
	public function get_post_type() {
		global $pagenow;

		if ( isset( $_GET['post_type'] ) ) { // phpcs:ignore
			return filter_input( INPUT_GET, 'post_type' );
		}

		if ( ! isset( $_GET['post'] ) && ! isset( $_POST['post_ID'] ) ) { // phpcs:ignore
			return '';
		}

		$post_id = filter_input( INPUT_GET, 'post', FILTER_VALIDATE_INT );
		if ( empty( $post_id ) ) {
			$post_id = filter_input( INPUT_POST, 'post_ID', FILTER_VALIDATE_INT );
		}

		$post = get_post( $post_id );

		if ( empty( $post ) || empty( $post->post_type ) ) {
			return '';
		}

		return $post->post_type;
	}

	/**
	 * Register directory settings if a directory page
	 */
	public function maybe_register_directory_settings() {
		$post_type = $this->get_post_type();
		if ( 'wpum_directory' !== $post_type ) {
			return;
		}

		$this->register_directory_settings();
	}

	/**
	 * Register settings for the directory
	 *
	 * @return void
	 */
	public function register_directory_settings() {
		do_action( 'wpum_before_register_directory_settings' );

		$general_settings_fields = array(
			Field::make( 'multiselect', 'directory_assigned_roles', esc_html__( 'User roles', 'wp-user-manager' ) )
				 ->set_help_text( esc_html__( 'Leave blank to display all user roles.', 'wp-user-manager' ) )
				 ->add_options( $this->get_roles() ),
			Field::make( 'text', 'directory_excluded_users', esc_html__( 'Exclude users', 'wp-user-manager' ) )
				 ->set_attribute( 'placeholder', esc_html__( 'Example: 1, 6, 32', 'wp-user-manager' ) )
				 ->set_help_text( esc_html__( 'Comma separated list of users id you wish to exclude.', 'wp-user-manager' ) ),
			Field::make( 'text', 'directory_profiles_per_page', esc_html__( 'Profiles per page', 'wp-user-manager' ) )
				 ->set_attribute( 'type', 'number' )
				 ->set_attribute( 'min', 1 )
				 ->set_help_text( esc_html__( 'Select how many profiles you wish to display per page.', 'wp-user-manager' ) ),
		);

		Container::make( 'post_meta', esc_html__( 'General settings', 'wp-user-manager' ) )
				 ->where( 'post_type', '=', 'wpum_directory' )
				 ->add_fields( apply_filters( 'wpum_directory_general_settings', $general_settings_fields ) );

		// translators: %s url to custom fields addon
		$search_field_text = sprintf( __( 'Select the fields to search in. Search custom fields using the <a href="%s" target="_blank">Custom Fields</a> addon.', 'wp-user-manager' ), 'https://wpusermanager.com/addons/custom-fields?utm_source=WP%20User%20Manager&utm_medium=insideplugin&utm_campaign=WP%20User%20Manager&utm_content=edit-directory' );
		$search_field_text = apply_filters( 'wpum_directory_search_fields_help_text', $search_field_text );

		$search_fields = array(
			Field::make( 'checkbox', 'directory_search_form', esc_html__( 'Display search form', 'wp-user-manager' ) )
				 ->set_option_value( 'yes' )
				 ->set_help_text( esc_html__( 'Enable this option to display the user search form', 'wp-user-manager' ) ),
			Field::make( 'multiselect', 'directory_search_fields', esc_html__( 'Search fields', 'wp-user-manager' ) )
				 ->set_help_text( $search_field_text )
				 ->add_options( $this->get_search_fields() )
				 ->set_default_value( array( 'first_name', 'last_name' ) ),
		);

		Container::make( 'post_meta', esc_html__( 'Search', 'wp-user-manager' ) )
				 ->where( 'post_type', '=', 'wpum_directory' )
				 ->add_fields( apply_filters( 'wpum_directory_search_settings', $search_fields ) );

		$sorting_fields = array(
			Field::make( 'checkbox', 'directory_display_sorter', esc_html__( 'Display sorter', 'wp-user-manager' ) )
				 ->set_option_value( 'yes' )
				 ->set_help_text( esc_html__( 'Enable this setting to display a dropdown menu into the directory with the sorting options.', 'wp-user-manager' ) ),
			Field::make( 'checkbox', 'directory_display_amount_filter', esc_html__( 'Display amount filter', 'wp-user-manager' ) )
				 ->set_option_value( 'yes' )
				 ->set_help_text( esc_html__( 'Enable this setting to display a dropdown menu into the directory with the results amount filter.', 'wp-user-manager' ) ),
			Field::make( 'select', 'directory_sorting_method', esc_html__( 'Sorting method', 'wp-user-manager' ) )
				 ->set_help_text( esc_html__( 'Select the sorting method for the directory. If the sorter field is visible, this will be used as default option.', 'wp-user-manager' ) )
				->add_options( apply_filters( 'wpum_directory_sort_options', array(
					'newest'    => esc_html__( 'Newest users first', 'wp-user-manager' ),
					'oldest'    => esc_html__( 'Oldest users first', 'wp-user-manager' ),
					'name'      => esc_html__( 'First name', 'wp-user-manager' ),
					'last_name' => esc_html__( 'Last Name', 'wp-user-manager' ),
				) ) ),
		);

		Container::make( 'post_meta', esc_html__( 'Sorting', 'wp-user-manager' ) )
				 ->where( 'post_type', '=', 'wpum_directory' )
				 ->add_fields( apply_filters( 'wpum_directory_sorting_settings', $sorting_fields ) );

		$template_fields = array(
			Field::make( 'select', 'directory_template', esc_html__( 'Template', 'wp-user-manager' ) )
				 ->set_help_text( esc_html__( 'Select a template for this directory.', 'wp-user-manager' ) )
				 ->add_options( wpum_get_directory_templates() ),
			Field::make( 'select', 'directory_user_template', esc_html__( 'User Template', 'wp-user-manager' ) )
				 ->set_help_text( esc_html__( 'Select a template for the users within this directory.', 'wp-user-manager' ) )
				 ->add_options( wpum_get_directory_user_templates() ),
		);

		Container::make( 'post_meta', esc_html__( 'Directory template', 'wp-user-manager' ) )
				 ->where( 'post_type', '=', 'wpum_directory' )
				 ->set_context( 'side' )
				 ->set_priority( 'default' )
				 ->add_fields( apply_filters( 'wpum_directory_template_settings', $template_fields ) );

		do_action( 'wpum_after_register_directory_settings' );
	}

	/**
	 * Return an array containing user roles.
	 *
	 * @return array
	 */
	private function get_roles() {

		$roles = array();

		foreach ( wpum_get_roles( true, true ) as $role ) {
			$roles[ $role['value'] ] = $role['label'];
		}

		return $roles;

	}

	/**
	 * @return array
	 */
	protected function get_search_fields() {
		$fields = array(
			'first_name'  => __( 'First Name', 'wp-user-manager' ),
			'last_name'   => __( 'Last Name', 'wp-user-manager' ),
			'description' => __( 'Description', 'wp-user-manager' ),
		);

		return apply_filters( 'wpum_directory_search_fields', $fields );
	}

	/**
	 * Adjust layout elements of the directory editor.
	 *
	 * @return void
	 */
	public function css() {

		$screen = get_current_screen();

		if ( 'wpum_directory' !== $screen->id ) {
			return;
		}

		?>
		<style>
		#edit-slug-box {display:none;}
		</style>
		<?php
	}

	/**
	 * Modifies the list of columns available into the directory post type.
	 *
	 * @access public
	 * @param mixed $columns
	 * @return array $columns
	 */
	public function post_type_columns( $columns ) {
		if ( ! is_array( $columns ) ) {
			$columns = array();
		}

		unset( $columns['date'], $columns['author'] );

		$columns['roles']             = esc_html__( 'User Roles', 'wp-user-manager' );
		$columns['search_form']       = esc_html__( 'Search form', 'wp-user-manager' );
		$columns['profiles_per_page'] = esc_html__( 'Profiles per page', 'wp-user-manager' );
		$columns['shortcode']         = esc_html__( 'Shortcode', 'wp-user-manager' );

		return $columns;
	}

	/**
	 * Adds the content to the custom columns for the directory post type
	 *
	 * @param string $column
	 * @param int    $post_id
	 *
	 * @return void
	 */
	public function post_type_columns_content( $column, $post_id ) {
		switch ( $column ) {
			case 'roles':
				$roles = \WPUM\carbon_get_post_meta( $post_id, 'directory_assigned_roles' );
				if ( $roles ) {
					echo esc_html( implode( ', ', array_map( 'ucfirst', $roles ) ) );
				} else {
					echo esc_html__( 'All', 'wp-user-manager' );
				}
				break;
			case 'search_form':
				if ( \WPUM\carbon_get_post_meta( $post_id, 'directory_search_form' ) ) {
					echo '<span class="dashicons dashicons-yes"></span>';
				} else {
					echo '<span class="dashicons dashicons-no"></span>';
				}
				break;
			case 'profiles_per_page':
				echo esc_html( \WPUM\carbon_get_post_meta( $post_id, 'directory_profiles_per_page' ) );
				break;
			case 'shortcode':
				echo esc_html( '[wpum_user_directory id="' . $post_id . '"]' );
				break;
		}
	}

	/**
	 * Modifies the action links into the post type page.
	 *
	 * @access public
	 *
	 * @param array    $actions
	 * @param \WP_Post $post
	 *
	 * @return array $actions array contains all action links.
	 */
	public function remove_action_rows( $actions, $post ) {
		if ( 'wpum_directory' === $post->post_type ) {
			unset( $actions['inline hide-if-no-js'] );
			unset( $actions['view'] );
		}

		return $actions;
	}

	/**
	 * Modifies the post update messages for this post type.
	 *
	 * @param array $messages
	 *
	 * @return array
	 */
	public function post_updated_messages( $messages ) {
		global $post, $post_ID;

		$revision = filter_input( INPUT_GET, 'revision', FILTER_VALIDATE_INT );

		$messages['wpum_directory'] = array(
			0 => '', // Unused. Messages start at index 1.
			1 => __( 'Directory updated.', 'wp-user-manager' ),
			2 => __( 'Custom field updated.', 'wp-user-manager' ),
			3 => __( 'Custom field deleted.', 'wp-user-manager' ),
			4 => __( 'Directory updated.', 'wp-user-manager' ),
			/* translators: %s: date and time of the revision */
			5 => $revision ? sprintf( __( 'Directory restored to revision from %s', 'wp-user-manager' ), wp_post_revision_title( $revision, false ) ) : false,
			/* translators: %s: directory shortcode */
			6 => sprintf( __( 'Directory created. Use the following shortcode to display this directory %s', 'wp-user-manager' ), '<code>[wpum_user_directory id="' . $post_ID . '"]</code>' ),
			7 => __( 'Directory saved.', 'wp-user-manager' ),
			8 => __( 'Directory submitted.', 'wp-user-manager' ),
		);

		return $messages;
	}

	/**
	 * Modifies the text of the trash function.
	 *
	 * @param array $bulk_messages
	 *
	 * @return array $bulk_messages
	 */
	public function bulk_post_updated_messages( $bulk_messages ) {
		global $post, $post_ID;

		$updated   = isset( $_REQUEST['updated'] ) ? filter_var( $_REQUEST['updated'], FILTER_VALIDATE_INT ) : false; // phpcs:ignore
		$locked    = isset( $_REQUEST['locked'] ) ? filter_var( $_REQUEST['locked'], FILTER_VALIDATE_INT ) : false; // phpcs:ignore
		$deleted   = isset( $_REQUEST['deleted'] ) ? filter_var( $_REQUEST['deleted'], FILTER_VALIDATE_INT ) : false; // phpcs:ignore
		$trashed   = isset( $_REQUEST['trashed'] ) ? filter_var( $_REQUEST['trashed'], FILTER_VALIDATE_INT ) : false; // phpcs:ignore
		$untrashed = isset( $_REQUEST['untrashed'] ) ? filter_var( $_REQUEST['untrashed'], FILTER_VALIDATE_INT ) : false; // phpcs:ignore

		$bulk_counts = array(
			'updated'   => $updated ? $updated : 0,
			'locked'    => $locked ? $locked : 0,
			'deleted'   => $deleted ? $deleted : 0,
			'trashed'   => $trashed ? $trashed : 0,
			'untrashed' => $untrashed ? $untrashed : 0,
		);

		$bulk_messages['wpum_directory'] = array(
			// translators: %s number of directories updated
			'updated'   => _n( '%s directory updated.', '%s directory updated.', $bulk_counts['updated'], 'wp-user-manager' ),
			// translators: %s number of directories locked
			'locked'    => _n( '%s directory not updated, somebody is editing it.', '%s directories not updated, somebody is editing them.', $bulk_counts['locked'], 'wp-user-manager' ),
			// translators: %s number of directories deleted
			'deleted'   => _n( '%s directory permanently deleted.', '%s directories permanently deleted.', $bulk_counts['deleted'], 'wp-user-manager' ),
			// translators: %s number of directories deleted
			'trashed'   => _n( '%s directory has been deleted.', '%s directories have been deleted.', $bulk_counts['trashed'], 'wp-user-manager' ),
			// translators: %s number of directories trashed
			'untrashed' => _n( '%s directory restored from the Trash.', '%s directories restored from the Trash.', $bulk_counts['untrashed'], 'wp-user-manager' ),
		);

		return $bulk_messages;
	}
}
