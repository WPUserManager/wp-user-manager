<?php
/**
 * E2E helpers for the Stripe specs. Copied into mu-plugins while they run.
 */

// Record payments the webhook refused, so a spec can tell "rejected" from "not delivered yet".
add_action(
	'wpum_stripe_webhook_plan_not_allowed',
	function ( $user_id, $price_id ) {
		update_user_meta( $user_id, '_wpum_e2e_plan_rejected', $price_id );
	},
	10,
	2
);

/**
 * A Contributor who registered on the default form for a one-time plan and hasn't paid,
 * as an abandoned Checkout leaves them.
 *
 * @param string $login
 * @param string $email
 * @param string $password
 * @param string $price_id
 * @param int    $amount
 *
 * @return int
 */
function wpum_e2e_create_unpaid_customer( $login, $email, $password, $price_id, $amount ) {
	$user_id = wp_insert_user(
		array(
			'user_login' => $login,
			'user_email' => $email,
			'user_pass'  => $password,
			'role'       => 'contributor',
		)
	);
	if ( is_wp_error( $user_id ) ) {
		return 0;
	}

	$forms = WPUM()->registration_forms->get_forms();
	update_user_meta( $user_id, 'wpum_form_id', $forms[0]->id );
	update_user_meta( $user_id, 'wpum_stripe_payment_required', 1 );

	$product = new \WPUserManager\Stripe\Models\Product( $price_id, array( 'name' => 'E2E Premium' ), array( 'type' => 'one_time', 'unit_amount' => (int) $amount ) );
	( new \WPUserManager\Stripe\Models\User( $user_id ) )->setPlanMeta( $product->to_array() );

	return $user_id;
}

/**
 * The user's Stripe plan and access, as JSON.
 *
 * @param string $login
 *
 * @return string
 */
function wpum_e2e_plan_state( $login ) {
	$wp_user = get_user_by( 'login', $login );
	if ( ! $wp_user ) {
		return wp_json_encode( array( 'exists' => false ) );
	}

	$user = new \WPUserManager\Stripe\Models\User( $wp_user->ID );
	$plan = $user->getPlanMeta();

	return wp_json_encode(
		array(
			'exists'            => true,
			'plan'              => is_array( $plan ) && isset( $plan['id'] ) ? $plan['id'] : null,
			'paid'              => is_array( $plan ) && ! empty( $plan['paid'] ),
			'subscribed'        => $user->subscription && $user->subscription->active(),
			'subscription_plan' => $user->subscription ? $user->subscription->plan_id : null,
			'can_edit_posts'    => user_can( $wp_user->ID, 'edit_posts' ),
			'rejected'          => (string) get_user_meta( $wp_user->ID, '_wpum_e2e_plan_rejected', true ),
		)
	);
}
