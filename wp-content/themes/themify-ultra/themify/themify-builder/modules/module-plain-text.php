<?php
defined('ABSPATH') || exit;

/**
 * Module Name: HTML / Text / Shortcode
 * Description: Display plain text
 */
class TB_Plain_Text_Module extends Themify_Builder_Component_Module {


    public static function get_module_name():string {
        return __('HTML / Text / Shortcode', 'themify');
    }

    public static function get_module_icon():string {
        return 'text';
    }

    public static function get_styling_image_fields() : array {
        return [
            'background_image' => ''
        ];
    }

	public static function get_translatable_text_fields( $module ) : array {
		return [ 'plain_text' ];
	}
}