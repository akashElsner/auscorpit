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

$args1 = array(
	'posts_per_page' => 1,
);

$args2 = array(
	'posts_per_page' => 4,
	'offset' => 1,
);

$args1 = wp_parse_args($args1, $defaults);
$args2 = wp_parse_args($args2, $defaults);

$the_query_big = new WP_Query( $args1 );
$the_query_smalls = new WP_Query( $args2 );


// The Loop
if ( $the_query_big->have_posts() || $the_query_smalls->have_posts() ): ?>

	<div class="znBpl-mainRow fxb">

		<?php if ( $the_query_big->have_posts() ): ?>
		<div class="fxb-col">

			<?php
			while ( $the_query_big->have_posts() ):
				$the_query_big->the_post();

				$no_smalls = ! $the_query_smalls->have_posts() ? 'no-smalls' : '';
			?>

				<div class="znBpl-bPost znBpl-bPost--big <?php echo $no_smalls; ?>">

					<?php

					$sizes = array(800, 800);
					// Create the featured image html
					if ( has_post_thumbnail() ) {
						$thumb   = get_post_thumbnail_id();
						$f_image = wp_get_attachment_url( $thumb );
						$alt = get_post_meta($thumb, '_wp_attachment_image_alt', true);
						$title = get_the_title($thumb);
						if ( ! empty ( $f_image ) ) {
							$image = vt_resize( '', $f_image, $sizes[0], $sizes[1], true );
							echo '<div class="znBpl-bPost-imgWrapper">';
							echo '<img src="'. $image['url'] . '" width="' . $image['width'] . '" height="' . $image['height'] . '" alt="'.$alt.'" title="'.$title.'" class=" znBpl-bPost-img cover-fit-img" />';
							echo get_the_category_list();
							echo '</div>';
						}
					}

					?>

					<div class="znBpl-bPost-header">
						<div class="znBpl-bPost-headerInner">
							<?php the_title( sprintf( '<h2 class="znBpl-bPost-title"><a href="%s" rel="bookmark">', esc_url( get_permalink() ) ), '</a></h2>' ); ?>
							<div class="znBpl-bPost-info">
								<?php
									echo sprintf(
										'%s %s <a href="%s">%s</a>',
										get_the_date(),
										__(' BY ', 'znpb-posts-layouts'),
										esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ),
										get_the_author()
									);
								?>
							</div>
						</div>
					</div><!-- /.znBpl-bPost-header -->

				</div>

			<?php endwhile; ?>

		</div>
		<?php endif; ?>

		<?php if ( $the_query_smalls->have_posts() ): ?>
		<div class="fxb-col">
			<div class="row znBpl-bPost-smallRow">
				<?php
				$i = 1;
				while ( $the_query_smalls->have_posts() ):
					$the_query_smalls->the_post();
				?>
				<div class="col-sm-6">

					<div class="znBpl-bPost znBpl-bPost--small">

						<?php

						// Create the featured image html
						if ( has_post_thumbnail() ) {

							$thumb   = get_post_thumbnail_id();
							$f_image = wp_get_attachment_url( $thumb );
							$alt = get_post_meta($thumb, '_wp_attachment_image_alt', true);

							if ( ! empty ( $f_image ) ) {
								$sizes = array(480, 280);
								$image = vt_resize( '', $f_image, $sizes[0], $sizes[1], true );
								$img = '<img src="'. $image['url'] . '" width="' . $image['width'] . '" height="' . $image['height'] . '" alt="'.$alt.'" title="'.get_the_title($thumb).'" class=" znBpl-bPost-img" />';
							}

							echo '<div class="znBpl-bPost-imgWrapper">';
							echo '<a href="' . esc_url( get_permalink() ) . '" class="znBpl-bPost-img">'.$img.'</a>';
							echo get_the_category_list();
							echo '</div>';
						}

						?>

						<div class="znBpl-bPost-header">
							<div class="znBpl-bPost-headerInner">
								<?php the_title( sprintf( '<h2 class="znBpl-bPost-title"><a href="%s" rel="bookmark">', esc_url( get_permalink() ) ), '</a></h2>' ); ?>
								<div class="znBpl-bPost-info">
									<?php
										echo sprintf(
											'%s %s <a href="%s">%s</a>',
											get_the_date(),
											__(' by ', 'znpb-posts-layouts'),
											esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ),
											get_the_author()
										);
									?>
								</div>
							</div>
						</div><!-- /.znBpl-bPost-header -->

					</div>

				</div>
				<?php if($i % 2 == 0) echo '<div class="clearfix"></div>'; ?>
				<?php $i++; ?>
				<?php endwhile; ?>

			</div>
		</div>
		<?php endif; ?>

	</div>

<?php wp_reset_postdata(); ?>

<?php else : ?>
	<p><?php _e( 'Sorry, no posts matched your criteria.' ); ?></p>
<?php endif; ?>
