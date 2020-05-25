<?php
/**
 * Register all the shortcodes for WPUM.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/gpl-license GNU Public License
 * @since       1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Login form shortcode.
 * Vuejs handles the display of the form.
 *
 * @param array  $atts
 * @param string $content
 * @return void
 */
function wpum_login_form( $atts, $content = null ) {

	extract(
		shortcode_atts(
			array(
				'psw_link'      => '',
				'register_link' => '',
			),
			$atts
		)
	);

	ob_start();

	if ( is_user_logged_in() && ! ( isset( $_GET['context'] ) && 'edit' === $_GET['context'] ) ) {
		WPUM()->templates
			->get_template_part( 'already-logged-in' );
	} else {
		echo WPUM()->forms->get_form( 'login', $atts );

		WPUM()->templates
			->set_template_data( $atts )
			->get_template_part( 'action-links' );
	}

	$output = ob_get_clean();

	return $output;

}
add_shortcode( 'wpum_login_form', 'wpum_login_form' );

/**
 * Password recovery shortcode.
 *
 * @param array  $atts
 * @param string $content
 * @return void
 */
function wpum_password_recovery( $atts, $content = null ) {

	extract(
		shortcode_atts(
			array(
				'login_link'    => '',
				'register_link' => '',
			),
			$atts
		)
	);

	ob_start();

	if ( is_user_logged_in() && ! ( isset( $_GET['context'] ) && 'edit' === $_GET['context'] ) ) {
		WPUM()->templates
			->get_template_part( 'already-logged-in' );
	} else {
		echo WPUM()->forms->get_form( 'password-recovery', $atts );
	}

	$output = ob_get_clean();

	return $output;

}
add_shortcode( 'wpum_password_recovery', 'wpum_password_recovery' );

/**
 * Display a login link.
 *
 * @param array  $atts
 * @param string $content
 * @return void
 */
function wpum_login_link( $atts, $content = null ) {

	extract(
		shortcode_atts(
			array(
				'redirect' => '',
				'label'    => esc_html__( 'Login', 'wp-user-manager' ),
			),
			$atts
		)
	);

	if ( is_user_logged_in() && ! ( isset( $_GET['context'] ) && 'edit' === $_GET['context'] ) ) {
		$output = '';
	} else {

		$wpum_login_page = wpum_get_core_page_id( 'login' );
		$wpum_login_page = get_permalink( $wpum_login_page );

		if ( $redirect ) {
			$wpum_login_page = add_query_arg( [ 'redirect_to' => $redirect ], $wpum_login_page );
		}

		if ( $wpum_login_page ) {
			$url = $wpum_login_page;
		} else {
			$url = wp_login_url( $redirect );
		}

		$output = '<a href="' . esc_url( $url ) . '" class="wpum-login-link">' . esc_html( $label ) . '</a>';
	}

	return $output;

}
add_shortcode( 'wpum_login', 'wpum_login_link' );

/**
 * Display a logout link.
 *
 * @param array  $atts
 * @param string $content
 * @return void
 */
function wpum_logout_link( $atts, $content = null ) {

	extract(
		shortcode_atts(
			array(
				'redirect' => '',
				'label'    => esc_html__( 'Logout', 'wp-user-manager' ),
			),
			$atts
		)
	);

	$output = '';

	if ( is_user_logged_in() || ! ( isset( $_GET['context'] ) && 'edit' === $_GET['context'] )  ) {
		$output = '<a href="' . esc_url( wp_logout_url( $redirect ) ) . '">' . esc_html( $label ) . '</a>';
	}

	return $output;

}
add_shortcode( 'wpum_logout', 'wpum_logout_link' );

/**
 * Show the registration form through a shortcode.
 *
 * @param array  $atts
 * @param string $content
 * @return void
 */
function wpum_registration_form( $atts, $content = null ) {

	extract(
		shortcode_atts(
			array(
				'login_link' => '',
				'psw_link'   => '',
			),
			$atts
		)
	);

	$is_success = isset( $_GET['registration'] ) && $_GET['registration'] == 'success' ? true : false;

	ob_start();

	if ( wpum_is_registration_enabled() ) {

		if ( is_user_logged_in() && ! $is_success && ! ( isset( $_GET['context'] ) && 'edit' === $_GET['context'] ) ) {

			WPUM()->templates
				->get_template_part( 'already-logged-in' );

		} elseif ( $is_success ) {

			$success_message = apply_filters( 'wpum_registration_success_message', esc_html__( 'Registration complete. We have sent you a confirmation email with your details.', 'wp-user-manager' ) );

			WPUM()->templates
				->set_template_data(
					[
						'message' => $success_message,
					]
				)
				->get_template_part( 'messages/general', 'success' );

		} else {

			echo WPUM()->forms->get_form( 'registration', $atts );

		}
	} else {

		WPUM()->templates
			->set_template_data(
				[
					'message' => esc_html__( 'Registrations are currently disabled.', 'wp-user-manager' ),
				]
			)
			->get_template_part( 'messages/general', 'error' );

	}

	$output = ob_get_clean();

	return $output;

}
add_shortcode( 'wpum_register', 'wpum_registration_form' );

/**
 * Display the account page of the user.
 *
 * @param array  $atts
 * @param string $content
 * @return void
 */
function wpum_account_page( $atts, $content = null ) {

	ob_start();

	WPUM()->templates
		->set_template_data( [] )
		->get_template_part( 'account' );

	$output = ob_get_clean();

	return $output;

}
add_shortcode( 'wpum_account', 'wpum_account_page' );

/**
 * Handles display of the profile shortcode.
 *
 * @param array  $atts
 * @param string $content
 * @return void
 */
function wpum_profile( $atts, $content = null ) {

	ob_start();

	$login_page        = get_permalink( wpum_get_core_page_id( 'login' ) );
	$registration_page = get_permalink( wpum_get_core_page_id( 'register' ) );

	if ( ! is_user_logged_in() && wpum_get_queried_user_id() ) {
		$user       = get_user_by( 'id', wpum_get_queried_user_id() );
		$redirect   = wpum_get_profile_url( $user );
		$login_page = add_query_arg( [ 'redirect_to' => $redirect ], $login_page );
	}

	$warning_message  = sprintf( __( 'This content is available to members only. Please <a href="%1$s">login</a> or <a href="%2$s">register</a> to view this area.', 'wp-user-manager' ), $login_page, $registration_page );

	/**
	 * Filter: allow developers to modify the profile restriction message.
	 *
	 * @param string $warning_message the original message.
	 * @return string
	 */
	$warning_message = apply_filters( 'wpum_profile_restriction_message', $warning_message );

	// Check if not logged in and on profile page - no given user
	if ( ! is_user_logged_in() && ! wpum_get_queried_user_id() ) {

		WPUM()->templates
			->set_template_data(
				[
					'message' => $warning_message,
				]
			)
			->get_template_part( 'messages/general', 'warning' );

	} elseif ( ! is_user_logged_in() && wpum_get_queried_user_id() && ! wpum_guests_can_view_profiles() ) {

		WPUM()->templates
			->set_template_data(
				[
					'message' => $warning_message,
				]
			)
			->get_template_part( 'messages/general', 'warning' );

	} elseif ( is_user_logged_in() && wpum_get_queried_user_id() && ! wpum_members_can_view_profiles() && ! wpum_is_own_profile() ) {

		WPUM()->templates
			->set_template_data(
				[
					'message' => esc_html__( 'You are not authorized to access this area.', 'wp-user-manager' ),
				]
			)
			->get_template_part( 'messages/general', 'warning' );

	} else {

		WPUM()->templates
			->set_template_data(
				[
					'user'            => get_user_by( 'id', wpum_get_queried_user_id() ),
					'current_user_id' => get_current_user_id(),
				]
			)
			->get_template_part( 'profile' );

	}

	$output = ob_get_clean();

	return $output;

}
add_shortcode( 'wpum_profile', 'wpum_profile' );

/**
 * Shortcode to display content to logged in users only.
 *
 * @param array  $atts
 * @param string $content
 * @return void
 */
function wpum_restrict_logged_in( $atts, $content = null ) {

	ob_start();

	if ( is_user_logged_in() && ! is_null( $content ) && ! is_feed() ) {

		echo do_shortcode( $content );

	} else {

		$login_page = get_permalink( wpum_get_core_page_id( 'login' ) );
		$login_page = add_query_arg(
			[
				'redirect_to' => get_permalink(),
			],
			$login_page
		);

		$message = sprintf( __( 'This content is available to members only. Please <a href="%1$s">login</a> or <a href="%2$s">register</a> to view this area.', 'wp-user-manager' ), $login_page, get_permalink( wpum_get_core_page_id( 'register' ) ) );

		/**
		 * Filter: allow developers to modify the content restriction shortcode message.
		 *
		 * @param string $message the original message.
		 * @return string
		 */
		$message = apply_filters( 'wpum_content_restriction_message', $message );

		WPUM()->templates
			->set_template_data(
				[
					'message' => $message,
				]
			)
			->get_template_part( 'messages/general', 'warning' );

	}

	$output = ob_get_clean();

	return $output;
}
add_shortcode( 'wpum_restrict_logged_in', 'wpum_restrict_logged_in' );

/**
 * Display content to a given list of users by ID.
 *
 * @param array  $atts
 * @param string $content
 * @return void
 */
function wpum_restrict_to_users( $atts, $content = null ) {

	extract(
		shortcode_atts(
			array(
				'ids' => null,
			),
			$atts
		)
	);

	ob_start();

	$allowed_users = explode( ',', $ids );
	$current_user  = get_current_user_id();

	if ( is_user_logged_in() && ! is_null( $content ) && ! is_feed() && in_array( $current_user, $allowed_users ) ) {

		echo do_shortcode( $content );

	} else {

		$login_page = get_permalink( wpum_get_core_page_id( 'login' ) );
		$login_page = add_query_arg(
			[
				'redirect_to' => get_permalink(),
			],
			$login_page
		);

		$message = sprintf( __( 'This content is available to members only. Please <a href="%1$s">login</a> or <a href="%2$s">register</a> to view this area.', 'wp-user-manager' ), $login_page, get_permalink( wpum_get_core_page_id( 'register' ) ) );

		/**
		 * Filter: allow developers to modify the content restriction shortcode message.
		 *
		 * @param string $message the original message.
		 * @return string
		 */
		$message = apply_filters( 'wpum_content_restriction_message', $message );

		WPUM()->templates
			->set_template_data(
				[
					'message' => $message,
				]
			)
			->get_template_part( 'messages/general', 'warning' );

	}

	$output = ob_get_clean();

	return $output;

}
add_shortcode( 'wpum_restrict_to_users', 'wpum_restrict_to_users' );

/**
 * Shortcode to display content to a set of user roles.
 *
 * @param array  $atts
 * @param string $content
 * @return void
 */
function wpum_restrict_to_user_roles( $atts, $content = null ) {

	extract(
		shortcode_atts(
			array(
				'roles' => null,
			),
			$atts
		)
	);

	ob_start();

	$allowed_roles = explode( ',', $roles );
	$allowed_roles = array_map( 'trim', $allowed_roles );
	$current_user  = wp_get_current_user();

	if ( is_user_logged_in() && ! is_null( $content ) && ! is_feed() && array_intersect( $current_user->roles, $allowed_roles ) ) {

		echo do_shortcode( $content );

	} else {

		$login_page = get_permalink( wpum_get_core_page_id( 'login' ) );
		$login_page = add_query_arg(
			[
				'redirect_to' => get_permalink(),
			],
			$login_page
		);

		$message = sprintf( __( 'This content is available to members only. Please <a href="%1$s">login</a> or <a href="%2$s">register</a> to view this area.', 'wp-user-manager' ), $login_page, get_permalink( wpum_get_core_page_id( 'register' ) ) );

		/**
		 * Filter: allow developers to modify the content restriction shortcode message.
		 *
		 * @param string $message the original message.
		 * @return string
		 */
		$message = apply_filters( 'wpum_content_restriction_message', $message );

		WPUM()->templates
			->set_template_data(
				[
					'message' => $message,
				]
			)
			->get_template_part( 'messages/general', 'warning' );

	}

	$output = ob_get_clean();

	return $output;
}
add_shortcode( 'wpum_restrict_to_user_roles', 'wpum_restrict_to_user_roles' );

/**
 * Display the recently registered users list.
 *
 * @param array  $atts
 * @param string $content
 * @return void
 */
function wpum_recently_registered( $atts, $content = null ) {

	extract(
		shortcode_atts(
			array(
				'amount'          => '1',
				'link_to_profile' => 'yes',
			),
			$atts
		)
	);

	ob_start();

	WPUM()->templates
		->set_template_data(
			[
				'amount'          => $amount,
				'link_to_profile' => $link_to_profile,
			]
		)
		->get_template_part( 'recently-registered' );

	$output = ob_get_clean();

	return $output;
}
add_shortcode( 'wpum_recently_registered', 'wpum_recently_registered' );

/**
 * Display a profile card.
 *
 * @param array  $atts
 * @param string $content
 * @return void
 */
function wpum_profile_card( $atts, $content = null ) {

	extract(
		shortcode_atts(
			array(
				'user_id'         => get_current_user_id(),
				'link_to_profile' => 'yes',
				'display_buttons' => 'yes',
				'display_cover'   => 'yes',
			),
			$atts
		)
	);

	ob_start();

	if ( empty( $user_id ) ) {
		$user_id = get_current_user_id();
	}

	WPUM()->templates
		->set_template_data(
			[
				'user_id'         => $user_id,
				'link_to_profile' => $link_to_profile,
				'display_buttons' => $display_buttons,
				'display_cover'   => $display_cover,
			]
		)
		->get_template_part( 'profile-card' );

	$output = ob_get_clean();

	return $output;

}
add_shortcode( 'wpum_profile_card', 'wpum_profile_card' );

/**
 * The shortcode to display the directory.
 *
 * @param array $atts
 * @param array $content
 * @return void
 */
function wpum_directory( $atts, $content = null ) {

	extract(
		shortcode_atts(
			array(
				'id' => '',
			),
			$atts
		)
	);

	ob_start();

	$directory_id = intval( $id );

	// Check if directory exists
	$check_directory = get_post_status( $directory_id );

	// Directory settings.
	$has_sort_by             = carbon_get_post_meta( $directory_id, 'directory_display_sorter' );
	$sort_by_default         = carbon_get_post_meta( $directory_id, 'directory_sorting_method' );
	$has_search_form         = carbon_get_post_meta( $directory_id, 'directory_search_form' );
	$has_amount_modifier     = carbon_get_post_meta( $directory_id, 'directory_display_amount_filter' );
	$assigned_roles          = carbon_get_post_meta( $directory_id, 'directory_assigned_roles' );
	$profiles_per_page       = carbon_get_post_meta( $directory_id, 'directory_profiles_per_page' ) ? carbon_get_post_meta( $directory_id, 'directory_profiles_per_page' ) : 10;
	$excluded_users          = carbon_get_post_meta( $directory_id, 'directory_excluded_users' );
	$directory_template      = carbon_get_post_meta( $directory_id, 'directory_template' );
	$directory_user_template = carbon_get_post_meta( $directory_id, 'directory_user_template' );

	// Modify the number argument if changed from the search form.
	if ( isset( $_POST['amount'] ) && ! empty( $_POST['amount'] ) ) {
		$profiles_per_page = absint( $_POST['amount'] );
	} elseif ( isset( $_GET['amount'] ) && ! empty( $_GET['amount'] ) ) {
		$profiles_per_page = absint( $_GET['amount'] );
	}

	// Prepare query arguments.
	$args = [
		'number' => $profiles_per_page,
	];

	// Add specific roles if any assigned.
	if ( is_array( $assigned_roles ) && ! empty( $assigned_roles ) ) {
		$args['role__in'] = $assigned_roles;
	}

	// Exclude users if anything specified.
	if ( $excluded_users && ! empty( $excluded_users ) ) {
		$excluded_users  = trim( str_replace( ' ', '', $excluded_users ) );
		$args['exclude'] = explode( ',', $excluded_users );
	}

	// Update pagination and offset users.
	$paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;
	if ( $paged == 1 ) {
		$offset = 0;
	} else {
		$offset = ( $paged - 1 ) * $profiles_per_page;
	}

	// Set sort by method if any specified from the search form.
	if ( isset( $_GET['sortby'] ) && ! empty( $_GET['sortby'] ) ) {
		$sortby = esc_attr( $_GET['sortby'] );
	} else {
		$sortby = $sort_by_default;
	}

	// Now actually set the arguments for the sort query.
	switch ( $sortby ) {
		case 'newest':
			$args['orderby'] = 'registered';
			$args['order']   = 'DESC';
			break;
		case 'oldest':
			$args['orderby'] = 'registered';
			break;
		case 'name':
			$args['meta_key'] = 'first_name';
			$args['orderby']  = 'meta_value';
			$args['order']    = 'ASC';
			break;
		case 'last_name':
			$args['meta_key'] = 'last_name';
			$args['orderby']  = 'meta_value';
			$args['order']    = 'ASC';
			break;
	}

	// Setup search if anything specified.
	if ( isset( $_GET['directory-search'] ) && ! empty( $_GET['directory-search'] ) ) {
		$search_string      = sanitize_text_field( esc_attr( trim( $_GET['directory-search'] ) ) );
		$args['search']     = '*' . esc_attr( $search_string ) . '*';

		$search_field_keys = carbon_get_post_meta( $directory_id, 'directory_search_fields' );
		$search_meta_keys  = apply_filters( 'wpum_directory_search_meta_keys', $search_field_keys );
		$search_meta_keys  = array_unique( $search_meta_keys );

		if ( ! empty( $search_meta_keys ) ) {
			$meta_query_keys = array();
			foreach ( $search_meta_keys as $search_meta_key ) {
				$meta_query_keys[] = array(
					'key'     => $search_meta_key,
					'value'   => esc_attr( $search_string ),
					'compare' => 'LIKE',
				);
			}

			$meta_query_keys['relation'] = 'OR';

			if ( ! isset( $args['meta_query' ] ) ) {
				$args['meta_query'] = array();
			}

			$args['meta_query'] = array_merge( $args['meta_query'], array( $meta_query_keys ) );

			add_filter( 'user_search_columns', function ( $columns, $search, $wp_user_query ) {
				global $wpum_directory_columns_search;

				$wpum_directory_columns_search = $wp_user_query->get_search_sql( $search, $columns, 'both' );

				return array();
			}, 10, 3 );
		}

		add_action( 'pre_user_query', function ( $uqi ) use ( $search_meta_keys ) {
			$search = '';
			if ( isset( $uqi->query_vars['search'] ) ) {
				$search = trim( $uqi->query_vars['search'] );
			}

			if ( $search ) {
				global $wpum_directory_columns_search;
				if ( ! empty( $wpum_directory_columns_search ) && ! empty( $search_meta_keys ) ) {
					$first_key = $search_meta_keys[0];

					$found_alias = false;
					foreach ( $uqi->meta_query->get_clauses() as $alias => $clause ) {
						if ( $clause['key'] === $first_key && $clause['compare'] == 'LIKE' ) {
							$found_alias = $alias;
							break;
						}
					}
					if ( $found_alias ) {
						$wpum_directory_columns_search = ltrim( $wpum_directory_columns_search, ' AND ' );
						$uqi->query_where = str_replace( ' ( ' . $found_alias . '.meta_key = \'' . $first_key, $wpum_directory_columns_search . ' OR ( ' . $found_alias . '.meta_key = \''  . $first_key, $uqi->query_where );
					}
				}

				$uqi->query_where = str_replace( ') AND ()', ') ', $uqi->query_where );
			}
		} );
	}

	$args['offset'] = $offset;
	$args           = apply_filters( 'wpum_directory_search_query_args', $args, $directory_id );
	$user_query     = new WP_User_Query( $args );
	$total_users    = $user_query->get_total();
	$total_pages    = ceil( $total_users / $profiles_per_page );

	if ( $check_directory == 'publish' ) {

		$directory_template = ( $directory_template !== 'default' || ! $directory_template ) ? $directory_template : 'directory';

		WPUM()->templates
			->set_template_data(
				[
					'has_sort_by'         => $has_sort_by,
					'sort_by_default'     => $sort_by_default,
					'has_search_form'     => $has_search_form,
					'has_amount_modifier' => $has_amount_modifier,
					'results'             => $user_query->get_results(),
					'total'               => $user_query->get_total(),
					'template'            => $directory_template,
					'user_template'       => $directory_user_template,
					'paged'               => $paged,
					'total_pages'         => $total_pages,
					'directory_id'        => $directory_id,
				]
			)
			->get_template_part( $directory_template );
	}

	$output = ob_get_clean();

	wp_enqueue_script( 'wpum-directories' );

	return $output;

}
add_shortcode( 'wpum_user_directory', 'wpum_directory' );

/**
 * Make sure search meta keys for custom fields don't have a leading underscore
 *
 * @param array $args
 *
 * @return array
 */
function wpum_maybe_fix_carbon_fields_search_keys( $args ) {
	if ( ! isset( $args['meta_query'][0] ) ) {
		return $args;
	}

	$wpum_meta = false;

	foreach ( $args['meta_query'][0] as $meta ) {
		if ( isset( $meta['key'] ) && substr( $meta['key'], 0, 5 ) === 'wpum_' ) {
			$wpum_meta = true;
			break;
		}
	}

	if ( ! $wpum_meta ) {
		return $args;
	}

	remove_action( 'pre_get_users', array(
		Carbon_Fields\Carbon_Fields::service( 'meta_query' ),
		'hook_pre_get_users',
	) );

	return $args;
}

add_filter( 'wpum_directory_search_query_args', 'wpum_maybe_fix_carbon_fields_search_keys', 100 );
