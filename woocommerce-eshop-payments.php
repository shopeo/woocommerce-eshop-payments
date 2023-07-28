<?php
/**
 * Plugin Name: WooCommerce Eshop Payments
 * Plugin URI:  https://woocommerce.com/products/woocommerce-eshop-payments/
 * Description: eshop complete payments processing solution.
 * Version:     0.0.1
 * Author:      SHOPEO
 * Author URI:  https://shopeo.cn/
 * License:     GPL-2.0
 * Requires PHP: 7.1
 * WC requires at least: 3.9
 * WC tested up to: 6.7
 * Text Domain: woocommerce-eshop-payments
 *
 */
require_once 'vendor/autoload.php';

use Shopeo\WoocommerceEshopPayments\EshopApi;


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
				public $app_id = '';
				public $app_secret = '';

				public function __construct() {
					$this->id                 = 'eshop_pay';
					$this->icon               = plugins_url( '/assets/images/paymethod.jpg', WOOCOMMERCE_ESHOP_PAYMENTS_PLUGIN_FILE );
					$this->method_title       = __( 'Debit / Credit Card Payment', 'woocommerce-eshop-payments' );
					$this->method_description = __( 'Please note the payment times will be take a bit time please don’t refresh the page Your order will not be shipped until the payment been successfully, if the payment fails, please use bank transfer to pay, or contact us directly through online consultation, any issues and questions please contact us.', 'woocommerce-eshop-payments' );

					$this->title       = $this->method_title;
					$this->description = $this->method_description;

					$this->supports = array(
						'products'
					);
					$this->init_form_fields();

					$this->init_settings();
					$this->enabled    = $this->get_option( 'enabled' );
					$this->app_id     = $this->get_option( 'app_id' );
					$this->app_secret = $this->get_option( 'app_secret' );

					add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array(
						$this,
						'process_admin_options'
					) );
					add_action( 'woocommerce_api_' . $this->id, array( $this, 'webhook' ) );
				}

				public function init_form_fields() {
					$this->form_fields = array(
						'enabled'    => array(
							'title'       => __( 'Enabled/Disable', 'woocommerce-eshop-payments' ),
							'label'       => __( 'Enable OmiPay Gateway', 'woocommerce-eshop-payments' ),
							'type'        => 'checkbox',
							'description' => '',
							'default'     => 'on'
						),
						'app_id'     => array(
							'title'       => __( 'APPID', 'woocommerce-eshop-payments' ),
							'type'        => 'text',
							'description' => '',
							'default'     => '',
							'desc_tip'    => true,
						),
						'app_secret' => array(
							'title'       => __( 'APP Secret', 'woocommerce-eshop-payments' ),
							'type'        => 'text',
							'description' => '',
							'default'     => '',
							'desc_tip'    => true,
						)
					);
				}

				public function process_payment( $order_id ) {
					global $woocommerce;
					$order    = new WC_Order( $order_id );
					$data     = array(
						'amount'               => $order->get_total() * 100,
						'city'                 => $order->get_shipping_city(),
						'countryTwoCode'       => $order->get_shipping_country(),
						'currency'             => $order->get_currency(),
						'language'             => 'EN',
						'notifyUrl'            => home_url() . '/wc-api/' . $this->id . '?id=' . $order->get_id(),
						'outOrderNo'           => $order->get_order_number(),
						'payType'              => 'omipay',
						'postCode'             => $order->get_shipping_postcode(),
						'province'             => $order->get_shipping_state(),
						'recipientContactInfo' => $order->get_billing_phone() ?: $order->get_billing_email(),
						'recipientName'        => $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name(),
						'redirectUrl'          => $this->get_return_url( $order ),
						'streetAddress'        => $order->get_shipping_address_1() . ' ' . $order->get_shipping_address_2()
					);
					$eshopApi = new EshopApi( $this->app_id, $this->app_secret );
					$response = $eshopApi->makeOrder( $data );
					error_log( print_r( $response['body'], true ) );
					$res = json_decode( $response['body'] );
					if ( $res->code === 200 ) {
						$transaction = $res->data;
						error_log( print_r( $transaction, true ) );
						$order->update_status( 'on-hold', __( 'Awaiting payment', 'woocommerce-eshop-payments' ) );
						$order->set_transaction_id( $transaction->transaction_id );
						$woocommerce->cart->empty_cart();

						return array(
							'result'   => 'success',
							'redirect' => $transaction->checkout_url
						);
					}
				}

				public function webhook() {
					$order = new WC_Order( $_GET['id'] );
					error_log( 'WebHook:' . $_GET['id'] );
					$params = file_get_contents( "php://input" );
					error_log( print_r( $params, true ) );
					$response  = json_decode( $params );
					$data      = $response->data;
					$sign      = $data->sign;
					$timestamp = $data->timestamp;
					$nonce_str = $data->nonce_str;
					$eshopApi  = new EshopApi( $this->app_id, $this->app_secret );
					$sign1     = $eshopApi->signature( $timestamp, $nonce_str );
					if ( $sign == $sign1 ) {
						error_log( 'Payment Complete' );
						$order->payment_complete();
						wc_reduce_stock_levels( $order->get_id() );
						echo json_encode( array( 'return_code' => 'SUCCESS' ) );
					} else {
						error_log( 'Payment Fail' );
						echo json_encode( array( 'return_code' => 'FAIL' ) );
					}
				}
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

