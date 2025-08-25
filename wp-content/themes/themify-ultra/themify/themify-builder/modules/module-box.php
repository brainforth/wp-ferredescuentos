<?php
defined('ABSPATH') || exit;

/**
 * Module Name: Box
 * Description: Display box content
 */
class TB_Box_Module extends Themify_Builder_Component_Module {


    public static function get_module_name():string {
        return __('Box', 'themify');
    }

    public static function get_module_icon():string {
        return 'layout-width-full';
    }

    public static function get_js_css():array {
        return array(
            'css' => 1
        );
    }

    public static function get_styling_image_fields() : array {
        return [
            'background_image' => ' .ui'
        ];
    }

	public static function get_translatable_text_fields( $module ) : array {
		return [ 'mod_title_box' ];
	}

	public static function get_translatable_textarea_fields( $module ) : array {
		return [ 'content_box' ];
	}
}