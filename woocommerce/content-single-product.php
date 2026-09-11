<?php
/**
 * Custom Single Product Template for Agro Aura
 *
 * @package Storefront_Child
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( empty( $product ) ) {
	return;
}

/**
 * Hook: woocommerce_before_single_product.
 */
do_action( 'woocommerce_before_single_product' );

if ( post_password_required() ) {
	echo get_the_password_form(); // WPCS: XSS ok.
	return;
}

// Calculate discount & pricing
$post_thumbnail_id = $product->get_image_id();
$main_img_url      = $post_thumbnail_id ? wp_get_attachment_image_url( $post_thumbnail_id, 'large' ) : wc_placeholder_img_src( 'woocommerce_single' );
$attachment_ids    = $product->get_gallery_image_ids();

// Calculate dynamic pack options
$pack_options    = array();
$attribute_label = 'Pack Size';

if ( $product->is_type( 'variable' ) ) {
	$variations = $product->get_available_variations();

	// Check attribute label
	$variation_attributes = $product->get_variation_attributes();
	if ( ! empty( $variation_attributes ) ) {
		$first_attr_key = key( $variation_attributes );
		$tax            = get_taxonomy( $first_attr_key );
		if ( $tax && isset( $tax->labels->singular_name ) ) {
			$attribute_label = $tax->labels->singular_name;
		} else {
			$attribute_label = wc_attribute_label( $first_attr_key, $product );
		}
	}

	$total_variations = count( $variations );

	foreach ( $variations as $idx => $var ) {
		$attr_name_key = '';
		$attr_val      = '';

		foreach ( $var['attributes'] as $k => $v ) {
			$attr_name_key = $k;
			$attr_val      = $v;
			break;
		}

		if ( empty( $attr_val ) ) {
			$attr_val = 'Pack ' . ( $idx + 1 );
		}

		$v_price   = (float) $var['display_price'];
		$v_regular = (float) $var['display_regular_price'];
		if ( $v_regular <= 0 || $v_regular < $v_price ) {
			$v_regular = $v_price;
		}

		$v_has_discount = ( $v_regular > $v_price );
		$v_discount     = $v_has_discount ? round( ( ( $v_regular - $v_price ) / $v_regular ) * 100 ) : 0;
		$v_save         = $v_has_discount ? round( $v_regular - $v_price ) : 0;

		// Parse unit price (e.g. "1 L", "2 L", "5 L", "500 g", "1 kg", "5 kg", "10 kg", "500 ml")
		$unit_price_str = '';
		$weight_trim    = trim( $attr_val );
		if ( preg_match( '/^([\d\.]+)\s*([a-zA-Z]+)?/i', $weight_trim, $matches ) ) {
			$qty_val  = (float) $matches[1];
			$unit_val = isset( $matches[2] ) ? strtolower( trim( $matches[2] ) ) : '';

			if ( $qty_val > 0 ) {
				if ( 'g' === $unit_val ) {
					$kg             = $qty_val / 1000;
					$unit_price_str = '₹' . number_format( round( $v_price / $kg ) ) . ' / kg';
				} elseif ( 'kg' === $unit_val ) {
					$unit_price_str = '₹' . number_format( round( $v_price / $qty_val ) ) . ' / kg';
				} elseif ( 'ml' === $unit_val ) {
					$l              = $qty_val / 1000;
					$unit_price_str = '₹' . number_format( round( $v_price / $l ) ) . ' / L';
				} elseif ( 'l' === $unit_val || 'litre' === $unit_val || 'liter' === $unit_val ) {
					$unit_price_str = '₹' . number_format( round( $v_price / $qty_val ) ) . ' / L';
				} else {
					$unit_price_str = '₹' . number_format( round( $v_price / $qty_val ) ) . ( ! empty( $unit_val ) ? ' / ' . $unit_val : ' / unit' );
				}
			}
		}

		if ( empty( $unit_price_str ) ) {
			$unit_price_str = '₹' . number_format( round( $v_price ) ) . ' / pack';
		}

		// Intelligent Badges
		$badge_text = '';
		$badge_type = '';
		if ( $total_variations >= 3 ) {
			if ( 1 === $idx ) {
				$badge_text = 'BEST VALUE';
				$badge_type = 'badge-value';
			} elseif ( 2 === $idx ) {
				$badge_text = 'BULK SAVINGS';
				$badge_type = 'badge-savings';
			}
		} elseif ( 2 === $total_variations && 1 === $idx ) {
			$badge_text = 'BEST VALUE';
			$badge_type = 'badge-value';
		}

		$pack_options[] = array(
			'variation_id' => $var['variation_id'],
			'label'        => $attr_val,
			'attr_name'    => $attr_name_key,
			'attr_val'     => $attr_val,
			'price'        => $v_price,
			'regular'      => $v_regular,
			'has_discount' => $v_has_discount,
			'discount'     => $v_discount,
			'save'         => $v_save,
			'unit_price'   => $unit_price_str,
			'badge_text'   => $badge_text,
			'badge_type'   => $badge_type,
			'is_in_stock'  => $var['is_in_stock'],
		);
	}
}

// Fallback for simple products
if ( empty( $pack_options ) ) {
	$base_price = (float) $product->get_price();
	if ( $base_price <= 0 ) {
		$base_price = 145;
	}
	$reg_price = (float) $product->get_regular_price();
	if ( $reg_price <= 0 || $reg_price < $base_price ) {
		$reg_price = $base_price;
	}
	$has_disc  = ( $reg_price > $base_price );
	$disc_perc = $has_disc ? round( ( ( $reg_price - $base_price ) / $reg_price ) * 100 ) : 0;

	// Dynamic attribute & size resolution
	$size_info = function_exists( 'agro_aura_get_product_display_size' ) ? agro_aura_get_product_display_size( $product ) : array( 'label' => '', 'attribute_name' => 'Pack Size', 'unit_price' => '' );

	if ( ! empty( $size_info['attribute_name'] ) ) {
		$attribute_label = $size_info['attribute_name'];
	}

	$weight_label   = ! empty( $size_info['label'] ) ? $size_info['label'] : ( $product->has_weight() ? ( $product->get_weight() . ' ' . get_option( 'woocommerce_weight_unit', 'kg' ) ) : 'Standard Pack' );
	$unit_price_str = ! empty( $size_info['unit_price'] ) ? $size_info['unit_price'] : ( '₹' . number_format( round( $base_price ) ) . ' / pack' );

	$pack_options[] = array(
		'variation_id' => 0,
		'label'        => $weight_label,
		'attr_name'    => '',
		'attr_val'     => '',
		'price'        => $base_price,
		'regular'      => $reg_price,
		'has_discount' => $has_disc,
		'discount'     => $disc_perc,
		'save'         => $has_disc ? ( $reg_price - $base_price ) : 0,
		'unit_price'   => $unit_price_str,
		'badge_text'   => '',
		'badge_type'   => '',
		'is_in_stock'  => $product->is_in_stock(),
	);
}

// Default selected pack (first item)
$default_pack  = $pack_options[0];
$default_price = $default_pack['price'];

// Category & SKU
$terms         = get_the_terms( $product->get_id(), 'product_cat' );
$category_name = ( ! empty( $terms ) && ! is_wp_error( $terms ) ) ? $terms[0]->name : 'RESERVE';
$sku           = $product->get_sku();
if ( empty( $sku ) ) {
	$sku = 'AA-' . strtoupper( substr( sanitize_title( $product->get_name() ), 0, 3 ) ) . '-' . $product->get_id();
}
?>

<div id="product-<?php the_ID(); ?>" <?php wc_product_class( 'agro-single-product-wrap', $product ); ?>>

	<!-- Left Column: Standard WooCommerce Product Gallery (with Slider & Zoom) -->
	<div class="agro-gallery-column">
		<?php if ( $default_pack['has_discount'] && $default_pack['discount'] > 0 ) : ?>
			<span class="agro-discount-badge onsale" id="agro-product-offer-badge"><?php echo esc_html( $default_pack['discount'] ); ?>% OFF</span>
		<?php elseif ( $product->is_on_sale() && $product->get_regular_price() > $product->get_price() ) : 
			$overall_disc = round( ( ( $product->get_regular_price() - $product->get_price() ) / $product->get_regular_price() ) * 100 );
		?>
			<span class="agro-discount-badge onsale" id="agro-product-offer-badge"><?php echo esc_html( $overall_disc ); ?>% OFF</span>
		<?php endif; ?>

		<?php
		/**
		 * Hook: woocommerce_before_single_product_summary.
		 *
		 * @hooked woocommerce_show_product_images - 20
		 */
		do_action( 'woocommerce_before_single_product_summary' );
		?>
	</div>

	<!-- Right Column: Product Summary & Selection -->
	<div class="summary entry-summary agro-product-summary">

		<!-- Collection & SKU Badges -->
		<div class="agro-product-meta-tags">
			<span class="agro-badge badge-collection">AGRO AURA <?php echo esc_html( strtoupper( $category_name ) ); ?> COLLECTION</span>
			<span class="agro-badge badge-sku">SKU: <?php echo esc_html( $sku ); ?></span>
		</div>

		<!-- Title -->
		<h1 class="product_title entry-title"><?php the_title(); ?></h1>

		<!-- Short Description / Grain Subtitle -->
		<div class="woocommerce-product-details__short-description">
			<p>
				<?php 
				$excerpt = get_the_excerpt();
				if ( ! empty( $excerpt ) ) {
					echo wp_kses_post( $excerpt );
				} else {
					echo esc_html__( 'Aged for 24 months in Himalayan foothills. Extra-long grains with unmistakable royal aroma.', 'storefront' );
				}
				?>
			</p>
		</div>

		<!-- Pack Size Selector -->
		<div class="agro-pack-size-section">
			<div class="pack-header">
				<span class="pack-label">Select <?php echo esc_html( ! empty( $attribute_label ) ? $attribute_label : 'Pack Size' ); ?></span>
				<!-- <a href="#weight-guide" class="weight-guide-link">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<path d="M16 16l3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1z"></path>
						<path d="M2 16l3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1z"></path>
						<path d="M7 21h10"></path>
						<path d="M12 3v18"></path>
						<path d="M3 7h18"></path>
					</svg>
					Weight Guide
				</a> -->
			</div>

			<div class="pack-cards-grid">
				<?php foreach ( $pack_options as $p_idx => $pack ) : 
					$is_sel = ( 0 === $p_idx );
				?>
					<div class="pack-card <?php echo $is_sel ? 'is-selected' : ''; ?>"
						 data-variation-id="<?php echo esc_attr( $pack['variation_id'] ); ?>"
						 data-weight="<?php echo esc_attr( $pack['label'] ); ?>"
						 data-price="<?php echo esc_attr( $pack['price'] ); ?>"
						 data-original="<?php echo esc_attr( $pack['regular'] ); ?>"
						 data-discount="<?php echo esc_attr( $pack['discount'] ); ?>"
						 data-unit="<?php echo esc_attr( $pack['unit_price'] ); ?>"
						 data-attr-name="<?php echo esc_attr( $pack['attr_name'] ); ?>"
						 data-attr-val="<?php echo esc_attr( $pack['attr_val'] ); ?>">
						<?php if ( ! empty( $pack['badge_text'] ) ) : ?>
							<span class="agro-badge <?php echo esc_attr( $pack['badge_type'] ); ?> pack-card-badge"><?php echo esc_html( $pack['badge_text'] ); ?></span>
						<?php endif; ?>
						<div class="pack-weight"><?php echo esc_html( $pack['label'] ); ?></div>
						<div class="pack-prices">
							<span class="current">₹<?php echo esc_html( number_format( $pack['price'] ) ); ?></span>
							<?php if ( $pack['has_discount'] && $pack['regular'] > $pack['price'] ) : ?>
								<span class="original">₹<?php echo esc_html( number_format( $pack['regular'] ) ); ?></span>
							<?php endif; ?>
						</div>
						<div class="pack-unit-price"><?php echo esc_html( $pack['unit_price'] ); ?></div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>

		<!-- Action Bar Form (Quantity + Add to Cart + 1-Click Buy) -->
		<form class="cart" method="post" enctype="multipart/form-data">
			<div class="agro-action-row">
				<!-- Quantity Stepper -->
				<div class="agro-quantity-stepper">
					<button type="button" class="qty-btn qty-minus" aria-label="Decrease quantity">−</button>
					<input type="number" id="agro-product-qty" class="qty-input" name="quantity" value="1" min="1" step="1" inputmode="numeric" />
					<button type="button" class="qty-btn qty-plus" aria-label="Increase quantity">+</button>
				</div>

				<!-- Primary Add to Cart -->
				<button type="submit" name="add-to-cart" value="<?php echo esc_attr( $product->get_id() ); ?>" class="single_add_to_cart_button btn-agro-primary" id="agro-add-to-cart-btn">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
						<line x1="3" y1="6" x2="21" y2="6"></line>
						<path d="M16 10a4 4 0 0 1-8 0"></path>
					</svg>
					<span>Add to Cart • <span id="agro-btn-price-display">₹<?php echo esc_html( number_format( $default_price ) ); ?></span></span>
				</button>

				<!-- 1-Click Buy -->
				<button type="button" class="btn-agro-buy-now" id="agro-buy-now-btn">
					<svg width="17" height="17" viewBox="0 0 24 24" fill="currentColor">
						<path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"></path>
					</svg>
					<span>1-Click Buy</span>
				</button>

				<!-- Hidden state inputs for WooCommerce and Buy Now -->
				<input type="hidden" name="agro_buy_now" id="agro-buy-now-flag" value="0" />
				<input type="hidden" name="variation_id" class="variation_id" id="agro-variation-id" value="<?php echo esc_attr( $default_pack['variation_id'] ); ?>" />
				<?php if ( ! empty( $default_pack['attr_name'] ) ) : ?>
					<input type="hidden" name="<?php echo esc_attr( $default_pack['attr_name'] ); ?>" id="agro-attr-input" value="<?php echo esc_attr( $default_pack['attr_val'] ); ?>" />
				<?php endif; ?>
				<input type="hidden" name="agro_pack_size" id="agro-pack-size-input" value="<?php echo esc_attr( $default_pack['label'] ); ?>" />
			</div>
		</form>

		<!-- Value Proposition Highlight Cards -->
		<div class="agro-feature-highlights">
			<!-- Feature 1 -->
			<div class="feature-card">
				<div class="feature-icon icon-aged">
					<svg viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<circle cx="12" cy="12" r="10"></circle>
						<polyline points="12 6 12 12 16 14"></polyline>
					</svg>
				</div>
				<div class="feature-text">
					<span class="feature-title">24M Aged</span>
					<span class="feature-subtitle">Himalayan foothills</span>
				</div>
			</div>

			<!-- Feature 2 -->
			<div class="feature-card">
				<div class="feature-icon icon-polish">
					<svg viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<path d="M12 2L15 9H22L16.5 13.5L18.5 21L12 16.5L5.5 21L7.5 13.5L2 9H9L12 2Z"></path>
					</svg>
				</div>
				<div class="feature-text">
					<span class="feature-title">Zero Polish</span>
					<span class="feature-subtitle">100% whole grain</span>
				</div>
			</div>

			<!-- Feature 3 -->
			<div class="feature-card">
				<div class="feature-icon icon-aroma">
					<svg viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 3z"></path>
					</svg>
				</div>
				<div class="feature-text">
					<span class="feature-title">Shahi Aroma</span>
					<span class="feature-subtitle">Authentic fragrance</span>
				</div>
			</div>
		</div>

	</div><!-- .summary -->

	<?php
	/**
	 * Hook: woocommerce_after_single_product_summary.
	 *
	 * @hooked woocommerce_output_product_data_tabs - 10
	 * @hooked woocommerce_upsell_display - 15
	 * @hooked woocommerce_output_related_products - 20
	 */
	do_action( 'woocommerce_after_single_product_summary' );
	?>
</div><!-- #product-<?php the_ID(); ?> -->

<?php do_action( 'woocommerce_after_single_product' ); ?>
