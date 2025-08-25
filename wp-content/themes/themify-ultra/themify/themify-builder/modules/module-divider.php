<?php
defined('ABSPATH') || exit;

/**
 * Module Name: Divider
 * Description: Display Divider
 */
class TB_Divider_Module extends Themify_Builder_Component_Module {


    public static function get_module_name():string {
        return __('Divider', 'themify');
    }

    public static function get_module_icon():string {
        return 'line-dashed';
    }

    public static function get_js_css():array {
        return array(
            'css' => 1
        );
    }

	public static function get_translatable_text_fields( $module ) : array {
		return [ 'mod_title_divider' ];
	}
}