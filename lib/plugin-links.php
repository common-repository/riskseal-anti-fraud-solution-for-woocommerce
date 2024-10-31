<?php

function riskseal_add_settings_link( $links ) {
	$settings_link = '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=riskseal_settings' ) . '">' . __( 'Settings' ) . '</a>';
	array_push( $links, $settings_link );

	return $links;
}

add_filter( 'plugin_action_links_' . RISKSEAL_PLUGIN_BASE, 'riskseal_add_settings_link' );
