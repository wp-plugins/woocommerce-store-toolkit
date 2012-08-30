<?php
if( is_admin() ) {

	/* Start of: WordPress Administration */

	/* WordPress Administration menu */
	function woo_st_admin_menu() {

		add_management_page( __( 'Store Toolkit', 'woo_st' ), __( 'Store Toolkit', 'woo_st' ), 'manage_options', 'woo_st', 'woo_st_html_page' );
		add_submenu_page( 'woocommerce', __( 'Store Toolkit', 'woo_st' ), __( 'Store Toolkit', 'woo_st' ), 'manage_options', 'woo_st', 'woo_st_html_page' );

	}
	add_action( 'admin_menu', 'woo_st_admin_menu', 11 );

	function woo_st_template_header() {

		global $woo_st; ?>
<div class="wrap">
	<div id="icon-tools" class="icon32"><br /></div>
	<h2><?php echo $woo_st['menu']; ?></h2>
<?php
	}

	function woo_st_template_footer() { ?>
</div>
<?php
	}

	function woo_st_return_count( $dataset ) {

		global $wpdb;

		$count_sql = null;
		switch( $dataset ) {

			case 'products':
				$post_type = 'product';
				$count = wp_count_posts( $post_type );
				break;

			case 'categories':
				$term_taxonomy = 'product_cat';
				$count = wp_count_terms( $term_taxonomy );
				break;

			case 'tags':
				$term_taxonomy = 'product_tag';
				$count = wp_count_terms( $term_taxonomy );
				break;

			case 'images':
				$count_sql = "SELECT COUNT(`post_id`) FROM `" . $wpdb->postmeta . "` WHERE `meta_key` = '_woocommerce_exclude_image'";
				break;

			case 'orders':
				$post_type = 'shop_order';
				$count = wp_count_posts( $post_type );
				break;

			case 'coupons':
				$post_type = 'shop_coupon';
				$count = wp_count_posts( $post_type );
				break;

			case 'credit-cards':
				$post_type = 'offline_payment';
				$count = wp_count_posts( $post_type );
				break;

			case 'attributes':
				$count_sql = "SELECT COUNT(`attribute_id`) FROM `" . $wpdb->prefix . "woocommerce_attribute_taxonomies`";
				break;

		}
		if( isset( $count ) || $count_sql ) {
			if( isset( $count ) ) {
				if( is_object( $count ) ) {
					$count_object = $count;
					$count = 0;
					foreach( $count_object as $key => $item )
						$count = $item + $count;
				}
				return $count;
			} else {
				$count = $wpdb->get_var( $count_sql );
			}
			return $count;
		} else {
			return false;
		}

	}

	function woo_st_clear_dataset( $dataset ) {

		global $wpdb;

		switch( $dataset ) {

			case 'products':
				$post_type = 'product';
				$products = (array)get_posts( array(
					'post_type' => $post_type,
					'post_status' => woo_st_post_statuses(),
					'numberposts' => -1
				) );
				if( $products ) {
					foreach( $products as $product ) {
						wp_delete_post( $product->ID, true );
						wp_set_object_terms( $product->ID, null, 'product_tag' );
						$attributes_sql = "SELECT `attribute_id` as ID, `attribute_name` as name, `attribute_label` as label, `attribute_type` as type FROM `" . $wpdb->prefix . "woocommerce_attribute_taxonomies`";
						$attributes = $wpdb->get_results( $attributes_sql );
						if( $attributes ) {
							foreach( $attributes as $attribute )
								wp_set_object_terms( $product->ID, null, 'pa_' . $attribute->name );
						}
					}
				}
				break;

			case 'categories':
				$term_taxonomy = 'product_cat';
				$categories_sql = "SELECT `term_id`, `term_taxonomy_id` FROM `" . $wpdb->term_taxonomy . "` WHERE `taxonomy` = '" . $term_taxonomy . "'";
				$categories = $wpdb->get_results( $categories_sql );
				if( $categories ) {
					foreach( $categories as $category ) {
						wp_delete_term( $category->term_id, $term_taxonomy );
						$wpdb->query( "DELETE FROM `" . $wpdb->terms . "` WHERE `term_id` = " . $category->term_id );
						$wpdb->query( "DELETE FROM `" . $wpdb->term_relationships . "` WHERE `term_taxonomy_id` = " . $category->term_taxonomy_id );
						$wpdb->query( "DELETE FROM `" . $wpdb->prefix . "woocommerce_termmeta` WHERE `woocommerce_term_id` = " . $category->term_id );
						delete_woocommerce_term_meta( $category->term_id, 'thumbnail_id' );
					}
					update_option( $term_taxonomy . '_children', '' );
				}
				break;

			case 'tags':
				$term_taxonomy = 'product_tag';
				$tags_sql = "SELECT `term_id` FROM `" . $wpdb->term_taxonomy . "` WHERE `taxonomy` = '" . $term_taxonomy . "'";
				$tags = $wpdb->get_results( $tags_sql );
				if( $tags ) {
					foreach( $tags as $tag ) {
						wp_delete_term( $tag->term_id, $term_taxonomy );
						$wpdb->query( "DELETE FROM `" . $wpdb->terms . "` WHERE `term_id` = " . $tag->term_id );
						$wpdb->query( "DELETE FROM `" . $wpdb->term_relationships . "` WHERE `term_taxonomy_id` = " . $tag->term_id );
					}
				}
				break;

			case 'images':
				$post_type = 'product';
				$products = (array)get_posts( array(
					'post_type' => $post_type,
					'post_status' => woo_st_post_statuses(),
					'numberposts' => -1
				) );
				if( $products ) {
					$upload_dir = wp_upload_dir();
					foreach( $products as $product ) {
						$args = array(
							'post_type' => 'attachment',
							'post_parent' => $product->ID,
							'post_status' => 'inherit',
							'post_mime_type' => 'image',
							'numberposts' => -1
						);
						$images = get_children( $args, ARRAY_A );
						if( $images ) {
							foreach( $images as $image ) {
								wp_delete_attachment( $image['ID'], true );
							}
							unset( $images, $image );
						}
					}
				}
				break;

			case 'orders':
				$post_type = 'shop_order';
				$orders = (array)get_posts( array(
					'post_type' => $post_type,
					'post_status' => woo_st_post_statuses(),
					'numberposts' => -1
				) );
				if( $orders ) {
					foreach( $orders as $order ) {
						if( isset( $order->ID ) )
							wp_delete_post( $order->ID, true );
					}
				}
				break;

			case 'coupons':
				$post_type = 'shop_coupon';
				$coupons = (array)get_posts( array(
					'post_type' => $post_type,
					'post_status' => woo_st_post_statuses(),
					'numberposts' => -1
				) );
				if( $coupons ) {
					foreach( $coupons as $coupon ) {
						if( isset( $coupon->ID ) )
							wp_delete_post( $coupon->ID, true );
					}
				}
				break;

			case 'attributes':
				if( !isset( $_POST['woo_st_attributes'] ) ) {
					$attributes_sql = "SELECT `attribute_id` as ID, `attribute_name` as name, `attribute_label` as label, `attribute_type` as type FROM `" . $wpdb->prefix . "woocommerce_attribute_taxonomies`";
					$attributes = $wpdb->get_results( $attributes_sql );
					if( $attributes ) {
						foreach( $attributes as $attribute ) {
							$terms_sql = "SELECT `term_id` FROM `" . $wpdb->prefix . "term_taxonomy` WHERE `taxonomy` = 'pa_" . $attribute->name . "'";
							$terms = $wpdb->get_results( $terms_sql );
							if( $terms ) {
								foreach( $terms as $term )
									wp_delete_term( $term->term_id, 'pa_' . $attribute->name );
							}
							$wpdb->query( "DELETE FROM `" . $wpdb->prefix . "woocommerce_termmeta` WHERE `meta_key` = 'order_pa_" . $attribute->name . "'" );
							$wpdb->query( "DELETE FROM `" . $wpdb->term_relationships . "` WHERE `term_taxonomy_id` = " . $attribute->ID );
						}
					}
					$wpdb->query( "DELETE FROM `" . $wpdb->prefix . "woocommerce_attribute_taxonomies`" );
				}
				break;

			case 'credit-cards':
				$post_type = 'offline_payment';
				$credit_cards = (array)get_posts( array( 
					'post_type' => $post_type,
					'post_status' => woo_st_post_statuses(),
					'numberposts' => -1
				) );
				if( $credit_cards ) {
					foreach( $credit_cards as $credit_card ) {
						if( isset( $credit_card->ID ) )
							wp_delete_post( $credit_card->ID, true );
					}
				}
				break;
		}

	}

	if( !function_exists( 'remove_filename_extension' ) ) {

		function remove_filename_extension( $filename ) {

			$extension = strrchr( $filename, '.' );
			$filename = substr( $filename, 0, -strlen( $extension ) );

			return $filename;

		}

	}

	function woo_st_post_statuses() {

		$output = array(
			'publish',
			'pending',
			'draft',
			'auto-draft',
			'future',
			'private',
			'inherit',
			'trash'
		);
		return $output;

	}

	/* End of: WordPress Administration */

}
?>