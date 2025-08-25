<?php

defined('ABSPATH') || exit;

/**
 * Module Name: Menu
 * Description: Display Custom Menu
 */
class TB_Menu_Module extends Themify_Builder_Component_Module {


    public static function get_module_name():string {
        return __('Menu', 'themify');
    }


    public static function get_module_icon():string {
        return 'view-list';
    }

    public static function get_js_css():array {
        return array(
            'css' => 1,
            'js' => 1
        );
    }

	public static function get_translatable_text_fields( $module ) : array {
		return [ 'mod_title_menu' ];
	}
}