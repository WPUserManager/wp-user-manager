<?php
/**
 * Functions meant to be used within the administration only.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/gpl-license GNU Public License
 * @since       1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Retrieve pages from the database and cache them as transient.
 *
 * @return array
 */
function wpum_get_pages( $force = false ) {

	$pages = [];

	if ( ( ! isset( $_GET['page'] ) || 'wpum-settings' != $_GET['page'] ) && ! $force ) {
		return $pages;
	}

	$transient =  get_transient( 'wpum_get_pages' );

	if ( $transient ) {
		$pages = $transient;
	} else {
		$available_pages = get_pages();
		if ( ! empty( $available_pages ) ) {
			foreach ( $available_pages as $page ) {
				$pages[] = array(
					'value' => $page->ID,
					'label' => $page->post_title
				);
			}
			set_transient( 'wpum_get_pages', $pages, DAY_IN_SECONDS );
		}
	}
	return $pages;
}

/**
 * Retrieve the options for the available login methods.
 *
 * @return array
 */
function wpum_get_login_methods() {
	return apply_filters( 'wpum_get_login_methods', array(
		'username'       => __( 'Username only', 'wpum' ),
		'email'          => __( 'Email only', 'wpum' ),
		'username_email' => __( 'Username or Email', 'wpum' ),
	) );
}

/**
 * Retrieve a list of all user roles and cache them into a transient.
 *
 * @return array
 */
function wpum_get_roles( $force = false ) {

	$roles = [];

	if ( ( ! isset( $_GET['page'] ) || 'wpum-settings' != $_GET['page'] ) && ! $force ) {
		return $roles;
	}

	$transient =  get_transient( 'wpum_get_roles' );

	if ( $transient ) {
		$roles = $transient;
	} else {

		global $wp_roles;
		$available_roles = $wp_roles->get_names();

		foreach ( $available_roles as $role_id => $role ) {
			if( $role_id == 'administrator' ) {
				continue;
			}
			$roles[] = array(
				'value' => esc_attr( $role_id ),
				'label' => esc_html( $role ),
			);
		}
		set_transient( 'wpum_get_roles', $roles, DAY_IN_SECONDS );

	}

	return $roles;

}

/**
 * Installs the default fields group into the WordPress database.
 *
 * @return void
 */
function wpum_install_default_field_group() {

	if( ! get_option( 'wpum_version_upgraded_from' ) ) {
		$default_group = new WPUM_Field_Group();
		$default_group->add(
			[
				'id'   => 1,
				'name' => esc_html__( 'Primary fields' )
			]
		);
	}

}

/**
 * Install the default primary fields within the default group.
 *
 * @return void
 */
function wpum_install_fields() {

	if( ! get_option( 'wpum_version_upgraded_from' ) ) {

		$group_id = 1;

		$fields = [
			array(
				'id'         => 1,
				'group_id'   => $group_id,
				'type'       => 'username',
				'name'       => 'Username',
				'metas'      => [
					'required'      => true,
					'visibility'    => 'public',
					'user_meta_key' => 'username'
				]
			),
			array(
				'id'         => 2,
				'group_id'   => $group_id,
				'type'       => 'user_email',
				'name'       => 'Email',
				'metas'      => [
					'required'      => true,
					'visibility'    => 'public',
					'editing'       => 'public',
					'user_meta_key' => 'user_email'
				]
			),
			array(
				'id'         => 3,
				'group_id'   => $group_id,
				'type'       => 'user_password',
				'name'       => 'Password',
				'metas'      => [
					'required'      => true,
					'user_meta_key' => 'user_password'
				]
			),
			array(
				'id'         => 4,
				'group_id'   => $group_id,
				'type'       => 'user_firstname',
				'name'       => 'First name',
				'metas'      => [
					'visibility'    => 'public',
					'editing'       => 'public',
					'user_meta_key' => 'firstname'
				]
			),
			array(
				'id'         => 5,
				'group_id'   => $group_id,
				'type'       => 'user_lastname',
				'name'       => 'Last name',
				'metas'      => [
					'visibility'    => 'public',
					'editing'       => 'public',
					'user_meta_key' => 'lastname'
				]
			),
			array(
				'id'         => 6,
				'group_id'   => $group_id,
				'type'       => 'user_nickname',
				'name'       => 'Nickname',
				'metas'      => [
					'required'      => true,
					'visibility'    => 'public',
					'editing'       => 'public',
					'user_meta_key' => 'nickname'
				]
			),
			array(
				'id'         => 7,
				'group_id'   => $group_id,
				'type'       => 'user_displayname',
				'name'       => 'Display name',
				'metas'      => [
					'required'      => true,
					'visibility'    => 'public',
					'editing'       => 'public',
					'user_meta_key' => 'display_name'
				]
			),
			array(
				'id'         => 8,
				'group_id'   => $group_id,
				'type'       => 'user_website',
				'name'       => 'Website',
				'metas'      => [
					'visibility'    => 'public',
					'editing'       => 'public',
					'user_meta_key' => 'user_url'
				]
			),
			array(
				'id'         => 9,
				'group_id'   => $group_id,
				'type'       => 'user_description',
				'name'       => 'Description',
				'metas'      => [
					'visibility'    => 'public',
					'editing'       => 'public',
					'user_meta_key' => 'description'
				]
			),
			array(
				'id'         => 10,
				'group_id'   => $group_id,
				'type'       => 'user_avatar',
				'name'       => 'Profile picture',
				'metas'      => [
					'visibility'    => 'public',
					'editing'       => 'public',
					'user_meta_key' => 'current_user_avatar'
				]
			)
		];

		$order = 0;

		foreach( $fields as $field ) {

			$order++;
			$field['field_order'] = $order;

			$save_field = new WPUM_Field();
			$save_field->add( $field );

			foreach( $field['metas'] as $meta_key => $meta_value ) {
				$save_field->add_meta( $meta_key, $meta_value );
			}

		}

	}

}

/**
 * An array of primary field types.
 *
 * @return array
 */
function wpum_get_primary_field_types() {

	$types = [
		'username',
		'user_email',
		'user_password',
		'user_firstname',
		'user_lastname',
		'user_nickname',
		'user_displayname',
		'user_website',
		'user_description',
		'user_avatar'
	];

	return apply_filters( 'wpum_get_primary_field_types', $types );

}

/**
 * Setup the tabs for the edit field dialog in the admin panel.
 *
 * @return array
 */
function wpum_get_edit_field_dialog_tabs() {

	$tabs = [
		array(
			'id'   => 'general',
			'name' => esc_html__( 'General' )
		),
		array(
			'id'   => 'validation',
			'name' => esc_html__( 'Validation' )
		),
		array(
			'id'   => 'privacy',
			'name' => esc_html__( 'Privacy' )
		),
		array(
			'id'   => 'permissions',
			'name' => esc_html__( 'Permissions' )
		),
	];

	return apply_filters( 'wpum_get_fields_editor_edit_tabs', $tabs );

}
