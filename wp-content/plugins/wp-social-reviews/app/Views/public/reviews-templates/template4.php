<?php
use WPSocialReviews\Framework\Support\Arr;
use WPSocialReviews\App\Services\Platforms\Reviews\Helper;

$average_rating = Arr::get($business_info, 'average_rating', 0);
$shouldDisplayAISummaryIcon = Arr::get($template_meta, 'ai_summary.display_ai_summary_icon', 'true');
if (!empty($reviews)) {
    foreach ($reviews as $index => $review) {
        $source_id = Arr::get($review, 'source_id', '');
        $media_id = Arr::get($review, 'review_id', '');
        $product_name  = Arr::get($review, 'fields.product_name', '');
        $product_thumbnail  = Arr::get($review, 'fields.product_thumbnail');
        $product_thumbnail_url  = Arr::get($product_thumbnail, '0', '');
        $image_size = Arr::get($template_meta, 'resolution', 'full');
        $reviewer_url = $review->platform_name === 'facebook' ? 'https://www.facebook.com/'.$review->source_id.'/reviews' : $review->reviewer_url;
        $enableExternalLink = ($review->platform_name === 'ai') ? 'false' : $template_meta['enableExternalLink'];
        /**
         * reviews_template_item_wrappers_before hook.
         *
         * @hooked ReviewsTemplateHandler::renderTemplateItemParentWrapper - 10 (outputs opening divs for the review item)
         * */
        do_action('wpsocialreviews/reviews_template_item_wrappers_before', $template_meta);
        ?>
            <div class="wpsr-review-template wpsr-review-template-four <?php echo ($review->platform_name) ? 'wpsr-review-template-' . esc_attr($review->platform_name) : ''; ?>"
                    data-index="<?php echo esc_attr($index); ?>"
                    data-source_id="<?php echo esc_attr($source_id); ?>"
                    data-media_id="<?php echo esc_attr($media_id); ?>"
                    data-review_platform="<?php echo esc_attr($review->platform_name); ?>"
                    data-product_thumbnail="<?php echo esc_attr($product_thumbnail_url); ?>"
                    data-product_name="<?php echo esc_attr($product_name); ?>"
                    data-image_resize="<?php echo esc_attr($image_size)?>"
            >
                <div class="wpsr-review-header"
                     style="<?php echo ($template_meta['equal_height'] === 'true') && $template_meta['contentType'] === 'excerpt' ? 'height:' . esc_attr($template_meta['equalHeightLen']) . 'px' : ''; ?>"
                >
                    <?php
                    /**
                     * review_platform hook.
                     *
                     * @hooked ReviewsTemplateHandler::renderReviewPlatformHtml 10
                     * */
                    do_action('wpsocialreviews/review_platform', $template_meta['isPlatformIcon'],
                        $template_meta['display_tp_brand'], $review->platform_name);

                    /**
                     * review_title hook.
                     *
                     * @hooked ReviewsTemplateHandler::renderReviewTitleHtml 10
                     * */
                    do_action('wpsocialreviews/review_title', $template_meta['display_review_title'], $review->review_title, $review->platform_name);

                    /**
                     * reviewer_rating hook.
                     *
                     * @hooked ReviewsTemplateHandler::renderReviewerRatingHtml 10
                     * */
                    $rating = ($review->category === 'ai_summary') ? $average_rating : $review->rating;
                    do_action('wpsocialreviews/reviewer_rating', $template_meta['reviewerrating'],
                        $template_meta['rating_style'], $rating, $review->platform_name,
                        $review->recommendation_type,  $template_meta['platform']);

                    /**
                     * AI summary total review hook, can be used to render content before ratings.
                     *
                     * @hooked ReviewsTemplateHandler::addTotalReviewsToAISummaryCard 10
                     * */
                    do_action('wpsocialreviews/render_ai_summary_total_reviews',
                        $business_info['total_rating'],
                        $template_meta['custom_number_of_reviews_text'],
                        $review
                    );

                    /**
                     * review_content hook.
                     *
                     * @hooked ReviewsTemplateHandler::renderReviewContentHtml 10
                     * */
                    if(
                        isset($template_meta['ai_summary']['enabled']) && $template_meta['ai_summary']['enabled'] === 'true'
                        && isset($template_meta['ai_summary']['style']) && $template_meta['ai_summary']['style'] === 'list'
                        && isset($review->summary_list)
                        && !empty($review->summary_list)
                    ) {
                        /**
                         * review ai summary as list hook.
                         *
                         * @hooked ReviewsTemplateHandler::renderReviewAiSummaryHtml 10
                         * */
                        do_action('wpsocialreviews/review_ai_summary_list',
                            $template_meta['ai_summary']['enabled'],
                            $review->summary_list,
                            $template_meta['ai_summary']['display_readmore'],
                            $template_meta['content_length'],
                            $template_meta['ai_summary']['text_typing_animation'],
                            $template_meta['contentType'],
                        );
                    } else {
                        do_action('wpsocialreviews/review_content',
                            $template_meta['isReviewerText'],
                            $template_meta['content_length'],
                            $template_meta['contentType'],
                            $review->reviewer_text,
                            $template_meta['contentLanguage']
                        );
                    }
                    ?>
                </div>
                <div class="wpsr-review-info">
                    <?php
                        /**
                         * reviewer_image hook.
                         *
                         * @hooked ReviewsTemplateHandler::renderReviewerImageHtml 10
                         * */
                        if (method_exists(Helper::class, 'shouldShowAISummaryIcon')) {
                            $shouldShowIcon = Helper::shouldShowAISummaryIcon($review, $shouldDisplayAISummaryIcon, $template_meta);
                        } else {
                            $shouldShowIcon = $template_meta['reviewer_image'];
                        }
                        do_action('wpsocialreviews/reviewer_image', $shouldShowIcon,
                            $reviewer_url, $review->reviewer_img, $review->reviewer_name, $enableExternalLink, $review->media_url, $review->platform_name);
                    ?>
                    <div class="wpsr-review-name-date">
                        <?php
                        /**
                         * reviewer_name hook.
                         *
                         * @hooked ReviewsTemplateHandler::renderReviewerNameHtml 10
                         * */
                        do_action('wpsocialreviews/reviewer_name', $template_meta['reviewer_name'],
                            $reviewer_url, $review->reviewer_name, $enableExternalLink, $review->platform_name, $template_meta['enable_verified_badge']);
                        /**
                         * review_date hook.
                         *
                         * @hooked ReviewsTemplateHandler::renderReviewDateHtml 10
                         * */
                        if($review->category !== 'ai_summary' && $review->platform_name !== 'ai'){
                            do_action('wpsocialreviews/review_date', $template_meta['timestamp'],
                                $review->review_time);
                        }
                        ?>
                    </div>
                </div>
            </div>
        <?php
        /**
         * reviews_template_item_wrappers_after hook.
         *
         * @hooked ReviewsTemplateHandler::renderTemplateItemParentWrapperEnd - 10 (outputs closing divs for the review item)
         * */
        do_action('wpsocialreviews/reviews_template_item_wrappers_after');
    }
}