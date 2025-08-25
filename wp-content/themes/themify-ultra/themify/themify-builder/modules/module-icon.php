<?php
defined('ABSPATH') || exit;

/**
 * Module Name: Icon
 * Description: Display Icon content
 */
class TB_Icon_Module extends Themify_Builder_Component_Module {


    public static function get_module_name():string {
        return __('Icon', 'themify');
    }

    public static function get_module_icon():string {
        return 'control-record';
    }

    public static function get_js_css():array {
        return array(
            'css' => 1
        );
    }

    public static function get_styling_image_fields() : array {
        return [
            'background_image' => ''
        ];
    }

	public static function get_translatable_repeatable_fields( $module ) {
		return [
			'content_icon' => [
				'label' => 'LINE',
				'link' => 'LINK',
				'image' => 'LINK'
			]
		];
	}
}