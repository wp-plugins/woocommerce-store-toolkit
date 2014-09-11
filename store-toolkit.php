<?php
/*
Plugin Name: WooCommerce - Store Toolkit
Plugin URI: http://www.visser.com.au/woocommerce/plugins/store-toolkit/
Description: Store Toolkit includes a growing set of commonly-used WooCommerce administration tools aimed at web developers and store maintainers.
Version: 1.4.1
Author: Visser Labs
Author URI: http://www.visser.com.au/about/
License: GPL2
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

define( 'WOO_ST_DIRNAME', basename( dirname( __FILE__ ) ) );
define( 'WOO_ST_RELPATH', basename( dirname( __FILE__ ) ) . '/' . basename( __FILE__ ) );
define( 'WOO_ST_PATH', plugin_dir_path( __FILE__ ) );
define( 'WOO_ST_PREFIX', 'woo_st' );

include_once( WOO_ST_PATH . 'commmon/common.php' );
include_once( WOO_ST_PATH . 'includes/functions.php' );

/**
 * For developers: Store Toolkit debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 */
define( 'WOO_ST_DEBUG', 'woo_st' );

function woo_st_i18n() {

	load_plugin_textdomain( 'woo_st', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

}
add_action( 'init', 'woo_st_i18n' );

if( is_admin() ) {

	/* Start of: WordPress Administration */


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
					woo_st_clear_dataset( 'product_images' );
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
				if( isset( $_POST['woo_st_media_images'] ) )
					woo_st_clear_dataset( 'media_images' );

				break;

		}

	}
	add_action( 'admin_init', 'woo_st_admin_init' );

	function woo_st_default_html_page() {

		global $wpdb;

		$tab = false;
		if( isset( $_GET['tab'] ) )
			$tab = $_GET['tab'];

		include_once( 'templates/admin/tabs.php' );

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
			add_meta_box( 'woo-order-post_data', __( 'Order Post Meta', 'woo_st' ), 'woo_st_order_data_meta_box', $post_type, 'normal', 'default' );
			add_meta_box( 'woo-order-post_item', __( 'Order Items Post Meta', 'woo_st' ), 'woo_st_order_items_data_meta_box', $post_type, 'normal', 'default' );
		}

	}
	add_action( 'add_meta_boxes', 'add_order_data_meta_box', 10, 2 );

	function woo_st_order_data_meta_box() {

		global $post;

		$post_meta = get_post_custom( $post->ID );

		include_once( WOO_ST_PATH . 'templates/admin/woo-admin_st-orders_data.php' );

	}

	function woo_st_order_items_data_meta_box() {

		global $post, $wpdb;

		$order_items_sql = $wpdb->prepare( "SELECT `order_item_id` as id, `order_item_name` as name, `order_item_type` as type FROM `" . $wpdb->prefix . "woocommerce_order_items` WHERE `order_id` = %d", $post->ID );
		if( $order_items = $wpdb->get_results( $order_items_sql ) ) {
			foreach( $order_items as $key => $order_item ) {
				$order_itemmeta_sql = $wpdb->prepare( "SELECT `meta_key`, `meta_value` FROM `" . $wpdb->prefix . "woocommerce_order_itemmeta` AS order_itemmeta WHERE `order_item_id` = %d ORDER BY `order_itemmeta`.`meta_key` ASC", $order_item->id );
				$order_items[$key]->meta = $wpdb->get_results( $order_itemmeta_sql );
			}
		}

		include_once( WOO_ST_PATH . 'templates/admin/woo-admin_st-order_items_data.php' );

	}

	function add_product_data_meta_box( $post_type, $post = '' ) {

		if( $post->post_status <> 'auto-draft' ) {
			$post_type = 'product';
			add_meta_box( 'woo-product-post_data', __( 'Product Post Meta', 'woo_st' ), 'woo_st_product_data_meta_box', $post_type, 'normal', 'default' );
		}

	}
	add_action( 'add_meta_boxes', 'add_product_data_meta_box', 10, 2 );

	function woo_st_product_data_meta_box() {

		global $post;

		$post_meta = get_post_custom( $post->ID );

		include_once( WOO_ST_PATH . 'templates/admin/woo-admin_st-products_data.php' );

	}

	/* End of: WordPress Administration */

}
?>