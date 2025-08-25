<?php

defined('ABSPATH') || exit;

/**
 * Module Name: Widgetized
 * Description: Display any registered sidebar
 */
class TB_Widgetized_Module extends Themify_Builder_Component_Module {

    public static function get_module_name():string {
        return __('Widgetized', 'themify');
    }

    public static function get_styling_image_fields() : array {
        return [
            'background_image' => ''
        ];
    }

    public static function get_static_content(array $module):string {
        return ''; // no static content for dynamic content
    }

	public static function get_translatable_text_fields( $module ) : array {
		return [ 'mod_title_widgetized' ];
	}
}