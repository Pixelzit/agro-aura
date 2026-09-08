<?php
/**
 * The header for our theme.
 *
 * Displays all of the <head> section and everything up till <div id="content">
 *
 * @package storefront
 */

?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="profile" href="http://gmpg.org/xfn/11">
<link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>">

<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>

<?php wp_body_open(); ?>

<?php do_action( 'storefront_before_site' ); ?>

<?php 
$account_url   = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : wp_login_url();
$current_user  = wp_get_current_user();
$display_name  = $current_user->exists() ? ( ! empty( $current_user->first_name ) ? $current_user->first_name : $current_user->display_name ) : __( 'My Account', 'storefront-child' );
$avatar_url    = $current_user->exists() ? get_avatar_url( $current_user->ID, array( 'size' => 80 ) ) : get_avatar_url( 0, array( 'size' => 80 ) );
?>

<div id="page" class="hfeed site">
	<?php do_action( 'storefront_before_header' ); ?>

	<header id="masthead" class="site-header site-header-wrap" role="banner" style="<?php storefront_header_styles(); ?>">
		<section class="site-top-header">
			<div class="site-container">
				50 % off on your first order upto Rs 200, will be credited in your Frugivore Wallet upon the delivery of your first order.
			</div>
		</section>

		<section class="site-main-header">
			<div class="site-container">
				<div class="site-main-header-row">
					<div class="header-logo-group">
						<button type="button" class="agro-mobile-menu-toggle" aria-label="<?php esc_attr_e( 'Open Menu', 'storefront-child' ); ?>" aria-controls="agro-side-drawer" aria-expanded="false">
							<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#111827" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
								<line x1="3" y1="6" x2="21" y2="6"></line>
								<line x1="3" y1="12" x2="21" y2="12"></line>
								<line x1="3" y1="18" x2="21" y2="18"></line>
							</svg>
						</button>
						<div class="header-logo">
							<?php storefront_site_title_or_logo(); ?>
						</div>
					</div>
					
					<div class="header-search">
						<?php
						if ( function_exists( 'the_widget' ) && class_exists( 'WC_Widget_Product_Search' ) ) {
							the_widget( 'WC_Widget_Product_Search', 'title=' );
						} elseif ( function_exists( 'storefront_product_search' ) ) {
							storefront_product_search();
						} else {
							get_search_form();
						}
						?>
					</div>
					
					<div class="header-cart">
						<!-- User Profile -->
						<a href="<?php echo esc_url( $account_url ); ?>" class="agro-header-user" title="<?php esc_attr_e( 'My Account', 'storefront-child' ); ?>">
							<?php if ( is_user_logged_in() && $current_user->exists() ) : ?>
								<img class="user-avatar" src="<?php echo esc_url( $avatar_url ); ?>" alt="<?php echo esc_attr( $display_name ); ?>">
							<?php else : ?>
								<span class="user-icon">
									<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
										<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
										<circle cx="12" cy="7" r="4"></circle>
									</svg>
								</span>
							<?php endif; ?>
						</a>

						<?php
						if ( function_exists( 'storefront_header_cart' ) ) {
							storefront_header_cart();
						}
						?>
					</div>
				</div>
			</div>
		</section>

		<section class="site-main-nav">
			<div class="site-container">
				<?php
				if ( function_exists( 'storefront_primary_navigation' ) ) {
					storefront_primary_navigation();
				}
				?>
			</div>
		</section>

		<!-- Mobile Side Drawer (< 1024px) -->
		<div id="agro-side-drawer" class="agro-side-drawer" aria-hidden="true">
			<div class="drawer-header">
				<div class="drawer-logo">
					<span class="site-title"><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Agro Aura</a></span>
				</div>
				<button type="button" class="drawer-close" aria-label="<?php esc_attr_e( 'Close Menu', 'storefront-child' ); ?>">
					<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#111827" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<line x1="18" y1="6" x2="6" y2="18"></line>
						<line x1="6" y1="6" x2="18" y2="18"></line>
					</svg>
				</button>
			</div>

			<!-- User Account Banner inside Drawer -->
			<div class="drawer-user-info">
				<div class="drawer-avatar">
					<?php if ( is_user_logged_in() && $current_user->exists() ) : ?>
						<img src="<?php echo esc_url( $avatar_url ); ?>" alt="<?php echo esc_attr( $display_name ); ?>">
					<?php else : ?>
						<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
							<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
							<circle cx="12" cy="7" r="4"></circle>
						</svg>
					<?php endif; ?>
				</div>
				<div class="drawer-user-text">
					<span class="drawer-greeting"><?php echo is_user_logged_in() ? esc_html__( 'Hello,', 'storefront-child' ) : esc_html__( 'Welcome', 'storefront-child' ); ?></span>
					<a href="<?php echo esc_url( $account_url ); ?>" class="drawer-user-link"><?php echo esc_html( $display_name ); ?></a>
				</div>
			</div>

			<!-- Navigation Links -->
			<div class="drawer-nav">
				<?php
				wp_nav_menu(
					array(
						'theme_location' => 'primary',
						'container'      => false,
						'menu_class'     => 'drawer-menu',
						'fallback_cb'    => '',
					)
				);
				?>
			</div>

			<!-- Drawer Offer Notice -->
			<div class="drawer-footer">
				<div class="drawer-offer">
					<span class="offer-badge"><?php esc_html_e( 'First Order', 'storefront-child' ); ?></span>
					<p><?php esc_html_e( '50% off up to Rs 200 credited in your Wallet!', 'storefront-child' ); ?></p>
				</div>
			</div>
		</div>
		<div class="agro-drawer-backdrop" aria-hidden="true"></div>

		<?php
		/**
		 * Functions hooked into storefront_header action
		 *
		 * @hooked storefront_header_container                 - 0
		 * @hooked storefront_skip_links                       - 5
		 * @hooked storefront_social_icons                     - 10
		 * @hooked storefront_site_branding                    - 20
		 * @hooked storefront_secondary_navigation             - 30
		 * @hooked storefront_product_search                   - 40
		 * @hooked storefront_header_container_close           - 41
		 * @hooked storefront_primary_navigation_wrapper       - 42
		 * @hooked storefront_primary_navigation               - 50
		 * @hooked storefront_header_cart                      - 60
		 * @hooked storefront_primary_navigation_wrapper_close - 68
		 */
		//do_action( 'storefront_header' );
		?>

	</header><!-- #masthead -->

	<?php
	/**
	 * Functions hooked in to storefront_before_content
	 *
	 * @hooked storefront_header_widget_region - 10
	 * @hooked woocommerce_breadcrumb - 10
	 */
	do_action( 'storefront_before_content' );
	?>

	<div id="content" class="site-content" tabindex="-1">
		<!-- <div class="site-container"> -->

		<?php
		do_action( 'storefront_content_top' );
