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
