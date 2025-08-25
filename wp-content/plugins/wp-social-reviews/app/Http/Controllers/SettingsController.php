<?php

namespace WPSocialReviews\App\Http\Controllers;

use WPSocialReviews\App\Services\TranslationString;
use WPSocialReviews\App\Services\Platforms\Reviews\Helper;
use WPSocialReviews\Framework\Support\Arr;
use WPSocialReviews\Framework\Request\Request;
use WPSocialReviews\App\Services\GlobalSettings;
use WPSocialReviews\App\Services\Platforms\PlatformManager;
use WPSocialReviews\App\Hooks\Handlers\UninstallHandler;
use WPSocialReviews\App\Services\DataProtector;

class SettingsController extends Controller
{
    public function index(Request $request)
    {
        $platform = $request->get('platform');

        if((!defined('WC_VERSION') && $platform === 'woocommerce') || (!defined('CUSTOM_FEED_FOR_TIKTOK') && $platform === 'tiktok')){
           return false;
        }

        do_action('wpsocialreviews/get_advance_settings_' . $platform);
    }

    public function update(Request $request)
    {
        $platform = $request->get('platform');
        $settingsJSON = $request->get('settings');
        $settings = json_decode($settingsJSON, true);
        $settings = wp_unslash($settings);
        do_action('wpsocialreviews/save_advance_settings_' . $platform, $settings);
    }

    public function delete(Request $request)
    {
        $platform = $request->get('platform');
        $cacheType = $request->get('cacheType');
        do_action('wpsocialreviews/clear_cache_' . $platform, $cacheType);
    }

    public function getFluentFormsSettings(Request $request)
    {
        $platform = 'fluent_forms';
        do_action('wpsocialreviews/get_advance_settings_' . $platform);
    }

    public function saveFluentFormsSettings(Request $request)
    {
        $platform = 'fluent_forms';
        $settingsJSON = $request->get('settings');
        $settings = json_decode($settingsJSON, true);
        $settings = wp_unslash($settings);
        do_action('wpsocialreviews/save_advance_settings_' . $platform, $settings);
    }

    public function deleteTwitterCard()
    {
        delete_option('wpsr_twitter_cards_data');

        return [
            'success' => 'success',
            'message' => __('Card Data Deleted Successfully!', 'wp-social-reviews')
        ];
    }

    public function getLicense(Request $request)
    {
        $response = apply_filters('wpsr_get_license', false, $request);
        if(!$response) {
            return $this->sendError([
                'message' => __('Sorry! License could not be retrieved. Please try again', 'wp-social-reviews')
            ]);
        }

        return $response;
    }

    public function removeLicense(Request $request)
    {
        $response = apply_filters('wpsr_deactivate_license', false, $request);
        if(!$response) {
            return $this->sendError([
                'message' => __('Sorry! License could not be removed. Please try again', 'wp-social-reviews')
            ]);
        }

        return $response;
    }

    public function addLicense(Request $request)
    {
        $response = apply_filters('wpsr_activate_license', false, $request);
        if(!$response) {
            return $this->sendError([
                'message' => __('Sorry! License could not be added. Please try again', 'wp-social-reviews')
            ]);
        }

        return $response;
    }

    public function getTranslations()
    {
        $translationsSettings = TranslationString::getStrings();

        return [
            'message'               => 'success',
            'translations_settings' => $translationsSettings
        ];
    }

    public function saveTranslations(Request $request)
    {
        $translationsSettings = $request->get('translations_settings');
        $settings = get_option('wpsr_global_settings', []);
        $settings['global_settings']['translations'] = $translationsSettings;

        $globalSettings = (new GlobalSettings())->formatGlobalSettings($settings);

        update_option('wpsr_global_settings', $globalSettings);

        return [
            'message'   =>  __('Settings saved successfully!', 'wp-social-reviews')
        ];
    }

    public function getAdvanceSettings(DataProtector $protector)
    {
        $advanceSettings = (new GlobalSettings())->getGlobalSettings('advance_settings');

        return [
            'message'           => 'success',
            'advance_settings'  => $advanceSettings,
            'ai_summarizer_settings_options' => $this->getAISummarizerAPISettingsOptions()
       ];
    }

    public function getAISummarizerAPISettingsOptions(){
        return (new GlobalSettings())->getAISummarizerAPISettingsOptions();
    }

    public function saveAdvanceSettings(Request $request, DataProtector $protector)
    {
        $advanceSettings = $request->get('advance_settings');
        $settings = get_option('wpsr_global_settings', []);

        $oldOptimizeImageFormat = Arr::get($settings, 'global_settings.advance_settings.optimize_image_format', '');
        $newOptimizeImageFormat = Arr::get($advanceSettings, 'optimize_image_format');

        if ($newOptimizeImageFormat != $oldOptimizeImageFormat) {
            $this->resetDataForOptimizeFormatChange($request);
        }

        $settings['global_settings']['advance_settings'] = $advanceSettings;
        $optimized_images = Arr::get($settings, 'global_settings.advance_settings.review_optimized_images', 'false');

        if($optimized_images == 'true') {
            $has_wpsr_optimize_images_table = get_option( 'wpsr_optimize_images_table_status', false);
            $older_version = get_option('_wp_social_ninja_version', '3.14.2');
            if(version_compare($older_version, '3.15.0', '<=') && $optimized_images === 'true' && !$has_wpsr_optimize_images_table){
                \WPSocialReviews\Database\Migrations\ImageOptimizationMigrator::migrate();
            }
        }

        $settings['global_settings']['advance_settings']['ai_api_key'] = $protector->encrypt($advanceSettings['ai_api_key']);
        $globalSettings = (new GlobalSettings())->formatGlobalSettings($settings);
        update_option('wpsr_global_settings', $globalSettings);

        return [
            'message'   =>  __('Settings saved successfully!', 'wp-social-reviews')
        ];
    }

    public function resetDataForOptimizeFormatChange(Request $request)
    {
        $manager = new PlatformManager();
        $platforms = $manager->getPlatformsListWithReviewAlias();
        foreach ($platforms as $platform) {
            $platformRequest = clone $request;
            $platformRequest->merge(['platform' => $platform]);
            $this->resetData($platformRequest);
        }
    }

    public function resetData(Request $request)
    {
        $platform = sanitize_text_field($request->get('platform'));

        if($platform == 'reviews'){
            $platforms = apply_filters('wpsocialreviews/available_valid_reviews_platforms', []);
            do_action('wpsocialreviews/review_reset_data', $platforms);
        }else{
            do_action('wpsocialreviews/reset_data', $platform);
        }

        return [
            'message'   =>  __('Images reset successfully!', 'wp-social-reviews')
        ];
    }

    public function resetErrorLog(Request $request)
    {
        delete_option('wpsr_errors');
        return [
            'message'   =>  __('Reset Error Logs successfully!', 'wp-social-reviews')
        ];
    }

    public function deleteAllData()
    {
        $isTableDelete = false;
        (new UninstallHandler())->deleteAllPlatformsData($isTableDelete);
        return [
            'message'   =>  __('Successfully deleted all datas!', 'wp-social-reviews')
        ];
    }

    public function getReviewCollectionQrCodes(Request $request){

        $qrCodes = (new GlobalSettings())->getGlobalSettings('advance_settings.qr_codes');


        // qrcodes is an associative array convert it to a regular array
        $qrCodes = array_values($qrCodes);
        if(empty($qrCodes)){
            return $this->sendError([
                'message' => __('Sorry! QR codes could not be retrieved. Please try again', 'wp-social-reviews')
            ]);
        }
        return [
            'message'   =>  __('QR codes retrieved successfully!', 'wp-social-reviews'),
            'data'      =>  $qrCodes
        ];
    }

    private function validateQrCodeData($name, $collection_form, $custom_url = '')
    {
        if (empty($name)) {
            return $this->sendError([
                'message' => __('Name cannot be empty.', 'wp-social-reviews')
            ], 400);
        }

        if (strlen($name) > 25) {
            return $this->sendError([
                'message' => __('Name cannot be more than 25 characters.', 'wp-social-reviews')
            ], 400);
        }

        if ($collection_form === 'custom-url') {
            if (empty($custom_url)) {
                return $this->sendError([
                    'message' => __('Custom URL cannot be empty.', 'wp-social-reviews')
                ], 400);
            }
            $urlToValidate = $custom_url;
        } else {
            $urlToValidate = $collection_form;
        }

        if (!filter_var($urlToValidate, FILTER_VALIDATE_URL)) {
            return $this->sendError([
                'message' => __('Invalid URL format. Please provide a valid URL.', 'wp-social-reviews')
            ], 400);
        }

        return true;
    }

    public function createReviewCollectionQrCode(Request $request)
    {
        $qrCodes = (new GlobalSettings())->getGlobalSettings('advance_settings.qr_codes');
        $id = $qrCodes ? (max(array_keys($qrCodes)) + 1) : 1;

        $name = $request->get('name');
        $collection_form = $request->get('collection_form');
        $custom_url = $collection_form === 'custom-url' ? $request->get('custom_url') : '';

        $validation = $this->validateQrCodeData($name, $collection_form, $custom_url);
        if ($validation !== true) {
            return $validation;
        }

        $qrCode = Helper::generateQrCodeArray($id, $name, $collection_form, $custom_url);
        $qrCodes[$id] = $qrCode;

        if ((new GlobalSettings())->setGlobalSettingsKeyValue('advance_settings.qr_codes', $qrCodes)) {
            return [
                'message' => __('QR code generated successfully!', 'wp-social-reviews'),
                'data' => $qrCode
            ];
        }

        return $this->sendError([
            'message' => __('Sorry! QR code could not be generated. Please try again', 'wp-social-reviews')
        ], 400);
    }

    public function updateReviewCollectionQrCode(Request $request, $id)
    {
        $qrCodes = (new GlobalSettings())->getGlobalSettings('advance_settings.qr_codes');

        if (!isset($qrCodes[$id])) {
            return $this->sendError([
                'message' => __('QR code not found.', 'wp-social-reviews')
            ], 404);
        }

        $name = $request->get('name');
        $url = $request->get('url');
        $custom_url = $url === 'custom-url' ? $request->get('custom_url') : '';

        $validation = $this->validateQrCodeData($name, $url, $custom_url);
        if ($validation !== true) {
            return $validation;
        }
        $existingQrCodeScans = null;
        if(isset($qrCodes[$id])){
            $existingQrCodeScans = $qrCodes[$id]['scan_counter'] ?? null;
        }
        
        $qrCode = Helper::generateQrCodeArray($id, $name, $url, $custom_url, $existingQrCodeScans);
        $qrCodes[$id] = $qrCode;

        if ((new GlobalSettings())->setGlobalSettingsKeyValue('advance_settings.qr_codes', $qrCodes)) {
            return [
                'message' => __('QR code updated successfully!', 'wp-social-reviews'),
                'data' => $qrCode
            ];
        }

        return $this->sendError([
            'message' => __('Sorry! QR code could not be updated. Please try again', 'wp-social-reviews')
        ], 400);
    }

    public function deleteReviewCollectionQrCode(Request $request, $id){

        $qrCodes = (new GlobalSettings())->getGlobalSettings('advance_settings.qr_codes');
        unset($qrCodes[$id]);

        if((new GlobalSettings())->setGlobalSettingsKeyValue('advance_settings.qr_codes', $qrCodes)){
            return [
                'message'   =>  __('QR code deleted successfully!', 'wp-social-reviews')
            ];
        } else {
            return $this->sendError([
                'message' => __('Sorry! QR code could not be deleted. Please try again', 'wp-social-reviews')
            ]);
        }
    }

    public function getReviewCollectionPlatforms(Request $request){
        $reviewsPlatforms   = apply_filters('wpsocialreviews/available_valid_reviews_platforms', []);
        $allBusinessInfo    = Helper::getBusinessInfoByPlatforms($reviewsPlatforms);

        if(isset($allBusinessInfo['total_platforms']) && $allBusinessInfo['total_platforms'] == 0){
            return [
                'message'   =>  __('No platforms found!', 'wp-social-reviews'),
                'data'      =>  []
            ];
        }

        $availablePlatforms = array_values($allBusinessInfo['platforms']);
        return [
            'message'   =>  __('Platforms BusinessInfo', 'wp-social-reviews'),
            'data'      =>  $availablePlatforms
        ];
    }
}