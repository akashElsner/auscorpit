<?php
/**
 * The template for displaying product content within loops
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/content-product.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 9.4.0
 */

defined( 'ABSPATH' ) || exit;

global $product;

// Check if the product is a valid WooCommerce product and ensure its visibility before proceeding.
if ( ! is_a( $product, WC_Product::class ) || ! $product->is_visible() ) {
	return;
}

$sku = $product->get_sku();
?>
<li <?php wc_product_class( 'auscorp-product-card', $product ); ?>>

	<div class="auscorp-product-card__image-wrap">
		<a href="<?php echo esc_url( get_permalink( $product->get_id() ) ); ?>" class="auscorp-product-card__image-link" tabindex="-1" aria-hidden="true">
			<?php
			if ( has_post_thumbnail( $product->get_id() ) ) {
				echo get_the_post_thumbnail(
					$product->get_id(),
					'woocommerce_thumbnail',
					[
						'class' => 'auscorp-product-card__image',
						'alt'   => esc_attr( $product->get_name() ),
					]
				);
			} elseif ( function_exists( 'wc_placeholder_img_src' ) ) {
				?>
				<img src="<?php echo esc_url( wc_placeholder_img_src() ); ?>" alt="" class="auscorp-product-card__image" />
				<?php
			}
			?>
		</a>
	</div>

	<div class="auscorp-product-card__body">
		<h3 class="auscorp-product-card__title">
			<a href="<?php echo esc_url( get_permalink( $product->get_id() ) ); ?>">
				<?php echo esc_html( $product->get_name() ); ?>
			</a>
		</h3>

		<?php if ( ! empty( $sku ) ) : ?>
			<div class="auscorp-product-card__meta">
				<span class="auscorp-product-card__sku"><?php echo esc_html( 'SKU: ' . $sku ); ?></span>
			</div>
		<?php endif; ?>

		<div class="auscorp-product-card__footer">
			<?php auscorp_render_shop_card_button( $product ); ?>
		</div>
	</div>
</li>
