<?php
/**
 * Template Gallery Showcase
 * 
 * This template can be overridden by copying it to your child_theme_folder/themify-builder/template-gallery-showcase.php.
 *
 * Access original fields: $args['mod_settings']
 * @author Themify
 */

defined( 'ABSPATH' ) || exit;

$settings=$args['settings'];
if ( is_object( $settings['gallery_images'][0] ) ) :
    $caption = $settings['gallery_images'][0]->post_excerpt;
    $title = $settings['gallery_images'][0]->post_title;
    $image_attr=array('src'=>$settings['gallery_images'][0]->ID, 'w'=> $settings['s_image_w_gallery'], 'h'=> $settings['s_image_h_gallery'],'image_size'=>$settings['s_image_size_gallery']);
    ?>
    <div class="gallery-showcase-image">
        <div class="image-wrapper gallery-icon tf_rel">
            <?php echo themify_get_image($image_attr);?>
            <?php if(($title!=='' && $settings['gallery_image_title'] === 'yes') ||  ($caption!=='' && $settings['gallery_exclude_caption'] !== 'yes')) : ?>
                <div class="gallery-showcase-title tf_hidden tf_abs tf_textl">
                    <?php
                    ($title!=='' && $settings['gallery_image_title'] === 'yes')
                    && printf( '<strong class="gallery-showcase-title-text">%s</strong>'
                                , esc_attr( $title ) );
                    
                        ($caption!=='' && $settings['gallery_exclude_caption'] !== 'yes')
                            && printf( '<span class="gallery-showcase-caption">%s</span>'
                                , esc_attr( $caption ) );
                    ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <div class="gallery-images tf_hidden">
        <?php
        $image_attr=array('w'=>$settings['thumb_w_gallery'],'h'=>$settings['thumb_h_gallery'],'image_size'=>$settings['image_size_gallery']);
        foreach ($settings['gallery_images'] as $image):?>
            <div class="gallery-icon">
                <?php
                $link = themify_get_image(array('src'=>$image->ID, 'w'=>$settings['s_image_w_gallery'], 'h'=>$settings['s_image_h_gallery'],'urlonly'=>true,'image_size'=>$settings['s_image_size_gallery']));
                if ( ! empty( $link ) ) {
                    echo '<a data-image="' . esc_url( $link ) . '" title="' . esc_attr($image->post_title ) . '" data-caption="' . esc_attr( $image->post_excerpt ) . '" href="#">';
                }
                $image_attr['src']=$image->ID;
                echo themify_get_image($image_attr);
                if ( ! empty( $link ) ) echo '</a>';
            ?>
        </div>
        <?php endforeach;?>
    </div>
<?php endif; 