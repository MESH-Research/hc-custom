<?php
/**
 * Template Name: HC Terms Acceptance
 *
 * Description: Use this page template for the HC Terms Acceptance needed for Legacy MLA Commons users.
 *
 * @package WordPress
 * @subpackage Boss
 * @since Boss 1.0.0
 */
get_header(); ?>

<div class="page-full-width">

	
	<div id="primary" class="site-content">
		<div id="content" role="main">
		<a href="#hc-terms-entry-form" class="button button-large">Accept Terms</a>

			<?php while ( have_posts() ) : the_post(); ?>
				<?php get_template_part( 'content', 'only' ); ?>
			<?php endwhile; // end of the loop. ?>

			<div id="hc-terms-entry-form">
			<form id="hc-terms-acceptance-form" class="standard-form" method="post" action="">
			        <?php wp_nonce_field( 'accept_hc_terms', 'accept_hc_terms_nonce' ); ?>
			        <div id="hc-terms-entry" class="entry">
			                <input type="checkbox" id="hc-accept-terms" name="hc_accept_terms" value="Yes" />
			                <span class="description"><strong>I agree</strong></span> &nbsp; &nbsp; &nbsp;
			                <input id="hc-accept-terms-continue" name="hc_accept_terms_continue" class="button-large" type="submit" value="Continue" /> &nbsp; &nbsp; &nbsp;
			                <a href="/Shibboleth.sso/Logout" id="hc-accept-terms-cancel" class="button button-large">Cancel</a>
			        </div>
			</form>
			</div>

		</div><!-- #content -->
	</div><!-- #primary -->

</div><!-- .page-full-width -->
<?php get_footer(); ?>
