<?php
/**
 * Template Testimonial Content
 *
 * This template can be overridden by copying it to your child_theme_folder/themify-builder/template-testimonial-slider-content.php.
 *
 * Access original fields: $args['mod_settings']
 * @author Themify
 */

defined('ABSPATH') || exit;

$settings = $args['settings'];
if (!empty($settings['tab_content_testimonial'])):
    $image_w = $settings['img_w_slider'];
    $image_h = $settings['img_h_slider'];
    $isSlider = !isset($settings['type_testimonial']) || $settings['type_testimonial'] === 'slider';
    $image_size = $settings['image_size_slider'] !== '' ? $settings['image_size_slider'] : themify_builder_get('setting-global_feature_size', 'image_global_size_field');
    $param_image_src = array('w' => $image_w, 'h' => $image_h, 'image_size' => $image_size);
    if ($isSlider === true) {
        $param_image_src['is_slider'] = true;
    }
    $limit= isset( $settings['visible_opt_slider'] ) ? (int) $settings['visible_opt_slider'] : 1;
    foreach ($settings['tab_content_testimonial'] as $i => $content):
        ?>
        <div class="post<?php echo $isSlider === true ? ' tf_swiper-slide' : '' ?>"<?php if( $isSlider && $i >= $limit ) : ?> style="content-visibility:hidden"<?php endif;?>>
            <div class="testimonial-item"<?php if ($settings['margin'] !== ''): ?> style="<?php echo $settings['margin']; ?>"<?php endif; ?>>
                <?php
                $image = '';
                if (!empty($content['person_picture_testimonial'])) {
                    $image_url = esc_url($content['person_picture_testimonial']);
                    $image_title = isset($content['title_testimonial']) ? $content['title_testimonial'] : '';
                    if ($alt_by_url = Themify_Builder_Model::get_alt_by_url($image_url)) {
                        $image_alt = $alt_by_url;
                    } else {
                        $image_alt = $image_title;
                    }
                    $param_image_src['src'] = $image_url;
                    $param_image_src['alt'] = $image_alt;
                    $image = themify_get_image($param_image_src);
                }
                ?>
                <div class="testimonial-content">
                    <?php if (!empty($content['title_testimonial'])): ?>
                        <h3 class="testimonial-title"><?php echo $content['title_testimonial'] ?></h3>
                    <?php endif; ?>
                    <?php
                    if (!empty($content['ic'])) {
                        $count = !empty($content['count']) ? (int) $content['count'] : 5;
                        $rating = isset($content['rating']) && $content['rating'] !== '' ? round((float) $content['rating'], 2) : 5;
                        $defaultIcon = themify_get_icon($content['ic']);
                        $fillIcon = themify_get_icon($content['ic'], false, false, false, array('class' => 'tb_rating_fill'));
                        ?>
                        <div class="tb_rating_wrap">
                            <?php
                                for ($j = 0; $j < $count; ++$j) {
                                    if (($rating - $j) >= 1) {
                                        echo $fillIcon;
                                    } elseif ($rating > $j) {
                                        $decimal = $rating - (int) $rating;
                                        $gid = $args['module_ID'] . $i;
                                        ?>
                                        <svg width="0" height="0" aria-hidden="true" style="visibility:hidden;position:absolute">
                                        <defs>
                                        <linearGradient id="<?php echo $gid ?>">
                                        <stop offset="<?php echo $decimal * 100 ?>%" class="tb_rating_fill"/>
                                        <stop offset="<?php echo $decimal * 100 ?>%" stop-color="currentColor"/>
                                        </linearGradient>
                                        </defs>
                                        </svg>
                                        <?php
                                        echo themify_get_icon($content['ic'], false, false, false, array('class' => 'tb_rating_half', 'style' => '--tb_rating_half:url(#' . $gid . ')'));
                                    } else {
                                        echo $defaultIcon;
                                    }
                                }
                                ?>
                            </div>
                    <?php } ?>
                    <?php if (!empty($content['content_testimonial'])): ?>
                        <div class="testimonial-entry-content">
                            <?php echo apply_filters('themify_builder_module_content', $content['content_testimonial']); ?>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($image)): ?>
                        <figure class="testimonial-image<?php if ($isSlider === true): ?> tf_lazy<?php endif; ?> tf_rel">
                            <?php echo $image ?>
                        </figure>
                    <?php endif; ?>

                    <?php if (!empty($content['person_name_testimonial'])): ?>
                        <div class="testimonial-author">
                            <div class="person-name"><?php echo $content['person_name_testimonial'] ?></div>
                            <?php if (!empty($content['person_position_testimonial'])): ?>
                                <span class="person-position"><?php echo $content['person_position_testimonial'] ?></span>
                            <?php endif; ?>
                            <?php if (!empty($content['company_testimonial'])): ?>
                                <div class="person-company">
                                    <?php if (!empty($content['company_website_testimonial'])): ?>
                                        <a href="<?php echo $content['company_website_testimonial'] ?>"><?php echo $content['company_testimonial'] ?></a>
                                    <?php else: ?>
                                        <?php echo $content['company_testimonial'] ?>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    <?php
 endif;
