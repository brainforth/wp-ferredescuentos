<?php
defined('ABSPATH') || exit;

/**
 * Module Name: Service Menu
 * Description: Display a Service item
 */
class TB_Service_Menu_Module extends Themify_Builder_Component_Module {

    public static function get_module_name():string {
        return __('Service Menu', 'themify');
    }

    public static function get_module_icon():string {
        return 'menu-alt';
    }

    public static function get_js_css():array {
        return array(
            'css' => 1
        );
    }

	public static function get_translatable_text_fields( $module ) : array {
		return [ 'title_service_menu', 'price_service_menu', 'highlight_text_service_menu' ];
	}

	public static function get_translatable_textarea_fields( $module ) : array {
		return [ 'description_service_menu' ];
	}

	public static function get_translatable_link_fields( $module ) : array {
		return [ 'image_service_menu', 'link_service_menu' ];
	}
}
