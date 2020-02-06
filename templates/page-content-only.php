<?php
/**
 * Template Name: Content Only
 *
 * Description: Use this page template for a page with no title.
 *
 * @package WordPress
 * @subpackage Boss
 * @since Boss 1.0.0
 */
get_header(); ?>

<div class="page-full-width">

	<div id="primary" class="site-content">
		<div id="content" role="main">

			<?php while ( have_posts() ) : the_post(); ?>
				<?php bp_get_template_part( 'content', 'only' ); ?>
			<?php endwhile; // end of the loop. ?>

		</div><!-- #content -->
	</div><!-- #primary -->

</div><!-- .page-full-width -->
<?php get_footer(); ?>
