<?php
/**
 * Newsletter opt-in for site admins.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2026, WP User Manager
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Asks the site admin, once, whether they want the WP User Manager newsletter.
 *
 * Nothing is sent anywhere until the admin clicks Subscribe. The form is shown on the
 * Getting Started page, and as a dismissible notice on WP User Manager's own admin
 * screens a week after install. Subscribing or dismissing stops it for the whole site.
 */
class WPUM_Newsletter {

	/**
	 * Sendy's form endpoint for the WP User Manager list.
	 */
	const SUBSCRIBE_URL = 'https://s.wpusermanager.com/subscribe';

	/**
	 * The WP User Manager newsletter list.
	 */
	const LIST_ID = 'CHYcl0dMX7hLOVpoAg2nBw';

	/**
	 * Site option: 'subscribed' or 'dismissed' once the admin has answered.
	 */
	const OPTION = 'wpum_newsletter_optin';

	/**
	 * Nonce action for the subscribe and dismiss requests.
	 */
	const NONCE = 'wpum_newsletter';

	/**
	 * How long after install the notice waits before asking.
	 */
	const NOTICE_DELAY = 7 * DAY_IN_SECONDS;

	/**
	 * Whether a form has been printed on this page, so the script is added once.
	 *
	 * @var bool
	 */
	private $form_rendered = false;

	/**
	 * The instance that owns the hooks.
	 *
	 * @var WPUM_Newsletter|null
	 */
	private static $instance = null;

	/**
	 * Get the instance that owns the hooks, creating it on first use.
	 *
	 * @return WPUM_Newsletter
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Get things started
	 */
	public function __construct() {
		add_action( 'admin_notices', array( $this, 'notice' ) );
		add_action( 'admin_footer', array( $this, 'footer' ) );
		add_action( 'wp_ajax_wpum_newsletter_subscribe', array( $this, 'handle_subscribe' ) );
		add_action( 'admin_post_wpum_newsletter_subscribe', array( $this, 'handle_subscribe' ) );
		add_action( 'wp_ajax_wpum_newsletter_dismiss', array( $this, 'handle_dismiss' ) );
		add_action( 'admin_post_wpum_newsletter_dismiss', array( $this, 'handle_dismiss' ) );
	}

	/**
	 * Has the site admin already subscribed or said no?
	 *
	 * @return bool
	 */
	public function is_answered() {
		return in_array( get_option( self::OPTION ), array( 'subscribed', 'dismissed' ), true );
	}

	/**
	 * Can the current user be asked at all?
	 *
	 * @return bool
	 */
	public function can_ask() {
		if ( ! current_user_can( 'manage_options' ) || $this->is_answered() ) {
			return false;
		}

		/**
		 * Filters whether WP User Manager asks the site admin to join its newsletter.
		 *
		 * @param bool $show True to ask.
		 */
		return (bool) apply_filters( 'wpum_show_newsletter_optin', true );
	}

	/**
	 * Should the notice show on the current screen?
	 *
	 * @return bool
	 */
	public function should_show_notice() {
		if ( is_network_admin() || ! $this->can_ask() ) {
			return false;
		}

		$installed = (int) get_option( 'wpum_activation_date' );
		if ( $installed && ( time() - $installed ) < self::NOTICE_DELAY ) {
			return false;
		}

		return $this->is_wpum_screen();
	}

	/**
	 * Is this one of WP User Manager's admin screens? The Getting Started page has its
	 * own form, so it doesn't count.
	 *
	 * @return bool
	 */
	private function is_wpum_screen() {
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( 'wpum-getting-started' === $page ) {
			return false;
		}

		if ( 0 === strpos( $page, 'wpum-' ) ) {
			return true;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		return $screen && 'wpum_directory' === $screen->post_type;
	}

	/**
	 * Print the notice.
	 *
	 * @return void
	 */
	public function notice() {
		if ( ! $this->should_show_notice() ) {
			return;
		}

		$dismiss_url = wp_nonce_url( admin_url( 'admin-post.php?action=wpum_newsletter_dismiss' ), self::NONCE );
		?>
		<div id="wpum-newsletter-notice" class="notice notice-info is-dismissible wpum-newsletter-notice">
			<?php $this->render_form(); ?>
			<p><a href="<?php echo esc_url( $dismiss_url ); ?>" class="wpum-newsletter-dismiss"><?php esc_html_e( 'No thanks', 'wp-user-manager' ); ?></a></p>
		</div>
		<?php
	}

	/**
	 * Print the opt-in form, pre-filled with the current user's email and first name.
	 *
	 * @return void
	 */
	public function render_form() {
		$status = isset( $_GET['wpum_newsletter'] ) ? sanitize_key( wp_unslash( $_GET['wpum_newsletter'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( ! $this->can_ask() ) {
			if ( 'subscribed' === $status && 'subscribed' === get_option( self::OPTION ) ) {
				echo '<p class="wpum-newsletter-optin__message">' . esc_html( $this->success_message() ) . '</p>';
			}
			return;
		}

		$this->form_rendered = true;

		$user       = wp_get_current_user();
		$first_name = get_user_meta( $user->ID, 'first_name', true );
		$redirect   = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : admin_url();
		?>
		<form class="wpum-newsletter-optin" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<p class="wpum-newsletter-optin__intro">
				<strong><?php esc_html_e( 'Get WP User Manager news by email', 'wp-user-manager' ); ?></strong><br/>
				<?php esc_html_e( 'Product updates, security notices, new addons and the occasional offer.', 'wp-user-manager' ); ?>
			</p>
			<p class="wpum-newsletter-optin__fields">
				<label class="screen-reader-text" for="wpum-newsletter-email"><?php esc_html_e( 'Email address', 'wp-user-manager' ); ?></label>
				<input type="email" id="wpum-newsletter-email" name="email" required value="<?php echo esc_attr( $user->user_email ); ?>" placeholder="<?php esc_attr_e( 'Email address', 'wp-user-manager' ); ?>">
				<label class="screen-reader-text" for="wpum-newsletter-name"><?php esc_html_e( 'First name', 'wp-user-manager' ); ?></label>
				<input type="text" id="wpum-newsletter-name" name="name" value="<?php echo esc_attr( $first_name ); ?>" placeholder="<?php esc_attr_e( 'First name', 'wp-user-manager' ); ?>">
				<button type="submit" class="button button-primary"><?php esc_html_e( 'Subscribe', 'wp-user-manager' ); ?></button>
			</p>
			<p class="description">
				<?php
				// translators: %s privacy policy URL
				echo wp_kses_post( sprintf( __( 'Clicking Subscribe sends this email address and name to wpusermanager.com. Unsubscribe at any time. <a href="%s" target="_blank">Privacy policy</a>', 'wp-user-manager' ), 'https://wpusermanager.com/privacy-policy/' ) );
				?>
			</p>
			<p class="wpum-newsletter-optin__message" role="status" aria-live="polite">
				<?php
				if ( 'error' === $status ) {
					esc_html_e( 'Sorry, that didn\'t work. Please check the email address and try again.', 'wp-user-manager' );
				}
				?>
			</p>
			<input type="hidden" name="action" value="wpum_newsletter_subscribe">
			<input type="hidden" name="redirect" value="<?php echo esc_attr( $redirect ); ?>">
			<?php wp_nonce_field( self::NONCE ); ?>
		</form>
		<?php
	}

	/**
	 * Subscribe the address from the form. Answers admin-ajax with JSON, and a plain
	 * form post with a redirect back.
	 *
	 * @return void
	 */
	public function handle_subscribe() {
		check_admin_referer( self::NONCE );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'wp-user-manager' ), 403 );
		}

		$email  = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		$name   = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$result = $this->subscribe( $email, $name );

		if ( ! is_wp_error( $result ) ) {
			update_option( self::OPTION, 'subscribed', false );
		}

		if ( wp_doing_ajax() ) {
			if ( is_wp_error( $result ) ) {
				wp_send_json_error( array( 'message' => $result->get_error_message() ) );
			}
			wp_send_json_success( array( 'message' => $this->success_message() ) );
		}

		$this->redirect_back( is_wp_error( $result ) ? 'error' : 'subscribed' );
	}

	/**
	 * Remember that the site admin doesn't want the newsletter.
	 *
	 * @return void
	 */
	public function handle_dismiss() {
		check_admin_referer( self::NONCE );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'wp-user-manager' ), 403 );
		}

		if ( 'subscribed' !== get_option( self::OPTION ) ) {
			update_option( self::OPTION, 'dismissed', false );
		}

		if ( wp_doing_ajax() ) {
			wp_send_json_success();
		}

		$this->redirect_back( 'dismissed' );
	}

	/**
	 * Send the address to the newsletter list.
	 *
	 * Uses Sendy's public form endpoint, which needs no API key: on success it redirects
	 * to the list's thank-you page or prints a "subscribed" page; on failure it prints
	 * the reason.
	 *
	 * @param string $email Email address.
	 * @param string $name  First name, may be empty.
	 *
	 * @return true|WP_Error
	 */
	public function subscribe( $email, $name = '' ) {
		if ( ! is_email( $email ) ) {
			return new WP_Error( 'wpum_newsletter_invalid_email', __( 'Please enter a valid email address.', 'wp-user-manager' ) );
		}

		$response = wp_remote_post(
			self::SUBSCRIBE_URL,
			array(
				'timeout'     => 15,
				'redirection' => 0,
				'body'        => array(
					'email'   => $email,
					'name'    => $name,
					'list'    => self::LIST_ID,
					'Source'  => 'plugin',
					'gdpr'    => 'true',
					'subform' => 'yes',
					'hp'      => '',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'wpum_newsletter_request_failed', __( 'Sorry, we couldn\'t reach wpusermanager.com. Please try again later.', 'wp-user-manager' ) );
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		if ( $code >= 300 && $code < 400 ) {
			return true;
		}

		$body = wp_remote_retrieve_body( $response );
		if ( 200 === $code && ( false !== stripos( $body, 'subscribed!' ) || false !== stripos( $body, 'confirmation email' ) ) ) {
			return true;
		}

		$reason = preg_match( '#<h2>(.*?)</h2>#is', $body, $matches ) ? wp_strip_all_tags( $matches[1] ) : '';

		return new WP_Error( 'wpum_newsletter_rejected', $reason ? $reason : __( 'Sorry, that didn\'t work. Please try again later.', 'wp-user-manager' ) );
	}

	/**
	 * What the admin sees after subscribing. The list uses double opt-in, so they
	 * aren't on it until they click the link in Sendy's confirmation email.
	 *
	 * @return string
	 */
	private function success_message() {
		return __( 'Thanks! Check your inbox and click the link in our email to confirm your subscription.', 'wp-user-manager' );
	}

	/**
	 * Send a plain form post back to the screen it came from.
	 *
	 * @param string $status Result, added to the URL as wpum_newsletter.
	 *
	 * @return void
	 */
	private function redirect_back( $status ) {
		$redirect = isset( $_POST['redirect'] ) ? esc_url_raw( wp_unslash( $_POST['redirect'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! $redirect ) {
			$redirect = wp_get_referer();
		}

		wp_safe_redirect( add_query_arg( 'wpum_newsletter', $status, $redirect ? $redirect : admin_url() ) );
		exit;
	}

	/**
	 * Submit the form and dismiss the notice without leaving the page.
	 *
	 * @return void
	 */
	public function footer() {
		if ( ! $this->form_rendered ) {
			return;
		}
		?>
		<style>
			.wpum-newsletter-optin__fields input[type="email"], .wpum-newsletter-optin__fields input[type="text"] { min-width: 220px; }
			.wpum-newsletter-optin__message:empty { display: none; }
		</style>
		<script>
		( function() {
			var ajaxUrl = <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;
			var nonce = <?php echo wp_json_encode( wp_create_nonce( self::NONCE ) ); ?>;

			document.querySelectorAll( '.wpum-newsletter-optin' ).forEach( function( form ) {
				form.addEventListener( 'submit', function( event ) {
					event.preventDefault();
					var button = form.querySelector( 'button[type="submit"]' );
					var message = form.querySelector( '.wpum-newsletter-optin__message' );
					var data = new FormData( form );
					button.disabled = true;

					fetch( ajaxUrl, { method: 'POST', credentials: 'same-origin', body: data } )
						.then( function( response ) { return response.json(); } )
						.then( function( result ) {
							message.textContent = result.data && result.data.message ? result.data.message : '';
							if ( result.success ) {
								form.querySelector( '.wpum-newsletter-optin__fields' ).remove();
								var notice = document.getElementById( 'wpum-newsletter-notice' );
								var noThanks = notice ? notice.querySelector( '.wpum-newsletter-dismiss' ) : null;
								if ( noThanks ) {
									noThanks.parentNode.remove();
								}
							} else {
								button.disabled = false;
							}
						} )
						.catch( function() {
							form.submit();
						} );
				} );
			} );

			var notice = document.getElementById( 'wpum-newsletter-notice' );
			if ( ! notice ) {
				return;
			}

			function dismiss() {
				var data = new FormData();
				data.append( 'action', 'wpum_newsletter_dismiss' );
				data.append( '_wpnonce', nonce );
				return fetch( ajaxUrl, { method: 'POST', credentials: 'same-origin', body: data } );
			}

			notice.addEventListener( 'click', function( event ) {
				if ( event.target.classList.contains( 'notice-dismiss' ) ) {
					dismiss();
				}
				if ( event.target.classList.contains( 'wpum-newsletter-dismiss' ) ) {
					event.preventDefault();
					dismiss().then( function() {
						notice.remove();
					} );
				}
			} );
		} )();
		</script>
		<?php
	}
}

WPUM_Newsletter::instance();
