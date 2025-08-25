<?php

defined('ABSPATH') || exit;

abstract class Builder_Optin_Service {

    public static abstract function get_id():string;

    /**
     * Provider Name
     */
    public static abstract function get_label(): string;
    /**
     * Module options, displayed in the Optin module form
     */
    public static abstract function get_settings():array;

    /**
     * Provider options that are not unique to each module
     * These are displayed in the Builder settings page
     */
    public static abstract function get_global_options():array;


    /**
     * Retrieves the $fields_args from module and determines if there is valid form to show.
     */
    public static abstract function validate_data(array $fields_args):string;


    protected static function init():void{}

    /**
     * Checks whether this service is available.
     */

    public static function is_available():bool {
        return true;
    }

    /**
     * Creates or returns an instance of this class.
     */
    public static function run():void {
        if (is_admin()) {
            add_action('wp_ajax_tb_optin_clear_cache', array(__CLASS__, 'ajax_clear_cache'));
            add_action('wp_ajax_tb_optin_subscribe', array(__CLASS__, 'ajax_subscribe'));
            add_action('wp_ajax_nopriv_tb_optin_subscribe', array(__CLASS__, 'ajax_subscribe'));
        }
    }

    /**
     * Initialize data providers for the module
     *
     * Other plugins or themes can extend or add to this list
     * by using the "builder_optin_services" filter.
     */
    private static function init_providers($type = 'all'):array {
        $coreList=array(
            'mailchimp' => 'Builder_Optin_Service_MailChimp',
            'activecampaign' => 'Builder_Optin_Service_ActiveCampaign',
            'convertkit' => 'Builder_Optin_Service_ConvertKit',
            'getresponse' => 'Builder_Optin_Service_GetResponse',
            'mailerlite' => 'Builder_Optin_Service_MailerLite',
            'newsletter' => 'Builder_Optin_Service_Newsletter',
        );
        $providers=[];
        $providersList = apply_filters('builder_optin_services',$coreList);
        if ( $type !== 'all' ) {
            if ( ! isset( $providersList[ $type ] ) ) {
                return $providers;
            }
            $providersList = array( $type => $providersList[ $type ] );
        }
        
        $dir = __DIR__ .DIRECTORY_SEPARATOR;

        foreach ( $providersList as $id => $provider ) {
            $path=isset($coreList[$id])?($dir . $id):$id;
            include_once( $path . '.php' );
            if ( class_exists( $provider,false ) && $provider::is_available() ) {
                $provider::init();
                $providers[ $id ] = $provider;
            }
        }
        return $providers;
    }

    /**
     * Helper function to retrieve list of providers or provider instance
     */
    public static function get_providers(string $id='all',bool $is_v75=false) {
        $providers= self::init_providers($id);
        if($id==='all'){
            return $providers;
        }
        if(!isset($providers[ $id ])){
            return null;
        }
        $class=$providers[ $id ];
        return $is_v75===true?$class:new $class();
    }

    /**
     * Handles the Ajax request for subscription form
     *
     * Hooked to wp_ajax_tb_optin_subscribe
     */
    public static function ajax_subscribe() {
        if (!isset($_POST['tb_optin_provider'], $_POST['tb_optin_fname'], $_POST['tb_optin_lname'], $_POST['tb_optin_email'], $_POST['tb_post_id'], $_POST['tb_element_id'])) {
            wp_send_json_error(array('error' => __('Required fields are empty.', 'themify')));
        }
        $module = Themify_Builder_Component_Module::get_element_settings( $_POST['tb_post_id'], $_POST['tb_element_id'] );
        if ( ! $module ) {
            die;
        }

        /* CAPTCHA validation */
        if ( isset( $module['captcha'] ) ) {
            $provider = $module['captcha'] === 'on' ? 'recaptcha' : 'hcaptcha';
            $response =
                $provider === 'recaptcha' && ! empty( $_POST['g-recaptcha-response'] ) ? $_POST['g-recaptcha-response']
                : ( $provider === 'hcaptcha' && ! empty( $_POST['h-captcha-response'] ) ? $_POST['h-captcha-response'] : null );
            if ( $response ) {
                $result = Themify_Builder_Model::validate_captcha( $provider, $response );
                if ( is_wp_error( $result ) ) {
                    wp_send_json_error( [ 'error' => $result->get_error_message() ] );
                }
            } else {
                wp_send_json_error( [ 'error' => __( 'Empty Captcha response.', 'themify' ) ] );
            }
        }

        $data = array();
        foreach ($_POST as $key => $value) {
            // remove "tb_optin_" prefix from the $_POST data
            $key = preg_replace('/^tb_optin_/', '', $key);
            $data[$key] = sanitize_text_field(trim($value));
        }

        if ($provider = self::get_providers($data['provider'],true)) {
            $result = $provider::subscribe($data);
            if (is_wp_error($result)) {
                wp_send_json_error(array('error' => $result->get_error_message()));
            } else {
                wp_send_json_success(array(
                    /* send name and email in GET, these may come useful when building the page that the visitor will be redirected to */
                    'redirect' => add_query_arg(array(
                        'fname' => $data['fname'],
                        'lname' => $data['lname'],
                        'email' => $data['email'],
                        ), $data['redirect'])
                ));
            }
        } else {
            wp_send_json_error(array('error' => __('Unknown provider.', 'themify')));
        }
    }

    public static function ajax_clear_cache() {
        check_ajax_referer('tf_nonce', 'nonce');
        if (current_user_can('manage_options') && Themify_Access_Role::check_access_backend()) {
            $providers = self::get_providers('all',true);
            foreach ($providers as $id => $instance) {
                $instance::clear_cache();
            }
            wp_send_json_success();
        }
        wp_send_json_error();
    }


    /**
     * Returns the value of a setting
     */
    protected static function get($id, $default = null):?string {
        if ($value = themify_builder_get("setting-{$id}", "setting-{$id}")) {
            return $value;
        } else {
            return $default;
        }
    }

    protected static function get_api_key():?string {
        return static::get(static::get_id() . '_key');
    }

    /**
     * Action to perform when Clear Cache is requested
     */
    public static function clear_cache():void {
        $key = static::get_api_key();
        if (!empty($key)) {
            $id = static::get_id();
            delete_transient('tb_optin_' . $id . '_' . md5($key));
            Themify_Storage::delete('tb_optin_' . $id . '_' . $key);
        }
    }

    /**
     * Get list of provider
     *
     * @return string|Array
     */
    protected static function get_lists(?string $key = '') {
        if ($key === '') {
            $key = static::get_api_key();
        }
        if (empty($key)) {
            return sprintf(__('%s API Key is missing.', 'themify'), static::get_label());
        }
        $id = static::get_id();
        $cache_key = 'tb_optin_' . $id . '_' . $key;
        if (false === ( $lists = Themify_Storage::get($cache_key) )) {
            delete_transient('tb_optin_' . $id . '_' . md5($key));
            if (is_wp_error(( $data = static::request_list()))) {
                return $data->get_error_message();
            }
            Themify_Storage::set($cache_key, $data, MONTH_IN_SECONDS);
            return $data;
        } 
        else {
            return json_decode($lists, true);
        }
    }

    /**
     * Subscribe visitor to the mailing list
     *
     * @param $args array( 'fname', 'lname', 'email' )
     *        it also includes options from get_options() method with their values.
     *
     * @return WP_Error|true
     */
    public static function subscribe(array $args) {
        return true;
    }
}

Builder_Optin_Service::run();
