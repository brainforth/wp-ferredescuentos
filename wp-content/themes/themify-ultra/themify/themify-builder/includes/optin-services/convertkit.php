<?php

defined('ABSPATH') || exit;

class Builder_Optin_Service_ConvertKit extends Builder_Optin_Service {

    public static function get_id():string {
        return 'convertkit';
    }

    public static function get_label():string  {
        return __('ConvertKit', 'themify');
    }

    public static function get_settings():array {
        $lists = self::get_lists();
        if (is_array($lists)) {
            return array(
                array(
                    'id' => 'ck_form',
                    'type' => 'select',
                    'label' => __('Form', 'themify'),
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

    public static function get_options():array {//backward for fw 7.5
        return self::get_settings();
    }

    public static function get_global_options():array {
        return array(
            array(
                'id' => 'convertkit_key',
                'type' => 'text',
                'label' => __('ConvertKit API Key', 'themify'),
                'description' => sprintf(__('<a href="%s" target="_blank">Get an API key</a>', 'themify'), 'https://app.convertkit.com/account/edit'),
            ),
        );
    }

    private static function request($request, $method = 'GET', $args = array()) {
        $args+= array(
            'api_key' => self::get_api_key(),
        );
        $url = 'https://api.convertkit.com/v3/' . $request . '?' . http_build_query($args);
        $results = wp_remote_request($url, array('method' => $method));

        if (!is_wp_error($results)) {
            if (200 == wp_remote_retrieve_response_code($results)) {
                return json_decode(wp_remote_retrieve_body($results), true);
            } else {
                $body = wp_remote_retrieve_body($results);
                if (is_string($body) && is_object($json = json_decode($body, true))) {
                    $body = (array) $json;
                }

                if (!empty($body['error'])) {
                    return new WP_Error('error', $body['error']);
                } elseif (!empty($body['message'])) {
                    return new WP_Error('error', $body['message']);
                } else {
                    return new WP_Error('error', sprintf(__('Error code: %s', 'themify'), wp_remote_retrieve_response_code($results)));
                }
            }
        } else {
            return $results;
        }
    }

    protected static function request_list() {
        if (is_wp_error(( $data = self::request('forms')))) {
            return $data;
        }
        if (is_array($data['forms']) && !empty($data['forms'])) {
            $list = array();
            foreach ($data['forms'] as $v) {
                $list[$v['id']] = $v['name'];
            }
            return $list;
        }

        return new WP_Error('list_error', __('Error retrieving forms.', 'themify'));
    }

    /**
     * Gets data from module and validates API key
     */
    public static function validate_data(array $fields_args):string {
        return isset($fields_args['ck_form']) ? '' :__('No form is selected.', 'themify');
    }

    /**
     *
     * @doc https://developers.convertkit.com/#forms
     */
    public static function subscribe(array $args) {
        return self::request(sprintf('forms/%s/subscribe', $args['ck_form']), 'POST', array(
                'email' => $args['email'],
                'first_name' => $args['fname'],
        ));
    }
}
