<?php
/**
 * Template Slider Text
 * 
 * This template can be overridden by copying it to your child_theme_folder/themify-builder/template-slider-text.php.
 *
 * Access original fields: $args['settings']
 * @author Themify
 */
defined( 'ABSPATH' ) || exit;

$settings = $args['settings'];
if (!empty($settings['text_content_slider'])) :
    $total_items = count( $settings['text_content_slider'] );
    $limit=$settings['visible_opt_slider'];
    foreach ($settings['text_content_slider'] as $index => $content): ?>
         <?php if ( $index % $settings['items_per_slide'] === 0 ) : ?><div class="tf_swiper-slide"<?php if($index>=$limit):?> style="content-visibility:hidden"<?php endif;?>><?php endif; ?>
            <div class="slide-inner-wrap"<?php if ($settings['margin'] !== ''): ?> style="<?php echo $settings['margin']; ?>"<?php endif; ?>>
                <div class="slide-content tb_text_wrap">
                    <?php
                    if (isset($content['text_caption_slider'])) {
                        echo apply_filters('themify_builder_module_content', $content['text_caption_slider']);
                    }
                    ?>
                </div>
            </div>
        <?php if ( ( $index + 1 ) % $settings['items_per_slide'] === 0 || ( $index + 1 ) === $total_items ) : ?></div><?php endif; ?>
    <?php endforeach; ?>
<?php endif; 