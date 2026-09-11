<?php
/**
 * The Template for displaying product archives (Shop page)
 * Custom Agro Aura layout matching reference design.
 *
 * @package Storefront_Child
 */

defined( 'ABSPATH' ) || exit;

get_header( 'shop' );
?>

<div class="site-container">

<?php
/**
 * Hook: woocommerce_before_main_content.
 *
 * @hooked woocommerce_output_content_wrapper - 10 (outputs opening divs for the content)
 * @hooked woocommerce_breadcrumb - 20
 */
do_action( 'woocommerce_before_main_content' );
?>

<?php
$has_products = woocommerce_product_loop();
?>

<div class="agro-shop-layout<?php echo ! $has_products ? ' agro-no-products-layout full-width' : ''; ?>">

	<?php if ( $has_products ) : ?>
		<!-- ========================================== -->
		<!-- 1. LEFT COLUMN: REFINE SHELF (SIDEBAR)     -->
		<!-- ========================================== -->
		<aside>
			<?php echo do_shortcode( '[agro_product_filters]' ); ?>
		</aside>
	<?php endif; ?>

	<!-- ========================================== -->
	<!-- 2. RIGHT COLUMN: MAIN PRODUCTS CONTENT     -->
	<!-- ========================================== -->
	<div class="agro-shop-main-content<?php echo ! $has_products ? ' full-width' : ''; ?>">
		<div class="agro-shop-loader-spinner" aria-hidden="true"></div>

		<?php if ( $has_products ) : ?>
			<!-- Shop Header / Controls Bar -->
			<div class="agro-shop-top-bar">
				<div class="shop-result-count">
					<?php
					global $wp_query;
					$total_products = $wp_query->found_posts;
					$paged          = max( 1, get_query_var( 'paged' ) );
					$per_page       = apply_filters( 'loop_shop_per_page', wc_get_default_products_per_row() * wc_get_default_product_rows_per_page() );
					$start          = ( $paged - 1 ) * $per_page + 1;
					$end            = min( $paged * $per_page, $total_products );

					if ( $total_products > 0 ) {
						echo 'Showing <strong>' . esc_html( $start ) . '–' . esc_html( $end ) . '</strong> of <strong>' . esc_html( $total_products ) . '</strong> fresh products';
					} else {
						echo 'Showing 0 fresh products';
					}
					?>
				</div>

				<div class="agro-top-right-controls">
					<!-- Active Filter Chips (Dynamically rendered via JS) -->
					<div class="agro-active-filters" id="agro-active-filters"></div>

					<!-- Catalog Ordering Dropdown -->
					<div class="agro-catalog-ordering">
						<span class="sort-label">SORT BY:</span>
						<?php woocommerce_catalog_ordering(); ?>
					</div>
				</div>
			</div>
		<?php endif; ?>

		<?php
		if ( $has_products ) {
		?>
			<ul class="products">
				<?php
				if ( wc_get_loop_prop( 'total' ) ) {
					while ( have_posts() ) {
						the_post();

						/**
						 * Hook: woocommerce_shop_loop.
						 */
						do_action( 'woocommerce_shop_loop' );

						wc_get_template_part( 'content', 'product' );
					}
				}
				?>
			</ul>
			<div class="agro-pagination-wrap" id="agro-shop-pagination">
				<?php
				/**
				 * Hook: woocommerce_after_shop_loop.
				 *
				 * @hooked woocommerce_pagination - 10
				 */
				do_action( 'woocommerce_after_shop_loop' );
				?>
			</div>
		<?php
		} else {
			/**
			 * Hook: woocommerce_no_products_found.
			 *
			 * @hooked wc_no_products_found - 10
			 */
			do_action( 'woocommerce_no_products_found' );
		}
		?>

	</div><!-- .agro-shop-main-content -->

</div><!-- .agro-shop-layout -->

<?php
/**
 * Hook: woocommerce_after_main_content.
 *
 * @hooked woocommerce_output_content_wrapper_end - 10
 */
do_action( 'woocommerce_after_main_content' );
?>

</div><!-- .site-container -->

<?php
get_footer( 'shop' );
