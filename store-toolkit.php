<?php
/*
Plugin Name: WooCommerce - Store Toolkit
Plugin URI: http://www.visser.com.au/woocommerce/plugins/store-toolkit/
Description: Store Toolkit includes a growing set of commonly-used WooCommerce administration tools aimed at web developers and store maintainers.
Version: 1.3.8
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
/**
 * For developers: Store Toolkit debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 */
$woo_st['debug'] = false;

if( is_admin() ) {

	/* Start of: WordPress Administration */

	function woo_st_add_settings_link( $links, $file ) {

		static $this_plugin;
		if( !$this_plugin ) $this_plugin = plugin_basename( __FILE__ );
		if( $file == $this_plugin ) {
			// Manage
			$manage_link = sprintf( '<a href="%s">' . __( 'Manage', 'woo_st' ) . '</a>', add_query_arg( 'page', 'woo_st', 'admin.php' ) );
			array_unshift( $links, $manage_link );
		}
		return $links;

	}
	add_filter( 'plugin_action_links', 'woo_st_add_settings_link', 10, 2 );

	function woo_st_enqueue_scripts( $hook ) {

		// Settings
		$page = 'woocommerce_page_woo_st';
		if( $page == $hook ) {
			wp_enqueue_style( 'woo_st_styles', plugins_url( '/templates/admin/woo-admin_st-toolkit.css', __FILE__ ) );
			wp_enqueue_script( 'woo_st_scripts', plugins_url( '/templates/admin/woo-admin_st-toolkit.js', __FILE__ ), array( 'jquery' ) );
		}
		// Simple check that WooCommerce is activated
		if( class_exists( 'WooCommerce' ) ) {

			global $woocommerce;

			wp_enqueue_style( 'woocommerce_admin_styles', $woocommerce->plugin_url() . '/assets/css/admin.css' );

		}

	}
	add_action( 'admin_enqueue_scripts', 'woo_st_enqueue_scripts' );

	function woo_st_admin_init() {

		$action = woo_get_action();
		switch( $action ) {

			case 'nuke':
				if( !ini_get( 'safe_mode' ) )
					set_time_limit( 0 );

				// WooCommerce
				if( isset( $_POST['woo_st_products'] ) )
					woo_st_clear_dataset( 'products' );
				if( isset( $_POST['woo_st_categories'] ) ) {
					$categories = $_POST['woo_st_categories'];
					woo_st_clear_dataset( 'categories', $categories );
				} else if( isset( $_POST['woo_st_product_categories'] ) ) {
					woo_st_clear_dataset( 'categories' );
				}
				if( isset( $_POST['woo_st_product_tags'] ) )
					woo_st_clear_dataset( 'tags' );
				if( isset( $_POST['woo_st_product_images'] ) )
					woo_st_clear_dataset( 'images' );
				if( isset( $_POST['woo_st_coupons'] ) )
					woo_st_clear_dataset( 'coupons' );
				if( isset( $_POST['woo_st_attributes'] ) )
					woo_st_clear_dataset( 'attributes' );
				if( isset( $_POST['woo_st_orders'] ) ) {
					$orders = $_POST['woo_st_orders'];
					woo_st_clear_dataset( 'orders', $orders );
				} else if( isset( $_POST['woo_st_sales_orders'] ) ) {
					woo_st_clear_dataset( 'orders' );
				}

				// 3rd Party
				if( isset( $_POST['woo_st_creditcards'] ) )
					woo_st_clear_dataset( 'credit-cards' );

				// WordPress
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
	add_action( 'admin_init', 'woo_st_admin_init' );

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

	function add_order_data_meta_box( $post_type, $post = '' ) {

		if( $post->post_status <> 'auto-draft' ) {
			$post_type = 'shop_order';
			add_meta_box( 'woo-order-post_data', __( 'Order Post Meta', 'woo_oc' ), 'woo_st_order_data_meta_box', $post_type, 'normal', 'default' );
			add_meta_box( 'woo-order-post_item', __( 'Order Items Post Meta', 'woo_oc' ), 'woo_st_order_items_data_meta_box', $post_type, 'normal', 'default' );
		}

	}
	add_action( 'add_meta_boxes', 'add_order_data_meta_box', 10, 2 );

	function woo_st_order_data_meta_box() {

		global $woo_st, $post;

		$post_meta = get_post_custom( $post->ID );

		require( $woo_st['abspath'] . '/templates/admin/woo-admin_st-orders_data.php' );

	}

	function woo_st_order_items_data_meta_box() {

		global $woo_st, $post, $wpdb;

		$order_items_sql = $wpdb->prepare( "SELECT `order_item_id` as id, `order_item_name` as name, `order_item_type` as type FROM `" . $wpdb->prefix . "woocommerce_order_items` WHERE `order_id` = %d", $post->ID );
		if( $order_items = $wpdb->get_results( $order_items_sql ) ) {
			foreach( $order_items as $key => $order_item ) {
				$order_itemmeta_sql = $wpdb->prepare( "SELECT `meta_key`, `meta_value` FROM `" . $wpdb->prefix . "woocommerce_order_itemmeta` AS order_itemmeta WHERE `order_item_id` = %d ORDER BY `order_itemmeta`.`meta_key` ASC", $order_item->id );
				$order_items[$key]->meta = $wpdb->get_results( $order_itemmeta_sql );
			}
		}

		require( $woo_st['abspath'] . '/templates/admin/woo-admin_st-order_items_data.php' );

	}

	function add_product_data_meta_box( $post_type, $post = '' ) {

		if( $post->post_status <> 'auto-draft' ) {
			$post_type = 'product';
			add_meta_box( 'woo-product-post_data', __( 'Product Post Meta', 'woo_oc' ), 'woo_st_product_data_meta_box', $post_type, 'normal', 'default' );
		}

	}
	add_action( 'add_meta_boxes', 'add_product_data_meta_box', 10, 2 );

	function woo_st_product_data_meta_box() {

		global $woo_st, $post;

		$post_meta = get_post_custom( $post->ID );

		require( $woo_st['abspath'] . '/templates/admin/woo-admin_st-products_data.php' );

	}

	/* End of: WordPress Administration */

}
?>