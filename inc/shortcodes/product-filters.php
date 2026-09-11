<?php
/**
 * Product Filters Shortcode (Refine Shelf Sidebar)
 *
 * Provides shortcodes [agro_product_filters] or [product_filters]
 * Dynamically queries categories, price ranges, and pack sizes with full AJAX support.
 *
 * @package Storefront_Child
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register Product Filters Assets (CSS & JS)
 */
function agro_aura_register_product_filters_assets() {
	$theme_dir = get_stylesheet_directory();
	$theme_uri = get_stylesheet_directory_uri();
	$ver       = wp_get_theme()->get( 'Version' );

	// 1. Dedicated CSS
	$css_file = $theme_dir . '/assets/css/product-filters.css';
	$css_ver  = file_exists( $css_file ) ? filemtime( $css_file ) : $ver;
	wp_register_style(
		'agro-product-filters',
		$theme_uri . '/assets/css/product-filters.css',
		array(),
		$css_ver
	);

	// 2. Dedicated JS
	$js_file = $theme_dir . '/assets/js/product-filters.js';
	$js_ver  = file_exists( $js_file ) ? filemtime( $js_file ) : $ver;
	wp_register_script(
		'agro-product-filters-js',
		$theme_uri . '/assets/js/product-filters.js',
		array(),
		$js_ver,
		true
	);

	wp_localize_script(
		'agro-product-filters-js',
		'agroFilterData',
		array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'agro_filter_nonce' ),
			'shopUrl' => function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' ),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'agro_aura_register_product_filters_assets' );

/**
 * Get Price Range Definitions
 *
 * @return array
 */
function agro_aura_get_price_range_definitions() {
	return array(
		'under-100' => array(
			'min'   => 0,
			'max'   => 100,
			'label' => __( 'Under ₹100', 'storefront-child' ),
		),
		'100-300'   => array(
			'min'   => 100,
			'max'   => 300,
			'label' => __( '₹100 - ₹300', 'storefront-child' ),
		),
		'300-500'   => array(
			'min'   => 300,
			'max'   => 500,
			'label' => __( '₹300 - ₹500', 'storefront-child' ),
		),
		'above-500' => array(
			'min'   => 500,
			'max'   => 999999,
			'label' => __( 'Above ₹500', 'storefront-child' ),
		),
	);
}

/**
 * Calculate Dynamic Product Counts for Price Ranges
 *
 * @param string $category_slug Optional category slug to narrow counts.
 * @return array Key => Count.
 */
function agro_aura_get_price_range_counts( $category_slug = '' ) {
	$definitions = agro_aura_get_price_range_definitions();
	$counts      = array();

	foreach ( $definitions as $key => $def ) {
		$counts[ $key ] = 0;
	}

	$args = array(
		'post_type'      => 'product',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'fields'         => 'ids',
	);

	if ( ! empty( $category_slug ) && 'all' !== $category_slug ) {
		$args['tax_query'] = array(
			array(
				'taxonomy' => 'product_cat',
				'field'    => 'slug',
				'terms'    => $category_slug,
			),
		);
	}

	$product_ids = get_posts( $args );

	if ( ! empty( $product_ids ) ) {
		foreach ( $product_ids as $pid ) {
			$product = wc_get_product( $pid );
			if ( ! $product ) {
				continue;
			}

			$price = (float) $product->get_price();
			if ( $price <= 0 ) {
				$price = 145; // Default fallback price
			}

			foreach ( $definitions as $key => $def ) {
				if ( 'under-100' === $key && $price < 100 ) {
					$counts[ $key ]++;
				} elseif ( '100-300' === $key && $price >= 100 && $price <= 300 ) {
					$counts[ $key ]++;
				} elseif ( '300-500' === $key && $price > 300 && $price <= 500 ) {
					$counts[ $key ]++;
				} elseif ( 'above-500' === $key && $price > 500 ) {
					$counts[ $key ]++;
				}
			}
		}
	}

	return $counts;
}

/**
 * Determine Available Pack Sizes for a Product
 *
 * @param WC_Product|int $product
 * @return array List of pack size strings e.g. ['500g', '1kg']
 */
function agro_aura_get_product_pack_sizes( $product ) {
	if ( is_numeric( $product ) ) {
		$product = wc_get_product( $product );
	}
	if ( ! is_a( $product, WC_Product::class ) ) {
		return array();
	}

	$packs = array();

	// 1. If variable product, check variation attributes
	if ( $product->is_type( 'variable' ) ) {
		$variations = $product->get_available_variations();
		foreach ( $variations as $var ) {
			foreach ( $var['attributes'] as $attr_val ) {
				if ( ! empty( $attr_val ) ) {
					$packs[] = trim( $attr_val );
				}
			}
		}
	}

	// 2. Check WooCommerce taxonomies (pa_weight, pa_size, pa_pack)
	$taxonomies = array( 'pa_weight', 'pa_size', 'pa_pack-size', 'pa_pack' );
	foreach ( $taxonomies as $tax ) {
		$terms = get_the_terms( $product->get_id(), $tax );
		if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				$packs[] = $term->name;
			}
		}
	}

	// 3. Check product weight
	if ( $product->has_weight() ) {
		$packs[] = $product->get_weight() . ' ' . get_option( 'woocommerce_weight_unit', 'kg' );
	}

	// 4. Check dynamic product display size (Volume, Bottle Size, etc.)
	if ( function_exists( 'agro_aura_get_product_display_size' ) ) {
		$size_info = agro_aura_get_product_display_size( $product );
		if ( ! empty( $size_info['label'] ) ) {
			$packs[] = $size_info['label'];
		}
	}

	return array_unique( $packs );
}

/**
 * Get Available Pack Sizes Across Store with Counts
 *
 * @param string $category_slug Optional category filter
 * @return array Pack size => count
 */
function agro_aura_get_available_pack_sizes_with_counts( $category_slug = '' ) {
	$standard_packs = array( '500g', '1kg', '2kg', '5kg', '10kg' );
	$counts         = array();
	foreach ( $standard_packs as $sp ) {
		$counts[ $sp ] = 0;
	}

	$args = array(
		'post_type'      => 'product',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'fields'         => 'ids',
	);

	if ( ! empty( $category_slug ) && 'all' !== $category_slug ) {
		$args['tax_query'] = array(
			array(
				'taxonomy' => 'product_cat',
				'field'    => 'slug',
				'terms'    => $category_slug,
			),
		);
	}

	$product_ids = get_posts( $args );

	if ( ! empty( $product_ids ) ) {
		foreach ( $product_ids as $pid ) {
			$product_packs = agro_aura_get_product_pack_sizes( $pid );
			foreach ( $standard_packs as $sp ) {
				foreach ( $product_packs as $pp ) {
					if ( strtolower( $pp ) === strtolower( $sp ) ) {
						$counts[ $sp ]++;
						break;
					}
				}
			}
		}
	}

	return $counts;
}

/**
 * AJAX Handler: Filter Products by Category, Price Range, Pack Size & Sort Order
 */
function agro_aura_ajax_filter_products() {
	check_ajax_referer( 'agro_filter_nonce', 'nonce' );

	$category     = isset( $_POST['category'] ) ? sanitize_text_field( wp_unslash( $_POST['category'] ) ) : 'all';
	$pack_size    = isset( $_POST['pack_size'] ) ? sanitize_text_field( wp_unslash( $_POST['pack_size'] ) ) : '';
	$orderby      = isset( $_POST['orderby'] ) ? sanitize_text_field( wp_unslash( $_POST['orderby'] ) ) : 'menu_order';
	$paged        = isset( $_POST['paged'] ) ? max( 1, intval( $_POST['paged'] ) ) : 1;
	$price_ranges = isset( $_POST['price_ranges'] ) ? (array) $_POST['price_ranges'] : array();
	$price_ranges = array_map( 'sanitize_text_field', $price_ranges );

	$per_page = apply_filters( 'loop_shop_per_page', wc_get_default_products_per_row() * wc_get_default_product_rows_per_page() );

	// Build WP_Query
	$query_args = array(
		'post_type'      => 'product',
		'post_status'    => 'publish',
		'posts_per_page' => $per_page,
		'paged'          => $paged,
	);

	// 1. Orderby sorting
	switch ( $orderby ) {
		case 'price':
			$query_args['orderby']  = 'meta_value_num';
			$query_args['meta_key'] = '_price';
			$query_args['order']    = 'ASC';
			break;
		case 'price-desc':
			$query_args['orderby']  = 'meta_value_num';
			$query_args['meta_key'] = '_price';
			$query_args['order']    = 'DESC';
			break;
		case 'date':
			$query_args['orderby'] = 'date';
			$query_args['order']   = 'DESC';
			break;
		case 'popularity':
			$query_args['orderby']  = 'meta_value_num';
			$query_args['meta_key'] = 'total_sales';
			$query_args['order']    = 'DESC';
			break;
		case 'rating':
			$query_args['orderby']  = 'meta_value_num';
			$query_args['meta_key'] = '_wc_average_rating';
			$query_args['order']    = 'DESC';
			break;
		default:
			$query_args['orderby'] = 'menu_order title';
			$query_args['order']   = 'ASC';
			break;
	}

	// 2. Category Filter
	if ( ! empty( $category ) && 'all' !== $category ) {
		$query_args['tax_query'] = array(
			array(
				'taxonomy' => 'product_cat',
				'field'    => 'slug',
				'terms'    => $category,
			),
		);
	}

	// 3. Price Ranges Filter
	if ( ! empty( $price_ranges ) ) {
		$price_meta = array( 'relation' => 'OR' );
		foreach ( $price_ranges as $range_key ) {
			if ( 'under-100' === $range_key ) {
				$price_meta[] = array(
					'key'     => '_price',
					'value'   => 100,
					'type'    => 'NUMERIC',
					'compare' => '<',
				);
			} elseif ( '100-300' === $range_key ) {
				$price_meta[] = array(
					'key'     => '_price',
					'value'   => array( 100, 300 ),
					'type'    => 'NUMERIC',
					'compare' => 'BETWEEN',
				);
			} elseif ( '300-500' === $range_key ) {
				$price_meta[] = array(
					'key'     => '_price',
					'value'   => array( 300, 500 ),
					'type'    => 'NUMERIC',
					'compare' => 'BETWEEN',
				);
			} elseif ( 'above-500' === $range_key ) {
				$price_meta[] = array(
					'key'     => '_price',
					'value'   => 500,
					'type'    => 'NUMERIC',
					'compare' => '>',
				);
			}
		}
		if ( count( $price_meta ) > 1 ) {
			$query_args['meta_query'] = isset( $query_args['meta_query'] ) ? array_merge( $query_args['meta_query'], array( $price_meta ) ) : array( $price_meta );
		}
	}

	// 4. Pack Size Filter
	if ( ! empty( $pack_size ) ) {
		// Find all products matching this pack size
		$all_prods = get_posts(
			array(
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			)
		);

		$matched_ids = array();
		foreach ( $all_prods as $pid ) {
			$prod_packs = agro_aura_get_product_pack_sizes( $pid );
			foreach ( $prod_packs as $pp ) {
				if ( strtolower( $pp ) === strtolower( $pack_size ) ) {
					$matched_ids[] = $pid;
					break;
				}
			}
		}

		if ( ! empty( $matched_ids ) ) {
			$query_args['post__in'] = $matched_ids;
		} else {
			// No products have this pack size
			$query_args['post__in'] = array( 0 );
		}
	}

	$products_query = new WP_Query( $query_args );

	// Output buffer products
	ob_start();
	if ( $products_query->have_posts() ) {
		while ( $products_query->have_posts() ) {
			$products_query->the_post();
			wc_get_template_part( 'content', 'product' );
		}
		wp_reset_postdata();
	} else {
		?>
		<li class="agro-no-products-found">
			<div class="no-products-msg">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
					<circle cx="11" cy="11" r="8"></circle>
					<line x1="21" y1="21" x2="16.65" y2="16.65"></line>
					<line x1="8" y1="11" x2="14" y2="11"></line>
				</svg>
				<h3><?php esc_html_e( 'No products found', 'storefront-child' ); ?></h3>
				<p><?php esc_html_e( 'No products match your selected filter criteria. Try clearing some filters.', 'storefront-child' ); ?></p>
				<button type="button" class="agro-clear-filters-btn"><?php esc_html_e( 'Clear All Filters', 'storefront-child' ); ?></button>
			</div>
		</li>
		<?php
	}
	$html = ob_get_clean();

	$total_found = $products_query->found_posts;
	$max_pages   = $products_query->max_num_pages;
	$start       = ( $paged - 1 ) * $per_page + 1;
	$end         = min( $paged * $per_page, $total_found );

	if ( $total_found > 0 ) {
		$result_count_text = sprintf(
			/* translators: 1: start count, 2: end count, 3: total products */
			__( 'Showing <strong>%1$s–%2$s</strong> of <strong>%3$s</strong> fresh products', 'storefront-child' ),
			esc_html( $start ),
			esc_html( $end ),
			esc_html( $total_found )
		);
	} else {
		$result_count_text = __( 'Showing <strong>0</strong> fresh products', 'storefront-child' );
	}

	// Dynamic Pagination HTML
	$pagination_html = '';
	if ( $max_pages > 1 ) {
		$pages = paginate_links(
			array(
				'base'      => '%_%',
				'format'    => '?paged=%#%',
				'current'   => $paged,
				'total'     => $max_pages,
				'prev_text' => '&larr;',
				'next_text' => '&rarr;',
				'type'      => 'array',
				'end_size'  => 3,
				'mid_size'  => 3,
			)
		);

		if ( is_array( $pages ) && ! empty( $pages ) ) {
			$pagination_html = '<div class="storefront-sorting"><nav class="woocommerce-pagination"><ul class="page-numbers">';
			foreach ( $pages as $page ) {
				$pagination_html .= '<li>' . $page . '</li>';
			}
			$pagination_html .= '</ul></nav></div>';
		}
	}

	// Build active chips
	$chips = array();
	if ( ! empty( $pack_size ) ) {
		$chips[] = array(
			'type'  => 'pack',
			'key'   => $pack_size,
			'label' => $pack_size . ' pack',
		);
	}

	$def_ranges = agro_aura_get_price_range_definitions();
	foreach ( $price_ranges as $rk ) {
		if ( isset( $def_ranges[ $rk ] ) ) {
			$chips[] = array(
				'type'  => 'price',
				'key'   => $rk,
				'label' => $def_ranges[ $rk ]['label'],
			);
		}
	}

	if ( ! empty( $category ) && 'all' !== $category ) {
		$term = get_term_by( 'slug', $category, 'product_cat' );
		if ( $term && ! is_wp_error( $term ) ) {
			$chips[] = array(
				'type'  => 'cat',
				'key'   => $category,
				'label' => $term->name,
			);
		}
	}

	// Updated URL
	$shop_permalink = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );
	if ( ! empty( $category ) && 'all' !== $category ) {
		$term = get_term_by( 'slug', $category, 'product_cat' );
		$page_url = ( $term && ! is_wp_error( $term ) ) ? get_term_link( $term ) : $shop_permalink;
	} else {
		$page_url = $shop_permalink;
	}

	if ( $paged > 1 ) {
		$page_url = add_query_arg( 'paged', $paged, $page_url );
	}

	// Dynamic updated counts
	$price_counts = agro_aura_get_price_range_counts( $category );
	$pack_counts  = agro_aura_get_available_pack_sizes_with_counts( $category );

	wp_send_json_success(
		array(
			'html'              => $html,
			'pagination_html'   => $pagination_html,
			'max_pages'         => $max_pages,
			'current_page'      => $paged,
			'found_posts'       => $total_found,
			'result_count_text' => $result_count_text,
			'chips'             => $chips,
			'price_counts'      => $price_counts,
			'pack_counts'       => $pack_counts,
			'url'               => $page_url,
		)
	);
}
add_action( 'wp_ajax_agro_filter_products', 'agro_aura_ajax_filter_products' );
add_action( 'wp_ajax_nopriv_agro_filter_products', 'agro_aura_ajax_filter_products' );

/**
 * Render Product Filters Shortcode
 *
 * @param array $atts Shortcode attributes.
 * @return string HTML markup for filters sidebar.
 */
function agro_aura_product_filters_shortcode( $atts ) {
	// Enqueue dedicated CSS & JS inside the shortcode
	wp_enqueue_style( 'agro-product-filters' );
	wp_enqueue_script( 'agro-product-filters-js' );

	$atts = shortcode_atts(
		array(
			'title'           => 'Refine Shelf',
			'show_categories' => 'true',
			'show_price'      => 'true',
			'show_pack_size'  => 'true',
			'show_promise'    => 'true',
			'class'           => '',
			'id'              => 'agro-shop-sidebar',
		),
		$atts,
		'agro_product_filters'
	);

	$shop_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );

	// Detect current category
	$current_term_id = function_exists( 'is_product_category' ) && is_product_category() ? get_queried_object_id() : 0;
	$current_cat_slug = 'all';
	if ( $current_term_id > 0 ) {
		$term = get_term( $current_term_id, 'product_cat' );
		if ( $term && ! is_wp_error( $term ) ) {
			$current_cat_slug = $term->slug;
		}
	}

	// Dynamic Price Counts
	$price_definitions = agro_aura_get_price_range_definitions();
	$price_counts      = agro_aura_get_price_range_counts( $current_cat_slug );

	// Dynamic Pack Counts
	$pack_counts = agro_aura_get_available_pack_sizes_with_counts( $current_cat_slug );

	ob_start();
	?>
	<div 
		class="agro-shop-sidebar<?php echo ! empty( $atts['class'] ) ? ' ' . esc_attr( $atts['class'] ) : ''; ?>" 
		id="<?php echo esc_attr( $atts['id'] ); ?>"
		data-ajax-url="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>"
		data-nonce="<?php echo esc_attr( wp_create_nonce( 'agro_filter_nonce' ) ); ?>"
		data-shop-url="<?php echo esc_url( $shop_url ); ?>"
		data-current-cat="<?php echo esc_attr( $current_cat_slug ); ?>"
	>
		
		<!-- Sidebar Header -->
		<div class="refine-shelf-header">
			<div class="refine-title">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<line x1="4" y1="21" x2="4" y2="14"></line>
					<line x1="4" y1="10" x2="4" y2="3"></line>
					<line x1="12" y1="21" x2="12" y2="12"></line>
					<line x1="12" y1="8" x2="12" y2="3"></line>
					<line x1="20" y1="21" x2="20" y2="16"></line>
					<line x1="20" y1="12" x2="20" y2="3"></line>
					<line x1="1" y1="14" x2="7" y2="14"></line>
					<line x1="9" y1="8" x2="15" y2="8"></line>
					<line x1="17" y1="16" x2="23" y2="16"></line>
				</svg>
				<span><?php echo esc_html( $atts['title'] ); ?></span>
			</div>
			<a href="<?php echo esc_url( $shop_url ); ?>" class="reset-all-btn"><?php esc_html_e( 'Reset All', 'storefront-child' ); ?></a>
		</div>

		<?php if ( 'false' !== strval( $atts['show_categories'] ) ) : ?>
			<!-- Filter Group 1: Categories -->
			<div class="filter-group">
				<div class="filter-heading"><?php esc_html_e( 'Categories', 'storefront-child' ); ?></div>
				<?php
				$total_count = wp_count_posts( 'product' )->publish;
				$terms       = get_terms(
					array(
						'taxonomy'   => 'product_cat',
						'hide_empty' => true,
						'orderby'    => 'name',
						'order'      => 'ASC',
					)
				);

				$is_all_active = ( 'all' === $current_cat_slug );
				?>
				<ul class="category-filter-list">
					<!-- All Groceries -->
					<li class="<?php echo $is_all_active ? 'is-active' : ''; ?>">
						<a href="<?php echo esc_url( $shop_url ); ?>" data-cat-slug="all">
							<span class="cat-name"><?php esc_html_e( 'All Groceries', 'storefront-child' ); ?></span>
							<span class="cat-count"><?php echo esc_html( $total_count > 0 ? $total_count : 0 ); ?></span>
						</a>
					</li>
					<?php
					if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
						foreach ( $terms as $cat ) {
							if ( 'uncategorized' === $cat->slug ) {
								continue;
							}
							$is_cat_active = ( $current_cat_slug === $cat->slug );
							$term_link     = get_term_link( $cat );
							?>
							<li class="<?php echo $is_cat_active ? 'is-active' : ''; ?>">
								<a href="<?php echo esc_url( is_wp_error( $term_link ) ? '#' : $term_link ); ?>" data-cat-slug="<?php echo esc_attr( $cat->slug ); ?>">
									<span class="cat-name"><?php echo esc_html( $cat->name ); ?></span>
									<span class="cat-count"><?php echo esc_html( $cat->count ); ?></span>
								</a>
							</li>
							<?php
						}
					}
					?>
				</ul>
			</div>
		<?php endif; ?>

		<?php if ( 'false' !== strval( $atts['show_price'] ) ) : ?>
			<!-- Filter Group 2: Price Range (Dynamic) -->
			<div class="filter-group">
				<div class="filter-heading"><?php esc_html_e( 'Price Range', 'storefront-child' ); ?></div>
				<ul class="filter-checkbox-list">
					<?php foreach ( $price_definitions as $r_key => $r_data ) : 
						$r_count = isset( $price_counts[ $r_key ] ) ? $price_counts[ $r_key ] : 0;
					?>
						<li>
							<label>
								<span class="checkbox-label-text">
									<input type="checkbox" name="price_range[]" value="<?php echo esc_attr( $r_key ); ?>" />
									<span><?php echo esc_html( $r_data['label'] ); ?></span>
								</span>
								<span class="item-count"><?php echo esc_html( $r_count ); ?></span>
							</label>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>

		<?php /*if ( 'false' !== strval( $atts['show_pack_size'] ) ) : ?>
			<!-- Filter Group 3: Pack Size (Dynamic) -->
			<div class="filter-group">
				<div class="filter-heading"><?php esc_html_e( 'Pack Size', 'storefront-child' ); ?></div>
				<div class="pack-size-filter-grid">
					<?php foreach ( $pack_counts as $pack_label => $p_count ) : ?>
						<button 
							type="button" 
							class="pack-pill-btn" 
							data-pack="<?php echo esc_attr( $pack_label ); ?>"
							title="<?php echo sprintf( esc_attr__( '%1$s products available', 'storefront-child' ), esc_attr( $p_count ) ); ?>"
						>
							<?php echo esc_html( $pack_label ); ?>
						</button>
					<?php endforeach; ?>
				</div>
			</div>
		<?php endif;*/ ?>

		<?php if ( 'false' !== strval( $atts['show_promise'] ) ) : ?>
			<!-- Filter Group 4: Agro Aura Promise Card -->
			<div class="agro-promise-card">
				<div class="promise-header">
					<svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
						<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
						<path d="m9 12 2 2 4-4"></path>
					</svg>
					<span><?php esc_html_e( 'Agro Aura Promise', 'storefront-child' ); ?></span>
				</div>
				<p><?php esc_html_e( 'Every grain is batch-checked for natural moisture, stone-cleaned, and double packed in food-grade breathe-easy sacks.', 'storefront-child' ); ?></p>
			</div>
		<?php endif; ?>

	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'agro_product_filters', 'agro_aura_product_filters_shortcode' );
add_shortcode( 'product_filters', 'agro_aura_product_filters_shortcode' );
