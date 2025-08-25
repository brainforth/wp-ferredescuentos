<?php

namespace WPSocialReviews\App\Services\Platforms\Reviews;

use Exception;
use WPSocialReviews\App\Models\Review;
use WPSocialReviews\Framework\Database\Orm\Collection;
use WPSocialReviews\Framework\Support\Arr;
use WPSocialReviews\App\Services\Helper as GlobalHelper;
use WPSocialReviews\App\Services\GlobalSettings;
use WPSocialReviews\App\Services\Platforms\MediaManager;
use WPSocialReviews\App\Services\Platforms\Feeds\CacheHandler;

if (!defined('ABSPATH')) {
    exit;
}

class Helper
{
    public static function getConnectedAccountsBusinessInfo($platforms)
    {
        $business_info = [];
        if (!is_array($platforms)) {
            $platforms = [$platforms];
        }

        foreach ($platforms as $platform) {
            $infos = get_option('wpsr_reviews_' . $platform . '_business_info');

            if(!empty($infos)) {
                $business_info += get_option('wpsr_reviews_'.$platform.'_business_info');
            } else {
                //for custom or fluent forms platform
                if($platform === 'custom'){
                    $data = Review::getInternalBusinessInfo($platform);
                    if (!empty($data) && is_array($data)) {
                        $business_info += $data;
                    }
                }    
            }
        }
        return $business_info;
    }

    public static function getNotificationMessage($businessInfo = [], $key = '')
    {
        $downloadedReviews = Arr::get($businessInfo, 'total_fetched_reviews');
        $businessName = Arr::get($businessInfo, $key.'.name');

        $message = __('Reviews fetched successfully!!', 'wp-social-reviews');
        if ($downloadedReviews && $businessName && $downloadedReviews > 0) {
            // translators: Please retain the placeholders (%s, %d, etc.) and ensure they are correctly used in context.
            $message = sprintf(__( '%1$s reviews fetched successfully from %2$s!!', 'wp-social-reviews' ), $downloadedReviews, $businessName);
        } else if($downloadedReviews && $downloadedReviews > 0) {
            // translators: Please retain the placeholders (%s, %d, etc.) and ensure they are correctly used in context.
            $message = sprintf(__( '%s reviews fetched successfully!!', 'wp-social-reviews' ), $downloadedReviews);
        } else if (empty($downloadedReviews) || $downloadedReviews < 1) {
            throw new \Exception(
                esc_html__('Reviews fetched failed, Please try again!!', 'wp-social-reviews')
            );
        }

        return $message;
    }

    public static function formattedTemplateMeta($settings = array())
    {
        $platform = Arr::get($settings, 'platform', []);

        $template = in_array('testimonial', $platform) ? 'testimonial1' : 'grid1';
        $timestamp = in_array('testimonial', $platform) ? 'false' : 'true';
	    $selectedIncList = Arr::get($settings, 'selectedIncList', []);
	    $selectedExcList = Arr::get($settings, 'selectedExcList', []);
	    $filterByTitle   = Arr::get($settings, 'filterByTitle', 'all');

		if (empty($selectedExcList) && empty($selectedIncList)) {
			$filterByTitle = 'all';
		}

        // support old minimum rating values 11/6
        if((Arr::get($settings, 'starFilterVal') === 11) || (!in_array('booking.com', $platform) && Arr::get($settings, 'starFilterVal') >= 6)) {
            $settings['starFilterVal']  = -1;
        }

        extract(static::getCarouselSettings($settings));

        //notification and badge old settings compatible for new version
        $notification_settings = Arr::get($settings, 'notification_settings');
        if(!Arr::get($notification_settings, 'display_mode', 'false')) {
            $settings['notification_settings']['display_mode'] = (Arr::get($notification_settings, 'display_reviews_on_click') === 'true') ? 'popup' : 'none';
        }

        $badge_settings = Arr::get($settings, 'badge_settings');
        if(!Arr::get($badge_settings, 'display_mode', 'false')) {
            $settings['badge_settings']['display_mode'] = (Arr::get($badge_settings, 'display_reviews_on_click') === 'true') ? 'popup' : 'none';
        }

        return array (
            //source
            'platform'       => $platform,

            //template
            'platformType'   => Arr::get($settings, 'platformType', 'single'),
            'template'       => Arr::get($settings, 'template', $template),
            'templateType'   => Arr::get($settings, 'templateType',  'grid'),
            'column'         => Arr::get($settings, 'column', '4'),
            'responsive_column_number'  => array(
                'desktop'  => Arr::get($settings, 'responsive_column_number.desktop', Arr::get($settings,'column', '4')),
                'tablet'   => Arr::get($settings, 'responsive_column_number.tablet','4'),
                'mobile'   => Arr::get($settings, 'responsive_column_number.mobile', '12')
            ),
            //settings
            'reviewer_name'  => Arr::get($settings, 'reviewer_name', 'true'),
            'reviewer_name_format'  => Arr::get($settings, 'reviewer_name_format', 'full-name'),
            'author_position'    => Arr::get($settings, 'author_position', 'true'),
            'author_company_name'   => Arr::get($settings, 'author_company_name', 'false'),
            'website_logo'   => Arr::get($settings, 'website_logo', 'true'),
            'rating_style'   => Arr::get($settings, 'rating_style', 'default'),
            'reviewer_image' => Arr::get($settings, 'reviewer_image', 'true'),
            'timestamp'      => Arr::get($settings, 'timestamp', $timestamp),
            'reviewerrating' => Arr::get($settings, 'reviewerrating', 'true'),
            'enable_verified_badge' => Arr::get($settings, 'enable_verified_badge', 'false'),
            'resolution' => Arr::get($settings, 'resolution', 'full'),
            
            'platform_label' => sanitize_text_field(Arr::get($settings, 'platform_label', __('On Site', 'wp-social-reviews'))),

            'equal_height'      => Arr::get($settings, 'equal_height', 'false'),
            'equalHeightLen'    => (int)Arr::get($settings, 'equalHeightLen', '250'),
            'content_length'    => (int)Arr::get($settings, 'content_length', 10),
            'contentLanguage'   => Arr::get($settings, 'contentLanguage', 'original'),
            'contentType'       => Arr::get($settings, 'contentType', 'excerpt'),
            'enableExternalLink'           => Arr::get($settings, 'enableExternalLink', 'true'),

            'display_review_title'         => Arr::get($settings, 'display_review_title', 'true'),
            'isReviewerText'               => Arr::get($settings, 'isReviewerText', 'true'),
            'isPlatformIcon'               => Arr::get($settings, 'isPlatformIcon', 'true'),
            'current_template_type'        => Arr::get($settings, 'current_template_type', 'grid'),

            //carousel
            'carousel_settings' => array(
                'autoplay'                     => Arr::get($settings, 'carousel_settings.autoplay', $autoplay),
                'autoplay_speed'               => (int)Arr::get($settings,'carousel_settings.autoplay_speed', $autoplay_speed),
                'slides_to_show'               => (int)Arr::get($settings,'carousel_settings.slides_to_show', $slides_to_show),
                'spaceBetween'                 => (int) Arr::get($settings,'carousel_settings.spaceBetween', 20),
                'responsive_slides_to_show'  => array(
	                'desktop'  => (int)Arr::get($settings, 'carousel_settings.responsive_slides_to_show.desktop', Arr::get($settings, 'carousel_settings.slides_to_show', $slides_to_show)),
	                'tablet'   => (int)Arr::get($settings, 'carousel_settings.responsive_slides_to_show.tablet',2),
	                'mobile'   => (int)Arr::get($settings, 'carousel_settings.responsive_slides_to_show.mobile', 1)
                ),
                'slides_to_scroll'             => (int)Arr::get($settings, 'carousel_settings.slides_to_scroll', $slides_to_scroll),
                'responsive_slides_to_scroll'             => array(
	                'desktop'  => (int)Arr::get($settings, 'carousel_settings.responsive_slides_to_scroll.desktop', Arr::get($settings, 'carousel_settings.slides_to_scroll', $slides_to_scroll)),
	                'tablet'   => (int)Arr::get($settings, 'carousel_settings.responsive_slides_to_scroll.tablet',2),
	                'mobile'   => (int)Arr::get($settings, 'carousel_settings.responsive_slides_to_scroll.mobile', 1)
                ),
                'navigation'                   => Arr::get($settings,'carousel_settings.navigation', $navigation),
            ),

            //filters
            'totalReviewsVal'              => (int)Arr::get($settings, 'totalReviewsVal', '50'),
            'totalReviewsNumber'           => array(
                'desktop'   => (int)Arr::get($settings, 'totalReviewsNumber.desktop', Arr::get($settings, 'totalReviewsVal', '50')),
                'mobile'    => (int)Arr::get($settings, 'totalReviewsNumber.mobile', Arr::get($settings, 'totalReviewsVal', '50'))
            ),
            'starFilterVal'                => (int)Arr::get($settings, 'starFilterVal', -1),
            'filterByTitle'                => $filterByTitle,
            'selectedIncList'              => $selectedIncList,
            'selectedExcList'              => $selectedExcList,
            'includes_inputs'              => sanitize_text_field(Arr::get($settings, 'includes_inputs', '')),
            'excludes_inputs'              => sanitize_text_field(Arr::get($settings, 'excludes_inputs', '')),
            'order'                        => Arr::get($settings, 'order', 'desc'),
            'hide_empty_reviews'           => Arr::get($settings, 'hide_empty_reviews', false),
            'selectedBusinesses'           => Arr::get($settings, 'selectedBusinesses', []),
            'selectedCategories'           => Arr::get($settings, 'selectedCategories', []),

            //header
            'show_header'                  => Arr::get($settings,'show_header', 'false'),
            'display_header_business_logo' => Arr::get($settings, 'display_header_business_logo', true),
            'display_header_business_name' => Arr::get($settings,'display_header_business_name', true),
            'display_header_rating'        => Arr::get($settings,'display_header_rating', true),
            'display_header_reviews'       => Arr::get($settings,'display_header_reviews', true),
            'display_header_write_review'  => Arr::get($settings,'display_header_write_review', true),
            'custom_write_review_text'     => sanitize_text_field(Arr::get($settings,'custom_write_review_text', __('Write a Review','wp-social-reviews'))),
            'add_custom_war_btn_url'       => Arr::get($settings,'add_custom_war_btn_url', false),
            'war_btn_source'               => Arr::get($settings,'war_btn_source', 'custom_url'),
            'war_btn_source_custom_url'    => sanitize_url(Arr::get($settings,'war_btn_source_custom_url', '')),
            'war_btn_open_in_new_window'   => Arr::get($settings,'war_btn_open_in_new_window', 'true'),
            'war_btn_source_form_shortcode_id'  => sanitize_text_field(Arr::get($settings,'war_btn_source_form_shortcode_id', null)),
            'custom_title_text'                 => sanitize_text_field(Arr::get($settings,'custom_title_text', '')),
            'custom_number_of_reviews_text'     => sanitize_text_field(Arr::get($settings,'custom_number_of_reviews_text', __('Based on {total_reviews} Reviews', 'wp-social-reviews'))),
            'display_tp_brand'             => Arr::get($settings, 'display_tp_brand', 'false'),

            //pagination
            'pagination_type'         => Arr::get($settings, 'pagination_type', 'none'),
            'load_more_button_text'   => sanitize_text_field(Arr::get($settings, 'load_more_button_text', __('Load More', 'wp-social-reviews'))),
            'paginate'                => (int)Arr::get($settings,'paginate', '6'),

            //Badge Settings
	        'badge_settings' => array (
		        'template'                  => Arr::get($settings,'badge_settings.template', 'badge1'),
		        'badge_position'            => Arr::get($settings,'badge_settings.badge_position', 'default'),
		        'display_platform_icon'     => Arr::get($settings,'badge_settings.display_platform_icon', 'true'),
		        'custom_title'              => sanitize_text_field(Arr::get($settings,'badge_settings.custom_title', __('Rating', 'wp-social-reviews'))),
                'custom_num_of_reviews_text'=> sanitize_text_field(Arr::get($settings, 'badge_settings.custom_num_of_reviews_text', __('Read our {reviews_count} Reviews', 'wp-social-reviews'))),
                'display_mode'  => Arr::get($settings,'badge_settings.display_mode', 'popup'),
                'url'        => sanitize_url(Arr::get($settings,'badge_settings.url', '')),
                'custom_url' => sanitize_url(Arr::get($settings,'badge_settings.custom_url', '')),
                'form_shortcode_id' => sanitize_text_field(Arr::get($settings,'badge_settings.form_shortcode_id', null)),
                'id'         => Arr::get($settings,'badge_settings.id', ''),
                'open_in_new_window' => Arr::get($settings,'badge_settings.open_in_new_window', 'true'),
	        ),

	        //Notification Settings
            'notification_settings' => array(
                'template'                  => Arr::get($settings,'notification_settings.template', 'notification1'),
                'notification_position'     => Arr::get($settings,'notification_settings.notification_position', 'float_left_bottom'),
                'display_mode'              => Arr::get($settings,'notification_settings.display_mode', 'popup'),
                'custom_url'                => sanitize_url(Arr::get($settings,'notification_settings.custom_url', '')),
                'id'                        => Arr::get($settings,'notification_settings.id', null),
                'url'                       => sanitize_url(Arr::get($settings,'notification_settings.url', '')),
                'page_list'                 => Arr::get($settings,'notification_settings.page_list', array('-1')),
                'exclude_page_list'         => Arr::get($settings,'notification_settings.exclude_page_list', array()),
                'post_types'                => Arr::get($settings,'notification_settings.post_types', array()),
                'hide_on_desktop'           => Arr::get($settings,'notification_settings.hide_on_desktop', 'false'),
                'hide_on_mobile'            => Arr::get($settings,'notification_settings.hide_on_mobile', 'false'),
                'notification_priority'     => Arr::get($settings,'notification_settings.notification_priority', 0),
                'display_close_button'      => Arr::get($settings,'notification_settings.display_close_button', 'true'),
                'display_date'              => Arr::get($settings,'notification_settings.display_date', 'true'),
                'custom_notification_text'  => sanitize_text_field(Arr::get($settings,'notification_settings.custom_notification_text', __('Just left us a {review_rating} star review', 'wp-social-reviews'))),
                'initial_delay'             => (int) Arr::get($settings,'notification_settings.initial_delay', 6000),
                'notification_delay'        => (int) Arr::get($settings,'notification_settings.notification_delay', 5000),
                'delay_for'                 => (int) Arr::get($settings,'notification_settings.delay_for', 5000),
                'display_read_all_reviews_btn'  => Arr::get($settings,'notification_settings.display_read_all_reviews_btn', 'false'),
                'read_all_reviews_btn_url'  => sanitize_url(Arr::get($settings,'notification_settings.read_all_reviews_btn_url', '#')),
            ),
            'enable_schema'         => Arr::get($settings,'enable_schema', 'false'),
            'schema_settings' => array (
                'business_logo'                => Arr::get($settings,'schema_settings.business_logo', ''),
                'business_name'                => sanitize_text_field(Arr::get($settings,'schema_settings.business_name', '')),
                'business_type'                => sanitize_text_field(Arr::get($settings,'schema_settings.business_type', '')),
                'business_telephone'           => sanitize_text_field(Arr::get($settings,'schema_settings.business_telephone', '')),
                'include_business_address'     => Arr::get($settings,'schema_settings.include_business_address', 'false'),
                'business_street_address'      => sanitize_text_field(Arr::get($settings,'schema_settings.business_street_address', '')),
                'business_address_city'        => sanitize_text_field(Arr::get($settings,'schema_settings.business_address_city', '')),
                'business_address_state'       => sanitize_text_field(Arr::get($settings,'schema_settings.business_address_state', '')),
                'business_address_postal_code' => sanitize_text_field(Arr::get($settings,'schema_settings.business_address_postal_code', '')),
                'business_address_country'     => sanitize_text_field(Arr::get($settings,'schema_settings.business_address_country', '')),
                'business_average_rating'      => Arr::get($settings,'schema_settings.business_average_rating', null),
                'business_total_rating'        => Arr::get($settings,'schema_settings.business_total_rating', null),
            ),

	        //styles
            'feed_settings'=> array(
                'enable_style'         => Arr::get($settings,'feed_settings.enable_style', 'false'),
            ),
            'template_width'               => Arr::get($settings,'template_width', ''),
            'template_height'              => Arr::get($settings,'template_height', ''),
            'ai_summary' => array(
                'enabled' => Arr::get($settings,'ai_summary.enabled', 'false'),
                'style'   => Arr::get($settings,'ai_summary.style', 'list'),
                'display_readmore' => Arr::get($settings,'ai_summary.display_readmore', false),
                'text_typing_animation' => Arr::get($settings,'ai_summary.text_typing_animation', true),
                'display_ai_summary_icon' => Arr::get($settings,'ai_summary.display_ai_summary_icon', true),
            ),
        );
    }

    public static function getCarouselSettings($settings)
    {
        $carousel_settings = array(
            'autoplay'                     => Arr::get($settings, 'autoplay', 'false'),
            'autoplay_speed'               => (int)Arr::get($settings,'autoplay_speed', '3000'),
            'slides_to_show'               => (int)Arr::get($settings,'slides_to_show', '3'),
            'slides_to_scroll'             => (int)Arr::get($settings, 'slides_to_scroll', '3'),
            'navigation'                   => Arr::get($settings,'navigation', 'dot'),
        );

        return $carousel_settings;
    }

    public static function validPlatforms($platforms = array())
    {
        $activePlatforms = apply_filters('wpsocialreviews/available_valid_reviews_platforms', []);
        //$activePlatforms = apply_filters('wpsocialreviews/push_testimonial_platform', $activePlatforms);
        //add custom with platforms if custom reviews exists
        $isCustomReviewsExists = Review::where('platform_name', 'custom')
                                       ->count();

        if ($isCustomReviewsExists) {
            $activePlatforms['custom'] = __('Custom', 'wp-social-reviews');
        }

        if (!empty($platforms)) {
            $activePlatforms = array_intersect($platforms, array_keys($activePlatforms));
        }

        return $activePlatforms;
    }


    public static function generateQrCodeArray($id, $name, $url, $custom_url = '', $scan_counter = null)
    {
        return [
            'id' => $id,
            'name' => $name,
            'url' => $url,
            'custom_url' => $custom_url,
            'qrcode_url' => home_url('/?wpsr_qr_code=' . $id),
            'scan_counter' => isset($scan_counter) ? $scan_counter : 0,
        ];
    }

    //public static function addCustomBusinessInfo($business_info, $template_meta)
//    {
//        $platforms = Arr::get($business_info, 'platforms', []);
//        if (!empty($platforms) && in_array('custom', array_column($platforms, 'platform_name'))) {
//            $business_info['platforms']['custom']['url'] = Arr::get($template_meta, 'war_btn_source_custom_url', '');
//            $business_info['total_business'] = count($platforms);
//            $business_info['total_platforms'] = count($platforms);
//        }
//        return $business_info;
//    }

    public static function getBusinessInfoByPlatforms($platforms)
    {
        $multi_business_info = [];
        $platform_urls       = [];
        $avg_rating          = 0;
        $total_rating        = 0;
        $connected_business_info = Helper::getConnectedAccountsBusinessInfo($platforms);

        $value = array_values($connected_business_info);
        $platformNames = array_column($value, 'platform_name');

        $isBooking = false;
        if(in_array('booking.com', $platformNames)) {
            if(count(array_unique($platformNames)) === 1 && end($platformNames) === 'booking.com') {
                $isBooking = true;
            }
        }

        $platforms = 0; $cnt = 0; $url = ""; $platform_name = ""; $total_platforms = count(array_unique($platformNames));
        foreach ($connected_business_info as $index => $business_info) {
            $place_id = Arr::get($business_info, 'place_id');
            $platform_urls[$place_id] = [
                'platform_name' => Arr::get($business_info, 'platform_name'),
                'name'          => Arr::get($business_info, 'name'),
                'url'           => Arr::get($business_info, 'url'),
                'average_rating'=> Arr::get($business_info, 'average_rating'),
                'total_rating'  => Arr::get($business_info, 'total_rating'),
                'product_url'   => Arr::get($business_info, 'platform_name') === 'woocommerce' ? get_the_post_thumbnail_url($place_id) : ''
            ];

            if(!empty(Arr::get($business_info, 'url'))) {
                $cnt++;
                $url = Arr::get($business_info, 'url');
                $platform_name = Arr::get($business_info, 'platform_name');
            } else {
                $total_platforms--;
            }

            $multi_business_info['platforms']  = $platform_urls;

            if(Arr::get($business_info, 'total_rating')) {
                $total_rating += $business_info['total_rating'];
                $multi_business_info['total_rating'] = $total_rating;
            }

            $average_rating = Arr::get($business_info, 'average_rating');
            if ($average_rating) {
                $platforms++;

                if(Arr::get($business_info, 'platform_name') === 'booking.com' && !$isBooking) {
                    $avg_rating += ((float)$average_rating/2);
                } else {
                    $avg_rating += $average_rating;
                }
                $multi_business_info['average_rating'] = $platform_urls && $avg_rating ? $avg_rating / $platforms : $avg_rating;
            }
        }

        $multi_business_info['url'] = $url;
        $multi_business_info['platform_name'] = $platform_name;
        $multi_business_info['total_business'] = $cnt;
        $multi_business_info['total_platforms'] = $total_platforms;
        return apply_filters('wpsocialreviews/reviews_business_info', $multi_business_info);
    }

    public static function getSelectedBusinessInfoByPlatforms($platforms, $selectedBusinesses)
    {
        $cnt = 0; // Reset the count
        $multi_business_info = self::getBusinessInfoByPlatforms($platforms);
        $cnt = Arr::get($multi_business_info, 'total_business', 0);
        $url = Arr::get($multi_business_info, 'url', '');
        $platform_name = Arr::get($multi_business_info, 'platform_name', '');
        $total_platforms = Arr::get($multi_business_info, 'total_platforms', 0);

        if(!empty($selectedBusinesses) && !empty($multi_business_info['platforms'])) {
            $multi_business_info['average_rating'] = 0;
            $multi_business_info['total_rating'] = 0;
            $avg_rating          = 0;
            $total_rating        = 0;
            $platforms = 0;

            $allPlatforms = [];
            foreach($selectedBusinesses as $key => $selected) {
                $platformName = isset($multi_business_info['platforms'][$selected]['platform_name']) ? $multi_business_info['platforms'][$selected]['platform_name'] : '';
                $allPlatforms[] = $platformName;
            }

            $isBooking = false;
            if(in_array('booking.com', $allPlatforms)) {
                if(count(array_unique($allPlatforms)) === 1 && end($allPlatforms) === 'booking.com') {
                    $isBooking = true;
                }
            }

            $total_platforms = count(array_unique($allPlatforms));
            foreach ($multi_business_info['platforms'] as $businessId => $business) {
                if(in_array($businessId, $selectedBusinesses)) {
                    $cnt++; // Increment count for selected businesses
                    $average_rating = Arr::get($business, 'average_rating');
                    if($average_rating) {
                        $platforms++;
                        if (Arr::get($business, 'platform_name') === 'booking.com' && !$isBooking) {
                            $avg_rating += ((float)$average_rating / 2);
                        } else {
                            $avg_rating += $average_rating;
                        }
                        $multi_business_info['average_rating'] = $avg_rating ? $avg_rating / $platforms : $avg_rating;
                    }
                    $total_rating                          += $business['total_rating'];
                    $multi_business_info['total_rating']   = $total_rating;

                    if(!empty(Arr::get($business, 'url'))) {
                        $url = Arr::get($business, 'url');
                        $platform_name = Arr::get($multi_business_info, 'platform_name', '');
                    }
                    else {
                        $total_platforms--;
                    }
                } else {
                    unset($multi_business_info['platforms'][$businessId]);
                }
            }
        }

        if($cnt === 1 && !empty($url)) {
            $multi_business_info['url'] = $url;
            $multi_business_info['platform_name'] = $platform_name;
        }

        $multi_business_info['total_business'] = $cnt;
        $multi_business_info['total_platforms'] = $total_platforms;

        return !empty($multi_business_info) ? $multi_business_info : [];
    }

    public static function platformDynamicClassName($business_info)
    {
        $count = [];
        $platforms = Arr::get($business_info, 'platforms');
        if(empty($platforms)){
            return;
        }

        foreach ($platforms as $index => $platform) {
            $platformName = Arr::get($platform, 'platform_name');
            if(isset($count[$platformName]) && $count[$platformName]){
                continue;
            }
            $count[$platformName] = 1;
        }
        $total_platforms = count($count);

        if($total_platforms === 1){
            $class = array_keys($count)[0];
        } else {
            $class = 'wpsr-has-multiple-reviews-platform';
        }
        return $class;
    }

    public static function convertToPercentage($value) {
        // Extract the decimal part of the value
        $decimalPart = $value - floor($value);

        // Convert the decimal part to a percentage
        $percentage = round($decimalPart * 100);

        return $percentage . '%';
    }

    /**
     * Generate rating SVG icon based on rating value
     *
     * @param $rating
     *
     * @return string
     * @since 1.0.0
     */
    public static function generateRatingIcon($rating, $templateId = null)
    {
        $stars = '';
        $uniqueId = $templateId ? 'rating-' . $templateId : 'rating-default';

        // Generate 5 stars
        for ($i = 0; $i < 5; $i++) {
            $fillPercentage = '0%';

            // Calculate fill percentage for each star
            $score = $rating - $i;
            if ($score >= 1) {
                $fillPercentage = '100%';
            } else if ($score > 0) {
                $fillPercentage = ($score * 100) . '%';
            }

            $stars .= sprintf(
                '<div class="wpsr-star-container %s" style="--wpsr-review-star-fill: %s;">
                    <div class="wpsr-star-empty"></div>
                    <div class="wpsr-star-filled"></div>
                </div>',
                $fillPercentage > 10 ? 'wpsr-star-background-filled' : 'wpsr-star-background-empty',
                $fillPercentage
            );
        }

        return $stars;
    }

    public static function platformIcon($platform_name = '', $size = '')
    {
        $img_size = $size === 'small' ? '-'.$size : '';

        $hidePlatformsIcon = static::getPlatformsWithCategories();
        if(in_array($platform_name, $hidePlatformsIcon)){
            return '';
        }

        return apply_filters('wpsocialreviews/' . $platform_name . '_reviews_platform_icon',
            WPSOCIALREVIEWS_URL . 'assets/images/icon/icon-' . $platform_name . $img_size.'.png');
    }

    public static function removeSpecialChars($text)
    {
        $text = str_replace("&#x27;", "'", $text);
        $text = html_entity_decode($text, ENT_NOQUOTES, 'UTF-8');
        $text = htmlspecialchars_decode($text, ENT_QUOTES);
        $text = wp_specialchars_decode($text);
        return $text;
    }

	public static function getPlatformsWithCategories()
	{
		return apply_filters('wpsocialreviews/platforms_with_categories', ['fluent_forms', 'custom', 'testimonial', 'ai']);
	}

    public static function hasReviewApproved()
    {
        global $wpdb;
        $table_name = $wpdb->prefix .'wpsr_reviews';
        $has_column = GlobalHelper::hasColumn($table_name, 'review_approved');

        return $has_column;
    }

    public static function is_tp($platform_name)
    {
        $trust = substr($platform_name, 0, 5);
        return ($trust === 'trust');
    }

    public static function hideLogo($templateMeta, $platform)
    {
        return Arr::get($templateMeta, 'isPlatformIcon') === 'false' || $platform === 'custom' || $platform === 'fluent_forms' || (static::is_tp($platform) && $templateMeta['display_tp_brand'] === 'false');
    }

    public static function trimProductTitle($reviews)
    {
        if (!empty($reviews)) {
            foreach ($reviews as $index => $review){
                $product_name = Arr::get($review, 'fields.product_name', '');
                $product_data = [];
                $product_data['product_name'] = wp_trim_words($product_name, 4, '...');
                $product_data['product_thumbnail'] = Arr::get($review, 'fields.product_thumbnail', '');
                if($product_name && Arr::get($review, 'fields')){
                    $reviews[$index]['fields'] = $product_data;
                }
            }
        }

        return $reviews;
    }

    public static function getIdsExistReviews($existReviews, $uniqueIdentifierKey)
    {
        $idsExistReviews = $existReviews->pluck($uniqueIdentifierKey)->map(function ($item) {
            return $item;
        })->toArray();

        return $idsExistReviews;
    }

    public static function getIdsCurrentReviews($currentReviews, $reviewIdentifyValue, $platform)
    {
        $idsCurrentReviews = array_map(function ($item) use ($reviewIdentifyValue, $platform) {
                if($platform == 'facebook'){
                    return $reviewIdentifyValue . $item['reviewer']['id'];
                }else if($platform == 'google'){
                    return $item['reviewId'];
                }
                return '';
            }, $currentReviews);

        return $idsCurrentReviews;
    }

    public static function getImageSettings($platform)
    {
        if(empty($platform)) {
            return [];
        }

        $global_settings = get_option('wpsr_'.$platform.'_global_settings');
        $advanceSettings = (new GlobalSettings())->getGlobalSettings('advance_settings');
        $has_gdpr = Arr::get($advanceSettings, 'has_gdpr', "false");
        $image_format = Arr::get($advanceSettings, 'optimize_image_format', 'jpg');
        $optimized_images = $platform === 'reviews'
            ? Arr::get($advanceSettings, 'review_optimized_images', "false") 
            : Arr::get($global_settings, 'global_settings.optimized_images', 'false');

        return [
            'optimized_images' => $optimized_images,
            'has_gdpr' => $has_gdpr,
            'image_format' => $image_format
        ];
    }

    public static function mediaUrlManage($platformName, $resizedImages, $advanceSettings, $imageSize, $filteredReviews,  $isOptimizedImage)
    {
        $mediaManager = new MediaManager($resizedImages, $advanceSettings, $imageSize, $platformName);
        foreach($filteredReviews as $index => $item)
        {
            if ($isOptimizedImage == 'true' && $item->platform_name != 'custom' && $item->platform_name != 'testimonial') {
                $item['media_url']  = $mediaManager->getMediaUri($item);
            } else {
                $item['media_url']  = Arr::get($item, 'reviewer_img');
            }
        }

        return $filteredReviews;
    }

    public static function handleReviewerName($reviews, $templateMeta)
    {
        $shouldShowName = filter_var(Arr::get($templateMeta, 'reviewer_name', false), FILTER_VALIDATE_BOOLEAN);
        $nameFormat = Arr::get($templateMeta, 'reviewer_name_format', 'full-name');

        if (!$shouldShowName) {
            return $reviews;
        }

        foreach ($reviews as &$review) {
            $fullName = trim($review['reviewer_name'] ?? '');

            if (empty($fullName)) {
                $review['reviewer_name'] = '';
                continue;
            }

            $nameParts = preg_split('/\s+/', $fullName);
            $firstName = $nameParts[0] ?? '';
            $lastName = $nameParts[1] ?? '';

            switch ($nameFormat) {
                case 'first-name':
                    $review['reviewer_name'] = $firstName;
                    break;

                case 'first-name-last-initial':
                    $lastInitial = $lastName ? strtoupper(substr($lastName, 0, 1)) . '.' : '';
                    $review['reviewer_name'] = trim("{$firstName} {$lastInitial}");
                    break;

                case 'initials-only':
                    $firstInitial = $firstName ? strtoupper(substr($firstName, 0, 1)) . '.' : '';
                    $lastInitial = $lastName ? strtoupper(substr($lastName, 0, 1)) . '.' : '';
                    $review['reviewer_name'] = trim("{$firstInitial} {$lastInitial}");
                    break;

                case 'full-name':
                default:
                    $review['reviewer_name'] = $fullName;
                    break;
            }
        }
        unset($review);

        return $reviews;
    }

    public static function getValidPlatforms($platforms)
    {
        $validPlatforms = [];
        foreach ($platforms as $platform) {
            if (!empty($platform['name']) && !empty($platform['url'])) {
                $validPlatforms = $platform;
            }
        }
        return $validPlatforms;
    }

    public static function getTemplateMetaByTemplateId($templateId)
    {
        $templateMeta       = get_post_meta($templateId, '_wpsr_template_config', true);
        $decodedMeta        = json_decode($templateMeta, true);
        $formattedMeta      = Helper::formattedTemplateMeta($decodedMeta);

        return $formattedMeta;
    }

    public static function getReviewsDataByTemplateId($templateId, $formattedMeta)
    {
        $reviewsData        = Review::collectReviewsAndBusinessInfo($formattedMeta, $templateId);
        return $reviewsData;
    }

    public static function shouldShowAISummaryIcon($review, $shouldDisplayAISummaryIcon = true, $template_meta = [])
    {
        if($review->platform_name === 'ai' && !$shouldDisplayAISummaryIcon){
            return 'false';
        }

        return $template_meta['reviewer_image'];
    }
}
