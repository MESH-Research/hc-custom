<?php
/**
 * Template Name: Not Enrolled Template
 *
 * Description: Handle logins that are not linked to any account in HC
 *
 * @since HCommons
 */
	$errors = false;
	$success = false;
	$mail_error = false;

	if( $_SERVER['REQUEST_METHOD'] == 'POST' && wp_verify_nonce($_POST['cu_nonce'], 'contact-us-nonce' ) ) {

		//sanitize post data first
		//$subject = filter_var( $_POST['subject'], FILTER_SANITIZE_STRIPPED );
		$msg = filter_var( $_POST['msg'], FILTER_SANITIZE_STRIPPED );
		$email = filter_var( $_POST['uemail'], FILTER_SANITIZE_EMAIL );
		$uname = filter_var( $_POST['uname'], FILTER_SANITIZE_STRIPPED );

		if( ! empty( $email ) && ! empty( $msg ) ) {

			if( !empty( $uname ) ) {
				$content .= "<p>User's name: {$uname}</p>";
			}

			$content .= "<p>User's E-mail: {$email}</p>";
			$content .= "<p>Message: {$msg}</p>";

 			$mail = wp_mail( 'hello@hcommons.org', 'User is not enrolled', $content, "\r\nReply-to: <" . $email . ">" );
 			
 			if( $mail == true ) {
 				$success = 'Mail sent! Please give us some time to respond back';
 			} else {
 				$mail_error = 'Uh oh! Something went wrong..';
 			}

		} else {

			$errors = true;
		
		}

	}

	//must set cookies before header
	setcookie( '_saml_idp', false, time()-3600, '/', '.' . getenv('WP_DOMAIN'), false, true );
	setcookie( 'stickyIdPSelection', false, time()-3600, '/', '.' . getenv('WP_DOMAIN'), true, true );
	wp_destroy_current_session();
	wp_clear_auth_cookie();

	$shib_urls = [
		// IDPs
		getenv('GOOGLE_IDP_URL') . '/idp/profile/Logout',
		getenv('TWITTER_IDP_URL') . '/idp/profile/Logout',
		getenv('MLA_IDP_URL') . '/idp/profile/Logout',
		getenv('HC_IDP_URL') . '/idp/profile/Logout',
		// SPs
		getenv('REGISTRY_SP_URL') . '/Shibboleth.sso/Logout',
		get_site_url() . '/Shibboleth.sso/Logout',
	];

	get_header(); ?>

	<style type="text/css">
		
		#cu-container {
			width: 50%;
			margin: 40px auto;
		}

			#cu-container h3 {
				margin-bottom: 10px;
			}

			#cu-container h4 {
				margin-bottom: 10px;
			}

		#contact-us input[type="text"], #contact-us input[type="email"], #contact-us textarea {
    		width: 70%;
    		margin-bottom: 20px;
		}

		#contact-us span {
			margin-bottom: 3px;
			color: red;
		}

		#contact-us .error {
			border: 1px solid red;
		}

	</style>

	<?php foreach( $shib_urls as $shib_url ): ?>
		<iframe src="<?php echo $shib_url ?>" style="display:none" title="Log Out" ></iframe>
	<?php endforeach ?>

        <div class="page-full-width">

        <?php if( empty( $success ) ) : ?>

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

        <?php endif ?>
         	
         	<div id="cu-container">
         		
         		<?php if( ! empty( $success ) ) : ?>
         			<h4><?php echo $success; ?></h4>
         		<?php endif; ?>

         		<?php if( ! empty( $mail_error ) ) : ?>
         			<h4><?php echo $success; ?></h4>
         		<?php endif; 

         			if( empty( $success ) ) :
         		?>

	         	<h3>Contact Us</h3>
	         	<form id="contact-us" action="/not-enrolled" method="POST">
					
					<p><input type="text" placeholder="Your Name" name="uname" /></p>

	         		<?php if( ! empty( $errors ) ) : ?>

	         		<p><span>Please enter your E-mail!</span><br/> 
	         			<input type="email" class="error" placeholder="Your E-mail" name="uemail" /></p>

					<p><span>Please enter message!</span><br />
						<textarea class="error" placeholder="message" name="msg"></textarea></p>

					<?php else : ?>
					
					<p><input type="email" placeholder="Your E-mail" name="uemail" /></p>
					<!--<p><input type="text" placeholder="Subject" name="subject" /></p>-->
					<p><textarea placeholder="Message" name="msg"></textarea></p>
					
					<?php endif; ?>
					
					<input type="hidden" name="cu_nonce" value="<?php echo wp_create_nonce('contact-us-nonce'); ?>" />
					<p><button>Submit</button></p>
				</form> <!-- /contact-us -->

				<?php endif;?>

			</div> <!-- /#cu-container -->

        </div><!-- #primary -->

</div><!-- .page-full-width -->
<?php get_footer(); ?>
