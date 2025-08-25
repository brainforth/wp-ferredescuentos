<?php
/**
 * Template Gallery Grid
 * 
 * This template can be overridden by copying it to your child_theme_folder/themify-builder/template-gallery-grid.php.
 *
 * Access original fields: $args['mod_settings']
 * @author Themify
 */

defined( 'ABSPATH' ) || exit;

Themify_Builder_Model::load_module_self_style($args['mod_name'],'grid');
$settings=$args['settings'];
$pagination = $settings['gallery_pagination'] && $settings['gallery_per_page'] > 0;

if ($pagination===true) {
    $total = count($settings['gallery_images']);
    if ($total <= $settings['gallery_per_page']) {
        $pagination = false;
    } else {
        $current = isset($_GET['builder_gallery']) ? $_GET['builder_gallery'] : 1;
        $offset = $settings['gallery_per_page'] * ( $current - 1 );
        $settings['gallery_images'] = array_slice($settings['gallery_images'], $offset, $settings['gallery_per_page'], true);
    }
}
$image_attr=array('w'=>$settings['thumb_w_gallery'],'h'=>$settings['thumb_h_gallery'],'image_size'=>$settings['image_size_gallery']);
$showTitle=$settings['gallery_image_title'] === 'yes';
$showCaption=$settings['gallery_exclude_caption'] !== 'yes';
$tableCols=!empty($settings['t_columns'])?$settings['t_columns']:$settings['gallery_columns'];
$stCols='--gald:'.$settings['gallery_columns'].';--galt:'.$tableCols.';--galm:';
$stCols.=!empty($settings['m_columns'])?$settings['m_columns']:$tableCols;

?>
<div class="module-gallery-grid<?php if($settings['layout_masonry'] === 'masonry'):?> gallery-masonry<?php endif;?>" style="<?php echo $stCols?>"<?php if($pagination===true):?> data-id="<?php echo $args['module_ID']?>"<?php endif;?>>
    <?php foreach ($settings['gallery_images'] as $image) :
        $caption = !empty($image->post_excerpt) ? $image->post_excerpt : '';
        $title = $image->post_title;
        ?>
        <dl class="gallery-item">
            <dt class="gallery-icon">
            <?php
            if ($settings['link_opt'] === 'file') {
                $link = wp_get_attachment_image_src($image->ID, $settings['link_image_size'])[0];
            }
            elseif ('none' === $settings['link_opt']) {
                $link = '';
            } 
            else {
                $link = get_attachment_link($image->ID);
            }
            $link_before = '' !== $link ? sprintf(
                '<a data-title="%s" title="%s" href="%s" data-rel="%s"%s>',
                esc_attr( $settings['lightbox_title'] ),
                esc_attr( $caption ),
                esc_url( $link ),
                $args['module_ID'],
                ( $settings['lightbox']===true ? ' class="themify_lightbox"' : '' )
            ) : '';
            $link_before = apply_filters('themify_builder_image_link_before', $link_before, $image, $settings);
            $link_after = '' !== $link ? '</a>' : '';
            $image_attr['src']=$image->ID;
            $img = themify_get_image($image_attr);
            
            if(!empty($img) ){
                echo $link_before, $img , $link_after;
            }
            ?>
            </dt>
            <?php if (($showTitle===true && $title!=='' ) || ( $showCaption===true && $caption!=='' )) : ?> 
                <dd class="wp-caption-text gallery-caption">
                    <?php if ($showTitle===true && $title!=='') : ?>
                        <strong class="themify_image_title tf_block"><?php echo $title ?></strong>
                    <?php endif; ?>
                    <?php if ($showCaption===true && $caption!=='') : ?>
                        <span class="themify_image_caption"><?php echo $caption ?></span>
                    <?php endif; ?>
                </dd>
            <?php endif; ?>
        </dl>
    <?php endforeach; // end loop  ?>
</div>
<?php
if ($pagination===true) :
    echo self::get_pagination('','','builder_gallery',0,ceil($total / $settings['gallery_per_page']),$current);
endif;