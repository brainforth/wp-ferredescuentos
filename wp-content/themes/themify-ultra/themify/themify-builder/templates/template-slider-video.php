<?php
/**
 * Template Slider Video
 * 
 * This template can be overridden by copying it to your child_theme_folder/themify-builder/template-slider-video.php.
 *
 * Access original fields: $args['settings']
 * @author Themify
 */

defined( 'ABSPATH' ) || exit;

$settings=$args['settings'];
if (!empty($settings['video_content_slider'])):?>
    <?php 
    $total_items = count( $settings['video_content_slider'] );
    $limit=$settings['visible_opt_slider'];
    $videoWidth=!empty($video['video_width_slider'])? ' style="max-width:' . $video['video_width_slider']. 'px"':'';
    $video_args=array('disable_lazy'=>true,'preload'=>'none');
    foreach ($settings['video_content_slider'] as $index => $video): ?>
        <?php if ( $index % $settings['items_per_slide'] === 0 ) : ?><div class="tf_swiper-slide"<?php if($index>=$limit):?> style="content-visibility:hidden"<?php endif;?>><?php endif; ?>
            <div class="slide-inner-wrap"<?php if ($settings['margin'] !== ''): ?> style="<?php echo $settings['margin']; ?>"<?php endif; ?>>
                <?php 
            $title_tag = isset($video['video_title_tag']) ? esc_attr( $video['video_title_tag'] ) : 'h3';
            if (!empty($video['video_url_slider'])): 
                $video_args['src']=$video['video_url_slider'];
                $iframe = themify_get_embed($video['video_url_slider'], $video_args);
            ?>
                <div class="slide-image tf_rel tf_lazy tf_overflow"<?php echo $videoWidth; ?>>
                    <?php if($iframe!==''):?>
                        <div class="video-wrap"><noscript><?php echo $iframe?></noscript></div>
                    <?php else:?>
                        <?php echo wp_video_shortcode($video_args)?>
                    <?php endif;?>
                </div>
                <?php endif; ?>
                <?php if(isset($video['video_caption_slider']) || isset($video['video_title_slider'])): ?>
                    <div class="slide-content tb_text_wrap">
                        <?php if(!empty($video['video_title_link_slider']) || isset($video['video_title_slider'])): ?>
                        <<?php echo $title_tag;?> class="slide-title">
                            <?php if (!empty($video['video_title_link_slider'])): ?>
                                <a href="<?php echo esc_url($video['video_title_link_slider']); ?>"<?php if('yes' === $settings['open_link_new_tab_slider']):?> target="_blank" rel="noopener"<?php endif;?>><?php echo $video['video_title_slider']; ?></a>
                            <?php elseif (isset($video['video_title_slider'])) : ?>
                                <?php echo $video['video_title_slider']; ?>
                            <?php endif; ?>
                        </<?php echo $title_tag;?>>
                        <?php endif; ?>
                        <?php if (isset($video['video_caption_slider'])):?>
                            <div class="video-caption">
                                <?php echo apply_filters('themify_builder_module_content', $video['video_caption_slider']);?>
                            </div>
                        <?php endif;?>
                    </div>
                <?php endif;?>
            </div>
        <?php if ( ( $index + 1 ) % $settings['items_per_slide'] === 0 || ( $index + 1 ) === $total_items ) : ?></div><?php endif; ?>
    <?php endforeach; // end loop video  ?>
<?php endif; 
