<?php
/**
 * 
 * Display an electronic resource / database entry
 * 
 */

get_header(); ?>

	<div id="primary" class="content-area">
		<div id="content" class="site-content" role="main">
			<?php
				// Start the Loop.
				while ( have_posts() ) : the_post(); ?>
				<?php
				
				$remote_access = get_post_meta( get_the_ID(), 'database_remote_access', true );
				
				$title_link = get_permalink( get_the_ID() );
				$title_link = get_post_meta( get_the_ID(), 'database_url', true );
				
				?>
				<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
					<header class="entry-header">
						<?php the_post_thumbnail(); ?>
						<h1 class="entry-title">
							<a href="<?php echo esc_url( $title_link ) ?>" rel="bookmark"><?php the_title(); ?></a>
						</h1>
						<p><strong><?php printf( __( 'Remote access: %s','njsl-databases' ), ucwords( $remote_access ) ) ?></strong></p>
					</header><!-- .entry-header -->
			
					<?php if ( is_search() ) : // Only display Excerpts for Search ?>
					<div class="entry-summary">
						<?php the_excerpt(); ?>
					</div><!-- .entry-summary -->
					<?php else : ?>
					<div class="entry-content">
						<?php the_content(); ?>
					</div><!-- .entry-content -->
					<?php endif; ?>
			
				</article><!-- #post -->
				
				<?php endwhile; ?>
		</div><!-- #content -->
	</div><!-- #primary -->

<?php
get_footer();
