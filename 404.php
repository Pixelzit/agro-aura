<?php
/**
 * The template for displaying 404 pages (not found).
 *
 * @package storefront
 */

get_header(); ?>

	<div id="primary" class="content-area">

		<main id="main" class="site-main" role="main">
			<div class="site-container">
				<div class="error-404 not-found">

					<div class="page-content">

						<div class="agro-404-hero">
							<header class="page-header agro-404-header">
								<div class="agro-404-number">404</div>
								<h1 class="page-title"><?php esc_html_e( 'Oops! That page can&rsquo;t be found.', 'storefront' ); ?></h1>
								<p class="page-subtitle"><?php esc_html_e( 'The page you are looking for might have been removed, had its name changed, or is temporarily unavailable. Try searching our store below or return to home.', 'storefront' ); ?></p>
							</header><!-- .page-header -->

							<section class="agro-404-search-section" aria-label="<?php esc_attr_e( 'Search Products', 'storefront' ); ?>">
								<?php /* ?>
								<form role="search" method="get" class="agro-404-search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
									<div class="search-field-wrap">
										<span class="search-icon" aria-hidden="true">
											<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
										</span>
										<input type="search" class="search-field" placeholder="<?php echo esc_attr__( 'Search products, categories, or keywords...', 'storefront' ); ?>" value="<?php echo get_search_query(); ?>" name="s" />
										<?php if ( storefront_is_woocommerce_activated() ) : ?>
											<input type="hidden" name="post_type" value="product" />
										<?php endif; ?>
										<button type="submit" class="search-submit"><?php esc_html_e( 'Search', 'storefront' ); ?></button>
									</div>
								</form>
								<?php */ ?>

								<div class="agro-404-quick-links">
									<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="agro-btn-home">
										<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
										<?php esc_html_e( 'Back to Homepage', 'storefront' ); ?>
									</a>
									<?php if ( storefront_is_woocommerce_activated() && function_exists( 'wc_get_page_permalink' ) ) : ?>
										<a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="agro-btn-shop">
											<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><line x1="3" x2="21" y1="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
											<?php esc_html_e( 'Browse All Products', 'storefront' ); ?>
										</a>
									<?php endif; ?>
								</div>
							</section>
						</div><!-- .agro-404-hero -->

						<?php
						if ( storefront_is_woocommerce_activated() ) {

							echo '<div class="fourohfour-columns-2">';

								echo '<section class="" aria-label="' . esc_html__( 'Promoted Products', 'storefront' ) . '">';

									storefront_promoted_products();

								echo '</section>';

								// echo '<nav class="col-2" aria-label="' . esc_html__( 'Product Categories', 'storefront' ) . '">';

								// 	echo '<h2>' . esc_html__( 'Product Categories', 'storefront' ) . '</h2>';

								// 	the_widget(
								// 		'WC_Widget_Product_Categories',
								// 		array(
								// 			'count' => 1,
								// 		)
								// 	);

								// echo '</nav>';

							echo '</div>';

							echo '<section aria-label="' . esc_html__( 'Popular Products', 'storefront' ) . '">';

								echo '<h2>' . esc_html__( 'Popular Products', 'storefront' ) . '</h2>';

								$shortcode_content = storefront_do_shortcode(
									'best_selling_products',
									array(
										'per_page' => 4,
										'columns'  => 4,
									)
								);

								echo $shortcode_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

							echo '</section>';
						}
						?>

					</div><!-- .page-content -->
				</div><!-- .error-404 -->
			</div>

		</main><!-- #main -->
	</div><!-- #primary -->

<?php
get_footer();
