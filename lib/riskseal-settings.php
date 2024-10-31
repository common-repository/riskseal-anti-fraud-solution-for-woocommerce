<?php

class WC_RiskSeal_Settings_Tab {

	/**
	 * Bootstraps the class and hooks required actions & filters.
	 */
	public static function init() {
		add_filter( 'woocommerce_settings_tabs_array', __CLASS__ . '::add_settings_tab', 50 );
		add_action( 'woocommerce_settings_tabs_riskseal_settings', __CLASS__ . '::settings_tab' );
		add_action( 'woocommerce_update_options_riskseal_settings', __CLASS__ . '::update_settings' );
	}
	
	
	/**
	 * Add a new settings tab to the WooCommerce settings tabs array.
	 *
	 * @param array $settings_tabs Array of WooCommerce setting tabs & their labels, excluding the Subscription tab.
	 * @return array $settings_tabs Array of WooCommerce setting tabs & their labels, including the Subscription tab.
	 */
	public static function add_settings_tab( $settings_tabs ) {
		$settings_tabs['riskseal_settings'] = __( 'RiskSeal Settings', 'riskseal-antifraud' );
		return $settings_tabs;
	}


	/**
	 * Uses the WooCommerce admin fields API to output settings via the @see woocommerce_admin_fields() function.
	 *
	 * @uses woocommerce_admin_fields()
	 * @uses self::get_settings()
	 */
	public static function settings_tab() {
		woocommerce_admin_fields( self::get_settings() );
	}


	/**
	 * Uses the WooCommerce options API to save settings via the @see woocommerce_update_options() function.
	 *
	 * @uses woocommerce_update_options()
	 * @uses self::get_settings()
	 */
	public static function update_settings() {
		woocommerce_update_options( self::get_settings() );
	}


	/**
	 * Get all the settings for this plugin for @see woocommerce_admin_fields() function.
	 *
	 * @return array Array of settings for @see woocommerce_admin_fields() function.
	 */
	public static function get_settings() {
		$apiKey = get_option('wc_riskseal_api_key', '');

		$statuses = wc_get_order_statuses();

		$statusOptions = [''=>'[Don\'t change status]'];
		foreach ($statuses as $k=>$v) {
			$statusOptions[$k]=$v;
		}

		$identifierOptions = [
			'email' => 'Email',
			'username' => 'Username',
			'id' => 'Internal WordPress user ID',
		];

		$integrationOptions = [
			'async' => 'Asynchrnous',
			'sync' => 'Synchrnous'
		];

		$payment_gateways = WC()->payment_gateways->payment_gateways();

		$settings = array(
			'section_title' => array(
				'name'     => __( 'RiskSeal AntiFraud', 'riskseal-antifraud' ),
				'type'     => 'title',
				'desc'     => '',
				'id'       => 'wc_settings_tab_demo_section_title'
			),
			'api_key' => array(
				'name' => __( 'API Key', 'riskseal-antifraud' ),
				'type' => 'text',
				'desc' => __( '<a href="https://riskseal.io/ecommerce-pricing/" target="_blank">Subscribe</a> for a plan or get your API key from the <a href="' . RISKSEAL_CLIENT_PORTAL_URL . '" target="_blank">RiskSeal Portal</a> if you already have one.', 'riskseal-antifraud' ),
				'id'   => 'wc_riskseal_api_key'
			),
			'integration_type' => array(
				'name' => __( 'Integration type', 'riskseal-antifraud' ),
				'type' => 'select',
				'options' => $integrationOptions,
				'desc' => __( 'Asynchrnous integration runs in the background without interruping checkout process. Synchrnous integration takes longer on the checkout page.', 'riskseal-antifraud' ),
				'id'   => 'wc_riskseal_integration_type'
			),
			'order_status_failed' => array(
				'name' => __( 'Failed order status', 'riskseal-antifraud' ),
				'type' => 'select',
				'options' => $statusOptions,
				'desc' => __( 'Select the order status you want to be automatically set if RiskSeal recommends failing it', 'riskseal-antifraud' ),
				'id'   => 'wc_riskseal_failed_order_status'
			),
			'order_status_review' => array(
				'name' => __( 'Review order status', 'riskseal-antifraud' ),
				'type' => 'select',
				'options' => $statusOptions,
				'desc' => __( 'Select the order status you want to be automatically set if RiskSeal recommends reviewing it', 'riskseal-antifraud' ),
				'id'   => 'wc_riskseal_review_order_status'
			),

			'user_identifier' => array(
				'name' => __( 'User Identifier', 'riskseal-antifraud' ),
				'type' => 'select',
				'options' => $identifierOptions,
				'desc' => __( 'Select your preferred user identifier that will be sent to RiskSeal', 'riskseal-antifraud' ),
				'id'   => 'wc_riskseal_user_identifier'
			),
		);

		$num = count($payment_gateways);
		$index = 0;

		foreach ( $payment_gateways as $gateway ) {
			$field = [
				'desc'            => __( $gateway->title, 'riskseal-antifraud' ),
				'id'              => 'wc_riskseal_pgw_' . $gateway->id,
				'default'         => $apiKey?'no':'yes',
				'type'            => 'checkbox',
				'checkboxgroup'   => '',
				'show_if_checked' => 'yes',
				'autoload'        => false,
			];

			if (0 == $index) {
				$field['title'] = __( 'Payment gateways', 'riskseal-antifraud' );
				$field['checkboxgroup'] = 'start';
			}

			if ( ( $num-1 ) == $index ) { 
				$field['checkboxgroup'] = 'end';
			}

			$settings['payment_gateway_' . $gateway->id] = $field;

			$index++;
		}

		$settings['section_end'] = array(
			'type' => 'sectionend',
			'id' => 'wc_settings_tab_demo_section_end'
		);

		return apply_filters( 'wc_riskseal_settings_settings', $settings );
	}

}
