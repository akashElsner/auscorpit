<?php if(! defined('ABSPATH')){ return; }

$defaults = array(
	'posts_per_page' => 5,
	'orderby' => 'date',
	'order'=> 'ID',
	'meta_key' => '_thumbnail_id'
);

$cat = $this->opt( 'blog_categories' );
if( ! empty( $cat ) ){
	$defaults['category__in'] = $cat;
}

$tags = $this->opt( 'blog_tags' );
if( ! empty( $tags ) ){
	$defaults['tag__in'] = $tags;
}

// Exclude the current viewed post from the query
if(is_single() && ('post' == get_post_type())){
	$defaults['post__not_in'] = array( get_the_ID() );
}

$args = array(
	'posts_per_page' => $this->opt('per_page_small', 4),
);

$args = wp_parse_args($args, $defaults);

$the_query = new WP_Query( $args );

// The Loop
if ( $the_query->have_posts() ): ?>

	<div class="znBpl-crsWrapper">

		<div class="znBpl-crsCol znBpl-crsCol--left">
			<div class="znBpl-leftInner">

				<?php

				$content_slick_attributes = array(
					"infinite" => true,
					"slidesToShow" => 1,
					"slidesToScroll" => 1,
					"autoplay" => $this->opt('autoplay', 'yes') == 'yes' ? true : false,
					"autoplaySpeed" => ( $this->opt('autoplaySpeed', 5) * 1000 ),
					"arrows" => $this->opt('arrows', 'yes') == 'yes' ? true : false,
					"appendArrows" => '.'.$uid.' .znBpl-carouselNav',
					"dots" => $this->opt('dots', 'yes') == 'yes' ? true : false,
					"appendDots" => '.'.$uid.' .znBpl-carouselDots',
					"asNavFor" => '.'.$uid.' .znBpl-imgCarousel',
					"adaptiveHeight" => true,
				);
				?>

				<div class="znBpl-contentCarousel js-slick" data-slick='<?php echo json_encode($content_slick_attributes) ?>'>
					<?php
						while ( $the_query->have_posts() ):
							$the_query->the_post();
						?>
							<div class="znBpl-bPost u-slick-show1stOnly">

								<?php
									the_title( sprintf( '<h2 class="znBpl-bPost-title"><a class="znBpl-bPost-titleLink" href="%s" rel="bookmark">', esc_url( get_permalink() ) ), '</a></h2>' );
								?>

								<?php
									if( $this->opt('show_excerpt', 'yes') == 'yes' ){
										echo '<div class="znBpl-bPost-excerpt">';
										echo the_excerpt();
										echo '</div>';
									}
								?>

								<div class="znBpl-bPost-info">
									<?php
										echo sprintf(
											'<span class="znBpl-bPost-infoDate">%s</span> %s <a href="%s" class="znBpl-bPost-infoAuthor">%s</a> %s <div class="znBpl-bPost-infoCats">%s</div>',
											get_the_date(),
											__(' by ', 'znpb-posts-layouts'),
											esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ),
											get_the_author(),
											__(' in ', 'znpb-posts-layouts'),
											get_the_category_list(',')
										);
									?>
								</div><!-- /.znBpl-bPost-info -->

							</div><!-- /.znBpl-bPost -->
						<?php
						endwhile;
					?>
				</div><!-- /.znBpl-contentCarousel -->

				<?php if($this->opt('dots', 'yes') == 'yes'): ?>
					<div class="znBpl-carouselDots"></div>
				<?php endif; ?>

			</div><!-- /.znBpl-leftInner -->
		</div><!-- /.znBpl-crsCol -->

		<div class="znBpl-crsCol znBpl-crsCol--right">
		<?php

			$img_slick_attributes = array(
				"infinite" => true,
				"slidesToShow" => 1,
				"slidesToScroll" => 1,
				"autoplay" => $this->opt('autoplay', 'yes') == 'yes' ? true : false,
				"autoplaySpeed" => ( $this->opt('autoplaySpeed', 5) * 1000 ),
				"fade" => true,
				"arrows" => false,
				"dots" => false,
			);
			?>

			<div class="znBpl-imgCarousel js-slick" data-slick='<?php echo json_encode($img_slick_attributes) ?>'>
				<?php
					while ( $the_query->have_posts() ):
						$the_query->the_post();
					?>
						<div class="znBpl-bPost znBpl-bPost--img u-slick-show1stOnly">

							<?php
							// Create the featured image html
							if ( has_post_thumbnail() ) {
								echo '<a class="znBpl-bPost-imgLink" href="'. esc_url( get_permalink() ) .'">';
								echo the_post_thumbnail( 'full', 'class="znBpl-bPost-img" ' );
								echo '</a>';
							}
							?>

						</div><!-- /.znBpl-bPost -->
					<?php
					endwhile;
				?>
			</div><!-- /.znBpl-imgCarousel -->
		</div><!-- /.znBpl-crsCol -->

	</div><!-- /.znBpl-crsWrapper -->

	<?php if($this->opt('arrows', 'yes') == 'yes'): ?>
		<div class="znBpl-carouselNav"></div>
	<?php endif; ?>

<?php wp_reset_postdata(); ?>

<?php else : ?>
	<p><?php _e( 'Sorry, no posts matched your criteria.' ); ?></p>
<?php endif; ?>
