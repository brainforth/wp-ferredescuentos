<?php

namespace WPSocialReviews\App\Services\Platforms\Feeds\Facebook;

use WPSocialReviews\Framework\Support\Arr;

if (!defined('ABSPATH')) {
    exit;
}

class Helper
{
    public static function getConncetedSourceList()
    {
        $configs = get_option('wpsr_facebook_feed_connected_sources_config', []);
        $sourceList = Arr::get($configs, 'sources') ? $configs['sources'] : [];
        return $sourceList;
    }

    public static function getTotalFeedReactions($feed = [])
    {
        $sum = 0;
        $sum += Arr::get($feed, 'like.summary.total_count', null);
        $sum += Arr::get($feed, 'love.summary.total_count', null);
        $sum += Arr::get($feed, 'wow.summary.total_count', null);
        $sum += Arr::get($feed, 'haha.summary.total_count', null);
        $sum += Arr::get($feed, 'sad.summary.total_count', null);
        $sum += Arr::get($feed, 'angry.summary.total_count', null);
        return $sum;
    }

    public static function secondsToMinutes($time)
    {
       $hours = floor($time / 3600);
       $minutes = floor(($time - floor($time / 3600) * 3600) / 60);
       $seconds = floor($time - floor($time / 60) * 60);

       $value = "";
       if ($hours > 0) {
          $value .= "" . $hours . ":" . ($hours < 10 ? "0" : "");
       }
       $value .= "" . $minutes . ":" . ($seconds < 10 ? "0" : "");
       $value .= "" . $seconds;

       return $value == '0:00' ? '0:01' : $value;
    }

    public static function getSiteUrl($attachment = [], $domain = false)
    {
        $url = Arr::get($attachment, 'target.url');
        if($url){
            $query_str = wp_parse_url($url, PHP_URL_QUERY);
            parse_str($query_str, $query_params);
            $site_url = Arr::get($query_params, 'u');
            if($site_url){
                $host = wp_parse_url($site_url);
                return $domain ? $host['host'] : $site_url;
            }
        } else {
            return false;
        }
    }

    public static function generatePhotoAlbumFeedClass($template_meta = [])
    {
        $display_posts = Arr::get($template_meta, 'filters.display_posts', []);

        if(empty($display_posts) || !is_array($display_posts)){
            return '';
        }

        $allowed_combinations = [
            ['photo', 'album'],
            ['photo'],
            ['album']
        ];
        sort($display_posts);

        $class = '';
        foreach ($allowed_combinations as $allowed) {
            sort($allowed);
            if ($display_posts === $allowed) {
                $class = 'wpsr-fb-feed-item-zero-padding';
                break;
            }
        }

        return $class;
    }

    public static function getSourceIDFromCache($cache)
    {
        $cacheValue = Arr::get($cache, 'option_value');
        $firstValue = (count($cacheValue) > 0) ? $cacheValue[0] : '';

        return Arr::get($firstValue, 'from.id', '');
    }

    public static function validateAndRetrieveSingleVideoPlaylistId($singleVideoPlayListURL){

        if (empty($singleVideoPlayListURL)) {
            return false;
        }

        if(is_numeric($singleVideoPlayListURL)){
            return $singleVideoPlayListURL;
        }

        // the url must follow the pattern
        // https://www.facebook.com/watch/100091416590270/1336242490940769
        $url = parse_url($singleVideoPlayListURL);
        if ($url['host'] === 'www.facebook.com' || $url['host'] === 'facebook.com') {
            $pattern = '/\/watch\/(\d+)\/(\d+)(?:\/)?$/';
            if (preg_match($pattern, $singleVideoPlayListURL, $matches)) {
            return $matches[2];
            }
        }
        return false;
    }

    public static function validateAndRetrieveSingleAlbumId($feedType, $singleAlbumId)
    {
        if ($feedType === 'single_album_feed') {
            if (empty($singleAlbumId)) {
                return null;
            }

            if (!is_numeric($singleAlbumId) && !filter_var($singleAlbumId, FILTER_VALIDATE_URL)) {
                return null;
            }

            // if the single album id is a url then extract id from the url;
            return static::getAlbumId($singleAlbumId);
        }

        return null;
    }

    public static function getAlbumId($value){

        if(is_numeric($value)){
            return $value;
        }

        // example URL: https://www.facebook.com/media/set/?set=a.145555168501702&type=3

        $url = parse_url($value);

        if($url['host'] === 'www.facebook.com' || $url['host'] === 'facebook.com'){
            $pattern = '/set=a\.(\d+)/';
            if (preg_match($pattern, $value, $matches)) {
                return $matches[1];
            }

            return null;
        }
        
    }
}