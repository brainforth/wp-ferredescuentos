<?php

defined('ABSPATH') || exit;

class Builder_Optin_Service_ActiveCampaign extends Builder_Optin_Service {

    public static function get_id():string {
        return 'activecampaign';
    }

    public static function get_label():string {
        return __('ActiveCampaign', 'themify');
    }

    public static function get_settings():array {
        $lists = self::get_lists();
        if (is_array($lists)) {
            return array(
                array(
                    'id' => 'ac_list',
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

    public function get_options():array {//backward for fw 7.5
        return self::get_settings();
    }

    public static function get_global_options():array {
        $help = __('To get your API credentials go to your ActiveCampaign dashboard, you can find both API URL and Key in Settings > Developer section.');
        $label = themify_is_themify_theme() ? __('ActiveCampaign API Key' . themify_help($help), 'themify') : __('ActiveCampaign API Key', 'themify');
        return array(
            array(
                'id' => 'activecampaign_url',
                'type' => 'text',
                'label' => __('ActiveCampaign API URL', 'themify'),
            ),
            array(
                'id' => 'activecampaign_key',
                'type' => 'text',
                'help' => $help,
                'label' => $label
            ),
        );
    }

    /**
     * Get the API key from Settings page
     *
     * @return array|false
     */
    private static function get_api_tokens():array {
        if (self::get('activecampaign_url') && self::get('activecampaign_key')) {
            return array(
                'key' => self::get('activecampaign_key'),
                'url' => self::get('activecampaign_url'),
            );
        } else {
            return array();
        }
    }

    private static function request($request, $method = 'GET', $args = array()) {
        $tokens = self::get_api_tokens();
        $url = $tokens['url'] . '/api/3/';
        $url .= $request;
        $args+= array(
            'method' => $method,
            'headers' => array(
                'Api-Token' => $tokens['key']
            ),
        );

        $response = wp_remote_request($url, $args);
        if (is_wp_error($response)) {
            return $response;
        } else {
            return json_decode(wp_remote_retrieve_body($response), true);
        }
    }

    /**
     * Get list of Lists (/admin/main.php?action=list)
     *
     * @return WP_Error|Array
     */
    protected static function get_lists(?string $key = '') {
        $tokens = self::get_api_tokens();
        return parent::get_lists((empty($tokens) ? null : $tokens['key']));
    }

    protected static function request_list() {
        if (is_wp_error(( $data = self::request('lists')))) {
            return $data;
        }
        if (is_array($data) && isset($data['lists'])) {
            $lists = array();
            foreach ($data['lists'] as $v) {
                $lists[$v['id']] = $v['name'];
            }
            return $lists;
        }

        return new WP_Error('list_error', __('Error retrieving lists.', 'themify'));
    }

    /**
     * Gets data from module and validates API key
     */
    public static function validate_data(array $fields_args):string {
        return isset($fields_args['ac_list']) ? '' : __('No list is selected.', 'themify');
    }

    public static function clear_cache():void {
        $tokens = self::get_api_tokens();
        if (!empty($tokens)) {
            delete_transient('tb_optin_activecampaign_' . md5($tokens['key']));
            Themify_Storage::delete('tb_optin_activecampaign_' . $tokens['key']);
        }
    }

    /**
     *
     *
     * @doc: https://developers.activecampaign.com/v3/reference#update-list-status-for-contact
     */
    public static function subscribe(array $args) {
        // create the contact
        $contact = self::request('contacts', 'POST', array(
            'body' => json_encode(array(
                'contact' => array(
                    'email' => $args['email'],
                    'firstName' => $args['fname'],
                    'lastName' => $args['lname']
                )
            )),
        ));
        if (is_wp_error($contact)) {
            return $contact;
        } elseif (isset($contact['contact']['id'])) {
            /* everything is good, continue on */
            $user_id = $contact['contact']['id'];
        } elseif (isset($contact['errors'][0]['code']) && $contact['errors'][0]['code'] === 'duplicate') {
            /* user exists, get ID */
			$get_contact = self::request( 'contacts?email=' . $args['email'], 'GET' );
			if ( ! is_wp_error( $get_contact ) && isset( $get_contact['contacts'][0]['id'] ) ) {
				$user_id = $get_contact['contacts'][0]['id'];
			} else {
				return new WP_Error('error', $contact['errors'][0]['title']);
			}
        } elseif (isset($contact['errors'][0]['title'])) {
            return new WP_Error('error', $contact['errors'][0]['title']);
        }

        $list = self::request('contactLists', 'POST', array(
            'body' => json_encode(array(
                'contactList' => array(
                    'list' => $args['ac_list'],
                    'contact' => $user_id,
                    'status' => 1
                )
            )),
        ));
        if (is_wp_error($list)) {
            return $list;
        }
        return isset($list['contacts'], $list['contactList']);
    }
}