<?php
/**
 * The template for displaying product content within loops (Product Card)
 * Custom Agro Aura product card with interactive variation/pack selection.
 *
 * @package Storefront_Child
 */

defined( 'ABSPATH' ) || exit;

global $product;

// Check if the product is a valid WooCommerce product and ensure its visibility before proceeding.
if ( ! is_a( $product, WC_Product::class ) || ! $product->is_visible() ) {
	return;
}

// Terroir / USP Subtitle tag dynamically from product meta (ACF or custom post meta)
$usp_tag = '';
if ( function_exists( 'get_field' ) ) {
	$usp_tag = get_field( 'usp_tag', $product->get_id() );
	if ( empty( $usp_tag ) ) {
		$usp_tag = get_field( 'usp', $product->get_id() );
	}
}
if ( empty( $usp_tag ) ) {
	$usp_tag = get_post_meta( $product->get_id(), '_usp_tag', true );
}
if ( empty( $usp_tag ) ) {
	$usp_tag = get_post_meta( $product->get_id(), 'usp_tag', true );
}
if ( empty( $usp_tag ) ) {
	$usp_tag = get_post_meta( $product->get_id(), 'usp', true );
}
if ( empty( $usp_tag ) ) {
	$usp_tag = get_post_meta( $product->get_id(), '_usp', true );
}
$usp_tag = trim( (string) $usp_tag );

// Build pack size options & pricing dynamically
$pack_options = array();

// 1. If product is a Variable product with variations
if ( $product->is_type( 'variable' ) ) {
	$variations = $product->get_available_variations();
	foreach ( $variations as $idx => $var ) {
		$attr_label = '';
		foreach ( $var['attributes'] as $attr_name => $attr_val ) {
			if ( ! empty( $attr_val ) ) {
				$attr_label = $attr_val;
				$tax_key    = str_replace( 'attribute_', '', $attr_name );
				$term       = get_term_by( 'slug', $attr_val, $tax_key );
				if ( $term && ! is_wp_error( $term ) ) {
					$attr_label = $term->name;
				}
				break;
			}
		}
		if ( empty( $attr_label ) ) {
			$attr_label = 'Pack ' . ( $idx + 1 );
		}

		$v_price   = (float) $var['display_price'];
		$v_regular = (float) $var['display_regular_price'];
		if ( $v_regular <= 0 || $v_regular < $v_price ) {
			$v_regular = $v_price;
		}

		$v_has_discount = ( $v_regular > $v_price );
		$v_save         = $v_has_discount ? round( $v_regular - $v_price ) : 0;
		$v_discount     = $v_has_discount ? round( ( ( $v_regular - $v_price ) / $v_regular ) * 100 ) : 0;

		$pack_options[] = array(
			'label'        => $attr_label,
			'price'        => $v_price,
			'regular'      => $v_regular,
			'save'         => $v_save,
			'discount'     => $v_discount,
			'has_discount' => $v_has_discount,
			'variation_id' => $var['variation_id'],
		);
	}
}

// 2. If not variable (e.g. Simple Product) or empty variations, use actual WooCommerce product data
if ( empty( $pack_options ) ) {
	$base_price = (float) $product->get_price();
	$reg_price  = (float) $product->get_regular_price();
	if ( $reg_price <= 0 || $reg_price < $base_price ) {
		$reg_price = $base_price;
	}

	$has_discount = ( $reg_price > $base_price );
	$discount     = $has_discount ? round( ( ( $reg_price - $base_price ) / $reg_price ) * 100 ) : 0;
	$save         = $has_discount ? round( $reg_price - $base_price ) : 0;

	// Fetch dynamic volume, size, weight or pack attribute for simple products
	$size_info  = function_exists( 'agro_aura_get_product_display_size' ) ? agro_aura_get_product_display_size( $product ) : array( 'label' => '' );
	$pack_label = ! empty( $size_info['label'] ) ? $size_info['label'] : '';

	if ( empty( $pack_label ) && $product->has_weight() ) {
		$pack_label = $product->get_weight() . ' ' . get_option( 'woocommerce_weight_unit', 'kg' );
	}

	if ( empty( $pack_label ) ) {
		$taxonomies = array( 'pa_weight', 'pa_size', 'pa_pack-size', 'pa_pack', 'pa_volume', 'pa_bottle-size' );
		foreach ( $taxonomies as $tax ) {
			$terms = get_the_terms( $product->get_id(), $tax );
			if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
				$pack_label = $terms[0]->name;
				break;
			}
		}
	}

	if ( ! empty( $pack_label ) ) {
		$pack_options[] = array(
			'label'        => $pack_label,
			'price'        => $base_price,
			'regular'      => $reg_price,
			'save'         => $save,
			'discount'     => $discount,
			'has_discount' => $has_discount,
			'variation_id' => $product->get_id(),
		);
	} else {
		// Even without a pack pill, initialize pricing data directly
		$init_price    = $base_price;
		$init_regular  = $reg_price;
		$init_save     = $save;
		$init_discount = $discount;
		$init_has_disc = $has_discount;
		$init_var_id   = $product->get_id();
		$init_pack     = null;
	}
}

// Find initial selected pack option
$selected_idx = 0;
foreach ( $pack_options as $idx => $opt ) {
	if ( ! empty( $opt['default'] ) ) {
		$selected_idx = $idx;
		break;
	}
}

if ( ! empty( $pack_options ) ) {
	$init_pack     = $pack_options[ $selected_idx ];
	$init_price    = $init_pack['price'];
	$init_regular  = $init_pack['regular'];
	$init_save     = $init_pack['save'];
	$init_discount = $init_pack['discount'];
	$init_has_disc = ! empty( $init_pack['has_discount'] );
	$init_var_id   = isset( $init_pack['variation_id'] ) && $init_pack['variation_id'] > 0 ? $init_pack['variation_id'] : $product->get_id();
}

// Short description fallback
$short_desc = wp_strip_all_tags( $product->get_short_description() );
if ( empty( $short_desc ) ) {
	$short_desc = wp_strip_all_tags( $product->get_description() );
}

$product_permalink = get_permalink();

$product_cat_slugs = array();
$prod_terms = get_the_terms( $product->get_id(), 'product_cat' );
if ( ! empty( $prod_terms ) && ! is_wp_error( $prod_terms ) ) {
	foreach ( $prod_terms as $pt ) {
		$product_cat_slugs[] = $pt->slug;
	}
}
$cat_data_attr = implode( ' ', $product_cat_slugs );
?>

<li <?php wc_product_class( 'agro-product-card-item', $product ); ?> data-product-id="<?php echo esc_attr( $product->get_id() ); ?>" data-categories="<?php echo esc_attr( $cat_data_attr ); ?>">

	<!-- Card Image Container -->
	<div class="card-image-wrap">
		<!-- Discount Pill (Top-Left) -->
		<span class="badge-discount" <?php echo ( empty( $init_has_disc ) || $init_discount <= 0 ) ? 'style="display:none;"' : ''; ?>>
			<?php echo esc_html( $init_discount ); ?>% OFF
		</span>

		<!-- Thumbnail Link -->
		<a href="<?php echo esc_url( $product_permalink ); ?>">
			<?php
			if ( has_post_thumbnail() ) {
				the_post_thumbnail( 'woocommerce_thumbnail' );
			} else {
				echo wc_placeholder_img( 'woocommerce_thumbnail' );
			}
			?>
		</a>
	</div>

	<!-- Card Body -->
	<div class="card-content-wrap">
		<!-- Terroir / USP Subtitle Tag -->
		<?php if ( ! empty( $usp_tag ) ) : ?>
			<div class="card-usp-tag"><?php echo esc_html( $usp_tag ); ?></div>
		<?php endif; ?>

		<!-- Product Title -->
		<a href="<?php echo esc_url( $product_permalink ); ?>" class="card-title-link">
			<h2 class="card-title"><?php the_title(); ?></h2>
		</a>

		<!-- Short Description -->
		<?php if ( ! empty( $short_desc ) ) : ?>
			<div class="card-short-desc"><?php echo esc_html( $short_desc ); ?></div>
		<?php endif; ?>

		<!-- Interactive Pack Size Pills -->
		<?php if ( ! empty( $pack_options ) ) : ?>
			<div class="card-pack-pills">
				<?php foreach ( $pack_options as $idx => $opt ) : 
					$is_active = ( $idx === $selected_idx );
					$v_id = isset( $opt['variation_id'] ) && $opt['variation_id'] > 0 ? $opt['variation_id'] : $product->get_id();
				?>
					<button type="button" 
						class="pack-pill <?php echo $is_active ? 'is-selected' : ''; ?>"
						data-price="<?php echo esc_attr( $opt['price'] ); ?>"
						data-regular="<?php echo esc_attr( $opt['regular'] ); ?>"
						data-save="<?php echo esc_attr( $opt['save'] ); ?>"
						data-discount="<?php echo esc_attr( $opt['discount'] ); ?>"
						data-variation-id="<?php echo esc_attr( $v_id ); ?>"
						data-pack="<?php echo esc_attr( $opt['label'] ); ?>">
						<?php echo esc_html( $opt['label'] ); ?>
					</button>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<!-- Bottom Pricing & Action Row -->
		<div class="card-bottom-row">
			<div class="card-price-col">
				<div class="price-numbers">
					<span class="current-price">₹<?php echo esc_html( number_format( $init_price ) ); ?></span>
					<span class="regular-price" <?php echo ( empty( $init_has_disc ) || $init_regular <= $init_price ) ? 'style="display:none;"' : ''; ?>>
						₹<?php echo esc_html( number_format( $init_regular ) ); ?>
					</span>
				</div>
				<span class="savings-text" <?php echo ( empty( $init_has_disc ) || $init_save <= 0 ) ? 'style="display:none;"' : ''; ?>>
					Save ₹<?php echo esc_html( number_format( $init_save ) ); ?>
				</span>
			</div>

			<!-- "+ Add" Button -->
			<div class="card-add-btn-wrap">
				<?php 
				woocommerce_template_loop_add_to_cart( array(
					'class' => 'button add_to_cart_button ajax_add_to_cart',
					'attributes' => array(
						'data-product_id'  => esc_attr( $init_var_id ),
						'data-product_sku' => esc_attr( $product->get_sku() ),
						'data-pack'        => esc_attr( ! empty( $init_pack['label'] ) ? $init_pack['label'] : '' ),
						'aria-label'       => sprintf( __( 'Add &ldquo;%s&rdquo; to your cart', 'woocommerce' ), $product->get_name() ),
					),
				) ); 
				?>
			</div>
		</div>
	</div>

</li>
