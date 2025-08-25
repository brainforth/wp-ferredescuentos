<!DOCTYPE html>
<html <?php language_attributes(); ?>>

    <head>
        <?php wp_head(); ?>
        <base target="_top">
    </head>

    <body <?php body_class( 'tb_post_lightbox' ); ?>>

        <?php wp_body_open(); ?>

		<article id="post-<?php the_id(); ?>" <?php post_class('post tf_clearfix'); ?>>

			<?php themify_post_media(); ?>

			<div class="post-content">
				<?php themify_post_title(); ?>
				<?php themify_post_content(); ?>
			</div>

		</article>

        <?php wp_footer(); ?>
    </body>

</html>