<?php
/**
 * Product Categories Shortcode
 *
 * Provides shortcode [agro_product_categories] or [agro_categories]
 * to dynamically display WooCommerce product categories with uploaded images.
 *
 * @package Storefront_Child
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register Product Categories Shortcode Assets
 */
function agro_aura_register_product_categories_assets() {
	$css_file = get_stylesheet_directory() . '/assets/css/product-categories.css';
	$version  = file_exists( $css_file ) ? filemtime( $css_file ) : wp_get_theme()->get( 'Version' );

	wp_register_style(
		'agro-product-categories',
		get_stylesheet_directory_uri() . '/assets/css/product-categories.css',
		array(),
		$version
	);
}
add_action( 'wp_enqueue_scripts', 'agro_aura_register_product_categories_assets' );

/**
 * Render Product Categories Shortcode
 *
 * @param array $atts Shortcode attributes.
 * @return string HTML markup.
 */
function agro_aura_product_categories_shortcode( $atts ) {
	// Enqueue stylesheet only when this shortcode is used
	wp_enqueue_style( 'agro-product-categories' );

	$atts = shortcode_atts(
		array(
			'number'     => 8,           // Default number of categories (use 0 to show all)
			'columns'    => 'auto',       // 'auto' (dynamic 1-8 cols) or numeric e.g. 4, 6, 7, 8
			'hide_empty' => 1,            // 1 = hide empty categories (0 products), 0 = show all
			'orderby'    => 'menu_order', // 'menu_order', 'name', 'count', 'id'
			'order'      => 'ASC',
			'parent'     => 0,            // 0 = top-level categories only, '' = all categories
			'include'    => '',           // Comma-separated category IDs
			'exclude'    => '',           // Comma-separated category IDs
			'title'      => '',           // Optional section heading
			'sub_title'  => '',           // Optional section subtitle
			'class'      => '',           // Additional CSS classes
		),
		$atts,
		'agro_product_categories'
	);

	$categories_data = array();
	$exclude_ids     = ! empty( $atts['exclude'] ) ? wp_parse_id_list( $atts['exclude'] ) : array();

	// Exclude default Uncategorized category
	$uncat = get_term_by( 'slug', 'uncategorized', 'product_cat' );
	if ( $uncat && ! is_wp_error( $uncat ) ) {
		$exclude_ids[] = $uncat->term_id;
	}

	$term_args = array(
		'taxonomy'   => 'product_cat',
		'hide_empty' => (bool) $atts['hide_empty'],
		'orderby'    => sanitize_text_field( $atts['orderby'] ),
		'order'      => sanitize_text_field( $atts['order'] ),
		'exclude'    => $exclude_ids,
	);

	if ( intval( $atts['number'] ) > 0 ) {
		$term_args['number'] = intval( $atts['number'] );
	}

	if ( '' !== $atts['parent'] ) {
		$term_args['parent'] = intval( $atts['parent'] );
	}

	if ( ! empty( $atts['include'] ) ) {
		$term_args['include'] = wp_parse_id_list( $atts['include'] );
	}

	$terms = get_terms( $term_args );

	if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
		foreach ( $terms as $term ) {
			// Exclude default Uncategorized category if empty or default slug
			if ( 'uncategorized' === $term->slug ) {
				continue;
			}

			// If hide_empty is active and category has 0 products, skip
			if ( (bool) $atts['hide_empty'] && intval( $term->count ) < 1 ) {
				continue;
			}

			// Fetch category image uploaded by user in WP Admin
			$image_url = '';

			// Method 1: WooCommerce standard thumbnail meta
			$thumb_id = get_term_meta( $term->term_id, 'thumbnail_id', true );
			if ( $thumb_id ) {
				$image_url = wp_get_attachment_image_url( $thumb_id, 'woocommerce_thumbnail' );
				if ( ! $image_url ) {
					$image_url = wp_get_attachment_image_url( $thumb_id, 'medium' );
				}
				if ( ! $image_url ) {
					$image_url = wp_get_attachment_url( $thumb_id );
				}
			}

			// Method 2: WooCommerce helper function
			if ( empty( $image_url ) && function_exists( 'woocommerce_get_product_category_thumbnail_url' ) ) {
				$wc_thumb = woocommerce_get_product_category_thumbnail_url( $term->term_id );
				if ( ! empty( $wc_thumb ) ) {
					$image_url = $wc_thumb;
				}
			}

			// Method 3: First product image fallback if category thumbnail is not set
			if ( empty( $image_url ) && function_exists( 'wc_get_products' ) ) {
				$cat_products = wc_get_products(
					array(
						'category' => array( $term->slug ),
						'limit'    => 1,
					)
				);
				if ( ! empty( $cat_products ) && $cat_products[0]->get_image_id() ) {
					$image_url = wp_get_attachment_image_url( $cat_products[0]->get_image_id(), 'medium' );
				}
			}

			// Method 4: Theme local category fallback image matching slug
			if ( empty( $image_url ) ) {
				$local_image = get_stylesheet_directory() . '/assets/images/categories/' . $term->slug . '.jpg';
				if ( file_exists( $local_image ) ) {
					$image_url = get_stylesheet_directory_uri() . '/assets/images/categories/' . $term->slug . '.jpg';
				}
			}

			// Method 5: Default WooCommerce placeholder
			if ( empty( $image_url ) && function_exists( 'wc_placeholder_img_src' ) ) {
				$image_url = wc_placeholder_img_src( 'woocommerce_thumbnail' );
			}

			$term_link = get_term_link( $term, 'product_cat' );
			if ( is_wp_error( $term_link ) ) {
				$term_link = '#';
			}

			$item_count = intval( $term->count );
			$count_text = sprintf(
				_n( '%s item', '%s items', $item_count, 'storefront-child' ),
				number_format_i18n( $item_count )
			);

			$categories_data[] = array(
				'id'         => $term->term_id,
				'name'       => $term->name,
				'slug'       => $term->slug,
				'url'        => $term_link,
				'image'      => $image_url,
				'count_text' => $count_text,
			);
		}
	}

	if ( empty( $categories_data ) ) {
		return '';
	}

	// Columns calculation
	$total_items = count( $categories_data );
	if ( 'auto' === $atts['columns'] || empty( $atts['columns'] ) ) {
		$columns_count = min( $total_items, 8 );
	} else {
		$columns_count = intval( $atts['columns'] ) > 0 ? intval( $atts['columns'] ) : 8;
	}

	$custom_class = ! empty( $atts['class'] ) ? ' ' . esc_attr( $atts['class'] ) : '';

	ob_start();
	?>
	<section class="agro-categories-section<?php echo esc_attr( $custom_class ); ?>" aria-label="<?php esc_attr_e( 'Product Categories', 'storefront-child' ); ?>">
		

			<?php if ( ! empty( $atts['title'] ) ) : ?>
				<div class="agro-categories-heading">
					<h2 class="agro-categories-title"><?php echo esc_html( $atts['title'] ); ?></h2>
					<?php if ( ! empty( $atts['sub_title'] ) ) : ?>
						<p class="agro-categories-subtitle"><?php echo esc_html( $atts['sub_title'] ); ?></p>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<div class="agro-categories-track">
				<?php foreach ( $categories_data as $cat ) : ?>
					<a href="<?php echo esc_url( $cat['url'] ); ?>" class="agro-category-card" title="<?php echo esc_attr( $cat['name'] ); ?>">
						<div class="agro-category-thumb-wrap">
							<img 
								src="<?php echo esc_url( $cat['image'] ); ?>" 
								alt="<?php echo esc_attr( $cat['name'] ); ?>" 
								class="agro-category-img" 
								loading="lazy"
								width="100" 
								height="100"
							/>
						</div>
						<h3 class="agro-category-name"><?php echo esc_html( $cat['name'] ); ?></h3>
						<span class="agro-category-count"><?php echo esc_html( $cat['count_text'] ); ?></span>
					</a>
				<?php endforeach; ?>
			</div>

		
	</section>
	<?php
	return ob_get_clean();
}
add_shortcode( 'agro_product_categories', 'agro_aura_product_categories_shortcode' );
add_shortcode( 'agro_categories', 'agro_aura_product_categories_shortcode' );

/**
 * Preview endpoint for testing the categories shortcode: /?preview_categories_shortcode=1
 */
function agro_aura_preview_categories_shortcode() {
	if ( isset( $_GET['preview_categories_shortcode'] ) ) {
		$demo   = ( isset( $_GET['demo'] ) && '1' === strval( $_GET['demo'] ) ) ? 'true' : 'false';
		$number = isset( $_GET['number'] ) ? intval( $_GET['number'] ) : 8;
		get_header();
		echo '<main style="padding: 40px 0 60px; background-color: #fcfcfc;">';
		echo do_shortcode( '[agro_product_categories demo="' . esc_attr( $demo ) . '" number="' . esc_attr( $number ) . '"]' );
		echo '</main>';
		get_footer();
		exit;
	}
}
add_action( 'template_redirect', 'agro_aura_preview_categories_shortcode' );

