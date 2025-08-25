<?php
defined('ABSPATH') || exit;

/**
 * Module Name: Button
 * Description: Display Button content
 */
class TB_Buttons_Module extends Themify_Builder_Component_Module {

    public static function get_module_name():string {
        return __('Button', 'themify');
    }

    public static function get_module_icon():string {
        return 'mouse-alt';
    }


    public static function get_js_css():array {
        return array(
            'css' => 1
        );
    }

    public static function get_styling_image_fields() : array {
        return [
            'background_image' => '.module',
            'b_i' => ' .module-buttons-item a',
            'bic_b_i' => ' .module-buttons-item em'
        ];
    }

	public static function get_translatable_repeatable_fields( $module ) {
		return [
			'content_button' => [
				'label' => 'LINE',
				'link' => 'LINK',
				'title' => 'LINE'
			]
		];
	}
}