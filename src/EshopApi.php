<?php

namespace Shopeo\WoocommerceEshopPayments;

class EshopApi {
	public $point = 'http://api.store.eshop-pay.com';
	public $app_id = '';
	public $app_secret = '';

	public function __construct( $app_id, $app_secret ) {
		$this->app_id     = $app_id;
		$this->app_secret = $app_secret;
	}

	private static function getMillisecond() {
		$time  = explode( " ", microtime() );
		$time1 = explode( ".", $time[0] * 1000 );//  $time[0] * 1000;
		if ( $time1[0] < 10 ) {
			$time2 = $time[1] . "00" . $time1[0];
		} else if ( $time1[0] < 100 ) {
			$time2 = $time[1] . "0" . $time1[0];

		} else {
			$time2 = $time[1] . $time1[0];
		}

		return $time2;
	}

	public function signature( $timestamp, $nonce_str = 'wc_gateway_eshop' ) {
		$originString = $this->app_id . '&' . $timestamp . '&' . $nonce_str . '&' . $this->app_secret;
		error_log( $originString );

		$sign = strtolower( md5( $originString ) );
		error_log( $sign );

		return $sign;
	}

	public function makeOrder( $data ) {
		$url       = $this->point . '/api/omipay/order/create';
		$timestamp = time();
		$nonce_str = 'wc_gateway_eshop';
		$sign      = $this->signature( $timestamp, $nonce_str );
		$body      = json_encode( $data );
		error_log( $body );
		$response = wp_remote_post( $url, array(
			'headers' => array(
				'content-type' => 'application/json',
				'appId'        => $this->app_id,
				'nonceStr'     => $nonce_str,
				'sign'         => $sign,
				'timestamp'    => $timestamp
			),
			'body'    => $body
		) );
		//返回结果
		if ( ! is_wp_error( $response ) ) {
			return array(
				'body' => $response['body']
			);
		} else {
			wc_add_notice( __( 'Please use direct bank deposit.', 'woocommerce-eshop-payments' ), 'error' );

			return;
		}
	}
}