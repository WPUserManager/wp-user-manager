<?php
/**
 * Handles the display of shortcode generator.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Display the button within the WordPress tinymce editor.
 */
final class WPUM_Shortcode_Button {

	/**
	 * List of available shortcodes.
	 *
	 * @var array
	 */
	public static $shortcodes;

	/**
	 * Get things started.
	 */
	public function __construct() {
		if ( is_admin() ) {
			global $pagenow;
			if ( ! empty( $pagenow ) && ( 'post-new.php' === $pagenow || 'post.php' === $pagenow ) ) {
				add_filter( 'mce_external_plugins', array( $this, 'mce_external_plugins' ), 15 );
				add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_assets' ) );
				add_action( 'admin_enqueue_scripts', array( $this, 'admin_localize_scripts' ), 13 );
				add_action( 'media_buttons', array( $this, 'shortcode_button' ) );
			}
		}
		add_action( 'wp_ajax_wpum_shortcode', array( $this, 'shortcode_ajax' ) );
		add_action( 'wp_ajax_nopriv_wpum_shortcode', array( $this, 'shortcode_ajax' ) );
	}

	/**
	 * Inject a new tinymce plugin.
	 *
	 * @param array $plugin_array
	 *
	 * @return array
	 */
	public function mce_external_plugins( $plugin_array ) {
		if ( ! current_user_can( 'edit_posts' ) && ! current_user_can( 'edit_pages' ) ) {
			return false;
		}
		$plugin_array['wpum_shortcode'] = WPUM_PLUGIN_URL . 'assets/js/admin/tinymce/mce-plugin.min.js';
		return $plugin_array;
	}

	/**
	 * Load the generators required scripts.
	 *
	 * @return void
	 */
	public function admin_enqueue_assets() {
		wp_enqueue_style( 'wpum-shortcodes', WPUM_PLUGIN_URL . 'assets/css/admin/shortcodes.css', array(), WPUM_VERSION );
		wp_enqueue_script( 'wpum_shortcode', WPUM_PLUGIN_URL . 'assets/js/admin/admin-shortcodes.min.js', array( 'jquery' ), WPUM_VERSION, true );
	}

	/**
	 * Add local js variables.
	 *
	 * @return void
	 */
	public function admin_localize_scripts() {
		if ( ! empty( self::$shortcodes ) ) {
			$variables = array();
			foreach ( self::$shortcodes as $shortcode => $values ) {
				if ( ! empty( $values['required'] ) ) {
					$variables[ $shortcode ] = $values['required'];
				}
			}
			wp_localize_script( 'wpum_shortcode', 'wpumShortcodes', $variables );
		}
	}

	/**
	 * Load the shortcode button into the editor.
	 */
	public function shortcode_button() {
		$screen = get_current_screen();

		// If we load wp editor by ajax then $screen will be empty which generate notice if we treat $screen as WP_Screen object.
		// For example we are loading wp editor by ajax in repeater field.
		if ( ! ( $screen instanceof WP_Screen ) ) {
			return;
		}
		$shortcode_button_pages = apply_filters( 'wpum_shortcode_button_pages', array(
			'post.php',
			'page.php',
			'post-new.php',
			'post-edit.php',
			'edit.php',
			'edit.php?post_type=page',
		) );

		// Only run in admin post/page creation and edit screens
		if ( in_array( $screen->parent_file, $shortcode_button_pages, true )
			 && apply_filters( 'wpum_shortcode_button_condition', true )
			 && ! empty( self::$shortcodes )
		) {
			$shortcodes = array();
			foreach ( self::$shortcodes as $shortcode => $values ) {
				if ( apply_filters( sanitize_title( $shortcode ) . '_condition', true ) ) {
					$shortcodes[ $shortcode ] = sprintf(
						'<div class="wpum-shortcode mce-menu-item wpum-shortcode-item-%1$s" data-shortcode="%2$s">%3$s</div>',
						$shortcode,
						$shortcode,
						$values['label']
					);
				}
			}
			if ( ! empty( $shortcodes ) ) {
				// Check current WP version.
				$img = ( version_compare( get_bloginfo( 'version' ), '3.5', '<' ) )
					? '<img src="' . WPUM_PLUGIN_URL . 'assets/images/wpum-media.png" />'
					: '<span class="wp-media-buttons-icon" id="wpum-media-button" style="background-image: url( ' . WPUM_PLUGIN_URL . 'assets/images/logo.svg ); background-repeat: no-repeat; background-position-x: 3px; background-position-y: -1px;"></span>';
				reset( $shortcodes );
				if ( 1 === count( $shortcodes ) ) {
					$shortcode = key( $shortcodes );
					printf(
						'<button type="button" class="button wpum-shortcode" data-shortcode="%s">%s</button>',
						esc_attr( $shortcode ),
						sprintf( '%s %s %s',
							$img, // phpcs:ignore
							esc_html__( 'Insert', 'wp-user-manager' ),
							esc_html( self::$shortcodes[ $shortcode ]['label'] )
						)
					);
				} else {
					printf(
						'<div class="wpum-wrap">' .
						'<button class="button wpum-button" type="button">%s %s</button>' .
						'<div class="wpum-menu mce-menu">%s</div>' .
						'</div>',
						$img, // phpcs:ignore
						esc_html__( 'User Shortcodes', 'wp-user-manager' ),
						wp_kses_post( implode( '', array_values( $shortcodes ) ) )
					);
				}
			}
		}
	}

	/**
	 * Load the required window based on the selected shortcode.
	 *
	 * @return void
	 */
	public function shortcode_ajax() {
		$shortcode = filter_input( INPUT_POST, 'shortcode' );
		$response  = false;
		if ( $shortcode && array_key_exists( $shortcode, self::$shortcodes ) ) {
			$data = self::$shortcodes[ $shortcode ];
			if ( ! empty( $data['errors'] ) ) {
				$data['btn_okay'] = array( esc_html__( 'Okay', 'wp-user-manager' ) );
			}
			$response = array(
				'body'      => $data['fields'],
				'close'     => $data['btn_close'],
				'ok'        => $data['btn_okay'],
				'shortcode' => $shortcode,
				'title'     => $data['title'],
			);
		} else {
			wp_send_json_error();
		}
		wp_send_json( $response );
	}

}

new WPUM_Shortcode_Button();
