<?php
/**
 * Getting started admin page.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class WPUM_Getting_Started {

	/**
	 * @var string The capability users should have to view the page
	 */
	public $minimum_capability = 'manage_options';

	/**
	 * Get things started
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'admin_menus') );
		add_action( 'admin_head', array( $this, 'admin_head' ) );
		add_action( 'admin_init', array( $this, 'welcome'    ) );
	}

	/**
	 * Register the Dashboard Pages which are later hidden but these pages
	 * are used to render the Welcome and Credits pages.
	 *
	 * @access public
	 * @return void
	 */
	public function admin_menus() {
		// Getting Started Page
		add_dashboard_page(
			__( 'Getting started with WP User Manager', 'wpum' ),
			__( 'Getting started with WP User Manager', 'wpum' ),
			$this->minimum_capability,
			'wpum-getting-started',
			array( $this, 'getting_started_screen' )
		);
	}

		/**
	 * Hide Individual Dashboard Pages
	 *
	 * @access public
	 * @since 1.0
	 * @return void
	 */
	public function admin_head() {
		//remove_submenu_page( 'index.php', 'wpum-getting-started' );
		// Badge for welcome page
		$badge_url = WPUM_PLUGIN_URL . 'assets/images/logo.svg';
		?>
		<style type="text/css" media="screen">
		/*<![CDATA[*/
		.wpum-badge {
			background: url('<?php echo $badge_url; ?>') center 24px/85px 85px no-repeat #fff;
			-webkit-background-size: 85px 85px;
			color: #7ec276;
			font-size: 14px;
			text-align: center;
			font-weight: 600;
			margin: 5px 0 0;
			padding-top: 120px;
			height: 40px;
			display: inline-block;
			width: 150px;
			text-rendering: optimizeLegibility;
			-webkit-box-shadow: 0 1px 3px rgba(0,0,0,.2);
			box-shadow: 0 1px 3px rgba(0,0,0,.2);
		}
		.about-wrap .wpum-badge {
			position: absolute;
			top: 0;
			right: 0;
		}
		.wpum-welcome-screenshots {
			float: right;
			margin-left: 10px!important;
		}
		.about-wrap .feature-section {
			margin-top: 20px;
		}
		#mc-embedded-subscribe-form {
			margin: 10px 0 0px -10px;
			max-width: 700px;
		}

		#mc-embedded-subscribe-form td {
			padding-right:0px;
		}
		/*]]>*/
		</style>
		<?php
	}

	/**
	 * Navigation tabs
	 *
	 * @access public
	 * @since 1.0
	 * @return void
	 */
	public function tabs() {
		$selected = isset( $_GET['page'] ) ? $_GET['page'] : 'wpum-about';
		?>
		<h2 class="nav-tab-wrapper">
			<a class="nav-tab <?php echo $selected == 'wpum-getting-started' ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'wpum-getting-started' ), 'index.php' ) ) ); ?>">
				<?php _e( 'Getting Started', 'wpum' ); ?>
			</a>
		</h2>
		<?php
	}

	/**
	 * Render Getting Started Screen
	 *
	 * @access public
	 * @return void
	 */
	public function getting_started_screen() {
		?>
		<div class="wrap about-wrap">

			<h1><?php printf( __( 'Welcome to WP User Manager %s', 'wpum' ), WPUM_VERSION ); ?></h1>
			<div class="about-text"><?php printf( __( 'Thank you for installing the latest version! WP User Manager %s is ready to provide improved control over your WordPress users.', 'wpum' ), WPUM_VERSION ); ?></div>
			<div class="wpum-badge"><?php printf( __( 'Version %s', 'wpum' ), WPUM_VERSION ); ?></div>

			<form action="https://themesdepot.us5.list-manage.com/subscribe/post?u=e68e0bb69f2cdf2dfd083856c&amp;id=054538336e" method="post" id="mc-embedded-subscribe-form" name="mc-embedded-subscribe-form" class="validate" target="_blank">

				<table class="form-table wpum-newsletter-form">
					<tbody>
						<tr valign="middle">
							<td>
									<input type="email" value="" placeholder="<?php esc_html_e( 'Email address*' ); ?>" name="EMAIL" class="required email" required id="mce-EMAIL">
							</td>
							<td>
								<div class="mc-field-group">
									<input type="text" value="" name="FNAME" class="" id="mce-FNAME" placeholder="<?php esc_html_e( 'First name' ); ?>">
								</div>
							</td>
							<td>
								<div class="mc-field-group">
									<input type="text" value="" name="LNAME" class="" id="mce-LNAME" placeholder="<?php esc_html_e( 'Last name' ); ?>">
								</div>
							</td>
							<td>
								<div id="mce-responses" class="clear">
									<div class="response" id="mce-error-response" style="display:none"></div>
									<div class="response" id="mce-success-response" style="display:none"></div>
								</div>
								<!-- real people should not fill this in and expect good things - do not remove this or risk form bot signups-->
								<div style="position: absolute; left: -5000px;" aria-hidden="true">
									<input type="text" name="b_e68e0bb69f2cdf2dfd083856c_054538336e" tabindex="-1" value="">
								</div>
								<div class="clear">
									<input type="submit" value="Subscribe" name="subscribe" id="mc-embedded-subscribe" class="button">
								</div>
							</td>
						</tr>
					</tbody>
				</table>
			</form>

			<?php $this->tabs(); ?>

			<p class="about-description"><?php _e('Use the tips below to get started using WP User Manager. You will be up and running in no time!', 'wpum'); ?></p>

			<div id="welcome-panel" class="welcome-panel" style="padding-top:0px;">
				<div class="welcome-panel-content">
					<div class="welcome-panel-column-container">
						<div class="welcome-panel-column">
							<h4><?php _e( 'Configure WP User Manager', 'wpum' );?></h4>
							<ul>
								<li><a href="<?php echo admin_url( 'users.php?page=wpum-settings#/general/login' ); ?>" class="welcome-icon dashicons-admin-network" target="_blank"><?php _e( 'Setup login method', 'wpum' ); ?></a></li>
								<li><a href="<?php echo admin_url( 'users.php?page=wpum-settings#/emails' ); ?>" class="welcome-icon dashicons-email-alt" target="_blank"><?php _e( 'Setup notifications', 'wpum' ); ?></a></li>
								<li><a href="<?php echo admin_url( 'users.php?page=wpum-emails' ); ?>" class="welcome-icon dashicons-admin-customizer" target="_blank"><?php _e( 'Customize email templates', 'wpum' ); ?></a></li>
							</ul>
						</div>
						<div class="welcome-panel-column">
							<h4><?php _e( 'Customize Profiles', 'wpum' );?></h4>
							<ul>
								<li><a href="<?php echo admin_url( 'users.php?page=wpum-settings#/profiles' ); ?>" class="welcome-icon dashicons-admin-users" target="_blank"><?php _e('Customize profiles', 'wpum'); ?></a></li>
								<li><a href="<?php echo admin_url( 'users.php?page=wpum-custom-fields#/' ); ?>" class="welcome-icon dashicons-admin-settings" target="_blank"><?php _e('Customize fields', 'wpum'); ?></a></li>
								<li><a href="<?php echo admin_url( 'edit.php?post_type=wpum_directory' ); ?>" class="welcome-icon dashicons-groups" target="_blank"><?php _e('Create user directories', 'wpum'); ?></a></li>
							</ul>
						</div>
						<div class="welcome-panel-column welcome-panel-last">
							<h4><?php _e('Documentation', 'wpum'); ?></h4>
							<p class="welcome-icon welcome-learn-more"><?php echo sprintf( __( 'Looking for help? <a href="%s" target="_blank">WP User Manager documentation</a> has got you covered.', 'wpum' ), 'http://docs.wpusermanager.com' ); ?> <br/><br/><a href="http://docs.wpusermanager.com" class="button" target="_blank"><?php _e('Read documentation', 'wpum') ;?></a></p>
						</div>
					</div>
				</div>
			</div>

			<div class="changelog under-the-hood feature-list">

				<div class="feature-section  two-col">

					<div class="col">
						<h3><?php _e('Looking for help ?', 'wpum'); ?></h3>
						<p><?php echo sprintf( __('We do all we can to provide every user with the best support possible. If you encounter a problem or have a question, please <a href="%s" target="_blank">contact us.</a> Make sure you <a href="%s">read the documentation</a> first.', 'wpum'), 'https://wpusermanager.com/contacts', 'https://docs.wpusermanager.com' ); ?></p>
					</div>

					<div class="last-feature col">
						<h3><?php _e('Extensions for WP User Manager', 'wpum'); ?></h3>
						<p><?php echo __('Browse our growing collection of add-ons built specifically for WPUM to customize the functionality of your community.', 'wpum'); ?></p>
						<a href="<?php echo admin_url(); ?>" class="button"><?php esc_html_e( 'Browse add-ons', 'wpum' ); ?> &raquo;</a>
					</div>

					<hr>

					<div class="return-to-dashboard">
						<a href="<?php echo admin_url( 'users.php?page=wpum-settings' ); ?>"><?php _e('Go To WP User Manager &rarr; Settings', 'wpum'); ?></a>
					</div>

				</div>
			</div>

		</div>

		<?php
	}

	/**
	 * Sends user to the Welcome page on first activation of WPUM.
	 *
	 * @access public
	 * @since 1.0
	 * @global $wpum_options Array of all the WPUM Options
	 * @return void
	 */
	public function welcome() {

		global $wpum_options;

		// Bail if no activation redirect
		if ( ! get_transient( '_wpum_activation_redirect' ) ) {
			return;
		}

		// Delete the redirect transient
		delete_transient( '_wpum_activation_redirect' );

		// Bail if activating from network, or bulk
		if ( is_network_admin() || isset( $_GET['activate-multi'] ) ) {
			return;
		}

		$upgrade = get_option( 'wpum_version_upgraded_from' );
		if( ! $upgrade ) {
			wp_safe_redirect( admin_url( 'index.php?page=wpum-getting-started' ) );
			exit;
		}

	}

}

new WPUM_Getting_Started();
