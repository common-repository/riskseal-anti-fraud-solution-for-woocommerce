<?php

// if uninstall.php is not called by WordPress, die
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die;
}

// RiskSeal settings
$options = [
	'wc_riskseal_api_key',
	'wc_riskseal_integration_type',
	'wc_riskseal_failed_order_status',
	'wc_riskseal_review_order_status',
	'wc_riskseal_user_identifier'
];

// Payment Gateway related settings
$payment_gateways = WC()->payment_gateways->payment_gateways();
foreach ( $payment_gateways as $gateway ) {
	$options[] = 'wc_riskseal_pgw_' . $gateway->id;
}

// Remove all RiskSeal settings from the database
foreach ($options as $option) {
	delete_option( $option );

	// for site options in Multisite
	delete_site_option( $option );
}
