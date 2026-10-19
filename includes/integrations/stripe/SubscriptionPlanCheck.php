<?php
/**
 * Finds members whose subscription is for a plan they may not pay for
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2026, WP User Manager
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
 */

namespace WPUserManager\Stripe;

use WPUserManager\Stripe\Models\User;

/**
 * A subscription switched to a price that isn't one of the member's plans, such as a
 * cheaper plan from another registration form chosen in the Stripe customer portal,
 * doesn't count as subscribed. Tell site admins which members that applies to, and
 * what to do about it.
 */
class SubscriptionPlanCheck {

	const CRON_HOOK = 'wpum_stripe_check_subscription_plans';

	const BATCH = 200;

	const NOTICE_LIMIT = 10;

	/**
	 * @var string
	 */
	protected $gateway_mode;

	/**
	 * @param string $gateway_mode
	 */
	public function __construct( $gateway_mode ) {
		$this->gateway_mode = $gateway_mode;
	}

	/**
	 * Init
	 */
	public function init() {
		add_action( self::CRON_HOOK, array( $this, 'scan' ) );

		add_action( 'wpum_stripe_webhook_subscription_created', array( $this, 'check_user' ) );
		add_action( 'wpum_stripe_webhook_subscription_updated', array( $this, 'check_subscription' ) );
		add_action( 'wpum_stripe_webhook_subscription_deleted', array( $this, 'check_subscription' ) );

		add_action( 'admin_init', array( $this, 'schedule' ) );
		add_action( 'admin_init', array( $this, 'handle_check_again' ) );
		add_action( 'admin_init', array( $this, 'register_notice' ) );
	}

	/**
	 * Check every member once a day, starting straight away after an update.
	 */
	public function schedule() {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time(), 'daily', self::CRON_HOOK );
		}
	}

	/**
	 * @return string
	 */
	protected function option_name() {
		return 'wpum_stripe_disallowed_plan_users_' . $this->gateway_mode;
	}

	/**
	 * Members with an active subscription to a plan they may not pay for.
	 *
	 * @return array User ID => Stripe price ID.
	 */
	public function get_affected_users() {
		$affected = get_option( $this->option_name(), array() );

		return is_array( $affected ) ? $affected : array();
	}

	/**
	 * @param array $affected
	 */
	protected function save( $affected ) {
		ksort( $affected );

		update_option( $this->option_name(), $affected, false );
	}

	/**
	 * The price of the member's subscription, if it's active and for a plan they may not pay for.
	 *
	 * @param int $user_id
	 *
	 * @return string
	 */
	public function disallowed_plan( $user_id ) {
		$user = new User( $user_id );
		if ( ! $user->exists() || ! $user->hasSubscriptionOnDisallowedPlan() ) {
			return '';
		}

		return (string) $user->subscription->plan_id;
	}

	/**
	 * Check every member with an active subscription.
	 */
	public function scan() {
		global $wpdb;

		$table = $wpdb->prefix . 'wpum_stripe_subscriptions';
		if ( $table !== $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) ) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			return;
		}

		$affected = array();
		$last_id  = 0;

		do {
			$user_ids = array_map( 'intval', $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT user_id FROM {$table} WHERE gateway_mode = %s AND user_id > %d AND ( ends_at IS NULL OR ends_at > %s ) ORDER BY user_id ASC LIMIT %d", $this->gateway_mode, $last_id, current_time( 'mysql' ), self::BATCH ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
			if ( empty( $user_ids ) ) {
				break;
			}

			cache_users( $user_ids );

			foreach ( $user_ids as $user_id ) {
				$plan_id = $this->disallowed_plan( $user_id );
				if ( '' !== $plan_id ) {
					$affected[ $user_id ] = $plan_id;
				}
			}

			$last_id   = end( $user_ids );
			$full_page = count( $user_ids ) === self::BATCH;
		} while ( $full_page );

		$this->save( $affected );
	}

	/**
	 * Recheck a member when Stripe tells us their subscription changed.
	 *
	 * @param object $subscription
	 */
	public function check_subscription( $subscription ) {
		if ( isset( $subscription->user_id ) ) {
			$this->check_user( $subscription->user_id );
		}
	}

	/**
	 * @param int $user_id
	 */
	public function check_user( $user_id ) {
		$user_id  = (int) $user_id;
		$affected = $this->get_affected_users();
		$plan_id  = $this->disallowed_plan( $user_id );

		if ( '' === $plan_id ) {
			if ( ! isset( $affected[ $user_id ] ) ) {
				return;
			}

			unset( $affected[ $user_id ] );
		} else {
			if ( isset( $affected[ $user_id ] ) && $affected[ $user_id ] === $plan_id ) {
				return;
			}

			$affected[ $user_id ] = $plan_id;
		}

		$this->save( $affected );
	}

	/**
	 * @return string
	 */
	protected function check_again_url() {
		return wp_nonce_url( add_query_arg( 'wpum_stripe_check_plans', 1, admin_url() ), 'wpum-stripe-check-plans' );
	}

	/**
	 * The "Check again" link in the notice.
	 */
	public function handle_check_again() {
		if ( empty( $_GET['wpum_stripe_check_plans'] ) || ! current_user_can( 'manage_options' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		check_admin_referer( 'wpum-stripe-check-plans' );

		$this->scan();

		wp_safe_redirect( remove_query_arg( array( 'wpum_stripe_check_plans', '_wpnonce' ) ) );
		exit;
	}

	/**
	 * The notice lists the members, so a new one shows it again after it was dismissed.
	 */
	public function register_notice() {
		$affected = $this->get_affected_users();
		if ( empty( $affected ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		WPUM()->notices->register_notice( 'wpum_stripe_disallowed_plans_' . md5( wp_json_encode( $affected ) ), 'warning', $this->get_notice_message( $affected ) );
	}

	/**
	 * @param array $affected User ID => Stripe price ID.
	 *
	 * @return string
	 */
	public function get_notice_message( $affected ) {
		$count = count( $affected );

		/* translators: %d the number of members. */
		$message  = '<strong>WP User Manager:</strong> ' . sprintf( _n( '%d member has an active Stripe subscription to a price that isn\'t one of their registration form\'s plans, so they don\'t have paid access.', '%d members have an active Stripe subscription to a price that isn\'t one of their registration form\'s plans, so they don\'t have paid access.', $count, 'wp-user-manager' ), $count );
		$message .= ' ' . esc_html__( 'This usually means the price was changed in the Stripe customer portal or the Stripe dashboard.', 'wp-user-manager' );

		$items = array();
		foreach ( array_slice( $affected, 0, self::NOTICE_LIMIT, true ) as $user_id => $plan_id ) {
			$user = get_userdata( $user_id );
			if ( ! $user ) {
				continue;
			}

			$form      = ( new User( $user_id ) )->getFormRegisteredWith();
			$form_name = $form && $form->exists() ? $form->get_name() : __( 'no registration form', 'wp-user-manager' );

			$items[] = sprintf( '<li><a href="%s">%s</a> (%s): <code>%s</code>, %s</li>', esc_url( get_edit_user_link( $user_id ) ), esc_html( $user->display_name ), esc_html( $user->user_email ), esc_html( $plan_id ), esc_html( $form_name ) );
		}

		$message .= '<ul style="list-style: disc; padding-left: 1.5em;">' . implode( '', $items ) . '</ul>';

		if ( $count > self::NOTICE_LIMIT ) {
			/* translators: %d the number of members not listed. */
			$message .= '<p>' . esc_html( sprintf( __( 'And %d more.', 'wp-user-manager' ), $count - self::NOTICE_LIMIT ) ) . '</p>';
		}

		$message .= '<p><strong>' . esc_html__( 'What to do', 'wp-user-manager' ) . '</strong></p><ol>';
		/* translators: %s link to the registration forms screen. */
		$message .= '<li>' . sprintf( esc_html__( 'If they should have access with that price, add it to the plans of their form in %s.', 'wp-user-manager' ), '<a href="' . esc_url( admin_url( 'users.php?page=wpum-registration-forms' ) ) . '">' . esc_html__( 'Users > Registration Forms', 'wp-user-manager' ) . '</a>' ) . '</li>';
		$message .= '<li>' . esc_html__( 'If not, ask them to switch back to their plan from Account > Billing > Manage Billing, or change their subscription in the Stripe dashboard.', 'wp-user-manager' ) . '</li>';
		$message .= '<li>' . esc_html__( 'To stop it happening, in the Stripe dashboard go to Settings > Billing > Customer portal and only offer prices from the same registration form, or turn off switching plans.', 'wp-user-manager' ) . '</li>';
		$message .= '</ol>';

		/* translators: %s the name of the filter. */
		$message .= '<p>' . sprintf( esc_html__( 'Developers can allow more prices with the %s filter.', 'wp-user-manager' ), '<code>wpum_stripe_user_allowed_plan_ids</code>' ) . ' <a href="' . esc_url( $this->check_again_url() ) . '">' . esc_html__( 'Check again', 'wp-user-manager' ) . '</a></p>';

		return $message;
	}
}
