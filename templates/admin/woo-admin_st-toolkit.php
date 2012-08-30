<script type="text/javascript">
	function showProgress() {
		window.scrollTo(0,0);
		document.getElementById('progress').style.display = 'block';
		document.getElementById('content').style.display = 'none';
	}
</script>
<div id="content">
	<h3><?php _e( 'Nuke WooCommerce', 'woo_st' ); ?></h3>
	<p><?php _e( 'Select the WooCommerce tables you wish to empty then click Remove to permanently remove WooCommerce generated details from your WordPress database.', 'woo_st' ); ?></p>
	<form method="post" onsubmit="showProgress()">
		<div id="poststuff">

			<div class="postbox">
				<h3 class="hndle"><?php _e( 'Empty WooCommerce Tables', 'woo_st' ); ?></h3>
				<div class="inside">
					<table class="form-table">

						<tr>
							<th>
								<label for="products"><?php _e( 'Products', 'woo_st' ); ?></label>
							</th>
							<td>
								<input type="checkbox" id="products" name="woo_st_products"<?php if( $products == 0 ) { ?> disabled="disabled"<?php } ?> /> (<?php echo $products; ?>)
							</td>
						</tr>

						<tr>
							<th>
								<label for="product_categories"><?php _e( 'Product Categories', 'woo_st' ); ?></label>
							</th>
							<td>
								<input type="checkbox" id="product_categories" name="woo_st_product_categories"<?php if( $categories == 0 ) { ?> disabled="disabled"<?php } ?> /> (<?php echo $categories; ?>)
							</td>
						</tr>

						<tr>
							<th>
								<label for="product_tags"><?php _e( 'Product Tags', 'woo_st' ); ?></label>
							</th>
							<td>
								<input type="checkbox" id="product_tags" name="woo_st_product_tags"<?php if( $tags == 0 ) { ?> disabled="disabled"<?php } ?> /> (<?php echo $tags; ?>)
							</td>
						</tr>

						<tr>
							<th>
								<label for="product_images"><?php _e( 'Product Images', 'woo_st' ); ?></label>
							</th>
							<td>
								<input type="checkbox" id="product_images" name="woo_st_product_images"<?php if( $images == 0 ) { ?> disabled="disabled"<?php } ?> /> (<?php echo $images; ?>)
							</td>
						</tr>

						<tr>
							<th>
								<label for="attributes"><?php _e( 'Product Attributes', 'woo_st' ); ?></label>
							</th>
							<td>
								<input type="checkbox" id="attributes" name="woo_st_attributes"<?php if( $attributes == 0 ) { ?> disabled="disabled"<?php } ?> /> (<?php echo $attributes; ?>)
							</td>
						</tr>

						<tr>
							<th>
								<label for="sales_orders"><?php _e( 'Sales', 'woo_st' ); ?></label>
							</th>
							<td>
								<input type="checkbox" id="sales_orders" name="woo_st_sales_orders"<?php if( $orders == 0 ) { ?> disabled="disabled"<?php } ?> /> (<?php echo $orders; ?>)
							</td>
						</tr>

						<tr>
							<th>
								<label for="coupons"><?php _e( 'Coupons', 'woo_st' ); ?></label>
							</th>
							<td>
								<input type="checkbox" id="coupons" name="woo_st_coupons"<?php if( $coupons == 0 ) { ?> disabled="disabled"<?php } ?> /> (<?php echo $coupons; ?>)
							</td>
						</tr>

<?php if( $credit_cards ) { ?>
						<tr>
							<th>
								<label for="creditcards"><?php _e( 'Credit Cards', 'woo_st' ); ?></label>
							</th>
							<td>
								<input type="checkbox" id="creditcards" name="woo_st_creditcards"<?php if( $credit_cards == 0 ) { ?> disabled="disabled"<?php } ?> /> (<?php echo $credit_cards; ?>)
							</td>
						</tr>

<?php } ?>
					</table>
				</div>
			</div>
			<!-- .postbox -->

		</div>
		<!-- #poststuff -->

		<p class="submit">
			<input type="submit" value="<?php _e( 'Remove', 'woo_st' ); ?>" class="button button-primary" />
		</p>
		<input type="hidden" name="action" value="nuke" />
	</form>
</div>
<div id="progress" style="display:none;">
	<p><?php _e( 'Chosen WooCommerce details are being nuked, this process can take awhile. Time for a beer?', 'woo_st' ); ?></p>
	<img src="<?php echo plugins_url( '/templates/admin/images/progress.gif', $woo_st['relpath'] ); ?>" alt="" />
</div>