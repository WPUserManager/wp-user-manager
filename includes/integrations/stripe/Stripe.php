<?php
/**
 * Handles the Stripe init
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2023, WP User Manager
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
 */

namespace WPUserManager\Stripe;

use WPUserManager\Stripe\Controllers\Products;

/**
 * Stripe
 */
class Stripe {

	/**
	 * @var Products
	 */
	protected static $products;

	/**
	 * Init
	 */
	public function init() {
		$connect = new Connect();
		$connect->init();

		$settings = new Settings( $connect );
		$settings->init();

		$key            = $connect->get_stripe_key();
		$secret         = $connect->get_stripe_secret();
		$webhook_secret = $connect->get_stripe_webhook_secret();

		$stripeWebhook   = new StripeWebhookController( $secret, $webhook_secret, $connect->get_gateway_mode() );
		$webhookEndpoint = new WebhookEndpoint( $stripeWebhook );

		if ( ! $key || ! $secret || ! $webhook_secret ) {
			return;
		}

		$products       = new Products( $secret, $connect->get_gateway_mode() );
		self::$products = $products;

		$billingClass = apply_filters( 'wpum_stripe_billing_class', Billing::class );
		$billing      = new $billingClass( $products, $connect->get_base_url() );

		$test_mode = $connect->is_test_mode();

		$settings->setProducts( $products );

		( new Assets( $key ) )->init();
		( new Registration( $key, $secret, $test_mode, $billing, $products ) )->init();
		( new Account( $key, $secret, $connect->get_gateway_mode(), $billing, $products ) )->init();

		$webhookEndpoint->init();
	}

	/**
	 * @return Products
	 */
	public static function products() {
		return self::$products;
	}

	/**
	 * @return string
	 */
	public static function getBillingURL() {
		return apply_filters( 'wpum_stripe_account_billing_url', get_permalink( wpum_get_core_page_id( 'account' ) ) . 'billing' );
	}

	/**
	 * @param string $currency
	 *
	 * @return string
	 */
	public static function currencySymbol( $currency ) {
		$currencies = array(
			'aed' => 'AED',
			'afn' => '&#1547;',
			'all' => '&#76;&#101;&#107;',
			'amd' => 'AMD',
			'ang' => '&#402;',
			'aoa' => 'AOA',
			'ars' => '&#36;',
			'aud' => '&#36;',
			'awg' => '&#402;',
			'azn' => '&#1084;&#1072;&#1085;',
			'bam' => '&#75;&#77;',
			'bbd' => '&#36;',
			'bdt' => 'BDT',
			'bgn' => '&#1083;&#1074;',
			'bhd' => 'BHD',
			'bif' => 'BIF',
			'bmd' => '&#36;',
			'bnd' => '&#36;',
			'bob' => '&#36;&#98;',
			'brl' => '&#82;&#36;',
			'bsd' => '&#36;',
			'btn' => 'BTN',
			'bwp' => '&#80;',
			'byr' => '&#112;&#46;',
			'bzd' => '&#66;&#90;&#36;',
			'cad' => '&#36;',
			'cdf' => 'CDF',
			'chf' => '&#67;&#72;&#70;',
			'clp' => '&#36;',
			'cny' => '&#165;',
			'cop' => '&#36;',
			'crc' => '&#8353;',
			'cuc' => 'CUC',
			'cup' => '&#8369;',
			'cve' => 'CVE',
			'czk' => '&#75;&#269;',
			'djf' => 'DJF',
			'dkk' => '&#107;&#114;',
			'dop' => '&#82;&#68;&#36;',
			'dzd' => 'DZD',
			'egp' => '&#163;',
			'ern' => 'ERN',
			'etb' => 'ETB',
			'eur' => '&#8364;',
			'fjd' => '&#36;',
			'fkp' => '&#163;',
			'gbp' => '&#163;',
			'gel' => 'GEL',
			'ggp' => '&#163;',
			'ghs' => '&#162;',
			'gip' => '&#163;',
			'gmd' => 'GMD',
			'gnf' => 'GNF',
			'gtq' => '&#81;',
			'gyd' => '&#36;',
			'hkd' => '&#36;',
			'hnl' => '&#76;',
			'hrk' => '&#107;&#110;',
			'htg' => 'HTG',
			'huf' => '&#70;&#116;',
			'idr' => '&#82;&#112;',
			'ils' => '&#8362;',
			'imp' => '&#163;',
			'inr' => '&#8377;',
			'iqd' => 'IQD',
			'irr' => '&#65020;',
			'isk' => '&#107;&#114;',
			'jep' => '&#163;',
			'jmd' => '&#74;&#36;',
			'jod' => 'JOD',
			'jpy' => '&#165;',
			'kes' => 'KES',
			'kgs' => '&#1083;&#1074;',
			'khr' => '&#6107;',
			'kmf' => 'KMF',
			'kpw' => '&#8361;',
			'krw' => '&#8361;',
			'kwd' => 'KWD',
			'kyd' => '&#36;',
			'kzt' => '&#1083;&#1074;',
			'lak' => '&#8365;',
			'lbp' => '&#163;',
			'lkr' => '&#8360;',
			'lrd' => '&#36;',
			'lsl' => 'LSL',
			'lyd' => 'LYD',
			'mad' => 'MAD',
			'mdl' => 'MDL',
			'mga' => 'MGA',
			'mkd' => '&#1076;&#1077;&#1085;',
			'mmk' => 'MMK',
			'mnt' => '&#8366;',
			'mop' => 'MOP',
			'mro' => 'MRO',
			'mur' => '&#8360;',
			'mvr' => 'MVR',
			'mwk' => 'MWK',
			'mxn' => '&#36;',
			'myr' => '&#82;&#77;',
			'mzn' => '&#77;&#84;',
			'nad' => '&#36;',
			'ngn' => '&#8358;',
			'nio' => '&#67;&#36;',
			'nok' => '&#107;&#114;',
			'npr' => '&#8360;',
			'nzd' => '&#36;',
			'omr' => '&#65020;',
			'pab' => '&#66;&#47;&#46;',
			'pen' => '&#83;&#47;&#46;',
			'pgk' => 'PGK',
			'php' => '&#8369;',
			'pkr' => '&#8360;',
			'pln' => '&#122;&#322;',
			'prb' => 'PRB',
			'pyg' => '&#71;&#115;',
			'qar' => '&#65020;',
			'ron' => '&#108;&#101;&#105;',
			'rsd' => '&#1044;&#1080;&#1085;&#46;',
			'rub' => '&#1088;&#1091;&#1073;',
			'rwf' => 'RWF',
			'sar' => '&#65020;',
			'sbd' => '&#36;',
			'scr' => '&#8360;',
			'sdg' => 'SDG',
			'sek' => '&#107;&#114;',
			'sgd' => '&#36;',
			'shp' => '&#163;',
			'sll' => 'SLL',
			'sos' => '&#83;',
			'srd' => '&#36;',
			'ssp' => 'SSP',
			'std' => 'STD',
			'syp' => '&#163;',
			'szl' => 'SZL',
			'thb' => '&#3647;',
			'tjs' => 'TJS',
			'tmt' => 'TMT',
			'tnd' => 'TND',
			'top' => 'TOP',
			'try' => '&#8378;',
			'ttd' => '&#84;&#84;&#36;',
			'twd' => '&#78;&#84;&#36;',
			'tzs' => 'TZS',
			'uah' => '&#8372;',
			'ugx' => 'UGX',
			'usd' => '&#36;',
			'uyu' => '&#36;&#85;',
			'uzs' => '&#1083;&#1074;',
			'vef' => '&#66;&#115;',
			'vnd' => '&#8363;',
			'vuv' => 'VUV',
			'wst' => 'WST',
			'xaf' => 'XAF',
			'xcd' => '&#36;',
			'xof' => 'XOF',
			'xpf' => 'XPF',
			'yer' => '&#65020;',
			'zar' => '&#82;',
			'zmw' => 'ZMW',
		);
		if ( array_key_exists( $currency, $currencies ) ) {
			$symbol = $currencies[ $currency ];
		} else {
			$symbol = $currency;
		}

		return $symbol;
	}
}
