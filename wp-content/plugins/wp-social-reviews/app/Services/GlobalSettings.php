<?php

namespace WPSocialReviews\App\Services;
use WPSocialReviews\Framework\Support\Arr;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register a widget that render a feed shortcode
 * @since 1.3.0
 */
class GlobalSettings
{
    public function formatGlobalSettings($settings = array())
    {
        return array(
            'global_settings' => array(
                'translations' => array(
                    'subscribers'       => sanitize_text_field(Arr::get($settings,'global_settings.translations.subscribers')),
                    'following'         => sanitize_text_field(Arr::get($settings,'global_settings.translations.following')),
                    'followers'         => sanitize_text_field(Arr::get($settings,'global_settings.translations.followers')),
                    'videos'            => sanitize_text_field(Arr::get($settings,'global_settings.translations.videos')),
                    'views'             => sanitize_text_field(Arr::get($settings,'global_settings.translations.views')),
                    'tweets'            => sanitize_text_field(Arr::get($settings,'global_settings.translations.tweets')),
                    'people_like_this'  => sanitize_text_field(Arr::get($settings,'global_settings.translations.people_like_this')),
                    'posts'             => sanitize_text_field(Arr::get($settings,'global_settings.translations.posts')),
                    'leave_a_review'    => sanitize_text_field(Arr::get($settings,'global_settings.translations.leave_a_review')),
                    'recommends'        => sanitize_text_field(Arr::get($settings,'global_settings.translations.recommends')),
                    'does_not_recommend' => sanitize_text_field(Arr::get($settings,'global_settings.translations.does_not_recommend')),
                    'on'                => sanitize_text_field(Arr::get($settings,'global_settings.translations.on')),
                    'read_all_reviews'  => sanitize_text_field(Arr::get($settings,'global_settings.translations.read_all_reviews')),
                    'read_more'         => sanitize_text_field(Arr::get($settings,'global_settings.translations.read_more')),
                    'read_less'         => sanitize_text_field(Arr::get($settings,'global_settings.translations.read_less')),
                    'comments'          => sanitize_text_field(Arr::get($settings,'global_settings.translations.comments')),
                    'view_on_fb'        => sanitize_text_field(Arr::get($settings,'global_settings.translations.view_on_fb')),
                    'view_on_ig'        => sanitize_text_field(Arr::get($settings,'global_settings.translations.view_on_ig')),
                    'view_on_tiktok'    => sanitize_text_field(Arr::get($settings,'global_settings.translations.view_on_tiktok')),
                    'likes'             => sanitize_text_field(Arr::get($settings,'global_settings.translations.likes')),
                    'people_responded'  => sanitize_text_field(Arr::get($settings,'global_settings.translations.people_responded')),
                    'online_event'      => sanitize_text_field(Arr::get($settings,'global_settings.translations.online_event')),
	                'interested'        => sanitize_text_field(Arr::get($settings,'global_settings.translations.interested')),
	                'going' 		   => sanitize_text_field(Arr::get($settings,'global_settings.translations.going')),
	                'went' 			   => sanitize_text_field(Arr::get($settings,'global_settings.translations.went')),
	                'ai_generated_summary' 	=> sanitize_text_field(Arr::get($settings,'global_settings.translations.ai_generated_summary')),
                ),
                'advance_settings' => array(
                    'has_gdpr'             => Arr::get($settings,'global_settings.advance_settings.has_gdpr', 'false'),
                    'optimize_image_format' => Arr::get($settings,'global_settings.advance_settings.optimize_image_format', 'jpg'),
                    'review_optimized_images'     => Arr::get($settings,'global_settings.advance_settings.review_optimized_images', 'false'),
                    'preserve_plugin_data' => Arr::get($settings,'global_settings.advance_settings.preserve_plugin_data', 'true'),
                    'email_report' => array(
                        'status'  => Arr::get($settings,'global_settings.advance_settings.email_report.status', 'false'),
                        'sending_day'  => Arr::get($settings,'global_settings.advance_settings.email_report.sending_day', 'Mon'),
                        'recipients'  => Arr::get($settings,'global_settings.advance_settings.email_report.recipients', get_option( 'admin_email', '' )),
                    ),
                    'qr_codes' => Arr::get($settings,'global_settings.advance_settings.qr_codes', []),
                    'ai_platform' => Arr::get($settings,'global_settings.advance_settings.ai_platform', 'OpenRouter'),
                    'ai_api_key' => sanitize_text_field(Arr::get($settings,'global_settings.advance_settings.ai_api_key', '')) ,
                    'selected_model' => Arr::get($settings,'global_settings.advance_settings.selected_model', null),
                )
            )
        );
    }

    public static function getTranslations()
    {
        $settings = get_option('wpsr_global_settings', []);
        $translations_settings = (new self)->formatGlobalSettings($settings);
        return Arr::get($translations_settings, 'global_settings.translations', []);
    }

    public function getGlobalSettings($key)
    {
        $settings = get_option('wpsr_global_settings', []);
        $formattedSettings = $this->formatGlobalSettings($settings);
        return Arr::get($formattedSettings, 'global_settings.'.$key, []);
    }

    public function setGlobalSettingsKeyValue($key, $value)
    {
        $settings = get_option('wpsr_global_settings', []);
        $formattedSettings = $this->formatGlobalSettings($settings);
        $settings = Arr::set($formattedSettings, 'global_settings.'.$key, $value);
        return update_option('wpsr_global_settings', $formattedSettings);
    }

    public function getAISummarizerAPISettingsOptions(){

        $available_ai_platforms = [
            'OpenAI' => 'OpenAI',
            'OpenRouter' => 'OpenRouter'
        ];

        $open_ai_supported_models = [
            'o3-mini' => 'o3-mini',
            'o1' => 'o1',
            'gpt-4o' => 'gpt-4o',
            'gpt-4o-mini' => 'gpt-4o-mini',
        ];

        $open_router_supported_models = [
            'google/gemini-2.0-flash-001' => 'google/gemini-2.0-flash-001',
            'mistralai/mistral-small-24b-instruct-2501' => 'mistralai/mistral-small-24b-instruct-2501',
            'deepseek/deepseek-r1-distill-qwen-32b' => 'deepseek/deepseek-r1-distill-qwen-32b',
            'deepseek/deepseek-r1' => 'deepseek/deepseek-r1',
        ];

        $deepseek_supported_models = [
            'deepseek/deepseek-r1-distill-qwen-32b' => 'deepseek/deepseek-r1-distill-qwen-32b',
            'deepseek/deepseek-r1' => 'deepseek/deepseek-r1',
        ];

        return apply_filters('wpsocialreviews/ai_summarizer_api_settings_option', [
            'available_ai_platforms' => $available_ai_platforms,
            'open_ai_supported_models' => $open_ai_supported_models,
            'open_router_supported_models' => $open_router_supported_models,
            'deepseek_supported_models' => $deepseek_supported_models,
        ]);
    }
}