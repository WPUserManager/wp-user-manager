<?php
/**
 * Hooks the widgets to elementor builder.
 *
 */

class WPUM_Elementor {

	/**
	 * @var WPUM_Elementor
	 */
	protected static $instance;

	protected $loader;

	/**
	 * Get instance.
	 *
	 * @return static
	 * @since
	 * @access static
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
			self::$instance->init();
		}

		return self::$instance;
	}

	public function init() {
		add_action( 'elementor/elements/categories_registered', [ $this, 'wpum_register_elementor_category' ], 10 );
		add_action( 'elementor/widgets/register', [ $this, 'wpum_register_elementor_widets' ], 10 );
		add_filter( 'elementor/widget/render_content', [ $this, 'wpum_restrict_widget_content' ], 10, 2 );
	}

	public function wpum_register_elementor_category( $elements ) {
		$elements->add_category(
			'wp-user-manager',
			[
				'title' => esc_html__( 'WP User Manager', 'wp-user-manager' )
			]
		);
	}

	public function wpum_register_elementor_widets( $widgets ) {
		spl_autoload_register( function ( $class ) {
			include 'extensions/class-wpum-' . strtolower( $class ) . '.php';
		});

		( new RestrictionControls )::get_instance();

		spl_autoload_register( function ( $class ) {
			include 'widgets/class-wpum-' . strtolower( $class ) . '.php';
		});

		$widgets->register( new LoginForm() );
		$widgets->register( new LoginLink() );
		$widgets->register( new LogoutLink() );
		$widgets->register( new ProfileCard() );
		$widgets->register( new ProfilePage() );
		$widgets->register( new RecoveryForm() );
		$widgets->register( new RegistrationForm() );
		$widgets->register( new AccountPage() );
		$widgets->register( new RecentlyRegisteredUsers() );
		$widgets->register( new UserDirectory() );

		if ( class_exists( 'WPUM_Groups' ) ) {
			$widgets->register( new GroupDirectory() );
		}

		if ( class_exists( 'WPUM_Frontend_Posting' ) ) {
			$widgets->register( new PostForm() );
		}
	}

	/**
	 * Filters widget content.
	 *
	 * @param string $widget_content      The widget HTML output.
	 * @param \Elementor\Widget_Base      $widget The widget instance.
	 * @return string                     The changed widget content.
	 */
	function wpum_restrict_widget_content( $widget_content, $widget ) {

		if ( ( is_admin() && isset( $_GET['action'] ) && $_GET['action'] === 'elementor' ) ) {
			return $widget_content;
		}
		
		if ( is_admin() && wp_doing_ajax() ) {
			return $widget_content;
		}

		$settings     = $widget->get_settings();
		$show_message = false;

		if ( empty( $settings['wpum_restrict_type'] ) ) {
			return $widget_content;
		}

		if ( ! empty( $settings['wpum_restrict_show_message'] ) ) {
			$show_message = true;
		}
		
		if ( $settings['wpum_restrict_type'] === 'wpum_restrict_type_state' && empty( $settings['wpum_restrict_state'] ) ) {
			return $widget_content;
		}

		if ( 'in' === $settings['wpum_restrict_state'] && is_user_logged_in() ) {
			return $widget_content;
		}

		if ( 'out' === $settings['wpum_restrict_state'] && ! is_user_logged_in() ) {
			return $widget_content;
		}

		if ( 'wpum_restrict_type_role' === $settings['wpum_restrict_type'] ) {
			$allowed_roles = empty( $settings['wpum_restrict_roles'] ) || ! is_array( $settings['wpum_restrict_roles'] ) ? array() : $settings['wpum_restrict_roles'];
			$allowed_roles = array_map( 'trim', $allowed_roles );
			$current_user  = wp_get_current_user();
			if ( is_user_logged_in() && array_intersect( $current_user->roles, $allowed_roles ) ) {
				return $widget_content;
			}
		}

		if ( 'wpum_restrict_type_user' === $settings['wpum_restrict_type'] ) {
			$allowed_users = empty( $settings['wpum_restrict_users'] ) || ! is_array( $settings['wpum_restrict_users'] ) ? array() : $settings['wpum_restrict_users'];
			$allowed_users = array_map( 'trim', $allowed_users );
			if ( is_user_logged_in() && in_array( wp_get_current_user()->ID, $allowed_users ) ) {
				return $widget_content;
			}
		}

		return $show_message ? $this->get_restricted_message() : '';
	}

	/**
	 * @return string
	 */
	public function get_restricted_message() {
		global $post;

		if ( ! isset( $post->ID ) ) {
			return '';
		}

		ob_start();
		$login_page = get_permalink( wpum_get_core_page_id( 'login' ) );
		$login_page = add_query_arg( [
			'redirect_to' => get_permalink(),
		], $login_page );

		$message = sprintf( __( 'This content is available to members only. Please <a href="%1$s">login</a> or <a href="%2$s">register</a> to view this area.', 'wp-user-manager' ), $login_page, get_permalink( wpum_get_core_page_id( 'register' ) ) );

		/**
		 * Filter: allow developers to modify the content restriction shortcode message.
		 *
		 * @param string $message the original message.
		 *
		 * @return string
		 */
		$message = apply_filters( 'wpum_content_restriction_message', $message );

		WPUM()->templates->set_template_data( [
			'message' => $message,
		] )->get_template_part( 'messages/general', 'warning' );

		$output = ob_get_clean();

		return $output;
	}
}

( new WPUM_Elementor )::get_instance();