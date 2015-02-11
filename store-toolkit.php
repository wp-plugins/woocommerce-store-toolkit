<?php
/*
Plugin Name: WooCommerce - Store Toolkit
Plugin URI: http://www.visser.com.au/woocommerce/plugins/store-toolkit/
Description: Store Toolkit includes a growing set of commonly-used WooCommerce administration tools aimed at web developers and store maintainers.
Version: 1.4.8
Author: Visser Labs
Author URI: http://www.visser.com.au/about/
License: GPL2
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

define( 'WOO_ST_DIRNAME', basename( dirname( __FILE__ ) ) );
define( 'WOO_ST_RELPATH', basename( dirname( __FILE__ ) ) . '/' . basename( __FILE__ ) );
define( 'WOO_ST_PATH', plugin_dir_path( __FILE__ ) );
define( 'WOO_ST_PREFIX', 'woo_st' );

include_once( WOO_ST_PATH . 'common/common.php' );
include_once( WOO_ST_PATH . 'includes/functions.php' );

/**
 * For developers: Store Toolkit debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 */
define( 'WOO_ST_DEBUG', false );

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

				// List of supported datasets
				$datasets = array(
					'product',
					'product_category',
					'product_tag',
					'product_brand',
					'product_vendor',
					'product_image',
					'coupon',
					'attribute',
					'order',
					'tax_rate',
					'download_permission',
					'credit_card',
					'post',
					'post_category',
					'post_tag',
					'link',
					'comment',
					'media_image'
				);
				// Check if the re-commence nuke notice has been enabled
				if( isset( $_GET['dataset'] ) ) {
					$dataset = strtolower( sanitize_text_field( $_GET['dataset'] ) );
					if( in_array( $dataset, $datasets ) )
						woo_st_clear_dataset( $dataset );
					return;
				}

				// WooCommerce
				if( isset( $_POST['woo_st_products'] ) )
					woo_st_clear_dataset( 'product' );
				if( isset( $_POST['woo_st_categories'] ) ) {
					$categories = $_POST['woo_st_categories'];
					woo_st_clear_dataset( 'product_category', $categories );
				} else if( isset( $_POST['woo_st_product_categories'] ) ) {
					woo_st_clear_dataset( 'product_category' );
				}
				if( isset( $_POST['woo_st_product_tags'] ) )
					woo_st_clear_dataset( 'product_tag' );
				if( isset( $_POST['woo_st_product_brands'] ) )
					woo_st_clear_dataset( 'product_brand' );
				if( isset( $_POST['woo_st_product_vendors'] ) )
					woo_st_clear_dataset( 'product_vendor' );
				if( isset( $_POST['woo_st_product_images'] ) )
					woo_st_clear_dataset( 'product_image' );
				if( isset( $_POST['woo_st_coupons'] ) )
					woo_st_clear_dataset( 'coupon' );
				if( isset( $_POST['woo_st_attributes'] ) )
					woo_st_clear_dataset( 'attribute' );
				if( isset( $_POST['woo_st_orders'] ) ) {
					$orders = $_POST['woo_st_orders'];
					woo_st_clear_dataset( 'order', $orders );
				} else if( isset( $_POST['woo_st_sales_orders'] ) ) {
					woo_st_clear_dataset( 'order' );
				}
				if( isset( $_POST['woo_st_tax_rates'] ) )
					woo_st_clear_dataset( 'tax_rate' );
				if( isset( $_POST['woo_st_download_permissions'] ) )
					woo_st_clear_dataset( 'download_permission' );

				// 3rd Party
				if( isset( $_POST['woo_st_creditcards'] ) )
					woo_st_clear_dataset( 'credit_card' );

				// WordPress
				if( isset( $_POST['woo_st_posts'] ) )
					woo_st_clear_dataset( 'post' );
				if( isset( $_POST['woo_st_post_categories'] ) )
					woo_st_clear_dataset( 'post_category' );
				if( isset( $_POST['woo_st_post_tags'] ) )
					woo_st_clear_dataset( 'post_tag' );
				if( isset( $_POST['woo_st_links'] ) )
					woo_st_clear_dataset( 'link' );
				if( isset( $_POST['woo_st_comments'] ) )
					woo_st_clear_dataset( 'comment' );
				if( isset( $_POST['woo_st_media_images'] ) )
					woo_st_clear_dataset( 'media_image' );
				break;

			case 'relink-rogue-simple-type':
				woo_st_relink_rogue_simple_type();
				$url = add_query_arg( 'action', null );
				wp_redirect( $url );
				exit();
				break;

			default:
				if( current_user_can( 'manage_options' ) ) {
					// Category
					$term_taxonomy = 'product_cat';
					add_action( $term_taxonomy . '_edit_form_fields', 'woo_st_category_data_meta_box', 11 );
					// Tag
					$term_taxonomy = 'product_tag';
					add_action( $term_taxonomy . '_edit_form_fields', 'woo_st_tag_data_meta_box', 11 );
					// Brand
					$term_taxonomy = 'product_brand';
					add_action( $term_taxonomy . '_edit_form_fields', 'woo_st_brand_data_meta_box', 11 );
					add_action( 'show_user_profile', 'woo_st_user_data_meta_box', 11 );
					add_action( 'edit_user_profile', 'woo_st_user_data_meta_box', 11 );
					add_action( 'add_meta_boxes', 'add_data_meta_boxes', 10, 2 );
				}
				break;

		}

	}
	add_action( 'admin_init', 'woo_st_admin_init' );

	function woo_st_default_html_page() {

		global $wpdb;

		$tab = false;
		if( isset( $_GET['tab'] ) )
			$tab = $_GET['tab'];

		include_once( WOO_ST_PATH . 'templates/admin/tabs.php' );

	}

	function woo_st_html_page() {

		global $wpdb;

		woo_st_template_header();
		woo_st_support_donate();
		$action = woo_get_action();
		switch( $action ) {

			case 'nuke':
				$message = __( 'The selected WooCommerce and/or WordPress details from the previous screen have been permanently erased from your store. <strong>Ta da!</strong>', 'woo_st' );
				woo_st_admin_notice_html( $message );

				woo_st_default_html_page();
				break;

			default:
				woo_st_default_html_page();
				break;

		}
		woo_st_template_footer();

	}

	function add_data_meta_boxes( $post_type, $post = '' ) {

		if( $post->post_status <> 'auto-draft' ) {
			$post_type = 'product';
			add_meta_box( 'woo-product-post_data', __( 'Product Post Meta', 'woo_st' ), 'woo_st_product_data_meta_box', $post_type, 'normal', 'default' );

			$post_type = 'shop_order';
			add_meta_box( 'woo-order-post_data', __( 'Order Post Meta', 'woo_st' ), 'woo_st_order_data_meta_box', $post_type, 'normal', 'default' );
			add_meta_box( 'woo-order-post_item', __( 'Order Items Post Meta', 'woo_st' ), 'woo_st_order_items_data_meta_box', $post_type, 'normal', 'default' );

			$post_type = 'shop_coupon';
			add_meta_box( 'woo-coupon-post_data', __( 'Coupon Post Meta', 'woo_st' ), 'woo_st_coupon_data_meta_box', $post_type, 'normal', 'default' );
		}

	}

	function woo_st_product_data_meta_box() {

		global $post;

		$post_meta = get_post_custom( $post->ID );

		include_once( WOO_ST_PATH . 'templates/admin/product_data.php' );

	}

	function woo_st_order_data_meta_box() {

		global $post;

		$post_meta = get_post_custom( $post->ID );

		include_once( WOO_ST_PATH . 'templates/admin/order_data.php' );

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

		include_once( WOO_ST_PATH . 'templates/admin/order_item_data.php' );

	}

	function woo_st_coupon_data_meta_box() {

		global $post;

		$post_meta = get_post_custom( $post->ID );

		include_once( WOO_ST_PATH . 'templates/admin/coupon_data.php' );

	}

	function woo_st_category_data_meta_box( $term = '', $taxonomy = '' ) {

		$term_taxonomy = 'woocommerce_term';
		$term_meta = get_metadata( $term_taxonomy, $term->term_id );

		include_once( WOO_ST_PATH . 'templates/admin/category_data.php' );

	}

	function woo_st_tag_data_meta_box( $term = '', $taxonomy = '' ) {

		$term_taxonomy = 'woocommerce_term';
		$term_meta = get_metadata( $term_taxonomy, $term->term_id );

		include_once( WOO_ST_PATH . 'templates/admin/tag_data.php' );

	}

	function woo_st_brand_data_meta_box( $term = '', $taxonomy = '' ) {

		$term_taxonomy = 'woocommerce_term';
		$term_meta = get_metadata( $term_taxonomy, $term->term_id );

		include_once( WOO_ST_PATH . 'templates/admin/brand_data.php' );

	}

	function woo_st_user_data_meta_box( $user = '' ) {

		$user_id = $user->data->ID;
		$user_meta = get_user_meta( $user_id );

		include_once( WOO_ST_PATH . 'templates/admin/user_data.php' );

	}

	/* End of: WordPress Administration */

}
?>