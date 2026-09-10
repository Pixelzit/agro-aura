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
 * Allow SVG upload in Media Library
 */
function agro_aura_allow_svg_upload( $mimes ) {
	$mimes['svg'] = 'image/svg+xml';
	return $mimes;
}
add_filter( 'upload_mimes', 'agro_aura_allow_svg_upload' );

/**
 * Adjust header hooks to prevent duplicate branding, search, and cart.
 */
require_once get_stylesheet_directory() . '/inc/storefront-template-functions.php';

/**
 * Custom WooCommerce hooks and template functions
 */
require_once get_stylesheet_directory() . '/inc/woocommerce/custom-storefront-woocommerce.php';

/**
 * Custom Shortcodes
 */
require_once get_stylesheet_directory() . '/inc/shortcodes/product-categories.php';
require_once get_stylesheet_directory() . '/inc/shortcodes/home-product-cat-list.php';
require_once get_stylesheet_directory() . '/inc/shortcodes/product-filters.php';




