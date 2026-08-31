<?php
/**
 * The template for displaying search results pages
 *
 * @package HelloElementorChild
 */

defined( 'ABSPATH' ) || exit;

get_header();

$template_id = 19536;
echo \Elementor\Plugin::$instance->frontend->get_builder_content( $template_id, true );

global $wp_query;
?>

<div class="auscorp-search-page">

	<?php if ( have_posts() ) : ?>

		<p class="auscorp-search-page__count">
			<?php
			printf(
				/* translators: 1: number of results, 2: search query */
				esc_html(
					_n(
						'%1$s result found for "%2$s"',
						'%1$s results found for "%2$s"',
						$wp_query->found_posts,
						'hello-elementor-child'
					)
				),
				esc_html( number_format_i18n( $wp_query->found_posts ) ),
				esc_html( get_search_query() )
			);
			?>
		</p>

		<div class="auscorp-search-page__results">
			<?php
			while ( have_posts() ) :
				the_post();
				$post_type_object = get_post_type_object( get_post_type() );
				?>
				<article <?php post_class( 'auscorp-search-card' ); ?>>

					<?php if ( has_post_thumbnail() ) : ?>
						<a href="<?php the_permalink(); ?>" class="auscorp-search-card__image-link" tabindex="-1" aria-hidden="true">
							<?php the_post_thumbnail( 'medium', array( 'class' => 'auscorp-search-card__image' ) ); ?>
						</a>
					<?php endif; ?>

					<div class="auscorp-search-card__body">
						<?php if ( $post_type_object ) : ?>
							<span class="auscorp-search-card__type"><?php echo esc_html( $post_type_object->labels->singular_name ); ?></span>
						<?php endif; ?>

						<h2 class="auscorp-search-card__title">
							<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
						</h2>

						<div class="auscorp-search-card__excerpt">
							<?php the_excerpt(); ?>
						</div>

						<a href="<?php the_permalink(); ?>" class="auscorp-search-card__link">
							<?php esc_html_e( 'View', 'hello-elementor-child' ); ?>
						</a>
					</div>
				</article>
				<?php
			endwhile;
			?>
		</div>

		<div class="auscorp-search-page__pagination">
			<?php
			the_posts_pagination(
				array(
					'prev_text' => '<i class="fa-solid fa-arrow-left" aria-hidden="true"></i>',
					'next_text' => '<i class="fa-solid fa-arrow-right" aria-hidden="true"></i>',
				)
			);
			?>
		</div>

	<?php else : ?>

		<div class="auscorp-search-page__empty">
			<i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
			<h2><?php esc_html_e( 'No results found', 'hello-elementor-child' ); ?></h2>
			<p>
				<?php
				printf(
					/* translators: %s: search query */
					esc_html__( 'We couldn\'t find anything matching "%s". Try a different search or browse our shop.', 'hello-elementor-child' ),
					esc_html( get_search_query() )
				);
				?>
			</p>

			<div class="auscorp-search-page__empty-form">
				<?php get_search_form(); ?>
			</div>

			<?php if ( function_exists( 'wc_get_page_permalink' ) ) : ?>
				<a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="auscorp-search-page__shop-link">
					<?php esc_html_e( 'Visit Shop', 'hello-elementor-child' ); ?>
				</a>
			<?php endif; ?>
		</div>

	<?php endif; ?>

</div>

<?php get_footer(); ?>
