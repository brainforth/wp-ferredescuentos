<?php

namespace WPSocialReviews\App\Http\Controllers\Platforms\Reviews;

use Exception;
use WPSocialReviews\App\App;
use WPSocialReviews\App\Http\Controllers\Controller;
use WPSocialReviews\Framework\Database\Orm\Collection;
use WPSocialReviews\Framework\Request\Request;
use WPSocialReviews\App\Models\Review;
use WPSocialReviews\App\Services\Platforms\Reviews\Helper;
use WPSocialReviews\App\Services\Helper as GlobalHelper;
use WPSocialReviews\Framework\Support\Arr;
use WPSocialReviews\App\Services\GlobalSettings;
use WPSocialReviews\App\Services\Platforms\MediaManager;
use WPSocialReviews\App\Services\Platforms\Reviews\Config as ReviewConfig;
use WPSocialReviews\App\Services\Platforms\ReviewImageOptimizationHandler;
use WPSocialReviews\App\Services\Includes\CountryNames;

class MetaController extends Controller
{
    public function index($templateId, $isFirstRound = false)
    {
        $reviewConfig = new ReviewConfig();
        $templateDetails    = get_post($templateId);
        $feed_template_style_meta = get_post_meta($templateId, '_wpsr_template_styles_config', true);

        $formattedMeta = Helper::getTemplateMetaByTemplateId($templateId);
        $reviewsData = Helper::getReviewsDataByTemplateId($templateId, $formattedMeta);

        $currentPlatforms   = Arr::get($formattedMeta, 'platform', array());
        $allBusinessInfo    = Helper::getBusinessInfoByPlatforms($currentPlatforms);
		$categories         = Review::getCategories();
        $pages           = GlobalHelper::getPagesList();
        $postTypes       = GlobalHelper::getPostTypes();
        $countryList     = (new CountryNames())->getCountries();

        $formattedMeta['styles_config']    = $reviewConfig->formatStylesConfig(json_decode($feed_template_style_meta, true), $templateId);

        $resizedImages        = [];
        $imageHandlerObj = new ReviewImageOptimizationHandler($currentPlatforms);
        $advanceSettings = Helper::getImageSettings('reviews');

        $optimized_image = Arr::get($advanceSettings, 'optimized_images', 'false');
        $has_gdpr = Arr::get($advanceSettings, 'has_gdpr', "false");
        $filtered_reviews = Arr::get($reviewsData, 'filtered_reviews', []);

        if ($optimized_image === 'true' && count($filtered_reviews)) {
            $resizedImages = $imageHandlerObj->getResizeNeededImageLists($filtered_reviews);
        }

        if ($has_gdpr === "true" && $optimized_image === "false") {
            $reviewsData['all_reviews'] = [];
            $filtered_reviews = [];
            $reviewsData['errors'][] = [
                'error_message' => $imageHandlerObj->getOptimizeErrorMessage()
            ];
        }


        $imageSize = Arr::get($formattedMeta, 'resolution', 'full');
        $filtered_reviews = Helper::mediaUrlManage('reviews', $resizedImages, $advanceSettings, $imageSize, $filtered_reviews, $optimized_image); // hardcode 'reviews' denotes its a platform

        $business_info = Review::formatBusinessInfo($reviewsData);
        // Add custom business info if custom platform exists
        //$business_info = Helper::addCustomBusinessInfo($business_info, $formattedMeta);

        $needsImmediateUpdate = false;
        $proAvailable = defined('WPSOCIALREVIEWS_PRO');
        $canEnableAISummary = false;
        if(
            $proAvailable &&
            class_exists('\WPSocialReviewsPro\App\Services\AI\AIReviewSummarizerHelper')
        ){
            $canEnableAISummary = \WPSocialReviewsPro\App\Services\AI\AIReviewSummarizerHelper::isAIModelAndApikeySet();
            $AISummaryData = \WPSocialReviewsPro\App\Services\AI\AIReviewSummarizerHelper::shouldIncludeAISummary($filtered_reviews, $templateId, $formattedMeta, $isFirstRound);

            $filtered_reviews = Arr::get($AISummaryData, 'filtered_reviews');
            $aiSummaryError = Arr::get($AISummaryData, 'aiSummaryError', '');
            $infos = Arr::get($AISummaryData, 'infos', []);
            // if this is not set to false it will trigger an immediate edit request by the frontend.
            $needsImmediateUpdate = Arr::get($AISummaryData, 'needsImmediateUpdate', false);
        }

        $data = [
            'message'            => 'success',
            'template_id'        => $templateId,
            'business_info'      => $business_info,
            'all_reviews'        => $reviewsData['all_reviews'],
            'filtered_reviews'   => $filtered_reviews,
            'template_details'   => $templateDetails,
            'template_meta'      => $formattedMeta,
            'pages'              => $pages,
            'country_list'       => $countryList,
            'post_types'         => $postTypes,
            'all_business_info'  => $allBusinessInfo,
	        'categories'         => $categories,
            'elements'           => $reviewConfig->getStyleElement(),
            'errors'             => Arr::get($reviewsData, 'errors'),
            'resized_images'     => $resizedImages,
            'image_settings'     => $advanceSettings,
            'needs_immediate_update' => $needsImmediateUpdate,
            'can_enable_ai_summary' => $canEnableAISummary,
        ];

        if(isset($aiSummaryError) && $aiSummaryError){
            $data['ai_summary_errors'] = $aiSummaryError;
        }

        if (isset($infos) && count($infos) > 0) {
            $data['infos'] = $infos;
        }

        return $data;
    }

    public function canUserEnableAISummary(Request $request, $templateId)
    {
        $proAvailable = defined('WPSOCIALREVIEWS_PRO');
        if (
            $proAvailable &&
            class_exists('\WPSocialReviewsPro\App\Services\AI\AIReviewSummarizerHelper') &&
            \WPSocialReviewsPro\App\Services\AI\AIReviewSummarizerHelper::isAIModelAndApikeySet()
        ) {
            return $this->sendSuccess([
                'can_enable' => true,
                'message' => __("AI summary enabled.", 'wp-social-reviews')
            ]);
        }

        $message = __("AI summary cannot be enabled because the model is not selected or the API key is missing.", 'wp-social-reviews');

        if(!$proAvailable){
            $message = __('WP Social Ninja Pro is required to enable AI summary.', 'wp-social-reviews');
        }

        return $this->sendError([
            'can_enable' => false,
            'message' => $message
        ]);
    }

    public function update(Request $request, $templateId)
    {
        $templateMeta = wp_unslash($request->get('template_meta'));
        $templateMeta = json_decode($templateMeta, true);
        if(
            defined('WPSOCIALREVIEWS_PRO') &&
            class_exists('\WPSocialReviewsPro\App\Services\TemplateCssHandler')){
            (new \WPSocialReviewsPro\App\Services\TemplateCssHandler())->saveCss($templateMeta, $templateId);
        }

        do_action('wpsocialreviews/template_meta_data', $templateId, $templateMeta);

        if(Arr::get($templateMeta, 'templateType') === 'badge' && !empty(Arr::get($templateMeta, 'badge_settings'))) {
            $url = $this->getUrl($templateMeta['badge_settings']);
            $templateMeta['badge_settings']['url'] = $url;
        }

        if(Arr::get($templateMeta, 'templateType') === 'notification' && !empty(Arr::get($templateMeta, 'notification_settings'))) {
            $url = $this->getUrl($templateMeta['notification_settings']);
            $templateMeta['notification_settings']['url'] = $url;
        }

        $formattedMeta      = Helper::formattedTemplateMeta($templateMeta);

        if($formattedMeta['templateType'] === 'notification') {
            unset($formattedMeta['badge_settings']);
            if (isset($formattedMeta['notification_settings'])) {
                $menuOrder = $formattedMeta['notification_settings']['notification_priority'];
                $db = App::getInstance('db');

                $db->table('posts')->where('ID', $templateId)
                    ->update([
                        'menu_order' => absint($menuOrder)
                    ]);
            }
        } else {
            unset($formattedMeta['notification_settings']);
        }

        $unsetKeys = ['styles_config', 'styles', 'responsive_styles'];
        foreach ($unsetKeys as $key){
            if(Arr::get($templateMeta, $key, false)){
                unset($templateMeta[$key]);
            }
        }

        $encodedMeta = json_encode($formattedMeta, JSON_UNESCAPED_UNICODE);
        update_post_meta($templateId, '_wpsr_template_config', $encodedMeta);

        $platforms = Arr::get($formattedMeta, 'platform', []);
        $platforms = implode(',', $platforms);

        $postData = [
            'ID'            => $templateId,
            'post_content'  => $platforms
        ];

        wp_update_post($postData);
        $updatedMeta = get_post_meta($templateId, '_wpsr_template_config', true);
        $decodedMeta = json_decode($updatedMeta);

        return [
            'message'       => __("Template saved successfully!!", 'wp-social-reviews'),
            'template_id'   => $templateId,
            'template_meta' => $decodedMeta,
        ];
    }

    public function edit(Request $request, $templateId)
    {
        $templateMeta = wp_unslash($request->get('template_meta'));
        $templateMeta = json_decode($templateMeta, true);
	    $currentPlatforms  = $templateMeta['platform'];
	    if (empty($templateMeta['platform'])) {
		    $templateMeta['filterByTitle']   = 'all';
		    $templateMeta['selectedExcList'] = [];
		    $templateMeta['selectedIncList'] = [];
	    }

        if((Arr::get($templateMeta, 'starFilterVal') === 11) || (!in_array('booking.com', $currentPlatforms) && Arr::get($templateMeta, 'starFilterVal') >= 6)) {
            $templateMeta['starFilterVal']  = -1;
        }

        if(Arr::get($templateMeta, 'templateType') === 'badge' && !empty(Arr::get($templateMeta, 'badge_settings'))) {
            $url = $this->getUrl($templateMeta['badge_settings']);
            $templateMeta['badge_settings']['url'] = $url;
        }

        if(Arr::get($templateMeta, 'templateType') === 'notification' && !empty(Arr::get($templateMeta, 'notification_settings'))) {
            $url = $this->getUrl($templateMeta['notification_settings']);
            $templateMeta['notification_settings']['url'] = $url;
        }

        $templateDetails    = get_post($templateId);
        $reviewsData        = Review::collectReviewsAndBusinessInfo($templateMeta, $templateId);
        $templateMeta       = Review::modifyIncludeAndExclude($templateMeta, $reviewsData);
        $allBusinessInfo    = Helper::getBusinessInfoByPlatforms($currentPlatforms);

        $resizedImages        = [];
        $imageHandlerObj = new ReviewImageOptimizationHandler($currentPlatforms);
        $advanceSettings = Helper::getImageSettings('reviews');
        $filtered_reviews = Arr::get($reviewsData, 'filtered_reviews', []);
        $all_reviews = Arr::get($reviewsData, 'all_reviews', []);
        $optimized_image = Arr::get($advanceSettings, 'optimized_images', 'false');
        $has_gdpr = Arr::get($advanceSettings, 'has_gdpr', "false");

        if ($optimized_image === 'true' && count($filtered_reviews)) {
            $resizedImages = $imageHandlerObj->getResizeNeededImageLists($filtered_reviews);
        }

        if($has_gdpr == "true" && $optimized_image === "false") {
            $filtered_reviews = [];
            $all_reviews = [];
        }

        $imageSize = Arr::get($templateMeta, 'resolution', 'full');
        $filtered_reviews = Helper::mediaUrlManage('reviews', $resizedImages, $advanceSettings, $imageSize, $filtered_reviews, $optimized_image); // hardcode 'reviews' denotes its a platform

        $business_info = Review::formatBusinessInfo($reviewsData);
        // Add custom business info if custom platform exists
       // $business_info = Helper::addCustomBusinessInfo($business_info, $templateMeta);

        $errors = [];
        $forceRegenerate = Arr::get($templateMeta, 'ai_summary.force_regenerate', false);

        $proAvailable = defined('WPSOCIALREVIEWS_PRO');
        if(
            $proAvailable &&
            class_exists('\WPSocialReviewsPro\App\Services\AI\AIReviewSummarizerHelper')
        ){
            $AISummaryData = \WPSocialReviewsPro\App\Services\AI\AIReviewSummarizerHelper::shouldIncludeAISummary($filtered_reviews, $templateId, $templateMeta, false, $forceRegenerate);

            $filtered_reviews = Arr::get($AISummaryData, 'filtered_reviews');
            $aiSummaryError = Arr::get($AISummaryData, 'aiSummaryError', '');
            $infos = Arr::get($AISummaryData, 'infos', []);
        }


        $data = [
            'has_gdpr' => $has_gdpr,
            'optimized_image' => $optimized_image,
            'image_settings'     => $advanceSettings,
            'message'            => 'success',
            'template_id'        => $templateId,
            'filtered_reviews'   => $filtered_reviews,
            'all_reviews'        => $all_reviews,
            'business_info'      => $business_info,
            'template_details'   => $templateDetails,
            'template_meta'      => $templateMeta,
            'all_business_info'  => $allBusinessInfo,
            'resize_data'        => $resizedImages,
            'errors'             => $errors
        ];

        if(isset($aiSummaryError) && $aiSummaryError){
            $data['ai_summary_errors'] = $aiSummaryError;
        }

        if (isset($infos) && count($infos) > 0) {
            $data['infos'] = $infos;
        }

        return $data;
    }

    public function getUrl($template_meta)
    {
        $display_mode = Arr::get($template_meta, 'display_mode');
        $url = Arr::get($template_meta, 'url');

        if($display_mode === 'custom_url') {
            $url = Arr::get($template_meta,'custom_url', '');
        }

        else if($display_mode === 'page') {
            $id = Arr::get($template_meta,'id', '');
            if($id) {
                $url = get_the_permalink($id);
            }
        }

        return $url;
    }

}