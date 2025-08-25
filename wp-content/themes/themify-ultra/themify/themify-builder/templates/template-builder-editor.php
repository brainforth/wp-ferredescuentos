<?php defined( 'ABSPATH' ) || exit;?>
<?php if(!did_action('wp_head')):?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
    <head>
        <?php wp_head(); ?>
    </head>
    <body <?php body_class('single'); ?>>
    <?php themify_body_start();?>
<?php endif;?>
        <div class="single-template-builder-container">
            <?php if (have_posts()) : the_post(); ?>
                    <?php the_content(); ?>
            <?php endif; ?>
        </div>
    <?php if(!did_action('wp_footer')):?>
        <!-- wp_footer -->
        <?php wp_footer();?> 
<?php endif;?>
<?php if(!did_action('wp_head')):?>
    </body>
</html>
<?php endif;?>