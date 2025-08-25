<?php
/**
 * Part of Aplazo Module
 * Author - Aplazo
 * Developer
 * License - https://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 *
 * @package Aplazo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WC_Aplazo_Api
 */
class WC_Aplazo_Api
{
    const LOGS_SUBCATEGORY_AUTH = 'auth';
    const LOGS_SUBCATEGORY_LOAN = 'loan';
    const LOGS_SUBCATEGORY_REQUEST = 'request';
    const LOGS_SUBCATEGORY_ORDER = 'order';
    const LOGS_SUBCATEGORY_REFUND = 'refund';
    const LOGS_SUBCATEGORY_WEBHOOK = 'webhook';
    const LOGS_CATEGORY_ERROR = 'error';
    const LOGS_CATEGORY_WARNING = 'warning';
    const LOGS_CATEGORY_INFO = 'info';
    /**
     * Static instance
     *
     * @var WC_Aplazo_Api
     */
    private static $instance = null;
    /**
     * @var WC_Gateway_Aplazo
     */
    private $aplazo_gateway;
    /**
     * @var mixed
     */
    private $log;

    public function __construct($log, $aplazo_gateway) {
        $this->log = $log;
        $this->aplazo_gateway = $aplazo_gateway;
    }

    public function apiPost($data, $path, $headers, $return = true)
    {
        $this->log->write_log('info', $data);
        if($return){
            $this->sendLog('Post http request', self::LOGS_CATEGORY_INFO, self::LOGS_SUBCATEGORY_REQUEST,
                ['url' => $path, 'data' => wp_json_encode($data), 'headers' => $headers]);
        }
        $response = wp_remote_post($path, array(
            'body'    => wp_json_encode($data),
            'headers' => $headers,
        ));
        return $return ? $this->returnResponse($response) : false;
    }

    public function apiGet($path, $headers)
    {
        $this->log->write_log('info', 'getUrl > ' . $path);
        $this->log->write_log('info', $headers);
        $this->sendLog('Get http request', self::LOGS_CATEGORY_INFO, self::LOGS_SUBCATEGORY_REQUEST,
        ['url' => $path, 'headers' => $headers]);
        $response = wp_remote_get( $path, array(
            'headers' => $headers
        ));
        return $this->returnResponse($response);
    }

    public function sendLog($message, $category, $subcategory, $metadata = [])
    {
        $metadata = array_merge($metadata, [
            "merchantId" => $this->aplazo_gateway->getProperty('merchantId'),
            "log" => $message
        ]);
        $body = [
            "eventType"=> "tag_plugin_w",
            "origin"=> "WOO",
            "category"=> $category,
            "subcategory"=> $subcategory,
            "metadata"=> $metadata
        ];
        $headers = [
            'merchant_id' => $this->aplazo_gateway->getProperty('merchantId'),
            'api_token' => $this->aplazo_gateway->getProperty('apiToken'),
            'Content-Type' => 'application/json'
        ];

        $this->apiPost($body, $this->aplazo_gateway->getProperty('log_environment'), $headers , false);
    }

    public function returnResponse($response)
    {
        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            $this->log->write_log('error', $error_message);
            $this->sendLog('Error en http Service > ' . $error_message, self::LOGS_CATEGORY_ERROR, self::LOGS_SUBCATEGORY_REQUEST);
            return false;
        } else {
            $response_to_log = wp_remote_retrieve_body($response);
            $response = json_decode($response_to_log, true) ? json_decode($response_to_log, true) : $response_to_log;
            $this->log->write_log('info', $response);
            $this->sendLog('Http Service Response > ' . $response_to_log, self::LOGS_CATEGORY_INFO, self::LOGS_SUBCATEGORY_REQUEST);
            return $response;
        }
    }

    /**
     * @return WC_Aplazo_Api|self|null
     */
	public static function init_aplazo_api($log, $aplazo_gateway) {
        if ( null === self::$instance ) {
            self::$instance = new self($log, $aplazo_gateway);
        }
        return self::$instance;
	}
}
