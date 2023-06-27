<?php
/**
 * Plugin Name: WooCommerce Eshop Payments
 * Plugin URI:  https://woocommerce.com/products/woocommerce-eshop-payments/
 * Description: eshop complete payments processing solution.
 * Version:     1.9.2
 * Author:      SHOPEO
 * Author URI:  https://www.shopeo.cn/
 * License:     GPL-2.0
 * Requires PHP: 7.1
 * WC requires at least: 3.9
 * WC tested up to: 6.7
 * Text Domain: woocommerce-eshop-payments
 *
 */
require_once 'vendor/autoload.php';


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! defined( 'WOOCOMMERCE_ESHOP_PAYMENTS_PLUGIN_FILE' ) ) {
	define( 'WOOCOMMERCE_ESHOP_PAYMENTS_PLUGIN_FILE', __FILE__ );
}

if ( ! defined( 'WOOCOMMERCE_ESHOP_PAYMENTS_PLUGIN_BASE' ) ) {
	define( 'WOOCOMMERCE_ESHOP_PAYMENTS_PLUGIN_BASE', plugin_basename( WOOCOMMERCE_ESHOP_PAYMENTS_PLUGIN_FILE ) );
}

if ( ! defined( 'WOOCOMMERCE_ESHOP_PAYMENTS_PATH' ) ) {
	define( 'WOOCOMMERCE_ESHOP_PAYMENTS_PATH', plugin_dir_path( WOOCOMMERCE_ESHOP_PAYMENTS_PLUGIN_FILE ) );
}

if ( ! function_exists( 'woocommerce_eshop_payments_activate' ) ) {
	function woocommerce_eshop_payments_activate() {

	}
}

register_activation_hook( __FILE__, 'woocommerce_eshop_payments_activate' );


if ( ! function_exists( 'woocommerce_eshop_payments_deactivate' ) ) {
	function woocommerce_eshop_payments_deactivate() {

	}
}

register_deactivation_hook( __FILE__, 'woocommerce_eshop_payments_deactivate' );

if ( ! function_exists( 'woocommerce_eshop_payments_load_textdomain' ) ) {
	function woocommerce_eshop_payments_load_textdomain() {
		load_plugin_textdomain( 'woocommerce-eshop-payments', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}
}

add_action( 'init', 'woocommerce_eshop_payments_load_textdomain' );

if ( ! function_exists( 'woocommerce_gateway_eshop_init' ) ) {
	function woocommerce_gateway_eshop_init() {
		if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
			return;
		}
		if ( ! class_exists( 'WC_Eshop_Gateway' ) ) {
			class WC_Eshop_Gateway extends WC_Payment_Gateway {
				
			}
		}
	}
}

add_action( 'plugins_loaded', 'woocommerce_gateway_eshop_init', 0 );

if ( ! function_exists( 'woocommerce_add_gateway_eshop_gateway' ) ) {
	function woocommerce_add_gateway_eshop_gateway( $methods ) {
		$methods[] = 'WC_Eshop_Gateway';

		return $methods;
	}
}

add_filter( 'woocommerce_payment_gateways', 'woocommerce_add_gateway_eshop_gateway' );

