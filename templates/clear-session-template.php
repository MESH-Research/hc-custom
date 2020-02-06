<?php
/**
 * Template Name: Clear Session Template
 *
 * Description: Clear cookies and log out of WP and all local IDPs and SPs
 *
 * @since HCommons
 */

	//must set cookies before header
	setcookie( '_saml_idp', false, time()-3600, '/', '.' . getenv('WP_DOMAIN'), false, true );
	setcookie( 'stickyIdPSelection', false, time()-3600, '/', '.' . getenv('WP_DOMAIN'), true, true );
	setcookie( 'redirect_to', false, time()-3600, '/', '.' . getenv('WP_DOMAIN'), true, true );
	wp_clear_auth_cookie();

	$manager = WP_Session_Tokens::get_instance( get_current_user_id() );
	$manager->destroy_all();

	$shib_urls = [
		// IDPs
		getenv('GOOGLE_IDP_URL') . '/idp/profile/Logout',
		getenv('TWITTER_IDP_URL') . '/idp/profile/Logout',
		getenv('MLA_IDP_URL') . '/idp/profile/Logout',
		getenv('HC_IDP_URL') . '/idp/profile/Logout',
		// SPs
		getenv('REGISTRY_SP_URL') . '/Shibboleth.sso/Logout',
		get_site_url() . '/Shibboleth.sso/Logout',
	]; ?>

	<?php get_header(); ?>

	<?php foreach( $shib_urls as $shib_url ): ?>
		<iframe src="<?php echo $shib_url ?>" style="display:none" title="Log Out" ></iframe>
	<?php endforeach ?>

        <div class="page-full-width">

        <div id="primary" class="site-content">
                <div id="content" role="main">

                <?php while ( have_posts() ) : the_post(); ?>

	<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
		
		<div class="entry-content">
		<?php the_content(); ?>
		</div><!-- .entry-content -->

	</article><!-- #post -->

                <?php endwhile; // end of the loop. ?>

                </div><!-- #content -->
        </div><!-- #primary -->

</div><!-- .page-full-width -->
<?php get_footer(); ?>
