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
		/**
		 * Register the WP User Manager elementor widgets category for the elementor builder.
		 */
		add_action( 'elementor/elements/categories_registered', [ $this, 'wpum_register_elementor_category' ], 10 );
		
		/**
		 * Register the WP User Manager elementor widgets for the elementor builder.
		 */
		add_action( 'elementor/widgets/register', [ $this, 'wpum_register_elementor_widets' ], 10 );
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
}
( new WPUM_Elementor )::get_instance();