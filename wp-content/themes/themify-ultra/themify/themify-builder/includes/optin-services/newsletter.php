<?php

defined('ABSPATH') || exit;

/**
 * Newsletter plugin
 * @link https://wordpress.org/plugins/newsletter/
 */
class Builder_Optin_Service_Newsletter extends Builder_Optin_Service {

    public static function is_available():bool {
        return defined('NEWSLETTER_VERSION');
    }

    public static function get_id():string {
        return 'newsletter';
    }

    public static function get_label():string  {
        return __('Newsletter plugin', 'themify');
    }
    

    public static function get_settings():array {
        if (self::is_available()) {
            $lists = self::get_lists();
            if (empty($lists)) {
                return array(); // no options to show
            } 
            else {
                return array(
                    array(
                        'id' => 'newsletter_list',
                        'type' => 'select',
                        'label' => __('List', 'themify'),
                        'options' => $lists
                    ),
                );
            }
        } else {
            return array(
                array(
                    'type' => 'message',
                    'class' => 'tb_field_error_msg',
                    'comment' => sprintf(__('<a href="%s" target="_blank">Newsletter plugin</a> is not installed or active.', 'themify'), 'https://wordpress.org/plugins/newsletter/')
                )
            );
        }
    }
    
    public function get_options():array {//backward for fw 7.5
        return self::get_settings();
    }

    public static function get_global_options():array {
        return array();
    }

    /**
     * Get list of Lists (/wp-admin/admin.php?page=newsletter_subscription_lists)
     */
    protected static function get_lists(?string $key = '') {
        $lists = Newsletter::instance()->get_lists_for_subscription();
        $_lists = array();
        if (!empty($lists)) {
            foreach ($lists as $list) {
                $_lists[$list->id] = $list->name;
            }
        }
        return $_lists;
    }

    /**
     * Gets data from module and validates API key
     */
    public static function validate_data(array $fields_args):string {
        return '';
    }

    /**
     * Subscribe action
     *
     * Based on NewsletterSubscription::hook_newsletter_action() method
     */
    public static function subscribe(array $args) {
        $instance = NewsletterSubscription::instance();

        $subscription = $instance->get_default_subscription();
        $data = $subscription->data;
        $data->email = $instance->normalize_email($args['email']);
        $data->name = $instance->normalize_name($args['fname']);
        $data->surname = $instance->normalize_name($args['lname']);
        if (isset($args['newsletter_list'])) {
            $data->lists[$args['newsletter_list']] = 1;
        }

        $result = $instance->subscribe2($subscription);
        return is_wp_error($result) ? $result : true;
    }
}
