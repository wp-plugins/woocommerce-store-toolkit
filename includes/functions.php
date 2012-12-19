<?php
if( is_admin() ) {

	/* Start of: WordPress Administration */

	/* WordPress Administration menu */
	function woo_st_admin_menu() {

		add_submenu_page( 'woocommerce', __( 'Store Toolkit', 'woo_st' ), __( 'Store Toolkit', 'woo_st' ), 'manage_options', 'woo_st', 'woo_st_html_page' );

	}
	add_action( 'admin_menu', 'woo_st_admin_menu', 11 );

	function woo_st_template_header( $title = '', $icon = 'tools' ) {

		global $woo_st;

		if( $title )
			$output = $title;
		else
			$output = $woo_st['menu'];
		$icon = woo_is_admin_icon_valid( $icon ); ?>
<div class="wrap">
	<div id="icon-<?php echo $icon; ?>" class="icon32"><br /></div>
	<h2><?php echo $output; ?></h2>
<?php
	}

	function woo_st_template_footer() { ?>
</div>
<?php
	}

	function woo_st_support_donate() {

		global $woo_st;

		$output = '';
		$show = true;
		if( function_exists( 'woo_vl_we_love_your_plugins' ) ) {
			if( in_array( $woo_st['dirname'], woo_vl_we_love_your_plugins() ) )
				$show = false;
		}
		if( $show ) {
			$donate_url = 'http://www.visser.com.au/#donations';
			$rate_url = 'http://wordpress.org/support/view/plugin-reviews/' . $woo_st['dirname'];
			$output = '
	<div id="support-donate_rate" class="support-donate_rate">
		<p>' . sprintf( __( '<strong>Like this Plugin?</strong> %s and %s.', 'woo_st' ), '<a href="' . $donate_url . '" target="_blank">' . __( 'Donate to support this Plugin', 'woo_st' ) . '</a>', '<a href="' . add_query_arg( array( 'rate' => '5' ), $rate_url ) . '#postform" target="_blank">rate / review us on WordPress.org</a>' ) . '</p>
	</div>
';
		}
		echo $output;

	}

	function woo_st_return_count( $dataset ) {

		global $wpdb;

		$count_sql = null;
		switch( $dataset ) {

			/* WooCommerce */

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

			case 'orders':
				$post_type = 'shop_order';
				$count = wp_count_posts( $post_type );
				break;

			case 'images':
				$count_sql = "SELECT COUNT(`post_id`) FROM `" . $wpdb->postmeta . "` WHERE `meta_key` = '_woocommerce_exclude_image'";
				break;

			case 'coupons':
				$post_type = 'shop_coupon';
				$count = wp_count_posts( $post_type );
				break;

			case 'attributes':
				$count_sql = "SELECT COUNT(`attribute_id`) FROM `" . $wpdb->prefix . "woocommerce_attribute_taxonomies`";
				break;

			/* 3rd Party */

			case 'credit-cards':
				$post_type = 'offline_payment';
				$count = wp_count_posts( $post_type );
				break;

			/* WordPress */

			case 'posts':
				$post_type = 'post';
				$count = wp_count_posts( $post_type );
				break;

			case 'post_categories':
				$term_taxonomy = 'category';
				$count = wp_count_terms( $term_taxonomy );
				break;

			case 'post_tags':
				$term_taxonomy = 'post_tag';
				$count = wp_count_terms( $term_taxonomy );
				break;

			case 'links':
				$count_sql = "SELECT COUNT(`link_id`) FROM `" . $wpdb->prefix . "links`";
				break;

			case 'comments':
				$count = wp_count_comments();
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
			return 0;
		}

	}

	function woo_st_clear_dataset( $dataset, $data = null ) {

		global $wpdb;

		switch( $dataset ) {

			/* WooCommerce */

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
				if( $data ) {
					foreach( $data as $single_category ) {
						$post_type = 'product';
						$args = array(
							'post_type' => $post_type,
							'tax_query' => array(
								array(
									'taxonomy' => $term_taxonomy,
									'field' => 'id',
									'terms' => $single_category
								)
							),
							'numberposts' => -1
						);
						$products = get_posts( $args );
						if( $products ) {
							foreach( $products as $product )
								wp_delete_post( $product->ID, true );
						}
					}
				} else {
					$categories = get_terms( $term_taxonomy, array( 'hide_empty' => false ) );
					if( $categories ) {
						foreach( $categories as $category ) {
							wp_delete_term( $category->term_id, $term_taxonomy );
							$wpdb->query( $wpdb->prepare( "DELETE FROM `" . $wpdb->terms . "` WHERE `term_id` = %d", $category->term_id ) );
							$wpdb->query( $wpdb->prepare( "DELETE FROM `" . $wpdb->term_relationships . "` WHERE `term_taxonomy_id` = %d", $category->term_taxonomy_id ) );
							$wpdb->query( $wpdb->prepare( "DELETE FROM `" . $wpdb->prefix . "woocommerce_termmeta` WHERE `woocommerce_term_id` = %d", $category->term_id ) );
							delete_woocommerce_term_meta( $category->term_id, 'thumbnail_id' );
						}
					}
					$wpdb->query( $wpdb->prepare( "DELETE FROM `" . $wpdb->term_taxonomy . "` WHERE `taxonomy` = '%s'", $term_taxonomy ) );
				}
				break;

			case 'tags':
				$term_taxonomy = 'product_tag';
				$tags = get_terms( $term_taxonomy, array( 'hide_empty' => false ) );
				if( $tags ) {
					foreach( $tags as $tag ) {
						wp_delete_term( $tag->term_id, $term_taxonomy );
						$wpdb->query( $wpdb->prepare( "DELETE FROM `" . $wpdb->terms . "` WHERE `term_id` = %d", $tag->term_id ) );
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
						$images = get_children( $args );
						if( $images ) {
							foreach( $images as $image ) {
								wp_delete_attachment( $image->ID, true );
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
							$terms_sql = $wpdb->prepare( "SELECT `term_id` FROM `" . $wpdb->prefix . "term_taxonomy` WHERE `taxonomy` = 'pa_%s'", $attribute->name );
							$terms = $wpdb->get_results( $terms_sql );
							if( $terms ) {
								foreach( $terms as $term )
									wp_delete_term( $term->term_id, 'pa_' . $attribute->name );
							}
							$wpdb->query( $wpdb->prepare( "DELETE FROM `" . $wpdb->prefix . "woocommerce_termmeta` WHERE `meta_key` = 'order_pa_%s'", $attribute->name ) );
							$wpdb->query( $wpdb->prepare( "DELETE FROM `" . $wpdb->term_relationships . "` WHERE `term_taxonomy_id` = %d", $attribute->ID ) );
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

			/* WordPress */

			case 'posts':
				$post_type = 'post';
				$posts = (array)get_posts( array( 
					'post_type' => $post_type,
					'post_status' => woo_st_post_statuses(),
					'numberposts' => -1
				) );
				if( $posts ) {
					foreach( $posts as $post ) {
						if( isset( $post->ID ) )
							wp_delete_post( $post->ID, true );
					}
				}
				break;

			case 'post_categories':
				$term_taxonomy = 'category';
				$post_categories = get_terms( $term_taxonomy, array( 'hide_empty' => false ) );
				if( $post_categories ) {
					foreach( $post_categories as $post_category ) {
						wp_delete_term( $post_category->term_id, $term_taxonomy );
						$wpdb->query( "DELETE FROM `" . $wpdb->terms . "` WHERE `term_id` = " . $post_category->term_id );
						$wpdb->query( "DELETE FROM `" . $wpdb->term_relationships . "` WHERE `term_taxonomy_id` = " . $post_category->term_taxonomy_id );
					}
				}
				$wpdb->query( "DELETE FROM `" . $wpdb->term_taxonomy . "` WHERE `taxonomy` = '" . $term_taxonomy . "'" );
				break;

			case 'post_tags':
				$term_taxonomy = 'post_tag';
				$post_tags = get_terms( $term_taxonomy, array( 'hide_empty' => false ) );
				if( $post_tags ) {
					foreach( $post_tags as $post_tag ) {
						wp_delete_term( $post_tag->term_id, $term_taxonomy );
						$wpdb->query( "DELETE FROM `" . $wpdb->terms . "` WHERE `term_id` = " . $post_tag->term_id );
						$wpdb->query( "DELETE FROM `" . $wpdb->term_relationships . "` WHERE `term_taxonomy_id` = " . $post_tag->term_taxonomy_id );
					}
				}
				$wpdb->query( "DELETE FROM `" . $wpdb->term_taxonomy . "` WHERE `taxonomy` = '" . $term_taxonomy . "'" );
				break;

			case 'links':
				$wpdb->query( "TRUNCATE TABLE `" . $wpdb->prefix . "links`" );
				break;

			case 'comments':
				$comments = get_comments();
				if( $comments ) {
					foreach( $comments as $comment ) {
						if( $comment->comment_ID )
							wp_delete_comment( $comment->comment_ID, true );
					}
				}
				break;

		}

	}

	function woo_st_remove_filename_extension( $filename ) {

		$extension = strrchr( $filename, '.' );
		$filename = substr( $filename, 0, -strlen( $extension ) );

		return $filename;

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

	function woo_st_admin_active_tab( $tab_name = null, $tab = null ) {

		if( isset( $_GET['tab'] ) && !$tab )
			$tab = $_GET['tab'];
		else
			$tab = 'overview';

		$output = '';
		if( isset( $tab_name ) && $tab_name ) {
			if( $tab_name == $tab )
				$output = ' nav-tab-active';
		}
		echo $output;

	}

	function woo_st_tab_template( $tab = '' ) {

		global $woo_st;

		if( !$tab )
			$tab = 'overview';

		switch( $tab ) {

			case 'nuke':
				$products = woo_st_return_count( 'products' );
				$images = woo_st_return_count( 'images' );
				$tags = woo_st_return_count( 'tags' );
				$categories = woo_st_return_count( 'categories' );
				if( $categories ) {
					$term_taxonomy = 'product_cat';
					$args = array(
						'hide_empty' => 0
					);
					$categories_data = get_terms( $term_taxonomy, $args );
				}
				$orders = woo_st_return_count( 'orders' );
				$coupons = woo_st_return_count( 'coupons' );

				$credit_cards = woo_st_return_count( 'credit-cards' );
				$attributes = woo_st_return_count( 'attributes' );

				$posts = woo_st_return_count( 'posts' );
				$post_categories = woo_st_return_count( 'post_categories' );
				$post_tags = woo_st_return_count( 'post_tags' );
				$links = woo_st_return_count( 'links' );
				$comments = woo_st_return_count( 'comments' );

				if( $products || $images || $tags || $categories || $orders || $credit_cards || $attributes )
					$show_table = true;
				else
					$show_table = false;
				break;

		}
		if( $tab )
			include_once( $woo_st['abspath'] . '/templates/admin/woo-admin_st-toolkit_' . $tab . '.php' );

	}

	/* End of: WordPress Administration */

}
?>