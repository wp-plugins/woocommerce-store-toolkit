<?php
/*
Plugin Name: WooCommerce - Store Toolkit
Plugin URI: http://www.visser.com.au/woocommerce/plugins/store-toolkit/
Description: Permanently remove all store-generated details of your WooCommerce store.
Version: 1.3.2
Author: Visser Labs
Author URI: http://www.visser.com.au/about/
License: GPL2
*/

load_plugin_textdomain( 'woo_st', null, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

include_once( 'includes/functions.php' );

include_once( 'includes/common.php' );

$woo_st = array(
	'filename' => basename( __FILE__ ),
	'dirname' => basename( dirname( __FILE__ ) ),
	'abspath' => dirname( __FILE__ ),
	'relpath' => basename( dirname( __FILE__ ) ) . '/' . basename( __FILE__ )
);

$woo_st['prefix'] = 'woo_st';
$woo_st['name'] = __( 'Store Toolkit for WooCommerce', 'woo_st' );
$woo_st['menu'] = __( 'Store Toolkit', 'woo_st' );

if( is_admin() ) {

	/* Start of: WordPress Administration */

	function woo_st_add_settings_link( $links, $file ) {

		static $this_plugin;
		if( !$this_plugin ) $this_plugin = plugin_basename( __FILE__ );
		if( $file == $this_plugin ) {
			/* Settings */
			$settings_link = sprintf( '<a href="%s">' . __( 'Manage', 'woo_st' ) . '</a>', add_query_arg( 'page', 'woo_st', 'admin.php' ) );
			array_unshift( $links, $settings_link );
		}
		return $links;

	}
	add_filter( 'plugin_action_links', 'woo_st_add_settings_link', 10, 2 );

	function woo_st_init() {

		$action = woo_get_action();
		switch( $action ) {

			case 'nuke':
				if( !ini_get( 'safe_mode' ) )
					set_time_limit( 0 );

				if( isset( $_POST['woo_st_products'] ) )
					woo_st_clear_dataset( 'products' );
				if( isset( $_POST['woo_st_product_categories'] ) )
					woo_st_clear_dataset( 'categories' );
				if( isset( $_POST['woo_st_product_tags'] ) )
					woo_st_clear_dataset( 'tags' );
				if( isset( $_POST['woo_st_product_images'] ) )
					woo_st_clear_dataset( 'images' );
				if( isset( $_POST['woo_st_sales_orders'] ) )
					woo_st_clear_dataset( 'orders' );
				if( isset( $_POST['woo_st_coupons'] ) )
					woo_st_clear_dataset( 'coupons' );
				if( isset( $_POST['woo_st_attributes'] ) )
					woo_st_clear_dataset( 'attributes' );
				if( isset( $_POST['woo_st_creditcards'] ) )
					woo_st_clear_dataset( 'credit-cards' );

				if( isset( $_POST['woo_st_categories'] ) ) {
					$categories = $_POST['woo_st_categories'];
					woo_st_clear_dataset( 'categories', $categories );
				}

				if( isset( $_POST['woo_st_posts'] ) )
					woo_st_clear_dataset( 'posts' );
				if( isset( $_POST['woo_st_post_categories'] ) )
					woo_st_clear_dataset( 'post_categories' );
				if( isset( $_POST['woo_st_post_tags'] ) )
					woo_st_clear_dataset( 'post_tags' );
				if( isset( $_POST['woo_st_links'] ) )
					woo_st_clear_dataset( 'links' );
				if( isset( $_POST['woo_st_comments'] ) )
					woo_st_clear_dataset( 'comments' );

				break;

		}

	}
	add_action( 'admin_init', 'woo_st_init' );

	function woo_st_enqueue_scripts( $hook ) {

		$page = 'woocommerce_page_woo_st';
		if( $page == $hook ) {
			wp_enqueue_style( 'woo_st_styles', plugins_url( '/templates/admin/woo-admin_st-toolkit.css', __FILE__ ) );
		}

	}
	add_action( 'admin_enqueue_scripts', 'woo_st_enqueue_scripts' );

	function woo_st_default_html_page() {

		global $woo_st, $wpdb;

		$tab = false;
		if( isset( $_GET['tab'] ) )
			$tab = $_GET['tab'];

		include_once( 'templates/admin/woo-admin_st-toolkit.php' );

	}

	function woo_st_html_page() {

		global $wpdb;

		woo_st_template_header();
		woo_st_support_donate();
		$action = woo_get_action();
		switch( $action ) {

			case 'nuke':
				$message = __( 'Chosen WooCommerce details have been permanently erased from your store.', 'woo_st' );
				$output = '<div class="updated settings-error"><p>' . $message . '</p></div>';
				echo $output;

				woo_st_default_html_page();
				break;

			default:
				woo_st_default_html_page();
				break;

		}
		woo_st_template_footer();

	}

	/* End of: WordPress Administration */

}
?>