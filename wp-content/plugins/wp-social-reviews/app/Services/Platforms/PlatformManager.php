<?php
namespace WPSocialReviews\App\Services\Platforms;

use WPSocialReviews\Framework\Support\Arr;

class PlatformManager
{
    private $feed_platforms = ['instagram', 'twitter', 'youtube', 'facebook_feed' , 'tiktok'];
    private $reviews_platforms = [
        'google',
        'airbnb',
        'yelp',
        'tripadvisor',
        'amazon',
        'aliexpress',
        'booking.com',
        'facebook',
        'woocommerce'
    ];
    /**
     * Set all feed platform name.
     *
     * @return array
     */
    public function feedPlatforms()
    {
        return $this->feed_platforms;
    }

    /**
     *  Set all review platform name.
     *
     * @return array
     */
    public function reviewsPlatforms()
    {
        return apply_filters('wpsocialreviews/reviews_platforms', $this->reviews_platforms);
    }

    public function getPlatformOfficialName($platform = '', $returnWithType = false)
    {
        if(empty($platform)){
            return;
        }

        $formattedPlatformName = str_replace( '_feed', '', ucfirst($platform) );
        $platformName = $platform === 'facebook' ? __('Facebook', 'wp-social-reviews') : $formattedPlatformName;
        $platformType = $platform === 'facebook' ? __(' Reviews', 'wp-social-reviews') : __(' Feed', 'wp-social-reviews');

        if($returnWithType){
            $platform = $platformName.$platformType;
        }

        return $platform;
    }

    public function isActivePlatform($platform)
    {
        if(in_array($platform, $this->feed_platforms)) {
            if ( $platform === 'tiktok' ) {
                return get_option('wpsr_' . $platform . '_connected_sources_config');
            }
            return get_option('wpsr_' . $platform . '_verification_configs');
        } else {
            return get_option('wpsr_reviews_' . $platform . '_settings');
        }
    }

    public function getConnectedSourcesConfigs($platformName)
    {
        if(empty($platformName)){
            return;
        }

        $connectedSourcesConfig = [];
        if (in_array($platformName, $this->feed_platforms)) {
            switch ($platformName) {
                case 'facebook_feed':
                    $connectedSourcesConfig = get_option('wpsr_facebook_feed_connected_sources_config', []);
                    $connectedSourcesConfig = Arr::get($connectedSourcesConfig, 'sources', []);
                    break;
                case 'instagram':
                    $connectedSourcesConfig = get_option('wpsr_' . $platformName . '_verification_configs', []);
                    $connectedSourcesConfig = Arr::get($connectedSourcesConfig, 'connected_accounts', []);
                    break;
                case 'tiktok':
                    $connectedSourcesConfig = get_option('wpsr_tiktok_connected_sources_config', []);
                    $connectedSourcesConfig = Arr::get($connectedSourcesConfig, 'sources', []);
                    break;
            }
        } else {
            $connectedSourcesConfig = get_option('wpsr_reviews_' . $platformName . '_settings');
        }

        return $connectedSourcesConfig;
    }

    public function getFeedVerificationConfigsBySourceId($platformName, $configsSources, $selectedAccounts)
    {
        if (empty($selectedAccounts) || !is_array($configsSources)) {
            return $configsSources;
        }

        $filteredConfigs = array_filter($configsSources, function ($config) use ($platformName, $selectedAccounts) {
            if($platformName === 'instagram'){
                $source_id = Arr::get($config, 'user_id', null);
            }elseif($platformName === 'tiktok'){
                $source_id = Arr::get($config, 'open_id', null);
            } else {
                $source_id = Arr::get($config, 'page_id', null);
            }

            return in_array($source_id, $selectedAccounts);
        });

        return array_intersect_key($configsSources, $filteredConfigs);
    }

    public function getFeedVerificationConfigs($platformName)
    {
        if (empty($platformName)){
            return;
        }

        $verificationConfigs = [];

        if (in_array($platformName, $this->feed_platforms)) {
            $optionKey = 'wpsr_' . $platformName . '_verification_configs';
            switch ($platformName) {
                case 'tiktok':
                    $optionKey = 'wpsr_tiktok_connected_sources_config';
                    break;
            }
            $verificationConfigs = get_option($optionKey, []);
        }

        return $verificationConfigs;
    }

    public function getSelectedFeedAccounts($platformName, $metaData)
    {
        if ($platformName === 'instagram') {
            return Arr::get($metaData, 'feed_settings.source_settings.account_ids', []);
        }
        return Arr::get($metaData, 'feed_settings.source_settings.selected_accounts', []);
    }

    public function getUserName($feed, $platform_name)
    {
        switch ($platform_name) {
            case 'instagram':
                return Arr::get($feed, 'username', '');
            case 'facebook_feed':
                return Arr::get($feed, 'page_id', '');
            case 'tiktok':
                return Arr::get($feed, 'user.name', '');
            case 'youtube':
                return Arr::get($feed, 'snippet.channelId', '');
            default:
                return Arr::get($feed, 'source_id', '');
        }
    }

    public function getPlatformsListWithReviewAlias()
    {
        $feedPlatforms = $this->feedPlatforms();
        $reviewsPlatforms = ['reviews'];

        return array_merge($feedPlatforms, $reviewsPlatforms);
    }
}

