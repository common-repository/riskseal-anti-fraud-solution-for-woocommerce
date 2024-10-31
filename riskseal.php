<?php

/*
 * Plugin Name: RiskSeal Anti-Fraud Solution for WooCommerce
 * Description: This plugin adds antifraud functionality to WooCommerce store.
 * Author: RiskSeal
 * Author URI: https://riskseal.io
 * License: GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Version: 1.0.0
 */


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! defined( 'RISKSEAL_PLUGIN_BASE' ) ) {
	define( 'RISKSEAL_PLUGIN_BASE', plugin_basename( __FILE__ ) );
}


require_once(dirname( __FILE__ ) . '/config.php');
require_once(dirname( __FILE__ ) . '/lib/riskseal-order-box.php');
require_once(dirname( __FILE__ ) . '/lib/riskseal-settings.php');
require_once(dirname( __FILE__ ) . '/lib/riskseal-orders-page.php');
require_once(dirname( __FILE__ ) . '/lib/riskseal-checkout.php');
require_once(dirname( __FILE__ ) . '/lib/riskseal-cron.php');
require_once(dirname( __FILE__ ) . '/lib/plugin-links.php');

/**
 * Checking that WooCommerce is installed as it is a dependancy
 */
register_activation_hook( __FILE__, 'riskseal_activate' );
function riskseal_activate() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		wp_die( 'Please install and activate WooCommerce first.' );
	}
}

WC_RiskSeal_Settings_Tab::init();
WC_RiskSeal_OrderDetails_Box::init();
WC_RiskSeal_Orders_Page::init();
WC_RiskSeal_Checkout::init();

/**
 * Redirecting users to the plugin settings page right after the activation
 */
function riskseal_activation( $plugin ) {
	if ( plugin_basename( __FILE__ ) == $plugin ) {
		wp_redirect( admin_url( 'admin.php?page=wc-settings&tab=riskseal_settings' ) );
		exit;
	}
}
add_action( 'activated_plugin', 'riskseal_activation' );


/**
 * Notifying administrators that they need to configure the plugin in order for it to work
 */
function riskseal_plugin_warning() {
	$apiKey = get_option('wc_riskseal_api_key', '');

	if (!$apiKey) :
		?>
	<div class="notice notice-warning is-dismissible">
		<p>You need to setup RiskSeal AntiFraud plugin on the <a href="<?php echo esc_html(admin_url( 'admin.php?page=wc-settings&tab=riskseal_settings' )); ?>">settings page</a></p>
	</div>
		<?php
	endif;
}
add_action( 'admin_notices', 'riskseal_plugin_warning' );
