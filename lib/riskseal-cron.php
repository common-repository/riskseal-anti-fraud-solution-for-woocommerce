<?php

add_filter( 'cron_schedules', 'riskseal_add_5min_interval' );

/**
 * Register 5 minutes interval in WP Cron
 */
function riskseal_add_5min_interval( $schedules ) { 
	$schedules['five_minutes'] = array(
		'interval' => 5*60,
		'display'  => esc_html__( 'Every Five Minutes' ), );
	return $schedules;
}

add_action( 'riskseal_cron_hook', 'riskseal_cron_exec' );

if ( ! wp_next_scheduled( 'riskseal_cron_hook' ) ) {
	wp_schedule_event( time(), 'five_minutes', 'riskseal_cron_hook' );
}

/**
 * Execute RiskSeal cron job
 */
function riskseal_cron_exec() {
	$apiKey = get_option('wc_riskseal_api_key', '');

	if ($apiKey) {
		riskseal_check_in_progress($apiKey);
		riskseal_send_failed_requests($apiKey);
	}
}

/**
 * Check orders that are currently marked as in progress
 */
function riskseal_check_in_progress( $apiKey ) {
	$apiURL = RISKSEAL_API_URL . '?api-key=' . $apiKey;

	$args = array(
		'limit' => 10,
		'meta_key' => '_riskseal_status',
		'meta_value' => 'in progress',
		'meta_compare' => '='
	);

	$order_query = new WC_Order_Query( $args );
	$posts = $order_query->get_orders();

	foreach ( $posts as $order ) {
		$check_id = get_post_meta( $order->ID, '_riskseal_check_id', true );
		$request = ['check_id' => $check_id];

		$response = wp_remote_post( $apiURL, array(
			'method' => 'POST',
			'timeout' => 10,
			'redirection' => 5,
			'httpversion' => '1.0',
			'blocking' => true,
			'headers'     => array('Content-Type' => 'application/json; charset=utf-8'),
			'body' => json_encode($request),
			'data_format' => 'body',
			'cookies' => array()
		) );
		if ( is_wp_error( $response ) ) {
			// There was an error sending the request to the API endpoint
			return;
		}

		$response_body = json_decode( wp_remote_retrieve_body( $response ) );

		if ( isset( $response_body->status ) ) {
			$order->update_meta_data( '_riskseal_status', $response_body->status );
			$order->update_meta_data( '_riskseal_score', $response_body->score );
			$order->delete_meta_data( '_riskseal_request' );

			$order->save_meta_data();

			WC_RiskSeal_Checkout::update_status(['order_id' => $order->ID]);
		}
	}
}

/**
 * Resend any requests that have failed previously
 */
function riskseal_send_failed_requests( $apiKey ) {
	$apiURL = RISKSEAL_API_URL . '?api-key=' . $apiKey;
	$integration = get_option('wc_riskseal_integration_type', 'sync');

	if ('async' == $integration) {
		$apiURL .= '&async=1';
	}

	$args = array(
		'limit' => 10,
		'meta_key' => '_riskseal_status',
		'meta_value' => 'pending retry',
		'meta_compare' => '='
	);

	$order_query = new WC_Order_Query( $args );
	$posts = $order_query->get_orders();


	foreach ( $posts as $order ) {
		$request = get_post_meta( $order->ID, '_riskseal_request', true );

		if ($request) {
			$response = wp_remote_post( $apiURL, array(
				'method' => 'POST',
				'timeout' => 10,
				'redirection' => 5,
				'httpversion' => '1.0',
				'blocking' => true,
				'headers'     => array('Content-Type' => 'application/json; charset=utf-8'),
				'body' => $request,
				'data_format' => 'body',
				'cookies' => array()
			) );
			if ( !is_wp_error( $response ) ) {
				$resp = wp_remote_retrieve_body( $response );
				$response_body = json_decode( $resp );

				if ( isset( $response_body->status ) ) {
					$order->update_meta_data( '_riskseal_check_id', $response_body->check_id );
					$order->update_meta_data( '_riskseal_status', $response_body->status );
					$order->update_meta_data( '_riskseal_score', $response_body->score );
				} elseif ( isset( $response_body->check_id ) ) {
					$order->update_meta_data( '_riskseal_check_id', $response_body->check_id );
					$order->update_meta_data( '_riskseal_status', 'in progress' );
				}

				$order->save_meta_data();
			}
		}
	}
}
