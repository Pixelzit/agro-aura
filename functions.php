<?php
/**
 * Storefront Child Theme functions.
 *
 * @package Storefront_Child
 */

defined( 'ABSPATH' ) || exit;

/**
 * Enqueue child theme stylesheet.
 */
function storefront_child_enqueue_styles() {
	$parent_theme = wp_get_theme( 'storefront' );
	$child_theme  = wp_get_theme();

	$main_css_path = get_stylesheet_directory() . '/assets/css/main.css';
	$main_css_ver  = file_exists( $main_css_path ) ? filemtime( $main_css_path ) : $child_theme->get( 'Version' );

	wp_enqueue_style(
		'storefront-child-main',
		get_stylesheet_directory_uri() . '/assets/css/main.css',
		array( 'storefront-child-style' ),
		$main_css_ver
	);

	wp_enqueue_style(
		'storefront-child-style',
		get_stylesheet_uri(),
		array( 'storefront-style' ),
		$child_theme->get( 'Version' )
	);

	$header_js_path = get_stylesheet_directory() . '/assets/js/header.js';
	$header_js_ver  = file_exists( $header_js_path ) ? filemtime( $header_js_path ) : $child_theme->get( 'Version' );

	wp_enqueue_script(
		'storefront-child-header',
		get_stylesheet_directory_uri() . '/assets/js/header.js',
		array(),
		$header_js_ver,
		true
	);

	if ( is_product() ) {
		wp_enqueue_script( 'zoom' );

		$single_js_path = get_stylesheet_directory() . '/assets/js/single-product.js';
		$single_js_ver  = file_exists( $single_js_path ) ? filemtime( $single_js_path ) : $child_theme->get( 'Version' );

		wp_enqueue_script(
			'storefront-child-single-product',
			get_stylesheet_directory_uri() . '/assets/js/single-product.js',
			array( 'jquery', 'zoom' ),
			$single_js_ver,
			true
		);
	}

	if ( is_shop() || is_product_taxonomy() || is_product() || is_front_page() ) {
		$archive_js_path = get_stylesheet_directory() . '/assets/js/archive-product.js';
		$archive_js_ver  = file_exists( $archive_js_path ) ? filemtime( $archive_js_path ) : $child_theme->get( 'Version' );

		wp_enqueue_script(
			'storefront-child-archive-product',
			get_stylesheet_directory_uri() . '/assets/js/archive-product.js',
			array(),
			$archive_js_ver,
			true
		);
	}
}
add_action( 'wp_enqueue_scripts', 'storefront_child_enqueue_styles', 20 );

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
* Adjust header hooks to prevent duplicate branding, search, and cart.
*/
require_once get_stylesheet_directory() . '/inc/storefront-template-functions.php';
// require_once get_stylesheet_directory() . '/inc/woocommerce/storefront-template-functions.php';

/**
 * Custom Shortcodes
 */
require_once get_stylesheet_directory() . '/inc/shortcodes/product-categories.php';
require_once get_stylesheet_directory() . '/inc/shortcodes/home-product-cat-list.php';
require_once get_stylesheet_directory() . '/inc/shortcodes/product-filters.php';



