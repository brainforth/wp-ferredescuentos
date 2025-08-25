<?php

namespace WPSocialReviews\App\Services\Platforms\Feeds\Instagram;

use WPSocialReviews\App\Services\DataProtector;
use WPSocialReviews\App\Services\Platforms\Feeds\Instagram\Common;
use WPSocialReviews\App\Services\Platforms\Feeds\CacheHandler;
use WPSocialReviews\App\Services\Platforms\Feeds\Instagram\RefreshToken;
use WPSocialReviews\Framework\Support\Arr;

if (!defined('ABSPATH')) {
    exit;
}

class AccountFeed
{
    protected $cacheHandler;

    public function __construct()
    {
        $this->cacheHandler = new CacheHandler('instagram');
    }

    /**
     * Get Accounts Feed
     *
     * @param array $accountIds
     *
     * @return array
     * @since 1.3.0
     */
    public function getMultipleAccountResponse($accountIds)
    {
        $response          = array();
        $connectedAccounts = (new Common())->findConnectedAccounts();
        $error_message = '';
        foreach ($accountIds as $index => $accountId) {
            $accountDetails     = Arr::get($connectedAccounts, $accountId) ? $connectedAccounts[$accountId] : '';

            if(empty($accountDetails)) {
                $multipleAccountsConnected = count($accountIds) > 1;
                $base_error_message = __('The account ID (%s) linked to your configuration has been deleted. To continue displaying your feed from this account, please reauthorize and reconnect it in the configuration settings. Then, go to the template editor, navigate to Source → Select a User Account, and choose the account again.', 'wp-social-reviews');
                if ($multipleAccountsConnected) {
                    $error_message .= __('There are multiple accounts being used on this template. ', 'wp-social-reviews') . sprintf($base_error_message, $accountId);
                } else {
                    $error_message .= sprintf($base_error_message, $accountId);
                }
                continue;
            }
            $user_avatar = Arr::get($accountDetails, 'user_avatar', '');
            $userName = Arr::get($accountDetails, 'username', '');
            $has_account_error_code = Arr::get($accountDetails, 'error_code');

            if($has_account_error_code) {
                if (sizeof($connectedAccounts) > 1) {
                    // translators: Please retain the placeholders (%s, %d, etc.) and ensure they are correctly used in context.
                    $error_message .= sprintf(__('There has been a problem with your account(%s). ', 'wp-social-reviews'), $userName) . Arr::get($connectedAccounts, $accountId . '.error_message', '') . '<br/> <br/>';
                } else {
                    // translators: Please retain the placeholders (%s, %d, etc.) and ensure they are correctly used in context.
                    $error_message .= sprintf(__('There has been a problem with your account(%s). ', 'wp-social-reviews'), $userName) . Arr::get($accountDetails, 'error_message', '') . '<br/> <br/>';
                }
            }

            $resultWithComments = array();
            if ($accountDetails) {
                $feedCacheName   = 'user_account_feed_id_' . $accountId;
                $instagramApiUrl = $this->getApiUrl($accountDetails);

                if ( is_array($instagramApiUrl) && (new Common())->instagramError($instagramApiUrl)) {
                    $error_message .= $instagramApiUrl;
                }
	            $resultWithComments = $this->cacheHandler->getFeedCache($feedCacheName);

                if (!$resultWithComments && empty($has_account_error_code) || !empty($resultWithComments) && !Arr::has($resultWithComments[0], 'user_avatar')) {
                    $resultWithoutComments = (new Common())->expandWithoutComments($instagramApiUrl);
                    if ((new Common())->instagramError($resultWithoutComments)) {
                        // translators: Please retain the placeholders (%s, %d, etc.) and ensure they are correctly used in context.
                        $error_message .= sprintf(__('There has been a problem with your account(%s). ', 'wp-social-reviews'), $userName) . Arr::get($resultWithoutComments, 'error.message');
                    }
                    if (!(new Common())->instagramError($resultWithoutComments)) {
                        $resultWithoutComments['data'] = $this->processFeedItems($userName, $accountId, $user_avatar, $resultWithoutComments);

                        $resultWithComments = (new Common())->expandWithComments($accountDetails, $resultWithoutComments);
                        if ((new Common())->instagramError($resultWithComments)) {
                            // translators: Please retain the placeholders (%s, %d, etc.) and ensure they are correctly used in context.
                            $error_message .= sprintf(__('There has been a problem with your account(%s). ', 'wp-social-reviews'), $userName) . Arr::get($resultWithComments, 'error.message');
                        }
                        $this->cacheHandler->createCache($feedCacheName, $resultWithComments);
                    } else {
                        $resultWithComments = false;
                    }
                }
            }
            $response[$index] = $resultWithComments ? $resultWithComments : array();
        }

        $accountFeed = array();
        foreach ($response as $index => $feeds) {
            if(!empty($feeds) && is_array($feeds)) {
                $accountFeed = array_merge($accountFeed, $feeds);
            }
        }

        return [
            'error_message' => $error_message,
            'feeds'         => $accountFeed
        ];
    }

    public function getNextPageUrlResponse($nextUrl, $response)
    {
        $posts   = $response;
        $limit   =  apply_filters('wpsocialreviews/instagram_feeds_total_limit', 100);
        $perPage = 100;
        $pages = (int)($limit/$perPage);

        $x = 1;
        while($x < $pages){
            $x++;
            $nextUrlResponse = (new Common())->makeRequest($nextUrl);
            $newData = Arr::get($nextUrlResponse, 'data', []);
            $oldData = Arr::get($response, 'data', []);
            $posts['data'] = array_merge($oldData, $newData);
        }

        return $posts;
    }

    /**
     * Process feed items to add user information and fix media URLs
     *
     * @param string $userName Instagram username
     * @param string $accountId Instagram account ID
     * @param string $user_avatar User avatar URL
     * @param array $resultWithoutComments Raw API response
     * @return array Processed feed items
     */
    public function processFeedItems($userName, $accountId, $user_avatar, $resultWithoutComments)
    {
        if(isset($resultWithoutComments['paging']) && defined('WPSOCIALREVIEWS_PRO')){
            $nextUrl = Arr::get($resultWithoutComments, 'paging.next');
            if($nextUrl){
                $resultWithoutComments = $this->getNextPageUrlResponse($nextUrl, $resultWithoutComments);
            }
        }

        $feeds = Arr::get($resultWithoutComments, 'data', []);

        // Process feeds only if not empty
        if (!empty($feeds) && is_array($feeds)) {
            // we modify username if collab feed there
            foreach ($feeds as $key => $feed) {
                $feeds[$key]['username'] = $userName;
                $feeds[$key]['accountId'] = $accountId;
                $feeds[$key]['user_avatar'] = $user_avatar;
            }

            // Then batch process all feeds that need media URL fixing
            //$feeds = (new SingleFeed())->batchFixMediaUrls($feeds);
        }

        return $feeds;
    }

    public function getApiUrl($accountDetails)
    {
        $dataProtector = new DataProtector();
        $num    = apply_filters('wpsocialreviews/instagram_feeds_limit', 10);
        $apiUrl = '';
        if ($accountDetails['api_type'] === 'business') {
            $decrypt_access_token = $dataProtector->decrypt($accountDetails['access_token']) ? $dataProtector->decrypt($accountDetails['access_token']) : $accountDetails['access_token'];
            $apiUrl = 'https://graph.facebook.com/' . $accountDetails['user_id'] . '/media?fields=media_url,thumbnail_url,media_product_type,caption,id,media_type,timestamp,username,comments_count,like_count,permalink,children{media_url,id,media_type,timestamp,permalink,thumbnail_url}&limit=' . $num . '&access_token=' .$decrypt_access_token. '&status_code=PUBLISHED';
        } elseif ($accountDetails['api_type'] === 'business_basic' || $accountDetails['api_type'] === 'personal') {
            $accessToken = (new RefreshToken())->getAccessToken($accountDetails);
            $accessToken = $dataProtector->decrypt($accessToken) ? $dataProtector->decrypt($accessToken) : $accessToken;

            // return errors
            if (is_array($accessToken) && (new Common())->instagramError($accessToken)) {
                return $accessToken;
            }

            $apiUrl      = 'https://graph.instagram.com/' . $accountDetails['user_id'] . '/media?fields=media_url,thumbnail_url,caption,id,media_type,timestamp,username,comments_count,like_count,permalink,children{media_url,id,media_type,timestamp,permalink,thumbnail_url}&limit=' . $num . '&access_token=' . $accessToken.'&status_code=PUBLISHED';
        }

        return $apiUrl;
    }
}
