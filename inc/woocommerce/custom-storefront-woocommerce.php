<?php
/**
 * Custom WooCommerce Functions and Hooks for Agro Aura
 *
 * @package Storefront_Child
 */

defined( 'ABSPATH' ) || exit;

/**
 * Enable WooCommerce theme supports for gallery zoom, lightbox, and slider.
 */
function agro_aura_theme_setup() {
	add_theme_support( 'wc-product-gallery-zoom' );
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'wc-product-gallery-slider' );
}
add_action( 'after_setup_theme', 'agro_aura_theme_setup', 50 );

/**
 * Handle 1-Click Buy button: redirect directly to checkout page
 */
function agro_aura_buy_now_redirect( $url ) {
	if ( isset( $_REQUEST['agro_buy_now'] ) && '1' === strval( $_REQUEST['agro_buy_now'] ) ) {
		return wc_get_checkout_url();
	}
	return $url;
}
add_filter( 'woocommerce_add_to_cart_redirect', 'agro_aura_buy_now_redirect', 20, 1 );

/**
 * Remove default single product sale flash so our custom offer badge over the image is used instead
 */
function agro_aura_remove_single_product_sale_flash() {
	if ( is_product() ) {
		remove_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_sale_flash', 10 );
	}
}
add_action( 'wp', 'agro_aura_remove_single_product_sale_flash', 20 );

/**
 * Custom Sale Badge showing calculated discount percentage
 */
function agro_aura_custom_sale_flash( $html, $post, $product ) {
	if ( ! $product ) {
		return $html;
	}
	$regular_price = (float) $product->get_regular_price();
	$sale_price    = (float) $product->get_sale_price();
	$percent       = 20;
	if ( $regular_price > 0 && $sale_price > 0 ) {
		$percent = round( ( ( $regular_price - $sale_price ) / $regular_price ) * 100 );
	}
	return '<span class="agro-discount-badge onsale">' . esc_html( $percent ) . '% OFF</span>';
}
add_filter( 'woocommerce_sale_flash', 'agro_aura_custom_sale_flash', 20, 3 );

/**
 * Change "Select options" button text to "Add to cart" for variable products
 */
function agro_aura_variable_add_to_cart_text( $text, $product ) {
	if ( $product && $product->is_type( 'variable' ) ) {
		return __( 'Add to cart', 'woocommerce' );
	}
	return $text;
}
add_filter( 'woocommerce_product_add_to_cart_text', 'agro_aura_variable_add_to_cart_text', 20, 2 );

/**
 * Update loop add to cart link for variable products to allow direct AJAX add to cart
 */
function agro_aura_variable_loop_add_to_cart_link( $html, $product, $args ) {
	if ( $product && $product->is_type( 'variable' ) ) {
		$html = str_replace( __( 'Select options', 'woocommerce' ), __( 'Add to cart', 'woocommerce' ), $html );
		$html = str_replace( 'Select options', 'Add to cart', $html );

		if ( isset( $args['attributes']['data-product_id'] ) && ! empty( $args['attributes']['data-product_id'] ) ) {
			$var_id = $args['attributes']['data-product_id'];
			$html   = str_replace( 'product_type_variable', 'product_type_simple', $html );
			$html   = preg_replace( '/href="([^"]*)"/', 'href="?add-to-cart=' . esc_attr( $var_id ) . '"', $html, 1 );
		}
	}
	return $html;
}
add_filter( 'woocommerce_loop_add_to_cart_link', 'agro_aura_variable_loop_add_to_cart_link', 20, 3 );

/**
 * Add modern quick-action shortcut cards to the My Account dashboard
 */
function agro_aura_account_dashboard_cards() {
	$orders_url  = wc_get_account_endpoint_url( 'orders' );
	$address_url = wc_get_account_endpoint_url( 'edit-address' );
	$account_url = wc_get_account_endpoint_url( 'edit-account' );
	$logout_url  = wc_logout_url();
	?>
	<div class="agro-dashboard-cards-grid">
		<a href="<?php echo esc_url( $orders_url ); ?>" class="dashboard-card">
			<div class="card-icon">
				<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 0 1-8 0"></path></svg>
			</div>
			<h4 class="card-title"><?php esc_html_e( 'My Orders', 'storefront-child' ); ?></h4>
			<p class="card-desc"><?php esc_html_e( 'View your recent orders and track their delivery status.', 'storefront-child' ); ?></p>
			<span class="card-arrow"><?php esc_html_e( 'View Orders', 'storefront-child' ); ?></span>
		</a>

		<a href="<?php echo esc_url( $address_url ); ?>" class="dashboard-card">
			<div class="card-icon">
				<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
			</div>
			<h4 class="card-title"><?php esc_html_e( 'Addresses', 'storefront-child' ); ?></h4>
			<p class="card-desc"><?php esc_html_e( 'Manage your shipping and billing addresses.', 'storefront-child' ); ?></p>
			<span class="card-arrow"><?php esc_html_e( 'Manage', 'storefront-child' ); ?></span>
		</a>

		<a href="<?php echo esc_url( $account_url ); ?>" class="dashboard-card">
			<div class="card-icon">
				<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
			</div>
			<h4 class="card-title"><?php esc_html_e( 'Account Details', 'storefront-child' ); ?></h4>
			<p class="card-desc"><?php esc_html_e( 'Update your name, email address and password.', 'storefront-child' ); ?></p>
			<span class="card-arrow"><?php esc_html_e( 'Edit Profile', 'storefront-child' ); ?></span>
		</a>

		<a href="<?php echo esc_url( $logout_url ); ?>" class="dashboard-card">
			<div class="card-icon">
				<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
			</div>
			<h4 class="card-title"><?php esc_html_e( 'Logout', 'storefront-child' ); ?></h4>
			<p class="card-desc"><?php esc_html_e( 'Safely sign out of your account on this device.', 'storefront-child' ); ?></p>
			<span class="card-arrow"><?php esc_html_e( 'Log Out', 'storefront-child' ); ?></span>
		</a>
	</div>
	<?php
}
add_action( 'woocommerce_account_dashboard', 'agro_aura_account_dashboard_cards', 20 );

/**
 * Dynamic helper to get size, volume, weight, or pack attribute for any WooCommerce product.
 * Supports custom product attributes (e.g. Volume: "90 ml"), taxonomies (pa_weight, pa_size, pa_volume),
 * and WooCommerce native weight.
 *
 * @param WC_Product|int $product
 * @return array array( 'label' => string, 'attribute_name' => string, 'unit_price' => string )
 */
function agro_aura_get_product_display_size( $product ) {
	if ( is_numeric( $product ) ) {
		$product = wc_get_product( $product );
	}
	if ( ! is_a( $product, 'WC_Product' ) ) {
		return array(
			'label'          => '',
			'attribute_name' => __( 'Pack Size', 'storefront-child' ),
			'unit_price'     => '',
		);
	}

	$size_label = '';
	$attr_title = __( 'Pack Size', 'storefront-child' );

	// 1. Check all product attributes
	$attributes = $product->get_attributes();
	if ( ! empty( $attributes ) ) {
		// Priority attribute slugs
		$priority_slugs = array(
			'volume', 'pa_volume',
			'pack-size', 'pa_pack-size', 'pack_size', 'pa_pack_size', 'pack', 'pa_pack',
			'bottle-size', 'pa_bottle-size', 'bottle_size',
			'size', 'pa_size',
			'weight', 'pa_weight', 'net-weight', 'pa_net-weight', 'net_weight',
			'net-quantity', 'pa_net-quantity', 'net_quantity', 'quantity', 'pa_quantity',
			'unit', 'pa_unit',
		);

		// Check priority slugs first
		foreach ( $priority_slugs as $slug ) {
			foreach ( $attributes as $k => $attr ) {
				$attr_slug = strtolower( is_a( $attr, 'WC_Product_Attribute' ) ? $attr->get_name() : $k );
				$clean_k   = strtolower( str_replace( array( 'pa_', 'attribute_' ), '', $k ) );
				$clean_s   = strtolower( str_replace( array( 'pa_', 'attribute_' ), '', $slug ) );

				if ( $clean_k === $clean_s || $attr_slug === $slug || $attr_slug === 'pa_' . $slug ) {
					$val = $product->get_attribute( is_a( $attr, 'WC_Product_Attribute' ) ? $attr->get_name() : $k );
					if ( ! empty( $val ) ) {
						$size_label = trim( $val );
						$attr_title = wc_attribute_label( is_a( $attr, 'WC_Product_Attribute' ) ? $attr->get_name() : $k, $product );
						break 2;
					}
				}
			}
		}

		// If no priority slug matched, check any attribute that has common size/weight/volume units
		if ( empty( $size_label ) ) {
			foreach ( $attributes as $k => $attr ) {
				$val = $product->get_attribute( is_a( $attr, 'WC_Product_Attribute' ) ? $attr->get_name() : $k );
				if ( ! empty( $val ) ) {
					if ( preg_match( '/\d+\s*(ml|l|litre|liter|g|gm|kg|pcs|pc|pack|can|bottle|sachet)/i', $val ) ) {
						$size_label = trim( $val );
						$attr_title = wc_attribute_label( is_a( $attr, 'WC_Product_Attribute' ) ? $attr->get_name() : $k, $product );
						break;
					}
				}
			}
		}

		// If still empty and there are attributes, take the first visible attribute
		if ( empty( $size_label ) ) {
			foreach ( $attributes as $k => $attr ) {
				$val = $product->get_attribute( is_a( $attr, 'WC_Product_Attribute' ) ? $attr->get_name() : $k );
				if ( ! empty( $val ) ) {
					$size_label = trim( $val );
					$attr_title = wc_attribute_label( is_a( $attr, 'WC_Product_Attribute' ) ? $attr->get_name() : $k, $product );
					break;
				}
			}
		}
	}

	// 2. If no attribute found, check WooCommerce native weight
	if ( empty( $size_label ) && $product->has_weight() ) {
		$weight = (float) $product->get_weight();
		$unit   = get_option( 'woocommerce_weight_unit', 'kg' );
		if ( $weight > 0 ) {
			$size_label = $weight . ' ' . $unit;
			$attr_title = __( 'Weight', 'storefront-child' );
		}
	}

	// 3. Compute dynamic unit price
	$price      = (float) $product->get_price();
	$unit_price = '';

	if ( ! empty( $size_label ) && $price > 0 ) {
		// Multi-pack check e.g. "4 x 1 L" or "5 x 500 ml"
		if ( preg_match( '/^(\d+)\s*[xX*]\s*([\d\.]+)\s*([a-zA-Z]+)?/i', $size_label, $m ) ) {
			$count = (float) $m[1];
			$each  = (float) $m[2];
			$unit  = isset( $m[3] ) ? strtolower( trim( $m[3] ) ) : '';
			$total = $count * $each;

			if ( $total > 0 ) {
				if ( 'ml' === $unit ) {
					$l          = $total / 1000;
					$unit_price = '₹' . number_format( round( $price / $l ) ) . ' / L';
				} elseif ( 'l' === $unit || 'liter' === $unit || 'litre' === $unit ) {
					$unit_price = '₹' . number_format( round( $price / $total ) ) . ' / L';
				} elseif ( 'g' === $unit || 'gm' === $unit ) {
					$kg         = $total / 1000;
					$unit_price = '₹' . number_format( round( $price / $kg ) ) . ' / kg';
				} elseif ( 'kg' === $unit ) {
					$unit_price = '₹' . number_format( round( $price / $total ) ) . ' / kg';
				}
			}
		} elseif ( preg_match( '/^([\d\.]+)\s*([a-zA-Z]+)?/i', $size_label, $m ) ) {
			$qty_val  = (float) $m[1];
			$unit_val = isset( $m[2] ) ? strtolower( trim( $m[2] ) ) : '';

			if ( $qty_val > 0 ) {
				if ( 'g' === $unit_val || 'gm' === $unit_val ) {
					$kg         = $qty_val / 1000;
					$unit_price = '₹' . number_format( round( $price / $kg ) ) . ' / kg';
				} elseif ( 'kg' === $unit_val ) {
					$unit_price = '₹' . number_format( round( $price / $qty_val ) ) . ' / kg';
				} elseif ( 'ml' === $unit_val ) {
					$l          = $qty_val / 1000;
					$unit_price = '₹' . number_format( round( $price / $l ) ) . ' / L';
				} elseif ( 'l' === $unit_val || 'litre' === $unit_val || 'liter' === $unit_val ) {
					$unit_price = '₹' . number_format( round( $price / $qty_val ) ) . ' / L';
				}
			}
		}
	}

	if ( empty( $unit_price ) && $price > 0 ) {
		$unit_price = '₹' . number_format( round( $price ) ) . ' / pack';
	}

	return array(
		'label'          => $size_label,
		'attribute_name' => ! empty( $attr_title ) ? $attr_title : __( 'Pack Size', 'storefront-child' ),
		'unit_price'     => $unit_price,
	);
}

