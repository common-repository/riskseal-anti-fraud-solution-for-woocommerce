<?php

/**
 * WC_RiskSeal_Checkout class
 * Provides functionality necessary to verify orders using RiskSeal API
 */
class WC_RiskSeal_Checkout {
	/**
	 * Adds necessary hooks and filters
	 */
	public static function init() {
		add_filter( 'woocommerce_payment_successful_result', __CLASS__ . '::update_status', 10, 1);
		add_action( 'woocommerce_checkout_order_processed', __CLASS__ . '::verify_order_and_update_status', 10, 1);
		add_action( 'wp_enqueue_scripts', __CLASS__ . '::enqueue_riskseal_script' );
		add_action( 'woocommerce_checkout_billing', __CLASS__ . '::add_sdk_data_input_to_checkout_form' );
	}

	/**
	 * Change order status according to user preferrences
	 *
	 * $data - order data
	 * @return $data
	 */
	public static function update_status( $data ) { 
		$order_id = $data['order_id'];
		$order = wc_get_order( $order_id );
		$status = get_post_meta($order_id, '_riskseal_status', true);
		if ('fail' == $status) {
			$newStatus = get_option('wc_riskseal_failed_order_status');
			$order->update_status( $newStatus, 'Status changed by RiskSeal because of high risk.' );
		} elseif ('review' == $status) {
			$newStatus = get_option('wc_riskseal_review_order_status');
			$order->update_status( $newStatus, 'Status changed by RiskSeal because of risk.' );
		}

		return $data;
	}

	/**
	 * Validate order using RiskSeal API
	 *
	 * $order_id - WooCommmerce order ID
	 * @return void
	 */
	public static function verify_order_and_update_status( $order_id ) {
		$apiKey = get_option('wc_riskseal_api_key', '');
		$order = wc_get_order( $order_id );
		$payment_method = $order->get_payment_method();
		$payment_method_title = $order->get_payment_method_title();
		$credit_card_number = get_post_meta( $order_id, '_payment_account_number', true );

		$identifier = get_option('wc_riskseal_user_identifier', 'id');
		$integration = get_option('wc_riskseal_integration_type', 'sync');

		// Skip any processing if no API key was provided
		if (!$apiKey) return;

		if ('id' == $identifier) {
			$user_id = $order->get_customer_id();
		} elseif ('email' == $identifier) {
			$current_user = wp_get_current_user();
			$user_id = $current_user->user_email;
		} elseif ('username' == $identifier) {
			$current_user = wp_get_current_user();
			$user_id = $current_user->user_login;
		}

		if ( isset( $_POST['riskseal_sdk_data'] ) && isset($_POST['woocommerce-process-checkout-nonce']) && wp_verify_nonce( sanitize_text_field( $_POST['woocommerce-process-checkout-nonce'] ), 'woocommerce-process_checkout' ) ) {
			$sdkData = sanitize_text_field( $_POST['riskseal_sdk_data'] );
		} else {
			$sdkData = '';
		}

		$list = $order->get_items();
		$items = [];
		foreach ( $list as $item ) {
			$items[] = [
				'name' => $item->get_name(),
				'qty' => $item->get_quantity(),
				'price' => (float) $item->get_total()
			];
		}

		$url = admin_url('post.php?post=' . absint( $order_id ) . '&action=edit');

		// Check if this payment gateway is selected for RiskSeal processing
		$apply = get_option('wc_riskseal_pgw_' . $payment_method);
		if ('yes' != $apply) {
			return;
		}

		// Compose request
		$order_data = [
			'sdk' => $sdkData,
			'action' => 'checkout',
			'userAgent' => isset($_SERVER['HTTP_USER_AGENT'])?sanitize_text_field($_SERVER['HTTP_USER_AGENT']):'',

			'order' => [
				'order_id' => $order_id,

				'url' => $url,

				'total' => (float) $order->get_total(),
				'currency' => $order->get_currency(),

				'user_id' => ( $user_id?$user_id:'guest' ),

				'billing_email' => $order->get_billing_email(),
				'billing_first_name' => $order->get_billing_first_name(),
				'billing_last_name' => $order->get_billing_last_name(),
				'billing_address_1' => $order->get_billing_address_1(),
				'billing_address_2' => $order->get_billing_address_2(),
				'billing_city' => $order->get_billing_city(),
				'billing_state' => $order->get_billing_state(),
				'billing_postcode' => $order->get_billing_postcode(),
				'billing_country' => $order->get_billing_country(),
				'billing_phone' => $order->get_billing_phone(),

				'payment_method' => $payment_method,
				'payment_method_title' => $payment_method_title,
				'credit_card_number' => $credit_card_number,

				'shipping_first_name' => $order->get_shipping_first_name(),
				'shipping_last_name' => $order->get_shipping_last_name(),
				'shipping_company' => $order->get_shipping_company(),
				'shipping_address_1' => $order->get_shipping_address_1(),
				'shipping_address_2' => $order->get_shipping_address_2(),
				'shipping_city' => $order->get_shipping_city(),
				'shipping_state' => $order->get_shipping_state(),
				'shipping_postcode' => $order->get_shipping_postcode(),
				'shipping_country' => $order->get_shipping_country(),
				'shipping_method' => $order->get_shipping_method(),

				'items' => $items
			]
		];

		$apiURL = RISKSEAL_API_URL . '?api-key=' . $apiKey;

		if ('async' == $integration) {
			$apiURL .= '&async=1';
		}

		$request = json_encode($order_data);

		// Save request to retry in the future if needed
		$order->update_meta_data( '_riskseal_request', $request );

		// Send request
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
		if ( is_wp_error( $response ) ) {
			// There was an error sending the request to the API endpoint. we will retry later from cron job.
			$order->update_meta_data( '_riskseal_status', 'pending retry' );
			$order->save_meta_data();
			return;
		} else {
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

	/**
	 * Load RiskSeal SDK
	 */
	public static function enqueue_riskseal_script() {
		if ( is_checkout() && ! is_wc_endpoint_url( 'order-received' ) ) {
			wp_enqueue_script( 'risk-seal-sdk', RISKSEAL_SDK_URL, array(), '1.0.0', false );

			$seal_script_url = RISKSEAL_INTEGRATION_JS_URL;
			wp_enqueue_script( 'risk-seal-integration', $seal_script_url, array('risk-seal-sdk'), '1.0.0', true );
		}
	}

	/**
	 * Add field for SDK data
	 */
	public static function add_sdk_data_input_to_checkout_form() {
		echo '<div id="riskseal_sdk_data_container">';
		woocommerce_form_field( 'riskseal_sdk_data', array(
			'type'          => 'hidden',
			'class'         => array( 'form-row-wide' ),
			'label'         => '',
			'required'      => false,
		), '' );
		echo '</div>';
	}
}
