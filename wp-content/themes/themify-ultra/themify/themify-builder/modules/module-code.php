<?php
defined('ABSPATH') || exit;

/**
 * Module Name: Code
 * Description: Display formatted code
 */
class TB_Code_Module extends Themify_Builder_Component_Module {

    public static function get_module_name():string {
        return __('Code', 'themify');
    }

    public static function get_module_icon():string {
        return 'notepad';
    }

    public static function get_js_css():array {
        return array(
            'async' => true,
            'css' => 1,
            'js' => 1
        );
    }

	public static function get_translatable_text_fields( $module ) : array {
		return [ 'm_t' ];
	}
}