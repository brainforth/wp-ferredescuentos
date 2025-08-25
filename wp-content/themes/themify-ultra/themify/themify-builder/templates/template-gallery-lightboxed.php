<?php
/**
 * Template Gallery Lightboxed
 * 
 * This template can be overridden by copying it to your child_theme_folder/themify-builder/template-gallery-lightboxed.php.
 *
 * Access original fields: $fields_args
 * @author Themify
 */

defined( 'ABSPATH' ) || exit;

$settings=$args['settings'];
$alt = isset($settings['gallery_images'][0]->post_excerpt) ? $settings['gallery_images'][0]->post_excerpt : '';

if(!empty($settings['thumbnail_gallery'])){
    $thumbnail = $settings['thumbnail_gallery'];
    $t_post = get_post(themify_get_attachment_id_from_url($settings['thumbnail_gallery']));
}
else{
    /* if no thumbnail is set for the gallery, use the first image */
    $thumbnail = wp_get_attachment_url($settings['gallery_images'][0]->ID);
    $t_post=null;
}
$thumbnail = themify_get_image(array('src'=>$thumbnail,'w'=>$settings['thumb_w_gallery'],'h'=>$settings['thumb_h_gallery'],'alt'=>$alt));
foreach ($settings['gallery_images'] as $key => $image):
    $is_thumbnail = 0 === $key && !empty($settings['thumbnail_gallery']);
    ?>
    <dl class="gallery-item<?php if($key===0):?> tf_hidden<?php endif;?>"<?php if($key!==0):?> style="display:none"<?php endif;?>>
        <?php
        $link = wp_get_attachment_url($image->ID);
        $title = $is_thumbnail===true && $t_post!==null? $t_post->post_title : $image->post_title;
        $caption = $is_thumbnail===true && $t_post!==null? $t_post->post_excerpt : $image->post_excerpt;
        if ( ! empty( $link ) ) : ?>
            <dt class="gallery-icon">
                <a data-title="<?php esc_attr_e($settings['lightbox_title']) ?>" href="<?php echo esc_url($link) ?>" title="<?php esc_attr_e($title) ?>" data-rel="<?php echo $args['module_ID']; ?>" class="themify_lightbox"<?php echo ($is_thumbnail===true && $t_post!==null)?' data-t="'.$image->post_title.'"':''; ?>>
        <?php endif; ?>

                <?php echo $key === 0 ? $thumbnail : wp_get_attachment_image_src($image->ID, 'full')[1]; ?>

        <?php if ( ! empty( $link ) ) : ?>
                </a>
            </dt>
        <?php endif; ?>
        <?php if (($settings['gallery_image_title'] === 'yes' && $title!=='' ) || ( $settings['gallery_exclude_caption'] !== 'yes' && $caption!=='' )) : ?> 
            <dd lass="wp-caption-text gallery-caption">
                <?php if ($settings['gallery_image_title'] === 'yes' && $title!==''): ?>
                    <strong class="themify_image_title tf_block"><?php echo $title; ?></strong>
                <?php endif; ?>
                <?php if ($settings['gallery_exclude_caption'] !== 'yes' && $caption!==''): ?>
                    <span class="themify_image_caption"><?php echo $caption; ?></span>
                <?php endif ?>
            </dd>
        <?php endif ?>
    </dl>

<?php endforeach; // end loop
$t_post = null;