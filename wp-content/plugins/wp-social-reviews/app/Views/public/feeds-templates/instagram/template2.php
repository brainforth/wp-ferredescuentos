<?php
use WPSocialReviews\Framework\Support\Arr;

if (!empty($feeds) && is_array($feeds)) {
    $column      = isset($template_meta['column_number']) ? $template_meta['column_number'] : 4;
    $columnClass = 'wpsr-col-' . $column;
    $layout_type = isset($template_meta['layout_type']) && defined('WPSOCIALREVIEWS_PRO') ? $template_meta['layout_type'] : 'grid';
    $imageAspectRatio = Arr::get($template_meta, 'post_settings.aspect_ratio', 'classic');

    foreach ($feeds as $index => $feed) {
        if ($index >= $sinceId && $index <= $maxId) {
            $feedLink = Arr::get($feed, 'shoppable_options.show_shoppable') ? Arr::get($feed, 'shoppable_options.url_settings.url', '') : Arr::get($feed,'permalink', '');
            $target = (Arr::get($template_meta, 'post_settings.display_mode', '') === 'instagram') ? '_blank' : '';
            $display_optimize_image = Arr::get($image_settings, 'optimized_images', '');
            $imageSize = Arr::get($template_meta, 'post_settings.resolution', 'full');
            $displayMode = $template_meta['post_settings']['display_mode'];
            $userName = Arr::get($feed, 'username', '');
            $feedId = Arr::get($feed, 'id', '');

            if (Arr::get($feed, 'shoppable_options')) {
                $target = Arr::get($feed, 'shoppable_options.url_settings.open_in_new_tab') ? '_blank' : '';
            }
            if ($layout_type !== 'carousel') {
                /**
                 * instagram_feed_template_item_wrapper_before hook.
                 *
                 * @hooked InstagramTemplateHandler::renderTemplateItemWrapper - 10 (outputs opening divs for the template item)
                 * */
                do_action('wpsocialreviews/instagram_feed_template_item_wrapper_before', $template_meta);
            }
            ?>
            <div role="group" class="wpsr-ig-post wpsr-image-ratio-<?php echo esc_attr($imageAspectRatio); ?> <?php echo ($layout_type === 'carousel' && defined('WPSOCIALREVIEWS_PRO')) ? 'swiper-slide' : ''; ?>" data-post_id="<?php echo esc_attr(Arr::get($feed, 'id', ''));?>" data-user_name="<?php echo esc_attr(Arr::get($feed, 'username', ''));?>" data-image_size="<?php echo esc_attr(Arr::get($template_meta, 'post_settings.resolution', 'full'));?>">
                <a class="wpsr-ig-playmode" <?php echo ($displayMode === 'instagram' && $feedLink) ? 'href=' . esc_url($feedLink) . '' : ''; ?>
                   target="<?php echo esc_attr($target); ?>"
                   data-index="<?php echo esc_attr($index); ?>"
                   data-playmode="<?php echo isset($displayMode) ? esc_attr($displayMode) : 'instagram'; ?>"
                   data-template-id="<?php echo esc_attr($templateId); ?>"
                   data-user_name="<?php echo esc_attr($userName);?>" data-post_id="<?php echo esc_attr($feedId);?>" 
                   data-image_size="<?php echo esc_attr($imageSize);?>"
                   data-optimized_images="<?php echo esc_attr($display_optimize_image);?>"
                   rel="noopener noreferrer"
                >
                    <?php
                    /**
                     * instagram_post_media hook.
                     *
                     * @hooked InstagramTemplateHandler::renderPostMedia 10
                     * */
                    do_action('wpsocialreviews/instagram_post_media', $feed, $template_meta, $index, $display_optimize_image);
                    ?>
                </a>

                <?php
                    if(Arr::get($feed, 'shoppable_options.show_shoppable') && Arr::get($template_meta, 'post_settings.display_mode') !== 'popup') {
                        do_action('wpsocialreviews/instagram_shoppable_button', $feed, $template_meta);
                    }
                ?>

                <?php if (count($feed) > 6) { ?>
                    <div class="wpsr-ig-post-info">
                        <?php
                        /**
                         * instagram_post_statistics hook.
                         *
                         * @hooked render_instagram_statistics_html 10
                         * */
                        do_action('wpsocialreviews/instagram_post_statistics', $feed, $template_meta);

                        /**
                         * instagram_post_caption hook.
                         *
                         * @hooked InstagramTemplateHandler::renderPostCaption 10
                         * */
                        do_action('wpsocialreviews/instagram_post_caption', $feed, $template_meta);
                        ?>
                    </div>
                <?php } ?>
            </div>
            <?php if ($layout_type !== 'carousel') { ?>
                </div>
            <?php } ?>
            <?php
        }
    }
}