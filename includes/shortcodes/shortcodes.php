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
 *
 * @return array
 */
function wpum_login_form( $atts, $content = null ) {

	extract( // phpcs:ignore
		shortcode_atts(
			array(
				'psw_link'      => '',
				'register_link' => '',
			),
			$atts,
			'wpum_login_form'
		)
	);

	ob_start();

	if ( is_user_logged_in() && ! apply_filters( 'wpum_shortcode_logged_in_override', false, 'wpum_login_form' ) ) {
		WPUM()->templates
			->get_template_part( 'already-logged-in' );
	} else {
		echo WPUM()->forms->get_form( 'login', $atts ); // phpcs:ignore

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
 *
 * @return string
 */
function wpum_password_recovery( $atts, $content = null ) {

	extract( // phpcs:ignore
		shortcode_atts(
			array(
				'login_link'    => '',
				'register_link' => '',
			),
			$atts
		)
	);

	ob_start();

	if ( is_user_logged_in() && ! apply_filters( 'wpum_shortcode_logged_in_override', false, 'wpum_password_recovery' ) ) {
		WPUM()->templates
			->get_template_part( 'already-logged-in' );
	} else {
		echo WPUM()->forms->get_form( 'password-recovery', $atts ); // phpcs:ignore
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
 *
 * @return string
 */
function wpum_login_link( $atts, $content = null ) {

	$args = shortcode_atts(
		array(
			'redirect' => '',
			'label'    => esc_html__( 'Login', 'wp-user-manager' ),
		),
		$atts
	);

	if ( is_user_logged_in() && ! apply_filters( 'wpum_shortcode_logged_in_override', false, 'wpum_login_link' ) ) {
		$output = '';
	} else {

		$wpum_login_page = wpum_get_core_page_id( 'login' );
		$wpum_login_page = get_permalink( $wpum_login_page );

		if ( $args['redirect'] ) {
			$wpum_login_page = add_query_arg( array( 'redirect_to' => apply_filters( 'wpum_login_redirect_to_url', $args['redirect'] ) ), $wpum_login_page );
		}

		if ( $wpum_login_page ) {
			$url = $wpum_login_page;
		} else {
			$url = wp_login_url( $args['redirect'] );
		}

		$output = '<a href="' . esc_url( $url ) . '" class="wpum-login-link">' . esc_html( $args['label'] ) . '</a>';
	}

	return $output;
}

add_shortcode( 'wpum_login', 'wpum_login_link' );

/**
 * Display a logout link.
 *
 * @param array  $atts
 * @param string $content
 *
 * @return string
 */
function wpum_logout_link( $atts, $content = null ) {

	$args = shortcode_atts(
		array(
			'redirect' => '',
			'label'    => esc_html__( 'Logout', 'wp-user-manager' ),
		),
		$atts
	);

	$output = '';

	if ( is_user_logged_in() || apply_filters( 'wpum_shortcode_logged_in_override', false, 'wpum_logout_link' ) ) {
		$output = '<a href="' . esc_url( wp_logout_url( $args['redirect'] ) ) . '">' . esc_html( $args['label'] ) . '</a>';
	}

	return $output;
}
add_shortcode( 'wpum_logout', 'wpum_logout_link' );

/**
 * Show the registration form through a shortcode.
 *
 * @param array       $atts
 * @param string|null $content
 *
 * @return string
 */
function wpum_registration_form( $atts, $content = null ) {

	extract( // phpcs:ignore
		shortcode_atts(
			array(
				'login_link' => '',
				'psw_link'   => '',
			),
			$atts
		)
	);

	$registration = filter_input( INPUT_GET, 'registration', FILTER_SANITIZE_STRING );
	$is_success   = 'success' === $registration;

	ob_start();

	if ( wpum_is_registration_enabled() ) {

		$finalstep = apply_filters( 'wpum_check_next_step', true );

		if ( is_user_logged_in() && $finalstep && ! $is_success && ! apply_filters( 'wpum_shortcode_logged_in_override', false, 'wpum_registration_form' ) ) {

			WPUM()->templates
				->get_template_part( 'already-logged-in' );

		} elseif ( $is_success && $finalstep ) {

			$success_message = apply_filters( 'wpum_registration_success_message', esc_html__( 'Registration complete. We have sent you a confirmation email with your details.', 'wp-user-manager' ) );

			WPUM()->templates
				->set_template_data(
					array(
						'message' => $success_message,
					)
				)
				->get_template_part( 'messages/general', 'success' );

		} else {

			echo WPUM()->forms->get_form( 'registration', $atts ); // phpcs:ignore

		}
	} else {

		WPUM()->templates
			->set_template_data(
				array(
					'message' => apply_filters( 'wpum_registration_disabled_message', esc_html__( 'Registrations are currently disabled.', 'wp-user-manager' ) ),
				)
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
 *
 * @return string
 */
function wpum_account_page( $atts, $content = null ) {

	ob_start();

	WPUM()->templates
		->set_template_data( array() )
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
 *
 * @return string
 */
function wpum_profile( $atts, $content = null ) {

	ob_start();

	$login_page        = get_permalink( wpum_get_core_page_id( 'login' ) );
	$registration_page = get_permalink( wpum_get_core_page_id( 'register' ) );

	if ( ! is_user_logged_in() && wpum_get_queried_user_id() ) {
		$user       = get_user_by( 'id', wpum_get_queried_user_id() );
		$redirect   = wpum_get_profile_url( $user );
		$login_page = add_query_arg( array( 'redirect_to' => apply_filters( 'wpum_login_redirect_to_url', $redirect ) ), $login_page );
	}

	// translators: %1$s login URL %2$s register URL
	$warning_message = sprintf( __( 'This content is available to members only. Please <a href="%1$s">login</a> or <a href="%2$s">register</a> to view this area.', 'wp-user-manager' ), $login_page, $registration_page );

	/**
	 * Filter: allow developers to modify the profile restriction message.
	 *
	 * @param string $warning_message the original message.
	 * @return string
	 */
	$warning_message = apply_filters( 'wpum_profile_restriction_message', $warning_message );

	$queried_user_id = wpum_get_queried_user_id();

	// Check if not logged in and on profile page - no given user
	if ( ! is_user_logged_in() && ! $queried_user_id ) {

		WPUM()->templates
			->set_template_data(
				array(
					'message' => $warning_message,
				)
			)
			->get_template_part( 'messages/general', 'warning' );

	} elseif ( ! is_user_logged_in() && $queried_user_id && ! wpum_guests_can_view_profiles( $queried_user_id ) ) {

		WPUM()->templates
			->set_template_data(
				array(
					'message' => $warning_message,
				)
			)
			->get_template_part( 'messages/general', 'warning' );

	} elseif ( is_user_logged_in() && $queried_user_id && ! wpum_members_can_view_profiles( $queried_user_id ) && ! wpum_is_own_profile() && ! current_user_can( 'administrator' ) ) {

		WPUM()->templates
			->set_template_data(
				array(
					'message' => esc_html__( 'You are not authorized to access this area.', 'wp-user-manager' ),
				)
			)
			->get_template_part( 'messages/general', 'warning' );

	} else {

		if ( ! $queried_user_id ) {
			WPUM()->templates
				->set_template_data(
					array(
						'message' => $warning_message,
					)
				)
				->get_template_part( 'messages/general', 'warning' );
		} else {
			WPUM()->templates
				->set_template_data(
					array(
						'user'            => get_user_by( 'id', $queried_user_id ),
						'current_user_id' => get_current_user_id(),
					)
				)
				->get_template_part( 'profile' );
		}
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
 *
 * @return string
 */
function wpum_restrict_logged_in( $atts, $content = null ) {
	extract( // phpcs:ignore
		shortcode_atts(
			array(
				'show_message' => 'yes',
			),
			$atts
		)
	);

	ob_start();

	if ( is_user_logged_in() && ! is_null( $content ) && ! is_feed() ) {

		echo do_shortcode( $content );

	} else {

		if ( ! $show_message || 'no' === $show_message ) {
			return '';
		}

		$login_page = get_permalink( wpum_get_core_page_id( 'login' ) );
		$login_page = add_query_arg(
			array(
				'redirect_to' => apply_filters( 'wpum_login_redirect_to_url', get_permalink() ),
			),
			$login_page
		);

		// translators: %1$s login URL %2$s register URL
		$message = sprintf( __( 'This content is available to members only. Please <a href="%1$s">login</a> or <a href="%2$s">register</a> to view this area.', 'wp-user-manager' ), $login_page, get_permalink( wpum_get_core_page_id( 'register' ) ) );

		/**
		 * Filter: allow developers to modify the content restriction shortcode message.
		 *
		 * @param string $message   Original message.
		 * @param string $shortcode Shortcode name.
		 * @return string
		 */
		$message = apply_filters( 'wpum_content_restriction_message', $message, 'wpum_restrict_logged_in' );

		WPUM()->templates
			->set_template_data(
				array(
					'message' => $message,
				)
			)
			->get_template_part( 'messages/general', 'warning' );

	}

	$output = ob_get_clean();

	return $output;
}
add_shortcode( 'wpum_restrict_logged_in', 'wpum_restrict_logged_in' );

/**
 * Shortcode to display content to logged out users only.
 *
 * @param array  $atts
 * @param string $content
 *
 * @return string
 */
function wpum_restrict_logged_out( $atts, $content = null ) {
	extract( // phpcs:ignore
		shortcode_atts(
			array(
				'show_message' => 'no',
			),
			$atts
		)
	);

	ob_start();

	if ( ! is_user_logged_in() && ! is_null( $content ) && ! is_feed() ) {

		echo do_shortcode( $content );

	} else {

		if ( ! $show_message || 'no' === $show_message ) {
			return '';
		}

		$login_page = get_permalink( wpum_get_core_page_id( 'login' ) );
		$login_page = add_query_arg(
			array(
				'redirect_to' => apply_filters( 'wpum_login_redirect_to_url', get_permalink() ),
			),
			$login_page
		);

		// translators: %1$s login URL %2$s register URL
		$message = sprintf( __( 'This content is available to members only. Please <a href="%1$s">login</a> or <a href="%2$s">register</a> to view this area.', 'wp-user-manager' ), $login_page, get_permalink( wpum_get_core_page_id( 'register' ) ) );

		/**
		 * Filter: allow developers to modify the content restriction shortcode message.
		 *
		 * @param string $message   Original message.
		 * @param string $shortcode Shortcode name.
		 * @return string
		 */
		$message = apply_filters( 'wpum_content_restriction_message', $message, 'wpum_restrict_logged_out' );

		WPUM()->templates
			->set_template_data(
				array(
					'message' => $message,
				)
			)
			->get_template_part( 'messages/general', 'warning' );

	}

	$output = ob_get_clean();

	return $output;
}
add_shortcode( 'wpum_restrict_logged_out', 'wpum_restrict_logged_out' );

/**
 * Display content to a given list of users by ID.
 *
 * @param array  $atts
 * @param string $content
 *
 * @return string
 */
function wpum_restrict_to_users( $atts, $content = null ) {

	extract( // phpcs:ignore
		shortcode_atts(
			array(
				'ids'          => null,
				'show_message' => 'yes',
			),
			$atts
		)
	);

	ob_start();

	$allowed_users = array_map( 'intval', explode( ',', $ids ) );
	$current_user  = get_current_user_id();

	if ( is_user_logged_in() && ! is_null( $content ) && ! is_feed() && in_array( $current_user, $allowed_users, true ) ) {

		echo do_shortcode( $content );

	} else {
		if ( ! $show_message || 'no' === $show_message ) {
			return '';
		}

		$login_page = get_permalink( wpum_get_core_page_id( 'login' ) );
		$login_page = add_query_arg(
			array(
				'redirect_to' => apply_filters( 'wpum_login_redirect_to_url', get_permalink() ),
			),
			$login_page
		);

		// translators: %1$s login URL %2$s register URL
		$message = sprintf( __( 'This content is available to members only. Please <a href="%1$s">login</a> or <a href="%2$s">register</a> to view this area.', 'wp-user-manager' ), $login_page, get_permalink( wpum_get_core_page_id( 'register' ) ) );

		/**
		 * Filter: allow developers to modify the content restriction shortcode message.
		 *
		 * @param string $message   Original message.
		 * @param string $shortcode Shortcode name.
		 * @return string
		 */
		$message = apply_filters( 'wpum_content_restriction_message', $message, 'wpum_restrict_to_users' );

		WPUM()->templates
			->set_template_data(
				array(
					'message' => $message,
				)
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
 *
 * @return string
 */
function wpum_restrict_to_user_roles( $atts, $content = null ) {

	extract( // phpcs:ignore
		shortcode_atts(
			array(
				'roles'        => null,
				'show_message' => 'yes',
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
		if ( ! $show_message || 'no' === $show_message ) {
			return '';
		}

		$login_page = get_permalink( wpum_get_core_page_id( 'login' ) );
		$login_page = add_query_arg(
			array(
				'redirect_to' => apply_filters( 'wpum_login_redirect_to_url', get_permalink() ),
			),
			$login_page
		);

		// translators: %1$s login URL %2$s register URL
		$message = sprintf( __( 'This content is available to members only. Please <a href="%1$s">login</a> or <a href="%2$s">register</a> to view this area.', 'wp-user-manager' ), $login_page, get_permalink( wpum_get_core_page_id( 'register' ) ) );

		/**
		 * Filter: allow developers to modify the content restriction shortcode message.
		 *
		 * @param string $message   Original message.
		 * @param string $shortcode Shortcode name.
		 * @return string
		 */
		$message = apply_filters( 'wpum_content_restriction_message', $message, 'wpum_restrict_to_user_roles' );

		WPUM()->templates
			->set_template_data(
				array(
					'message' => $message,
				)
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
 *
 * @return string
 */
function wpum_recently_registered( $atts, $content = null ) {

	extract( // phpcs:ignore
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
			array(
				'amount'          => $amount,
				'link_to_profile' => $link_to_profile,
			)
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
 *
 * @return string
 */
function wpum_profile_card( $atts, $content = null ) {

	extract( // phpcs:ignore
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

	// Block returns boolean value, convert it to string
	$link_to_profile = $link_to_profile ? 'yes' : '';
	$display_buttons = $display_buttons ? 'yes' : '';
	$display_cover   = $display_cover ? 'yes' : '';

	ob_start();

	if ( empty( $user_id ) ) {
		$user_id = get_current_user_id();
	}

	if ( empty( $user_id ) ) {
		return '';
	}

	WPUM()->templates
		->set_template_data(
			array(
				'user_id'         => $user_id,
				'link_to_profile' => $link_to_profile,
				'display_buttons' => $display_buttons,
				'display_cover'   => $display_cover,
			)
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
 *
 * @return string
 */
function wpum_directory( $atts, $content = null ) {

	extract( // phpcs:ignore
		shortcode_atts(
			array(
				'id' => '',
			),
			$atts
		)
	);

	ob_start();

	WPUM()->directories_editor->register_directory_settings();

	$directory_id = intval( $id );

	// Check if directory exists
	$check_directory = get_post_status( $directory_id );

	// Directory settings.
	$has_sort_by             = \WPUM\carbon_get_post_meta( $directory_id, 'directory_display_sorter' );
	$sort_by_default         = \WPUM\carbon_get_post_meta( $directory_id, 'directory_sorting_method' );
	$has_search_form         = \WPUM\carbon_get_post_meta( $directory_id, 'directory_search_form' );
	$has_amount_modifier     = \WPUM\carbon_get_post_meta( $directory_id, 'directory_display_amount_filter' );
	$assigned_roles          = \WPUM\carbon_get_post_meta( $directory_id, 'directory_assigned_roles' );
	$profiles_per_page       = \WPUM\carbon_get_post_meta( $directory_id, 'directory_profiles_per_page' ) ? carbon_get_post_meta( $directory_id, 'directory_profiles_per_page' ) : 10;
	$excluded_users          = \WPUM\carbon_get_post_meta( $directory_id, 'directory_excluded_users' );
	$directory_template      = \WPUM\carbon_get_post_meta( $directory_id, 'directory_template' );
	$directory_user_template = \WPUM\carbon_get_post_meta( $directory_id, 'directory_user_template' );

	// Modify the number argument if changed from the search form.
	$amount_post = filter_input( INPUT_POST, 'amount', FILTER_VALIDATE_INT );
	$amount_get  = filter_input( INPUT_GET, 'amount', FILTER_VALIDATE_INT );

	if ( $amount_post ) {
		$profiles_per_page = absint( $amount_post );
	} elseif ( $amount_get ) {
		$profiles_per_page = absint( $amount_get );
	}

	// Prepare query arguments.
	$args = array(
		'number' => $profiles_per_page,
	);

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
	if ( 1 === (int) $paged ) {
		$offset = 0;
	} else {
		$offset = ( $paged - 1 ) * $profiles_per_page;
	}

	$sortby = filter_input( INPUT_GET, 'sortby', FILTER_SANITIZE_STRING );
	// Set sort by method if any specified from the search form.
	if ( $sortby ) {
		$sortby = esc_attr( $sortby );
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
		default:
			$args['meta_key'] = $sortby;
			$args['orderby']  = 'meta_value';
			$args['order']    = 'ASC';
	}

	$privacy_meta_query_key = is_user_logged_in() ? '_hide_profile_members' : '_hide_profile_guests';
	$args['meta_query']     = array(); // phpcs:ignore
	$args['meta_query'][]   = array(
		'relation' => 'OR',
		array(
			'key'     => $privacy_meta_query_key,
			'value'   => '',
			'compare' => 'NOT EXISTS',
		),
		array(
			'key'   => $privacy_meta_query_key,
			'value' => '',
		),
	);

	remove_action( 'pre_get_users', array(
		Carbon_Fields\Carbon_Fields::service( 'meta_query' ),
		'hook_pre_get_users',
	) );

	// Setup search if anything specified.
	$directory_search = filter_input( INPUT_GET, 'directory-search' );

	if ( $directory_search ) {
		$search_string  = sanitize_text_field( trim( wp_unslash( $directory_search ) ) );
		$args['search'] = '*' . esc_attr( $search_string ) . '*';

		$search_field_keys = \WPUM\carbon_get_post_meta( $directory_id, 'directory_search_fields' );
		$search_meta_keys  = apply_filters( 'wpum_directory_search_meta_keys', $search_field_keys );
		$search_meta_keys  = array_unique( $search_meta_keys );

		if ( ! empty( $search_meta_keys ) ) {
			$meta_query_keys = array();
			foreach ( $search_meta_keys as $search_meta_key ) {
				$meta_query_keys[] = array(
					'key'     => $search_meta_key,
					'value'   => $search_string,
					'compare' => 'LIKE',
				);
			}

			$meta_query_keys['relation'] = 'OR';

			if ( ! isset( $args['meta_query'] ) ) {
				$args['meta_query'] = array();  // phpcs:ignore
			}

			$args['meta_query'] = array_merge( $args['meta_query'], array( $meta_query_keys ) ); // phpcs:ignore

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
						if ( $clause['key'] === $first_key && 'LIKE' === $clause['compare'] ) {
							$found_alias = $alias;
							break;
						}
					}
					if ( $found_alias ) {
						$wpum_directory_columns_search = ltrim( $wpum_directory_columns_search, ' AND ' );
						$uqi->query_where              = str_replace( ' ( ' . $found_alias . '.meta_key = \'' . $first_key, $wpum_directory_columns_search . ' OR ( ' . $found_alias . '.meta_key = \'' . $first_key, $uqi->query_where );
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

	if ( 'publish' === $check_directory ) {

		$directory_template = ( ! $directory_template || 'default' !== $directory_template ) ? $directory_template : 'directory';

		WPUM()->templates
			->set_template_data(
				array(
					'has_sort_by'         => $has_sort_by,
					'sort_by_default'     => $sort_by_default,
					'has_search_form'     => $has_search_form,
					'has_amount_modifier' => $has_amount_modifier,
					'results'             => apply_filters( 'wpum_directory_users', $user_query->get_results(), $directory_id ),
					'total'               => apply_filters( 'wpum_directory_users_total', $user_query->get_total(), $directory_id ),
					'template'            => $directory_template,
					'user_template'       => $directory_user_template,
					'paged'               => $paged,
					'total_pages'         => $total_pages,
					'directory_id'        => $directory_id,
				)
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

add_filter( 'wpum_shortcode_logged_in_override', function ( $override ) {
	$context = filter_input( INPUT_GET, 'context', FILTER_SANITIZE_STRING );

	if ( empty( $context ) ) {
		return $override;
	}

	return 'edit' === $context;
} );
