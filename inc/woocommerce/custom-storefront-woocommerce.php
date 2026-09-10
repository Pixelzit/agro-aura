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
