<?php
/**
 * Archive page for Electronic resource / database listing
 *
 */

get_header(); ?>

	<section id="primary" class="content-area">
		<div id="content" class="site-content" role="main">

			<?php if ( have_posts() ) : ?>
				<header class="page-header">
					<h1 class="page-title">
						<?php _e( 'Databases', 'njsl-databases' ); ?>
					</h1>
				</header><!-- .page-header -->
	
				<?php while ( have_posts() ) : the_post(); ?>
					<?php get_template_part( 'content', get_post_type() ); ?>
				<?php endwhile; ?>

			<?php else : // If no posts were found for the query ?>
				<?php get_template_part( 'content', 'none' ); ?>
			<?php endif; ?>
				
		</div><!-- #content -->
	</section><!-- #primary -->

<?php
get_footer();
			