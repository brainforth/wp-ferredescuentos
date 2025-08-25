<?php

defined('ABSPATH') || exit;

class Builder_Optin_Service_GetResponse extends Builder_Optin_Service {

    public static function get_id():string {
        return 'getresponse';
    }

    public static function get_label():string  {
        return __('GetResponse', 'themify');
    }

    public static function get_settings():array {
        $lists = self::get_lists();
        if (is_array($lists)) {
            return array(
                array(
                    'id' => 'gr_list',
                    'type' => 'select',
                    'label' => __('Campaign (List)', 'themify'),
                    'options' => $lists,
                ),
            );
        } else {
            return array(
                array(
                    'type' => 'message',
                    'class' => 'tb_field_error_msg',
                    'comment' => $lists
                )
            );
        }
    }

    public function get_options():array {//backward for fw 7.5
        return self::get_settings();
    }

    public static function get_global_options():array {
        return array(
            array(
                'id' => 'getresponse_key',
                'type' => 'text',
                'label' => __('GetResponse API Key', 'themify'),
                'description' => sprintf(__('<a href="%s" target="_blank">Get an API key</a>', 'themify'), 'https://app.getresponse.com/api'),
            ),
        );
    }

    private static function request($request, $method = 'GET', $args = array()) {
        $api_key = self::get_api_key();
        $url = 'https://api.getresponse.com/v3/';
        $url .= $request;
        $args+= array(
            'method' => $method,
            'headers' => array(
                'X-Auth-Token' => 'api-key ' . $api_key,
                'Content-Type' => 'application/json'
            ),
        );

        $response = wp_remote_request($url, $args);
        if (is_wp_error($response)) {
            return $response;
        } else {
            return json_decode(wp_remote_retrieve_body($response), true);
        }
    }

    protected static function request_list() {
        if (is_wp_error(( $data = self::request('campaigns')))) {
            return $data;
        }
        if (!empty($data) && is_array($data)) {
            $lists = array();
            foreach ($data as $v) {
                $lists[$v['campaignId']] = $v['name'];
            }
            return $lists;
        }
        return new WP_Error('list_error', __('Error retrieving campaigns.', 'themify'));
    }

    /**
     * Gets data from module and validates API key
     */
    public static function validate_data(array $fields_args):string {
        return isset($fields_args['gr_list']) ? '' :__('No list is selected.', 'themify');
    }

    /**
     * Subscribe action
     *
     * @doc: https://apidocs.getresponse.com/v3/resources/contacts
     */
    public static function subscribe(array $args) {
        $sub = self::request('contacts', 'POST', array(
            'body' => json_encode(array(
                'email' => $args['email'],
                'campaign' => array(
                    'campaignId' => $args['gr_list']
                ),
                'name' => sprintf('%s %s', $args['fname'], $args['lname']),
            )),
        ));

        if (is_wp_error($sub)) {
            return $sub;
        } 
        elseif (isset($sub['httpStatus'], $sub['message'])) {
            return new WP_Error('error', $sub['message']);
        } 
        else {
            return true;
        }
    }
}
