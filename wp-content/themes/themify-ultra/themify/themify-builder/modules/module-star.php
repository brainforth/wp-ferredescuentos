<?php
defined('ABSPATH') || exit;

/**
 * Module Name: Star
 * Description: Display Rating Star
 */
class TB_Star_Module extends Themify_Builder_Component_Module {


    public static function get_module_name():string {
        return __('Rating Star', 'themify');
    }

    public static function get_module_icon():string {
        return 'star';
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
		return [ 'm_t' ];
	}

	public static function get_translatable_repeatable_fields( $module ) {
		return [
			'rates' => [
				'text_b' => 'LINE',
				'text_a' => 'LINE'
			]
		];
	}
}