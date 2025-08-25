<?php

defined('ABSPATH') || exit;

class Builder_Optin_Service_MailerLite extends Builder_Optin_Service {

    public static function get_id():string {
        return 'mailerlite';
    }

    public static function get_label():string  {
        return __('MailerLite', 'themify');
    }
    
    public static function get_settings():array {
        $lists = self::get_lists();
        if (is_array($lists)) {
            return array(
                array(
                    'id' => 'ml_form',
                    'type' => 'select',
                    'label' => __('Groups', 'themify'),
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
                'id' => 'mailerlite_version',
                'type' => 'select',
                'label' => __('Version', 'themify'),
                'options' => [
                    '' => __( 'Classic', 'themify' ), /* v1 or v2 */
                    'new' => __( 'New', 'themify' ),
                ],
                'description' => sprintf(__('Accounts created before March 22nd, 2022 use MailerLite Classic. Accounts created after March 22nd, 2022 use the new MailerLite. <a href="%s" target="_blank">Which version of MailerLite am I using?</a>', 'themify'), 'https://www.mailerlite.com/help/which-version-of-mailerlite-am-i-using'),
                'bind' => [
                    '' => [ 'show' => 'mailerlite_key', 'hide' => 'mailerlite_key_new' ],
                    'new' => [ 'show' => 'mailerlite_key_new', 'hide' => 'mailerlite_key' ],
                ]
            ),
            array(
                'id' => 'mailerlite_key',
                'type' => 'text',
                'label' => __('MailerLite Classic API Key', 'themify'),
                'description' => sprintf(__('<a href="%s" target="_blank">Get an API key</a>', 'themify'), 'https://app.mailerlite.com/integrations/api/'),
                'wrap_attr' => [
                    'data-show-if-element' => '[name=setting-mailerlite_version]',
                    'data-show-if-value' => ''
                ],
                'wrap_class' => 'mailerlite_key'
            ),
            array(
                'id' => 'mailerlite_key_new',
                'type' => 'textarea',
                'label' => __('MailerLite New API Key', 'themify'),
                'description' => sprintf(__('<a href="%s" target="_blank">Get an API key</a>', 'themify'), 'https://www.mailerlite.com/help/where-to-find-the-mailerlite-api-key-groupid-and-documentation#new'),
                'wrap_attr' => [
                    'data-show-if-element' => '[name=setting-mailerlite_version]',
                    'data-show-if-value' => 'new'
                ],
                'wrap_class' => 'mailerlite_key_new'
            ),
        );
    }

    private static function request($request = 'groups', $method = 'GET', $args = array()) {
        $args += array(
            'method' => $method,
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
        );
        if ( self::is_classic() ) {
            $args['headers']['X-MailerLite-ApiKey'] = self::get_api_key();
            $url = 'https://api.mailerlite.com/api/v2/' . $request;
        } else {
            $args['user-agent'] = null;
            $args['headers']['Authorization'] = 'Bearer ' . self::get_api_key();
            $url = 'https://connect.mailerlite.com/api/' . $request;
        }
        $results = wp_remote_request($url, $args);

        if (!is_wp_error($results)) {
            $response = json_decode(wp_remote_retrieve_body($results), true);
            if (empty($response)) {
                return new WP_Error('empty_response', __('Empty response.', 'themify'));
            }
            if (isset($response['error'])) {
                return new WP_Error('error-' . $response['error']['code'], $response['error']['message']);
            }

            // all good!
            return $response;
        } else {
            return $results;
        }
    }

    protected static function request_list() {
        if (is_wp_error(( $data = self::request('groups')))) {
            return $data;
        }
        if (!empty($data) && is_array($data)) {
            if ( ! self::is_classic() && isset( $data['data'] ) ) {
                /* handle /groups request in new mailerlite */
                $data = $data['data'];
            }

            $lists = array();
            foreach ($data as $v) {
                $lists[$v['id']] = $v['name'];
            }
            return $lists;
        }

        return new WP_Error('list_error', __('Error retrieving forms.', 'themify'));
    }

    /**
     * Gets data from module and validates API key
     */
    public static function validate_data(array $fields_args):string {
        return isset($fields_args['ml_form']) ? '' : __('No campaign is selected.', 'themify');
    }

    /**
     *
     * @doc https://developers.mailerlite.com/reference#add-single-subscriber
     */
    public static function subscribe(array $args) {
        $fields = array(
            'email' => $args['email'],
            'fields' => [
                'name' => $args['fname'],
                'last_name' => $args['lname'],
            ]
        );
        if ( self::is_classic() ) {
            return self::request(sprintf('groups/%s/subscribers', $args['ml_form']), 'POST', array(
                'body' => json_encode( $fields ),
            ));
        } else {
            $fields['groups'] = [ $args['ml_form'] ];
            return self::request( 'subscribers', 'POST', array(
                'body' => json_encode( $fields ),
            ));
        }
    }

    /**
     * True: using MailerLite V1 or V2. False: using the New MailerLite
     */
    private static function is_classic() : bool {
        return empty( self::get( 'mailerlite_version' ) );
    }

    protected static function get_api_key() :? string {
        $option_key = static::get_id() . '_key';
        if ( ! self::is_classic() ) {
            $option_key .= '_new';
        }

        return static::get( $option_key );
    }
}