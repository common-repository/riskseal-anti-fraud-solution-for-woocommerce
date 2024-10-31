<?php

/**
 * WC_RiskSeal_Orders_Page class
 * Displays RiskSeal order verification status on the WooCommerce Orders page
 */
class WC_RiskSeal_Orders_Page {
	public static function init() {
		add_filter( 'manage_edit-shop_order_columns', __CLASS__ . '::add_custom_column_to_orders_table' );
		add_action( 'manage_shop_order_posts_custom_column', __CLASS__ . '::add_data_to_custom_column_in_orders_table' );
	}

	public static function add_custom_column_to_orders_table( $columns ) {
		$columns['riskseal_column'] = __( 'RiskSeal Status', 'textdomain' );
		return $columns;
	}

	public static function add_data_to_custom_column_in_orders_table( $column ) {
		global $post;

		if ( 'riskseal_column' === $column ) {
			$check_id = get_post_meta( $post->ID, '_riskseal_check_id', true );
			$score = get_post_meta( $post->ID, '_riskseal_score', true );
			$status = get_post_meta( $post->ID, '_riskseal_status', true );


			if ($check_id) :
				?>

				<?php echo esc_html(ucfirst($status)); ?>
				<a href="<?php echo esc_url_raw( RISKSEAL_CLIENT_PORTAL_URL . 'checks/' . $check_id ); ?>" target="_blank">
					<?php
					switch ($status) {
						case 'fail':
							?>
							<svg style="vertical-align:middle;margin-bottom:4px" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 6C12.5523 6 13 6.44772 13 7V13C13 13.5523 12.5523 14 12 14C11.4477 14 11 13.5523 11 13V7C11 6.44772 11.4477 6 12 6Z" fill="#d63638"/><path d="M12 16C11.4477 16 11 16.4477 11 17C11 17.5523 11.4477 18 12 18C12.5523 18 13 17.5523 13 17C13 16.4477 12.5523 16 12 16Z" fill="#d63638"/><path fill-rule="evenodd" clip-rule="evenodd" d="M12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2ZM4 12C4 16.4183 7.58172 20 12 20C16.4183 20 20 16.4183 20 12C20 7.58172 16.4183 4 12 4C7.58172 4 4 7.58172 4 12Z" fill="#d63638"/></svg>
						<?php
							break;
						case 'review':
							?>
							<svg style="vertical-align:middle;margin-bottom:4px" fill="#f8c141" width="24" height="24" viewBox="0 -8 72 72" id="Layer_1" data-name="Layer 1" xmlns="http://www.w3.org/2000/svg"><title>warning</title><path d="M15.8,49.7H56.22a3.78,3.78,0,0,0,3.36-5.5L39.38,8.39a3.8,3.8,0,0,0-6.78,0L12.4,44.2A3.81,3.81,0,0,0,15.8,49.7Zm23.38-8.33a3.29,3.29,0,1,1-6.58,0V41.3a3.29,3.29,0,0,1,6.58,0ZM34.11,17.18h3.8a1.63,1.63,0,0,1,1.54,2L37.79,33.75a1.78,1.78,0,0,1-3.56,0L32.56,19.19A1.64,1.64,0,0,1,34.11,17.18Z"/></svg>
						<?php
							break;
						case 'ok':
							?>
							<svg style="vertical-align:middle;margin-bottom:4px" fill="#5b841b" width="24" height="24" version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 24 24" xml:space="preserve"><style type="text/css">.st0{fill:none;}</style><path d="M12,2C6.5,2,2,6.5,2,12s4.5,10,10,10s10-4.5,10-10S17.5,2,12,2z M10.8,16.8l-3.7-3.7l1.4-1.4l2.2,2.2l5.8-6.1L18,9.3 L10.8,16.8z"/><rect class="st0" width="24" height="24"/></svg>
						<?php
							break;
						default:
							break;
					}

					if ( isset( $score ) && ( '' !== $score ) ) { 
						echo esc_html($score);
					}
					?>
				</a>
				<?php
			endif;
		}
	}
}
