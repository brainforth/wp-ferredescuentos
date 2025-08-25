<?php
/**
 * Template Gallery Slider
 * 
 * This template can be overridden by copying it to your child_theme_folder/themify-builder/template-gallery-slider.php.
 *
 * Access original fields: $args['settings']
 * @author Themify
 */

defined('ABSPATH') || exit;

$settings = $args['settings'];
$margins = '';
$element_id = $args['module_ID'] . '_thumbs';
if ($settings['left_margin_slider'] !== '') {
    $margins .= 'margin-left:' . $settings['left_margin_slider'] . 'px;';
}

if ($settings['right_margin_slider'] !== '') {
    $margins .= 'margin-right:' . $settings['right_margin_slider'] . 'px';
}
$image_attr = array(
    'is_slider'=>true,
    'image_size' => $settings['s_image_size_gallery']
);
$arr=['slider'];
$thumbLimit=$slideLimit=$settings['visible_opt_slider'];
$showThumbs=$settings['slider_thumbs']!=='yes';
$showTitle=$settings['gallery_image_title'] === 'yes';
$showCaption=$settings['gallery_exclude_caption'] !== 'yes';
if($showThumbs===true){
    $arr[]='thumbs';
    $slideLimit=1;
}
foreach ($arr as $mode) :
    $is_slider = $mode === 'slider';
    //$image_attr['is_slider'] = true;
    $image_attr['w'] = $is_slider === false ? $settings['thumb_w_gallery'] : $settings['s_image_w_gallery'];
    $image_attr['h'] = $is_slider === false ? $settings['thumb_h_gallery'] : $settings['s_image_h_gallery'];
    $hasNav = $mode === ( ($is_slider === true && $showThumbs===false) || $settings['show_arrow_buttons_vertical'] ? 'slider' : 'thumbs' ) ? ($settings['show_arrow_slider'] === 'yes' ? 1 : 0) : 0;
    ?>
    <?php if ($hasNav === 1): ?>
        <div class="themify_builder_slider_vertical tf_rel">
        <?php endif; ?>
        <div class="tf_swiper-container tf_carousel themify_builder_slider<?php if ($is_slider === false): ?> <?php echo $element_id ?> tf_swiper-thumbs<?php endif; ?> tf_rel tf_overflow"
             <?php if (Themify_Builder::$frontedit_active === false): ?> data-lazy="1"<?php endif; ?>
                data-pager="<?php echo ($is_slider === false ||$showThumbs===false) && $settings['show_nav_slider'] === 'yes' ? 1 : 0 ?>"
                data-speed="<?php echo $settings['speed_opt_slider']; ?>"
                data-slider_nav="<?php echo $hasNav ?>"
                data-wrapvar="<?php echo $settings['wrap_slider'] === 'yes' ? 1 : 0 ?>"
                data-height="<?php echo isset($settings['horizontal']) && $settings['horizontal'] === 'yes' ? 'variable' : $settings['height_slider'] ?>"
                <?php if($is_slider===true ):?>
                    data-effect="<?php echo $settings['effect_slider'] ?>"
                    data-css_url="<?php echo THEMIFY_BUILDER_CSS_MODULES ?>sliders/carousel,<?php echo THEMIFY_BUILDER_CSS_MODULES ?>sliders/<?php echo $args['mod_name'] ?>"
                    <?php if ($settings['auto_scroll_opt_slider'] !== 'off'): ?>
                    data-auto="<?php echo $settings['auto_scroll_opt_slider'] ?>"
                    data-pause_hover="<?php echo $settings['pause_on_hover_slider'] === 'resume' ? 1 : 0 ?>"
                    <?php if($settings['play_pause_control'] === 'yes'):?>
                        data-controller="1"
                    <?php endif;?>
                <?php endif; ?>
                <?php endif;?>
                <?php if($showThumbs===true):?>
                    <?php if($is_slider===true): ?>
                        data-thumbs="<?php echo $element_id ?>"
                    <?php else:?>
                        data-thumbs-id="<?php echo $element_id ?>"
                    <?php endif ?>
                <?php endif;?>
             <?php if($is_slider === false ||$showThumbs===false):?>
                data-visible="<?php echo $thumbLimit ?>" 
                data-tab-visible="<?php echo $settings['tab_visible_opt_slider'] ?>"
                data-mob-visible="<?php echo $settings['mob_visible_opt_slider'] ?>"
                data-scroll="<?php echo $settings['scroll_opt_slider']; ?>"
                <?php if (!empty($settings['touch_swipe'])) : ?>data-touch_swipe="<?php echo $settings['touch_swipe']; ?>" <?php endif; ?>
             <?php endif;?>
            >
            <div class="tf_swiper-wrapper tf_lazy tf_rel tf_w tf_h tf_textc">
                <?php $limit=$is_slider===true?$slideLimit:$thumbLimit?>
                <?php foreach ($settings['gallery_images'] as $i=>$image) : ?>
                    <div class="tf_swiper-slide" style="<?php if($i>=$limit):?>display:none<?php endif;?>">
                        <div class="slide-inner-wrap"<?php $margins !== '' && printf(' style="%s"', $margins) ?>>
                            <div class="tf_lazy slide-image gallery-icon">
                            <?php
                                $image_attr['alt'] = get_post_meta($image->ID, '_wp_attachment_image_alt', true);
                                $image_attr['src'] = wp_get_attachment_image_url($image->ID, 'full');
                                $image_html = themify_get_image($image_attr);

                                $lightbox = '';
                                $link = null;
                                if ($is_slider === true) {
                                    if ($settings['link_opt'] === 'file') {
                                        $link = wp_get_attachment_image_src($image->ID, $settings['link_image_size']);
                                        if(!empty($link)){
                                            $link = $link[0];
                                        }
                                        if ($settings['lightbox'] === true) {
                                            $lightbox = ' class="themify_lightbox"';
                                        }
                                    } 
                                    elseif ('none' !== $settings['link_opt']) {
                                        $link = get_attachment_link($image->ID);
                                    }
                                }
                                if ($is_slider === true && !empty($link)) {
                                    printf('<a href="%s"%s>%s</a>', esc_url($link), $lightbox, $image_html);
                                } else {
                                    echo $image_html; 
                                }
                                ?>
                            </div>
                            <?php if ($is_slider === true && (( $showTitle===true && $image->post_title) || ($showCaption===true && $image->post_excerpt ))) : ?>
                                <div class="slide-content tf_opacity tf_texl tf_abs">
                                    <?php if ($showTitle===true && !empty($image->post_title)): ?>
                                        <h3 class="slide-title"><?php echo wp_kses_post($image->post_title) ?></h3>
                                    <?php endif; ?>

                                    <?php if ($showCaption===true && !empty($image->post_excerpt)) : ?>
                                        <p><?php echo apply_filters('themify_builder_module_content', $image->post_excerpt) ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div></div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php if ($hasNav === 1): ?>
        </div>
    <?php endif; ?>
<?php endforeach; ?>