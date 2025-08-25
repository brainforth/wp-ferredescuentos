<?php
defined('ABSPATH') || exit;

/**
 * Module Name: Feature
 * Description: Display Feature content
 */
class TB_Feature_Module extends Themify_Builder_Component_Module {

    
    public static function get_module_name():string {
        return __('Feature', 'themify');
    }

    public static function get_module_icon():string {
        return 'layout-media-left';
    }

    public static function get_js_css():array {
        return array(
            'css' => 1,
            'js' => 1
        );
    }

    public static function get_styling_image_fields() : array {
        return [
            'background_image' => ''
        ];
    }

	public static function get_translatable_text_fields( $module ) : array {
		return [ 'mod_title_feature', 'title_feature' ];
	}

	public static function get_translatable_textarea_fields( $module ) : array {
		return [ 'content_feature' ];
	}

	public static function get_translatable_link_fields( $module ) : array {
		return [ 'link_feature', 'overlap_image_feature', 'image_feature' ];
	}
}