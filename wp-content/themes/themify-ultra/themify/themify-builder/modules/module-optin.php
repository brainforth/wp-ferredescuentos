<?php
defined('ABSPATH') || exit;

/**
 * Module Name: Optin Forms
 * Description: Displays Optin form
 */
class TB_Optin_Module extends Themify_Builder_Component_Module {

    public static function init():void {
        include_once( THEMIFY_BUILDER_INCLUDES_DIR . '/optin-services/base.php' );
        add_action('wp_ajax_tb_optin_get_settings', array(__CLASS__, 'ajax_tb_optin_get_settings'));
    }

    public static function get_module_name():string {
        return __('Optin Form', 'themify');
    }

    public static function get_module_icon():string {
        return 'email';
    }

    public static function get_js_css():array {
        $_arr = array(
            'css' => 1
        );
        if (!Themify_Builder_Model::is_front_builder_activate()) {
            $_arr['js'] = 1;
        }
        return $_arr;
    }


    /**
     * Handles Ajax request to get the options for providers
     *
     * @since 4.2.3
     */
    public static function ajax_tb_optin_get_settings() {
        check_ajax_referer('tf_nonce', 'nonce');
        $providers = Builder_Optin_Service::get_providers('all',true);
        $providers_settings = $providers_list = $providers_binding = array();
        foreach ($providers as $id => $instance) {
            $providers_list[$id] = $instance::get_label();

            $providers_settings[] = array(
                'type' => 'group',
                'options' => $instance::get_settings(),
                'wrap_class' => $id
            );

            $providers_binding[$id] = array(
                'hide' => array_values(array_diff(array_keys($providers), array($id))),
                'show' => $id,
            );
        }

        $options = array(
            array(
                'id' => 'provider',
                'type' => 'select',
                'options' => $providers_list,
                'binding' => $providers_binding
            ),
            array(
                'type' => 'group',
                'id' => 'provider_settings',
                'options' => $providers_settings
            )
        );
        die(json_encode($options));
    }


    /**
     * Render plain content for static content.
     * 
     * @param array $module 
     * @return string
     */
    public static function get_static_content(array $module):string {
        return '';
    }

    public static function get_styling_image_fields() : array {
        return [
            'b_i' => ''
        ];
    }

	public static function get_translatable_text_fields( $module ) : array {
		return [ 'mod_title', 'label_firstname', 'fn_placeholder', 'label_lastname', 'ln_placeholder', 'label_email', 'email_placeholder', 'label_submit' ];
	}

	public static function get_translatable_textarea_fields( $module ) : array {
		return [ 'gdpr_label', 'errmsg', 'message' ];
	}

	public static function get_translatable_link_fields( $module ) : array {
		return [ 'redirect_to' ];
	}
}

TB_Optin_Module::init();
