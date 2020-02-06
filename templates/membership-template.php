<?php
/**
 * Template Name: Membership Template
 *
 * Description: Handle registration prerequisites for each organization.
 *
 * @since HCommons
 */

get_header(); ?>

<div class="page-full-width">

        <div id="primary" class="site-content">
                <div id="content" role="main">

                        <?php while ( have_posts() ) : the_post(); ?>

	<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
		
		<div class="entry-content">

	<?php the_content();
		echo '<p /><a href="' . Humanities_Commons::hcommons_register_url( '' ) . '" title="Register now">Register now</a>';
	 ?>
		</div><!-- .entry-content -->

	</article><!-- #post -->

                        <?php endwhile; // end of the loop. ?>

                </div><!-- #content -->
        </div><!-- #primary -->

</div><!-- .page-full-width -->
<?php get_footer(); ?>

