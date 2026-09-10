<?php
/**
 * Storefront template functions.
 *
 * @package storefront
 */



if ( ! function_exists( 'storefront_primary_navigation' ) ) {
	/**
	 * Display Primary Navigation
	 *
	 * @since  1.0.0
	 * @return void
	 */
	function storefront_primary_navigation() {
		?>
		<nav id="site-navigation" class="main-navigation" role="navigation" aria-label="<?php esc_attr_e( 'Primary Navigation', 'storefront' ); ?>">
		<button id="site-navigation-menu-toggle" class="menu-toggle" aria-controls="site-navigation" aria-expanded="false"><span><?php echo esc_html( apply_filters( 'storefront_menu_toggle_text', __( 'Menu', 'storefront' ) ) ); ?></span></button>
			<?php
			wp_nav_menu(
				array(
					'theme_location'  => 'primary',
					'container_class' => 'primary-navigation',
				)
			);

			// wp_nav_menu(
			// 	array(
			// 		'theme_location'  => 'handheld',
			// 		'container_class' => 'handheld-navigation',
			// 	)
			// );
			?>
		</nav><!-- #site-navigation -->
		<?php
	}
}

if ( ! function_exists( 'storefront_credit' ) ) {
	/**
	 * Display the theme credit
	 *
	 * @since  1.0.0
	 * @return void
	 */
	function storefront_credit() {
		$links_output = '';

		if ( apply_filters( 'storefront_credit_link', true ) ) {
			if ( storefront_is_woocommerce_activated() ) {
				$links_output .= '<a href="https://woocommerce.com" target="_blank" title="' . esc_attr__( 'WooCommerce - The Best eCommerce Platform for WordPress', 'storefront' ) . '" rel="noreferrer nofollow">' . esc_html__( 'Built with WooCommerce', 'storefront' ) . '</a>.';
			} else {
				$links_output .= '<a href="https://woocommerce.com/products/storefront/" target="_blank" title="' . esc_attr__( 'Storefront -  The perfect platform for your next WooCommerce project.', 'storefront' ) . '" rel="noreferrer nofollow">' . esc_html__( 'Built with Storefront', 'storefront' ) . '</a>.';
			}
		}

		if ( apply_filters( 'storefront_privacy_policy_link', true ) && function_exists( 'the_privacy_policy_link' ) ) {
			$separator    = '<span role="separator" aria-hidden="true"></span>';
			$links_output = get_the_privacy_policy_link( '', ( ! empty( $links_output ) ? $separator : '' ) ) . $links_output;
		}

		$links_output = apply_filters( 'storefront_credit_links_output', $links_output );
		?>
		<div class="site-info footer-copyright-wrap">

			<div class="copyright-text">
				<?php echo esc_html( apply_filters( 'storefront_copyright_text', $content = '&copy; ' . get_bloginfo( 'name' ) . ' ' . gmdate( 'Y' ) ) ); ?>. All Rights Reserved.
			</div>

			<div class="policies-links">
				<ul>
					<li><a href="/privacy-policy">Privacy Policy</a></li>
					<li><a href="/terms-and-conditions">Terms & Conditions</a></li>
					<!-- <li><a href="/return-exchange">Return & Exchange</a></li> -->
				</ul>
			</div>

		</div><!-- .site-info -->
		<?php
	}
}


/**
 * Adjust header hooks to prevent duplicate branding, search, cart, and navigation.
 */
function storefront_child_adjust_header_hooks() {
	remove_action( 'storefront_header', 'storefront_header_container', 0 );
	remove_action( 'storefront_header', 'storefront_site_branding', 20 );
	remove_action( 'storefront_header', 'storefront_product_search', 40 );
	remove_action( 'storefront_header', 'storefront_header_container_close', 41 );
	remove_action( 'storefront_header', 'storefront_primary_navigation_wrapper', 42 );
	remove_action( 'storefront_header', 'storefront_primary_navigation', 50 );
	remove_action( 'storefront_header', 'storefront_header_cart', 60 );
	remove_action( 'storefront_header', 'storefront_primary_navigation_wrapper_close', 68 );
	remove_action( 'storefront_footer', 'storefront_handheld_footer_bar', 999 );

	// Remove duplicate ordering and result count from after_shop_loop (bottom storefront-sorting)
	remove_action( 'woocommerce_after_shop_loop', 'woocommerce_catalog_ordering', 10 );
	remove_action( 'woocommerce_after_shop_loop', 'woocommerce_result_count', 20 );
}
add_action( 'init', 'storefront_child_adjust_header_hooks' );


/**
 * Replace col-full with site-container in WooCommerce breadcrumbs.
 */
function agro_aura_woocommerce_breadcrumbs( $defaults ) {
	$defaults['wrap_before'] = '<div class="storefront-breadcrumb"><div class="site-container"><nav class="woocommerce-breadcrumb" aria-label="' . esc_attr__( 'breadcrumbs', 'storefront' ) . '">';
	$defaults['wrap_after']  = '</nav></div></div>';
	return $defaults;
}
add_filter( 'woocommerce_breadcrumb_defaults', 'agro_aura_woocommerce_breadcrumbs', 20 );