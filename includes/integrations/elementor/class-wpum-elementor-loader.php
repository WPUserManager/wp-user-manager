<?php
/**
 * Hooks the widgets to elementor builder.
 */

use Elementor\Elements_Manager;
use Elementor\Widgets_Manager;

/**
 * Elementor loader
 */
class WPUM_Elementor_Loader {

	/**
	 * @var WPUM_Elementor
	 */
	protected static $instance;

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

	/**
	 * Init
	 */
	public function init() {
		add_action( 'elementor/elements/categories_registered', array( $this, 'wpum_register_elementor_category' ) );
		add_action( 'elementor/widgets/register', array( $this, 'wpum_register_elementor_widets' ) );
		add_filter( 'elementor/widget/render_content', array( $this, 'wpum_restrict_widget_content' ), 10, 2 );

		add_filter( 'wpum_shortcode_logged_in_override', function ( $override ) {
			$post       = filter_input( INPUT_GET, 'post', FILTER_SANITIZE_STRING );
			$elementor1 = filter_input( INPUT_GET, 'elementor', FILTER_SANITIZE_STRING );
			$elementor2 = 'elementor' === filter_input( INPUT_GET, 'action', FILTER_SANITIZE_STRING );

			if ( ! empty( $post ) && ( ! empty( $elementor1 ) || $elementor2 ) ) {
				return true;
			}

			return $override;
		} );
	}

	/**
	 * Register category
	 *
	 * @param Elements_Manager $elements
	 */
	public function wpum_register_elementor_category( $elements ) {
		$elements->add_category(
			'wp-user-manager',
			array(
				'title' => esc_html__( 'WP User Manager', 'wp-user-manager' ),
			)
		);
	}

	/**
	 * @param Widgets_Manager $widgets
	 */
	public function wpum_register_elementor_widets( $widgets ) {
		spl_autoload_register( function ( $class ) {
			$file = 'extensions/class-' . str_replace( '_', '-', strtolower( $class ) ) . '.php';
			if ( file_exists( WPUM_PLUGIN_DIR . 'includes/integrations/elementor/' . $file ) ) {
				include $file;
			}
		} );

		( new WPUM_RestrictionControls() )::get_instance();

		spl_autoload_register( function ( $class ) {
			$file = 'widgets/class-' . str_replace( '_', '-', strtolower( $class ) ) . '.php';
			if ( file_exists( WPUM_PLUGIN_DIR . 'includes/integrations/elementor/' . $file ) ) {
				include $file;
			}
		} );

		$widgets->register( new WPUM_LoginForm() );
		$widgets->register( new WPUM_LoginLink() );
		$widgets->register( new WPUM_LogoutLink() );
		$widgets->register( new WPUM_ProfileCard() );
		$widgets->register( new WPUM_ProfilePage() );
		$widgets->register( new WPUM_RecoveryForm() );
		$widgets->register( new WPUM_RegistrationForm() );
		$widgets->register( new WPUM_AccountPage() );
		$widgets->register( new WPUM_RecentlyRegisteredUsers() );
		$widgets->register( new WPUM_UserDirectory() );

		if ( class_exists( 'WPUM_Groups' ) ) {
			$widgets->register( new WPUM_GroupDirectory() );
		}

		if ( class_exists( 'WPUM_Frontend_Posting' ) ) {
			$widgets->register( new WPUM_PostForm() );
		}
	}

	/**
	 * Filters widget content.
	 *
	 * @param string                 $widget_content The widget HTML output.
	 * @param \Elementor\Widget_Base $widget         The widget instance.
	 *
	 * @return string                     The changed widget content.
	 */
	public function wpum_restrict_widget_content( $widget_content, $widget ) {
		if ( ( is_admin() && isset( $_GET['action'] ) && 'elementor' === $_GET['action'] ) ) { // phpcs:ignore
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

		if ( 'wpum_restrict_type_state' === $settings['wpum_restrict_type'] && empty( $settings['wpum_restrict_state'] ) ) {
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
			$allowed_users = array_map( 'intval', $allowed_users );
			if ( is_user_logged_in() && in_array( wp_get_current_user()->ID, $allowed_users, true ) ) {
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
		$login_page = add_query_arg( array(
			'redirect_to' => get_permalink(),
		), $login_page );

		// translators: %1s$ login url %2$s register url
		$message = sprintf( __( 'This content is available to members only. Please <a href="%1$s">login</a> or <a href="%2$s">register</a> to view this area.', 'wp-user-manager' ), $login_page, get_permalink( wpum_get_core_page_id( 'register' ) ) );

		/**
		 * Filter: allow developers to modify the content restriction shortcode message.
		 *
		 * @param string $message the original message.
		 *
		 * @return string
		 */
		$message = apply_filters( 'wpum_content_restriction_message', $message );

		WPUM()->templates->set_template_data( array(
			'message' => $message,
		) )->get_template_part( 'messages/general', 'warning' );

		$output = ob_get_clean();

		return $output;
	}
}
