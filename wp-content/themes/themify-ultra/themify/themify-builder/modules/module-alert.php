<?php
defined('ABSPATH') || exit;

/**
 * Module Name: Alert
 * Description: Display Alert content
 */
class TB_Alert_Module extends Themify_Builder_Component_Module {

    public static function get_module_name():string {
        return __('Alert', 'themify');
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

    public static function get_styling_image_fields() : array {
        return [
            'background_image' => ''
        ];
    }

	public static function get_translatable_text_fields( $module ) : array {
		return [ 'mod_title_alert', 'heading_alert', 'action_btn_text_alert' ];
	}

	public static function get_translatable_textarea_fields( $module ) : array {
		return [ 'text_alert', 'alert_message_text' ];
	}

	public static function get_translatable_link_fields( $module ) : array {
		return [ 'action_btn_link_alert' ];
	}
}