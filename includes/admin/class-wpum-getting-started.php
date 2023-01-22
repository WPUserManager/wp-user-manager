<?php
/**
 * Getting started admin page.
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
 * Getting started page
 */
class WPUM_Getting_Started {

	/**
	 * @var string The capability users should have to view the page
	 */
	public $minimum_capability = 'manage_options';

	/**
	 * Get things started
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'admin_menus' ) );
		add_action( 'admin_head', array( $this, 'admin_head' ) );
		add_action( 'admin_init', array( $this, 'welcome' ) );
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
			__( 'Getting started with WP User Manager', 'wp-user-manager' ),
			__( 'Getting started with WP User Manager', 'wp-user-manager' ),
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
		remove_submenu_page( 'index.php', 'wpum-getting-started' );
		// Badge for welcome page
		$badge_url = WPUM_PLUGIN_URL . 'assets/images/logo.svg';
		?>
		<style type="text/css" media="screen">
		/*<![CDATA[*/
		.wpum-badge {
			background: url('<?php echo esc_url( $badge_url ); ?>') center 24px/85px 85px no-repeat #fff;
			-webkit-background-size: 85px 85px;
			color: #016afe;
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
		.welcome-panel {
			background: #016afe;
		}
		.welcome-panel-column {
			display: inline-block;
		}
		.welcome-panel-content {
			min-height:auto
		}
		.wpum-welcome-screenshots {
			float: right;
			margin-left: 10px!important;
		}
		.about-wrap .feature-section {
			margin-top: 20px;
		}
		.wpum-newsletter-subscribe {
			background: white;
			padding: 10px 20px;
			margin-top: 20px;
			border-radius: 5px;
			border-top: solid 5px #016afe;
		}

		.wpum-newsletter-subscribe input[type="text"], .wpum-newsletter-subscribe input[type="email"] {
			width: 100%;
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
		$page = filter_input( INPUT_GET, 'page' );

		$selected = $page ? $page : 'wpum-about';
		?>
		<h2 class="nav-tab-wrapper">
			<a class="nav-tab <?php echo 'wpum-getting-started' === $selected ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'wpum-getting-started' ), 'index.php' ) ) ); ?>">
				<?php esc_html_e( 'Getting Started', 'wp-user-manager' ); ?>
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

			<h1>
				<?php
				// translators: %s WP User Manager version
				echo esc_html( sprintf( __( 'Welcome to WP User Manager %s', 'wp-user-manager' ), WPUM_VERSION ) );
				?>
			</h1>
			<div class="about-text">
				<div id="fb-root"></div>
				<script>(function(d, s, id) {
				var js, fjs = d.getElementsByTagName(s)[0];
				if (d.getElementById(id)) return;
				js = d.createElement(s); js.id = id;
				js.src = 'https://connect.facebook.net/en_GB/sdk.js#xfbml=1&version=v3.0&appId=5482936995082769&autoLogAppEvents=1';
				fjs.parentNode.insertBefore(js, fjs);
				}(document, 'script', 'facebook-jssdk'));</script>

				<a href="https://twitter.com/wpusermanager?ref_src=twsrc%5Etfw" class="twitter-follow-button" data-size="large" data-dnt="true" data-show-count="false">Follow @wpusermanager</a><script async src="https://platform.twitter.com/widgets.js" charset="utf-8"></script><div class="fb-like" style="top: -8px; margin-left: 3px;" data-href="https://www.facebook.com/wpusermanager/" data-layout="button" data-action="like" data-size="large" data-show-faces="false" data-share="true"></div> <?php // phpcs:ignore; ?>
				<br/>
				<?php
				// translators: %s WP User Manager version
				echo esc_html( sprintf( __( 'Thank you for installing the latest version! WP User Manager %s is ready to provide improved control over your WordPress users.', 'wp-user-manager' ), WPUM_VERSION ) );
				?>
				<form target="_blank" class="wpum-newsletter-subscribe" action="https://s.wpusermanager.com/subscribe" method="post" accept-charset="utf-8">
					<p class="wpum-pre-newsletter-form">&#9993; <?php esc_html_e( 'Be sure to sign up for the WPUM newsletter below to stay informed of important updates and news.', 'wp-user-manager' ); ?></p>
					<table class="form-table wpum-newsletter-form">
						<tbody>
							<tr>
								<td>
									<input type="email" required value="" placeholder="<?php esc_html_e( 'Email address*', 'wp-user-manager' ); ?>" name="email" class="required email">
								</td>
								<td>
									<div class="mc-field-group">
										<input type="text" value="" name="name" placeholder="<?php esc_html_e( 'First name', 'wp-user-manager' ); ?>">
									</div>
								</td>
								<td>
									<div style="display:none;">
										<label for="hp">HP</label><br/>
										<input type="text" name="hp" id="hp"/>
									</div>
									<input type="hidden" name="list" value="CHYcl0dMX7hLOVpoAg2nBw"/>
									<input type="hidden" name="Source" value="plugin"/>
									<input type="hidden" name="subform" value="yes"/>
									<div class="clear">
										<input type="submit" value="Subscribe" name="subscribe" class="button button-primary">
									</div>
								</td>
							</tr>
						</tbody>
					</table>
				</form>
			</div>
			<div class="wpum-badge">
				<?php
				// translators: %s WP User Manager version
				echo esc_html( sprintf( __( 'Version %s', 'wp-user-manager' ), WPUM_VERSION ) );
				?>
			</div>

			<?php $this->tabs(); ?>

			<p class="about-description"><?php esc_html_e( 'Use the tips below to get started using WP User Manager. You will be up and running in no time!', 'wp-user-manager' ); ?></p>

			<div id="welcome-panel" class="welcome-panel" style="padding-top:0px;">
				<div class="welcome-panel-content">
					<div class="welcome-panel-column-container">
						<div class="welcome-panel-column">
							<h4><?php esc_html_e( 'Configure WP User Manager', 'wp-user-manager' ); ?></h4>
							<ul>
								<li><a href="<?php echo esc_url( admin_url( 'users.php?page=wpum-settings#/general/login' ) ); ?>" class="welcome-icon dashicons-admin-network" target="_blank"><?php esc_html_e( 'Setup login method', 'wp-user-manager' ); ?></a></li>
								<li><a href="<?php echo esc_url( admin_url( 'users.php?page=wpum-settings#/emails' ) ); ?>" class="welcome-icon dashicons-email-alt" target="_blank"><?php esc_html_e( 'Setup notifications', 'wp-user-manager' ); ?></a></li>
								<li><a href="<?php echo esc_url( admin_url( 'users.php?page=wpum-emails' ) ); ?>" class="welcome-icon dashicons-admin-customizer" target="_blank"><?php esc_html_e( 'Customize email templates', 'wp-user-manager' ); ?></a></li>
							</ul>
						</div>
						<div class="welcome-panel-column">
							<h4><?php esc_html_e( 'Customize Profiles', 'wp-user-manager' ); ?></h4>
							<ul>
								<li><a href="<?php echo esc_url( admin_url( 'users.php?page=wpum-settings#/profiles' ) ); ?>" class="welcome-icon dashicons-admin-users" target="_blank"><?php esc_html_e( 'Customize profiles', 'wp-user-manager' ); ?></a></li>
								<li><a href="<?php echo esc_url( admin_url( 'users.php?page=wpum-custom-fields#/' ) ); ?>" class="welcome-icon dashicons-admin-settings" target="_blank"><?php esc_html_e( 'Customize fields', 'wp-user-manager' ); ?></a></li>
								<li><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=wpum_directory' ) ); ?>" class="welcome-icon dashicons-groups" target="_blank"><?php esc_html_e( 'Create user directories', 'wp-user-manager' ); ?></a></li>
							</ul>
						</div>
						<div class="welcome-panel-column welcome-panel-last">
							<h4><?php esc_html_e( 'Documentation', 'wp-user-manager' ); ?></h4>
							<p class="welcome-icon welcome-learn-more">
								<?php
								// translators: WPUM URL
								echo wp_kses_post( sprintf( __( 'Looking for help? <a href="%s" target="_blank">WP User Manager documentation</a> has got you covered.', 'wp-user-manager' ), 'https://wpusermanager.com/docs/?utm_source=WP%20User%20Manager&utm_medium=insideplugin&utm_campaign=WP%20User%20Manager&utm_content=welcome-panel' ) );
								?>
								<br/><br/><a href="https://wpusermanager.com/docs/?utm_source=WP%20User%20Manager&utm_medium=insideplugin&utm_campaign=WP%20User%20Manager&utm_content=welcome-panel" class="button" target="_blank"><?php esc_html_e( 'Read documentation', 'wp-user-manager' ); ?></a></p>
						</div>
					</div>
				</div>
			</div>

			<div class="changelog under-the-hood feature-list">

				<div class="feature-section  two-col">

					<div class="col">
						<h3><?php esc_html_e( 'Looking for help?', 'wp-user-manager' ); ?></h3>
						<p>
							<?php
							// translators: %1$s contact URL %2$s documenation URL
							echo wp_kses_post( sprintf( __( 'We do all we can to provide every user with the best support possible. If you encounter a problem or have a question, please <a href="%1$s" target="_blank">contact us.</a> Make sure you <a href="%2$s">read the documentation</a> first.', 'wp-user-manager' ), 'https://wpusermanager.com/contacts?utm_source=WP%20User%20Manager&utm_medium=insideplugin&utm_campaign=WP%20User%20Manager&utm_content=welcome-panel', 'https://wpusermanager.com/docs/?utm_source=WP%20User%20Manager&utm_medium=insideplugin&utm_campaign=WP%20User%20Manager&utm_content=welcome-panel' ) );
							?>
						</p>
					</div>

					<div class="last-feature col">
						<h3><?php esc_html_e( 'Extensions for WP User Manager', 'wp-user-manager' ); ?></h3>
						<p><?php esc_html_e( 'Browse our growing collection of addons built specifically for WPUM to customize the functionality of your community.', 'wp-user-manager' ); ?></p>
						<a href="<?php echo esc_url( admin_url( 'users.php?page=wpum-addons' ) ); ?>" class="button"><?php esc_html_e( 'Browse addons', 'wp-user-manager' ); ?> &raquo;</a>
					</div>

					<hr>

					<div class="return-to-dashboard">
						<a href="<?php echo esc_url( admin_url( 'users.php?page=wpum-settings' ) ); ?>"><?php esc_html_e( 'Go To WP User Manager &rarr; Settings', 'wp-user-manager' ); ?></a>
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
		if ( is_network_admin() || isset( $_GET['activate-multi'] ) ) { // phpcs:ignore
			return;
		}

		$upgrade = get_option( 'wpum_version_upgraded_from' );
		if ( ! $upgrade ) {
			wp_safe_redirect( admin_url( 'index.php?page=wpum-getting-started' ) );
			exit;
		}

	}

}

new WPUM_Getting_Started();
