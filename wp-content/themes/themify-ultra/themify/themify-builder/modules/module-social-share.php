<?php
defined('ABSPATH') || exit;

/**
 * Module Name: Social Share
 */
class TB_Social_Share_Module extends Themify_Builder_Component_Module {


    public static function get_module_name():string {
        return __('Social Share', 'themify');
    }

    public static function get_module_icon():string {
        return 'twitter';
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
            'background_image' => '',
            'b_i' => ' a'
        ];
    }

	public static function get_translatable_text_fields( $module ) : array {
		return [ 'mod_title' ];
	}
}