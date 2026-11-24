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
	 * Whether Elementor is saving a document, which is when it writes the plain
	 * text copy of the content to post_content.
	 *
	 * @var bool
	 */
	protected $saving_document = false;

	/**
	 * Option that records how far the post_content clean-up has got.
	 */
	const PLAIN_TEXT_CLEANUP_OPTION = 'wpum_elementor_plain_text_cleanup';

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
		add_action( 'elementor/document/before_save', array( $this, 'start_document_save' ) );
		add_action( 'elementor/document/after_save', array( $this, 'end_document_save' ) );
		add_action( 'admin_init', array( $this, 'maybe_clean_up_plain_text' ) );

		add_filter( 'wpum_shortcode_logged_in_override', function ( $override ) {
			$post = filter_input( INPUT_GET, 'post', FILTER_UNSAFE_RAW );
			$post = sanitize_text_field( $post );

			$elementor1 = filter_input( INPUT_GET, 'elementor', FILTER_UNSAFE_RAW );
			$elementor1 = sanitize_text_field( $elementor1 );

			$action     = filter_input( INPUT_GET, 'action', FILTER_UNSAFE_RAW );
			$action     = sanitize_text_field( $action );
			$elementor2 = 'elementor' === $action;

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
		spl_autoload_register( function ( $class ) { // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.classFound -- Matches spl_autoload_register signature.
			$file = 'extensions/class-' . str_replace( '_', '-', strtolower( $class ) ) . '.php';
			if ( file_exists( WPUM_PLUGIN_DIR . 'includes/integrations/elementor/' . $file ) ) {
				include $file;
			}
		} );

		( new WPUM_RestrictionControls() )::get_instance();

		spl_autoload_register( function ( $class ) { // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.classFound -- Matches spl_autoload_register signature.
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
		$settings = $widget->get_settings();

		// Elementor saves the rendered widgets as a plain text copy in post_content,
		// which the REST API, feeds and search show. Leave restricted widgets out.
		if ( $this->saving_document ) {
			return $this->is_restricted( $settings ) ? '' : $widget_content;
		}

		// The editor shows restricted widgets so they can be edited.
		if ( is_admin() && isset( $_GET['action'] ) && 'elementor' === $_GET['action'] && current_user_can( 'edit_posts' ) ) { // phpcs:ignore
			return $widget_content;
		}

		if ( is_admin() && wp_doing_ajax() && current_user_can( 'edit_posts' ) ) {
			return $widget_content;
		}

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
			if ( is_user_logged_in() && array_intersect( wpum_get_user_access_roles( $current_user ), $allowed_roles ) ) {
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
	 * Whether a widget's settings restrict who sees it.
	 *
	 * @param array $settings Widget settings.
	 *
	 * @return bool
	 */
	public function is_restricted( $settings ) {
		if ( empty( $settings['wpum_restrict_type'] ) ) {
			return false;
		}

		if ( 'wpum_restrict_type_state' === $settings['wpum_restrict_type'] && empty( $settings['wpum_restrict_state'] ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Elementor is saving a document.
	 */
	public function start_document_save() {
		$this->saving_document = true;
	}

	/**
	 * Elementor has saved a document.
	 */
	public function end_document_save() {
		$this->saving_document = false;
	}

	/**
	 * Rewrite the plain text copy of posts saved before restricted widgets were
	 * left out of it. Runs in batches on admin requests while Elementor is active.
	 */
	public function maybe_clean_up_plain_text() {
		$state = get_option( self::PLAIN_TEXT_CLEANUP_OPTION );

		if ( 'done' === $state || wp_doing_ajax() || ! did_action( 'elementor/loaded' ) ) {
			return;
		}

		if ( ! class_exists( '\Elementor\Plugin' ) || empty( \Elementor\Plugin::$instance->db ) ) {
			return;
		}

		$done = $this->clean_up_plain_text( (int) $state );

		update_option( self::PLAIN_TEXT_CLEANUP_OPTION, true === $done ? 'done' : $done, false );
	}

	/**
	 * Rewrite the plain text copy of one batch of posts that have restricted widgets.
	 *
	 * @param int $after_id Continue after this post ID.
	 * @param int $limit    Posts per batch.
	 *
	 * @return true|int True when finished, otherwise the last post ID handled.
	 */
	public function clean_up_plain_text( $after_id = 0, $limit = 50 ) {
		global $wpdb;

		$post_ids = $wpdb->get_col( $wpdb->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			"SELECT DISTINCT p.ID FROM {$wpdb->posts} p
			INNER JOIN {$wpdb->postmeta} data ON data.post_id = p.ID AND data.meta_key = '_elementor_data'
			INNER JOIN {$wpdb->postmeta} mode ON mode.post_id = p.ID AND mode.meta_key = '_elementor_edit_mode' AND mode.meta_value = 'builder'
			WHERE p.ID > %d AND p.post_type <> 'revision' AND data.meta_value LIKE %s
			ORDER BY p.ID ASC LIMIT %d",
			$after_id,
			// Elementor doesn't store settings left at their default, so match any restriction setting.
			'%' . $wpdb->esc_like( '"wpum_restrict_' ) . '%',
			$limit
		) );

		$last_id = $after_id;

		foreach ( $post_ids as $post_id ) {
			$post_id = (int) $post_id;
			$last_id = $post_id;

			$this->saving_document = true;

			try {
				$plain_text = $this->get_plain_text( $post_id );
			} catch ( \Throwable $e ) {
				$plain_text = null;
			}

			$this->saving_document = false;

			if ( ! is_string( $plain_text ) || get_post_field( 'post_content', $post_id, 'raw' ) === $plain_text ) {
				continue;
			}

			// Write the column directly, as Elementor does through wp_update_post(), but
			// without a new revision or a changed modified date.
			$wpdb->update( $wpdb->posts, array( 'post_content' => $plain_text ), array( 'ID' => $post_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			clean_post_cache( $post_id );
		}

		return count( $post_ids ) < $limit ? true : $last_id;
	}

	/**
	 * Elementor's plain text copy of a post, built the way Elementor builds it on save.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return string|null
	 */
	protected function get_plain_text( $post_id ) {
		$elementor = \Elementor\Plugin::$instance;

		if ( empty( $elementor->db ) || ! method_exists( $elementor->db, 'get_plain_text' ) ) {
			return null;
		}

		$dynamic_tags = isset( $elementor->dynamic_tags ) ? $elementor->dynamic_tags : null;
		$parsing_mode = null;

		if ( $dynamic_tags && method_exists( $dynamic_tags, 'get_parsing_mode' ) ) {
			$parsing_mode = $dynamic_tags->get_parsing_mode();
			$dynamic_tags->set_parsing_mode( 'remove' );
		}

		$plain_text = $elementor->db->get_plain_text( $post_id );

		if ( null !== $parsing_mode ) {
			$dynamic_tags->set_parsing_mode( $parsing_mode );
		}

		return $plain_text;
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
