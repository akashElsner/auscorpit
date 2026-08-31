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
	'posts_per_page' => $this->opt('per_page_small', 10),
	'offset' => 1,
);

$args1 = wp_parse_args($args1, $defaults);
$args2 = wp_parse_args($args2, $defaults);

$the_query_big = new WP_Query( $args1 );
$the_query_smalls = new WP_Query( $args2 );


// The Loop
if ( $the_query_big->have_posts() || $the_query_smalls->have_posts() ): ?>

	<div class="znBpl-mainRow">

		<?php if ( $the_query_big->have_posts() ): ?>
		<div class="znBpl-bPost-row clearfix">

			<?php
			while ( $the_query_big->have_posts() ):
				$the_query_big->the_post();

			?>

				<div class="znBpl-bPost znBpl-bPost--big text-<?php echo $this->opt('align','center'); ?>">

					<div class="znBpl-bPost-header">
						<div class="znBpl-bPost-headerInner">
							<?php the_title( sprintf( '<h2 class="znBpl-bPost-title"><a class="znBpl-bPost-titleLink" href="%s" rel="bookmark">', esc_url( get_permalink() ) ), '</a></h2>' ); ?>
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
							</div>
						</div>
					</div><!-- /.znBpl-bPost-header -->

					<?php

					// Create the featured image html
					if ( has_post_thumbnail() ) {
						echo '<div class="znBpl-bPost-imgWrapper"><a class="znBpl-bPost-imgLink" href="'. esc_url( get_permalink() ) .'">';
						echo the_post_thumbnail( 'full', 'class="znBpl-bPost-img" ' );
						echo '</a></div>';
					}

					?>

					<div class="znBpl-bPost-excerpt">
					<?php
						if( $this->opt('show_excerpt', 'yes') == 'yes' ){
							echo the_excerpt();
						}
					?>
					</div>

				</div><!-- /.znBpl-bPost -->

			<?php endwhile; ?>

		</div>
		<?php endif; ?>


		<?php if ( $the_query_smalls->have_posts() ): ?>

		<?php

			$isotope_options = array(
				'itemSelector' => '.znBpl-bPost',
				'animationOptions' => '',
			);

		?>

		<div class="znBpl-bPost-row clearfix">
			<div class="znBpl-bPost-smallRow row js-isotope <?php echo $this->opt('gutter_size','') ?>" data-kl-isotope='<?php echo json_encode($isotope_options); ?>'>
				<?php
				$i = 1;
				while ( $the_query_smalls->have_posts() ):
					$the_query_smalls->the_post();
				?>

				<div class="znBpl-bPost col-sm-6 col-md-<?php echo $this->opt('columns', 6); ?> text-<?php echo $this->opt('align','center'); ?>">

					<div class="znBpl-bPost--small">

						<div class="znBpl-bPost-header">
							<div class="znBpl-bPost-headerInner">
								<?php the_title( sprintf( '<h2 class="znBpl-bPost-title"><a class="znBpl-bPost-titleLink" href="%s" rel="bookmark">', esc_url( get_permalink() ) ), '</a></h2>' ); ?>
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
								</div>
							</div>
						</div><!-- /.znBpl-bPost-header -->

						<?php

						// Create the featured image html
						if ( has_post_thumbnail() ) {
							echo '<div class="znBpl-bPost-imgWrapper"><a class="znBpl-bPost-imgLink" href="'. esc_url( get_permalink() ) .'">';
							echo the_post_thumbnail( 'large', 'class="znBpl-bPost-img" ' );
							echo '</a></div>';
						}
						?>

						<div class="znBpl-bPost-excerpt">
						<?php
							if( $this->opt('show_excerpt', 'yes') == 'yes' ){
								echo the_excerpt();
							}
						?>
						</div>

					</div><!-- /.znBpl-bPost--small -->

				</div>

				<?php
					// if($i % 2 == 0) echo '<div class="clearfix"></div>';
				?>
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
