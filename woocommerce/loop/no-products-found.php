<?php
/**
 * Displayed when no products are found matching the current query.
 *
 * Override WooCommerce loop/no-products-found.php template.
 *
 * @package Storefront_Child
 */

defined( 'ABSPATH' ) || exit;

$search_query = get_search_query();
$is_search    = is_search() || ! empty( $search_query );
$shop_url     = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );
?>

<div class="agro-empty-shop-state">
	<div class="agro-empty-shop-card">

		<div class="empty-icon-wrap" aria-hidden="true">
			<div class="empty-icon-circle">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
					<circle cx="11" cy="11" r="8"></circle>
					<line x1="21" y1="21" x2="16.65" y2="16.65"></line>
					<line x1="8" y1="11" x2="14" y2="11"></line>
				</svg>
			</div>
		</div>

		<header class="empty-state-header">
			<?php if ( $is_search && ! empty( $search_query ) ) : ?>
				<h2 class="empty-title">
					<?php
					printf(
						/* translators: %s: search query */
						esc_html__( 'No products found for "%s"', 'storefront-child' ),
						'<span class="search-term">' . esc_html( $search_query ) . '</span>'
					);
					?>
				</h2>
				<p class="empty-subtitle">
					<?php esc_html_e( 'We couldn\'t find any fresh products matching your search. Please check the spelling or try searching for something else.', 'storefront-child' ); ?>
				</p>
			<?php else : ?>
				<h2 class="empty-title"><?php esc_html_e( 'No products found', 'storefront-child' ); ?></h2>
				<p class="empty-subtitle">
					<?php esc_html_e( 'No products were found matching your selection. Try browsing our full catalog or return to the homepage.', 'storefront-child' ); ?>
				</p>
			<?php endif; ?>
		</header>

		<?php /* if ( $is_search ) : ?>
			<div class="empty-search-box">
				<form role="search" method="get" class="agro-inline-search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
					<div class="search-input-wrap">
						<span class="search-icon" aria-hidden="true">
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
								<circle cx="11" cy="11" r="8"></circle>
								<line x1="21" y1="21" x2="16.65" y2="16.65"></line>
							</svg>
						</span>
						<input type="search" class="search-field" placeholder="<?php echo esc_attr__( 'Search other fresh products...', 'storefront-child' ); ?>" value="<?php echo esc_attr( $search_query ); ?>" name="s" />
						<input type="hidden" name="post_type" value="product" />
						<button type="submit" class="search-submit-btn"><?php esc_html_e( 'Search', 'storefront-child' ); ?></button>
					</div>
				</form>
			</div>
		<?php endif; */ ?>

		<div class="agro-empty-action-buttons">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="agro-btn-home">
				<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
					<polyline points="9 22 9 12 15 12 15 22"></polyline>
				</svg>
				<span><?php esc_html_e( 'Back to Homepage', 'storefront-child' ); ?></span>
			</a>
			<a href="<?php echo esc_url( $shop_url ); ?>" class="agro-btn-shop">
				<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"></path>
					<line x1="3" x2="21" y1="6"></line>
					<path d="M16 10a4 4 0 0 1-8 0"></path>
				</svg>
				<span><?php esc_html_e( 'Browse All Products', 'storefront-child' ); ?></span>
			</a>
		</div>

	</div>
</div>
