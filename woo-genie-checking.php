<?php
/*
Plugin Name: WooCommerce Genie Checking
Plugin URI: http://plugins.rmweblab.com/woo-genie-checking
Description: WooCommerce Genie Checking Payment Gateway Extends WooCommerce Payment Gateway allow customer to pay using Genie Checking.
Author: Anas
Version: 2.1.0
Author URI: http://rmweblab.com
Text Domain: woo-genie-checking
Domain Path: /languages
WC tested up to: 3.2.3
WC requires at least: 3.2.3

Copyright: © 2017 RMWebLab.
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}



/**
 * Main WC_GenieChecking class which sets the gateway up for us
 */
class WC_GenieChecking {

	/**
	 * Constructor
	 */
	public function __construct() {
		define( 'WC_GCHECK_VERSION', '1.2.1' );
		define( 'WC_GCHECK_PLUGIN_URL', untrailingslashit( plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) ) );
		define( 'WC_GCHECK_MAIN_FILE', __FILE__ );

		// Actions
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'plugin_action_links' ) );
		add_action( 'plugins_loaded', array( $this, 'init' ), 0 );
		//Process when payment complete of done.
		add_action( 'wp', array( $this, 'process_genie_checking_order_init' ) );

		add_filter( 'woocommerce_payment_gateways', array( $this, 'register_gateway' ) );
		add_action( 'woocommerce_order_status_on-hold_to_processing', array( $this, 'capture_order' ) );
		add_action( 'woocommerce_order_status_on-hold_to_completed', array( $this, 'capture_order' ) );
		add_action( 'woocommerce_order_status_on-hold_to_cancelled', array( $this, 'cancel_order' ) );
		add_action( 'woocommerce_order_status_pending_to_cancelled', array( $this, 'cancel_order' ) );
		add_action( 'woocommerce_order_status_on-hold_to_refunded', array( $this, 'cancel_order' ) );

	}

	/**
	 * Add relevant links to plugins page
	 * @param  array $links
	 * @return array
	 */
	public function plugin_action_links( $links ) {
		$plugin_links = array(
			'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=geniechecking' ) . '">' . __( 'Settings', 'woo-genie-checking' ) . '</a>',
			'<a href="http://rmweblab.com/support">' . __( 'Support', 'woo-genie-checking' ) . '</a>',
			'<a href="http://plugins.rmweblab.com/woo-genie-checking">' . __( 'Docs', 'woo-genie-checking' ) . '</a>',
		);
		return array_merge( $plugin_links, $links );
	}

	/**
	 * Init localisations and files
	 */
	public function init() {
		if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
			return;
		}

		// Includes
		require_once( 'inc/class-wc-gateway-genie-checking.php' );

		// Localisation
		load_plugin_textdomain( 'woo-genie-checking', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}

	/**
	 * Process call back result after genie cashbox payment webhook
	 *
	 * @return void
	 */
	function process_genie_checking_order_init(){
		$genie_gateway = new WC_Gateway_Genie_Checking();
		$genie_gateway->process_genie_checking_order();
	}

	/**
	 * Register the gateway for use
	 */
	public function register_gateway( $methods ) {
			if(class_exists( 'WC_Gateway_Genie_Checking' )){
				$methods[] = 'WC_Gateway_Genie_Checking';
			}
		return $methods;
	}

	/**
	 * Capture payment when the order is changed from on-hold to complete or processing
	 *
	 * @param  int $order_id
	 */
	public function capture_order( $order_id ) {
		$order = new WC_Order( $order_id );
		if ( $order->get_payment_method() == 'geniechecking' ) {
				//Used for custom order data
		}
	}

	/**
	 * Cancel pre-auth on cancellation
	 *
	 * @param  int $order_id
	 * @param  obj $order
	 */
	public function cancel_order( $order_id ) {
		$order = new WC_Order( $order_id );
		if ( $order->get_payment_method() == 'geniechecking' ) {
				//Use when cancel order

		}

	}

}

new WC_GenieChecking();
