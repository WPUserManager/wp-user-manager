<?php
/**
 * Handles all the email templates the WPUM sends.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * The class that handles sending templated emails.
 */
class WPUM_Emails {

	/**
	 * Email address from which the email should come from.
	 *
	 * @var string
	 */
	private $from_address;

	/**
	 * The name from which the email should come from.
	 *
	 * @var string
	 */
	private $from_name;

	/**
	 * Content type encoding of the email.
	 *
	 * @var string
	 */
	private $content_type;

	/**
	 * Headers string of the email.
	 *
	 * @var string
	 */
	private $headers;

	/**
	 * Wether the email is being sent as html or not.
	 *
	 * @var boolean
	 */
	private $html = true;

	/**
	 * Selected template for the email.
	 *
	 * @var string
	 */
	private $template;

	/**
	 * Heading title of the email.
	 *
	 * @var string
	 */
	private $heading = '';

	/**
	 * All available dynamic tags of the email.
	 *
	 * @var array
	 */
	private $tags;

	/**
	 * The dynamic user id on which some tags are based.
	 *
	 * @var string
	 */
	private $user_id;

	/**
	 * Get things started.
	 */
	public function __construct() {

		if ( 'none' === $this->get_template() ) {
			$this->html = false;
		}

		add_action( 'wpum_email_send_before', array( $this, 'send_before' ) );
		add_action( 'wpum_email_send_after', array( $this, 'send_after' ) );

	}

	/**
	 * Set properties of the class.
	 *
	 * @param string $key
	 * @param mixed $value
	 */
	public function __set( $key, $value ) {
		$this->$key = $value;
	}

	/**
	 * Retrieve the "From name" setting for the email.
	 *
	 * @return string
	 */
	public function get_from_name() {
		if ( ! $this->from_name ) {
			$this->from_name = wpum_get_option( 'from_name', get_bloginfo( 'name' ) );
		}
		return apply_filters( 'wpum_email_from_name', wp_specialchars_decode( $this->from_name ), $this );
	}

	/**
	 * Retrieve the "from address" email setting.
	 *
	 * @return string
	 */
	public function get_from_address() {
		if ( ! $this->from_address ) {
			$this->from_address = wpum_get_option( 'from_email', get_option( 'admin_email' ) );
		}
		return apply_filters( 'wpum_email_from_address', $this->from_address, $this );
	}

	/**
	 * Get the content type encoding of the email.
	 *
	 * @return string
	 */
	public function get_content_type() {
		if ( ! $this->content_type && $this->html ) {
			$this->content_type = apply_filters( 'wpum_email_default_content_type', 'text/html', $this );
		} elseif ( ! $this->html ) {
			$this->content_type = 'text/plain';
		}
		return apply_filters( 'wpum_email_content_type', $this->content_type, $this );
	}

	/**
	 * Retrieve the headers of the email.
	 *
	 * @return string
	 */
	public function get_headers() {
		if ( ! $this->headers ) {
			$this->headers  = "From: {$this->get_from_name()} <{$this->get_from_address()}>\r\n";
			$this->headers .= "Reply-To: {$this->get_from_address()}\r\n";
			$this->headers .= "Content-Type: {$this->get_content_type()}; charset=utf-8\r\n";
		}
		return apply_filters( 'wpum_email_headers', $this->headers, $this );
	}

	/**
	 * Retrieve a list of available templates for the emails.
	 *
	 * @return array
	 */
	public function get_templates() {
		$templates    = array(
			'default' => __( 'Default Template' ),
			'none'	  => __( 'No template, plain text only' )
		);
		return apply_filters( 'wpum_email_templates', $templates );
	}

	/**
	 * Retrieve the selected template from the options panel.
	 *
	 * @return string
	 */
	public function get_template() {
		if ( ! $this->template ) {
			$this->template = wpum_get_option( 'email_template', 'default' );
		}
		return apply_filters( 'wpum_email_template', $this->template );
	}

	/**
	 * Retrieve the heading title set for the email.
	 *
	 * @return void
	 */
	public function get_heading() {
		return apply_filters( 'wpum_email_heading', $this->heading );
	}

	/**
	 * Prepare the email to be sent.
	 *
	 * @param string $message
	 * @return void
	 */
	public function build_email( $message ) {

		if ( false === $this->html ) {
			return apply_filters( 'wpum_email_message', wp_strip_all_tags( $message ), $this );
		}

		$message = $this->text_to_html( $message );

		ob_start();

		WPUM()->templates
			->get_template_part( 'emails/header', $this->get_template() );

		do_action( 'wpum_email_header', $this );

		WPUM()->templates
			->get_template_part( 'emails/body', $this->get_template() );

		do_action( 'wpum_email_body', $this );

		WPUM()->templates
			->get_template_part( 'emails/footer', $this->get_template() );

		do_action( 'wpum_email_footer', $this );

		$body	 = ob_get_clean();
		$message = str_replace( '{email}', $message, $body );

		return apply_filters( 'wpum_email_message', $message, $this );
	}

	/**
	 * Finally send the email now.
	 *
	 * @param string $to
	 * @param string $subject
	 * @param string $message
	 * @param string $attachments
	 * @return void
	 */
	public function send( $to, $subject, $message, $attachments = '' ) {

		if ( ! did_action( 'init' ) && ! did_action( 'admin_init' ) ) {
			_doing_it_wrong( __FUNCTION__, __( 'You cannot send emails with WPUM_Emails until init/admin_init has been reached' ), null );
			return false;
		}

		$this->setup_email_tags();

		do_action( 'wpum_email_send_before', $this );

		$message     = $this->build_email( $message );
		$message     = $this->parse_tags( $message );
		$attachments = apply_filters( 'wpum_email_attachments', $attachments, $this );
		$sent        = wp_mail( $to, $subject, $message, $this->get_headers(), $attachments );

		do_action( 'affwp_email_send_after', $this );

		return $sent;

	}

	/**
	 * Modify core WP's filter to inject our own settings.
	 *
	 * @return void
	 */
	public function send_before() {
		add_filter( 'wp_mail_from', array( $this, 'get_from_address' ) );
		add_filter( 'wp_mail_from_name', array( $this, 'get_from_name' ) );
		add_filter( 'wp_mail_content_type', array( $this, 'get_content_type' ) );
	}

	/**
	 * Remove our customized filters after the email is sent.
	 *
	 * @return void
	 */
	public function send_after() {
		remove_filter( 'wp_mail_from', array( $this, 'get_from_address' ) );
		remove_filter( 'wp_mail_from_name', array( $this, 'get_from_name' ) );
		remove_filter( 'wp_mail_content_type', array( $this, 'get_content_type' ) );
		$this->heading = '';
	}

	/**
	 * Convert content of the message.
	 *
	 * @param string $message
	 * @return void
	 */
	public function text_to_html( $message ) {
		if ( 'text/html' === $this->content_type || true === $this->html ) {
			$message = wpautop( $message );
		}
		return $message;
	}

	/**
	 * Parse email tags with the appropriate callback.
	 *
	 * @param string $content
	 * @return string
	 */
	private function parse_tags( $content ) {
		// Make sure there's at least one tag
		if ( empty( $this->tags ) || ! is_array( $this->tags ) ) {
			return $content;
		}
		$new_content = preg_replace_callback( "/{([A-z0-9\-\_]+)}/s", array( $this, 'do_tag' ), $content );
		return $new_content;
	}

	/**
	 * Load all email tags into the class.
	 *
	 * @return void
	 */
	private function setup_email_tags() {
		$tags = $this->get_tags();
		foreach( $tags as $tag ) {
			if ( isset( $tag['function'] ) && is_callable( $tag['function'] ) ) {
				$this->tags[ $tag['tag'] ] = $tag;
			}
		}
	}

	/**
	 * List of available dynamic email tags.
	 *
	 * @return array
	 */
	public function get_tags() {

		$email_tags = array(
			array(
				'tag'         => 'website',
				'description' => __( 'The website url.' ),
				'function'    => 'wpum_email_tag_website'
			),
		);

		return apply_filters( 'wpum_email_tags', $email_tags, $this );

	}

	/**
	 * Parse a specific tag with it's own callback.
	 *
	 * @param string $m
	 * @return void
	 */
	private function do_tag( $m ) {
		// Get tag.
		$tag = $m[1];
		// Return tag if not set.
		if ( ! $this->email_tag_exists( $tag ) ) {
			return $m[0];
		}
		return call_user_func( $this->tags[ $tag ]['function'], $this->user_id, $tag );
	}

	/**
	 * Check if a tag exists.
	 *
	 * @param string $tag
	 * @return void
	 */
	public function email_tag_exists( $tag ) {
		return array_key_exists( $tag, $this->tags );
	}

}
