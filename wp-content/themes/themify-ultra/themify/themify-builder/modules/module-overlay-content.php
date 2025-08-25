<?php

defined('ABSPATH') || exit;

/**
 * Module Name: Overlay Content
 * Description: Overlay Content Module
 */
class TB_Overlay_Content_Module extends Themify_Builder_Component_Module {



    public static function get_module_name():string {
        return __('Overlay Content', 'themify');
    }

    public static function get_module_icon():string {
        return 'new-window';
    }

    public static function get_js_css():array {
        return array(
            'css' => 1,
            'js' => 1
        );
    }

    public static function get_styling_image_fields() : array {
        return [
            'background_image' => '',
            'ctr_b_i' => [ ' .tb_oc_overlay', ' .tb_overlay_content_lp' ]
        ];
    }

    public static function get_static_content(array $module):string {
        return ''; // no static content for dynamic content
    }

	public static function get_translatable_text_fields( $module ) : array {
		return [ 'icon_title' ];
	}
}