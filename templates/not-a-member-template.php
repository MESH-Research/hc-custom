<?php
/**
 * Template Name: Not A Member Template
 *
 * Description: Handle logins without necessary privs.
 *
 * @since HCommons
 */
	$memberships = Humanities_Commons::hcommons_get_user_memberships();
	if ( ! empty( $memberships ) ) {
		global $comanage_api;
		$comanage_roles = $comanage_api->get_person_roles( Humanities_Commons::hcommons_get_session_username(), Humanities_Commons::$society_id );
		$inactive_role = false;
		foreach( $comanage_roles as $comanage_key => $comanage_role ) {
			if ( $comanage_key == strtoupper( Humanities_Commons::$society_id ) && 'Active' != $comanage_role['status'] ) {
				$inactive_role = true;
			}
		}
		if ( $inactive_role ) {
			wp_redirect( '/inactive-member/' );
		}
	}
//must redirect first

get_header(); ?>

<div class="page-full-width">

        <div id="primary" class="site-content">
                <div id="content" role="main">

                        <?php while ( have_posts() ) : the_post(); ?>

	<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
		
		<div class="entry-content">

	<?php if ( ! empty( $memberships ) ) {
		the_content();
	} else if ( ! empty( Humanities_Commons::hcommons_get_session_username() ) ) {
		echo '<h1 class="entry-title">Something is wrong!</h1>';
		echo "You have logged in with a username (" . Humanities_Commons::hcommons_get_session_username() . ") that does not have any memberships.";
		echo '<a href="mailto:hello@hcommons.org">Please let us know.</a>';
	} else {
		$identity_provider = Humanities_Commons::hcommons_get_identity_provider();
		echo '<h1 class="entry-title">Unknown Login</h1>';
		echo 'You have chosen a login method (' . $identity_provider . ') that is not linked to any account in Humanities Commons.';
		echo '<p /><a href="https://hcommons-dev.org/remind-me/" title="Not sure what your username is? Search for yourself on the Commons!">Forgotten how you logged in?</a>';


	} ?>
		</div><!-- .entry-content -->

	</article><!-- #post -->

                        <?php endwhile; // end of the loop. ?>

                </div><!-- #content -->
        </div><!-- #primary -->

</div><!-- .page-full-width -->
<?php get_footer(); ?>

