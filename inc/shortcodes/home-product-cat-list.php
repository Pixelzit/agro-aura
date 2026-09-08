<?php
/**
 * Home Product Category List Shortcode
 *
 * Provides shortcode [home_product_cat_list] or [agro_product_cat_list]
 * Displays category filter tabs with 4 products per page loaded via AJAX,
 * and a "View More" button redirecting to the active category or shop page.
 *
 * @package Storefront_Child
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register Shortcode Assets
 */
function agro_aura_register_home_product_cat_list_assets() {
	$css_file = get_stylesheet_directory() . '/assets/css/home-product-cat-list.css';
	$version  = file_exists( $css_file ) ? filemtime( $css_file ) : wp_get_theme()->get( 'Version' );

	wp_register_style(
		'agro-home-product-cat-list',
		get_stylesheet_directory_uri() . '/assets/css/home-product-cat-list.css',
		array(),
		$version
	);
}
add_action( 'wp_enqueue_scripts', 'agro_aura_register_home_product_cat_list_assets' );

/**
 * AJAX Handler: Get 4 Products for Selected Category
 */
function agro_aura_ajax_get_cat_products() {
	check_ajax_referer( 'agro_cat_nonce', 'nonce' );

	$category = isset( $_POST['category'] ) ? sanitize_text_field( wp_unslash( $_POST['category'] ) ) : 'all';
	$number   = isset( $_POST['number'] ) ? intval( $_POST['number'] ) : 4;
	if ( $number <= 0 ) {
		$number = 4;
	}

	$query_args = array(
		'post_type'      => 'product',
		'post_status'    => 'publish',
		'posts_per_page' => $number,
		'orderby'        => 'date',
		'order'          => 'DESC',
	);

	if ( 'all' !== $category && ! empty( $category ) ) {
		$query_args['tax_query'] = array(
			array(
				'taxonomy' => 'product_cat',
				'field'    => 'slug',
				'terms'    => $category,
			),
		);
	}

	$products_query = new WP_Query( $query_args );

	ob_start();
	if ( $products_query->have_posts() ) {
		while ( $products_query->have_posts() ) {
			$products_query->the_post();
			wc_get_template_part( 'content', 'product' );
		}
		wp_reset_postdata();
	} else {
		echo '<li class="agro-no-products"><p>' . esc_html__( 'No products found in this category.', 'storefront-child' ) . '</p></li>';
	}
	$html = ob_get_clean();

	wp_send_json_success( array( 'html' => $html ) );
}
add_action( 'wp_ajax_agro_get_cat_products', 'agro_aura_ajax_get_cat_products' );
add_action( 'wp_ajax_nopriv_agro_get_cat_products', 'agro_aura_ajax_get_cat_products' );

/**
 * Render Product Category List with Filter Tabs and View More
 *
 * @param array $atts Shortcode attributes.
 * @return string HTML output.
 */
function agro_aura_home_product_cat_list_shortcode( $atts ) {
	// Enqueue CSS only when this shortcode is used on the page
	wp_enqueue_style( 'agro-home-product-cat-list' );

	$atts = shortcode_atts(
		array(
			'all_label'       => 'All Staples',
			'view_more_label' => 'View More',
			'categories'      => '', // Comma-separated slugs e.g. "rice,daal,edible-oil,aata"
			'number'          => 4,  // Exactly 4 products per page
			'columns'         => 4,
			'orderby'         => 'date',
			'order'           => 'DESC',
			'title'           => '',
			'sub_title'       => '',
			'class'           => '',
			'container'       => 'true',
		),
		$atts,
		'agro_product_cat_list'
	);

	// 1. Get Categories for Tabs
	$tab_categories = array();

	if ( ! empty( $atts['categories'] ) ) {
		$slugs = array_map( 'trim', explode( ',', $atts['categories'] ) );
		foreach ( $slugs as $slug ) {
			$term = get_term_by( 'slug', $slug, 'product_cat' );
			if ( $term && ! is_wp_error( $term ) ) {
				$tab_categories[] = $term;
			}
		}
	} else {
		// Automatically get non-empty product categories (excluding uncategorized)
		$uncat   = get_term_by( 'slug', 'uncategorized', 'product_cat' );
		$exclude = ( $uncat && ! is_wp_error( $uncat ) ) ? array( $uncat->term_id ) : array();

		$terms = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => true,
				'orderby'    => 'count',
				'order'      => 'DESC',
				'number'     => 6,
				'exclude'    => $exclude,
			)
		);

		if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
			$tab_categories = $terms;
		}
	}

	// 2. Initial Query: 4 Products for "All Staples"
	$posts_per_page = intval( $atts['number'] ) > 0 ? intval( $atts['number'] ) : 4;

	$query_args = array(
		'post_type'      => 'product',
		'post_status'    => 'publish',
		'posts_per_page' => $posts_per_page,
		'orderby'        => sanitize_text_field( $atts['orderby'] ),
		'order'          => sanitize_text_field( $atts['order'] ),
	);

	$products_query = new WP_Query( $query_args );

	if ( ! $products_query->have_posts() ) {
		return '';
	}

	$unique_id      = 'agro-cat-list-' . wp_rand( 100, 999 );
	$columns_count  = intval( $atts['columns'] ) > 0 ? intval( $atts['columns'] ) : 4;
	$custom_class   = ! empty( $atts['class'] ) ? ' ' . esc_attr( $atts['class'] ) : '';
	$shop_page_url  = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : '#';
	$ajax_url       = admin_url( 'admin-ajax.php' );
	$ajax_nonce     = wp_create_nonce( 'agro_cat_nonce' );

	ob_start();
	?>
	<section 
		id="<?php echo esc_attr( $unique_id ); ?>" 
		class="agro-home-cat-list-section<?php echo esc_attr( $custom_class ); ?>"
		data-ajax-url="<?php echo esc_url( $ajax_url ); ?>"
		data-nonce="<?php echo esc_attr( $ajax_nonce ); ?>"
		data-number="<?php echo esc_attr( $posts_per_page ); ?>"
	>
		

			<?php if ( ! empty( $atts['title'] ) ) : ?>
				<div class="agro-cat-list-header">
					<h2 class="agro-cat-list-title"><?php echo esc_html( $atts['title'] ); ?></h2>
					<?php if ( ! empty( $atts['sub_title'] ) ) : ?>
						<p class="agro-cat-list-subtitle"><?php echo esc_html( $atts['sub_title'] ); ?></p>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<!-- Category Filter Tabs -->
			<div class="agro-cat-tabs-wrapper">
				<div class="agro-cat-tabs-pill-group" role="tablist">
					<button 
						type="button" 
						class="agro-cat-tab-btn active" 
						role="tab" 
						aria-selected="true" 
						data-filter="all"
						data-url="<?php echo esc_url( $shop_page_url ); ?>"
					>
						<?php echo esc_html( $atts['all_label'] ); ?>
					</button>

					<?php foreach ( $tab_categories as $cat_term ) : ?>
						<?php $term_link = get_term_link( $cat_term, 'product_cat' ); ?>
						<button 
							type="button" 
							class="agro-cat-tab-btn" 
							role="tab" 
							aria-selected="false" 
							data-filter="<?php echo esc_attr( $cat_term->slug ); ?>"
							data-url="<?php echo esc_url( is_wp_error( $term_link ) ? '#' : $term_link ); ?>"
						>
							<?php echo esc_html( $cat_term->name ); ?>
						</button>
					<?php endforeach; ?>
				</div>
			</div>

			<!-- Product Cards Grid (Using Common Product Cards) -->
			<div class="agro-cat-products-wrapper">
				<div class="agro-cat-loader-spinner" aria-hidden="true"></div>
				<ul class="products agro-cat-products-grid">
					<?php
					while ( $products_query->have_posts() ) :
						$products_query->the_post();
						wc_get_template_part( 'content', 'product' );
					endwhile;
					wp_reset_postdata();
					?>
				</ul>
			</div>

			<!-- View More Button -->
			<div class="agro-cat-view-more-wrap">
				<a 
					href="<?php echo esc_url( $shop_page_url ); ?>" 
					class="agro-view-more-btn" 
					id="btn-view-more-<?php echo esc_attr( $unique_id ); ?>"
				>
					<span class="btn-text"><?php echo esc_html( $atts['view_more_label'] ); ?></span>
					<svg class="btn-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
						<line x1="5" y1="12" x2="19" y2="12"></line>
						<polyline points="12 5 19 12 12 19"></polyline>
					</svg>
				</a>
			</div>

		

		<script>
		(function() {
			var container = document.getElementById('<?php echo esc_js( $unique_id ); ?>');
			if (!container) return;

			var tabButtons = container.querySelectorAll('.agro-cat-tab-btn');
			var productsWrapper = container.querySelector('.agro-cat-products-wrapper');
			var productsGrid = container.querySelector('.agro-cat-products-grid');
			var viewMoreBtn = document.getElementById('btn-view-more-<?php echo esc_js( $unique_id ); ?>');

			var ajaxUrl = container.getAttribute('data-ajax-url');
			var nonce = container.getAttribute('data-nonce');
			var number = container.getAttribute('data-number') || 4;

			// Client-side cache to make revisiting tabs instant
			var productsCache = {
				'all': productsGrid.innerHTML
			};

			tabButtons.forEach(function(btn) {
				btn.addEventListener('click', function(e) {
					e.preventDefault();
					if (this.classList.contains('active')) return;

					var filter = this.getAttribute('data-filter');
					var redirectUrl = this.getAttribute('data-url');

					// 1. Update active tab UI
					tabButtons.forEach(function(b) {
						b.classList.remove('active');
						b.setAttribute('aria-selected', 'false');
					});
					this.classList.add('active');
					this.setAttribute('aria-selected', 'true');

					// 2. Update View More URL
					if (viewMoreBtn && redirectUrl) {
						viewMoreBtn.setAttribute('href', redirectUrl);
					}

					// 3. Render cached products if already loaded
					if (productsCache[filter]) {
						productsGrid.innerHTML = productsCache[filter];
						return;
					}

					// 4. AJAX request for 4 category products
					productsWrapper.classList.add('is-loading');

					var formData = new FormData();
					formData.append('action', 'agro_get_cat_products');
					formData.append('category', filter);
					formData.append('number', number);
					formData.append('nonce', nonce);

					fetch(ajaxUrl, {
						method: 'POST',
						body: formData
					})
					.then(function(response) {
						return response.json();
					})
					.then(function(res) {
						productsWrapper.classList.remove('is-loading');
						if (res && res.success && res.data && res.data.html) {
							productsCache[filter] = res.data.html;
							productsGrid.innerHTML = res.data.html;
						}
					})
					.catch(function(err) {
						productsWrapper.classList.remove('is-loading');
						console.error('Agro Aura AJAX Error:', err);
					});
				});
			});
		})();
		</script>
	</section>
	<?php
	return ob_get_clean();
}
add_shortcode( 'agro_product_cat_list', 'agro_aura_home_product_cat_list_shortcode' );
add_shortcode( 'home_product_cat_list', 'agro_aura_home_product_cat_list_shortcode' );
add_shortcode( 'agro_category_products', 'agro_aura_home_product_cat_list_shortcode' );

/**
 * Preview endpoint for testing: /?preview_home_cat_list=1
 */
function agro_aura_preview_home_cat_list_shortcode() {
	if ( isset( $_GET['preview_home_cat_list'] ) ) {
		get_header();
		echo '<main style="padding: 40px 0 60px; background-color: #fafafa;">';
		echo do_shortcode( '[home_product_cat_list]' );
		echo '</main>';
		get_footer();
		exit;
	}
}
add_action( 'template_redirect', 'agro_aura_preview_home_cat_list_shortcode' );
