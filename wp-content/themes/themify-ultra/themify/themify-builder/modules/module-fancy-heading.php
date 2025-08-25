<?php
defined('ABSPATH') || exit;

/**
 * Module Name: Fancy Heading
 * Description: Heading with fancy styles
 */
class TB_Fancy_Heading_Module extends Themify_Builder_Component_Module {


    public static function get_module_name():string {
        return __('Fancy Heading', 'themify');
    }

    public static function get_module_icon():string {
        return 'smallcap';
    }

    public static function get_js_css():array {
        return array(
            'css' => 1
        );
    }


    /**
     * Render plain content for static content.
     * 
     * @param array $module 
     * @return string
     */
    public static function get_static_content(array $module):string {
        $mod_settings = $module['mod_settings']+array(
            'heading' => '',
            'heading_tag' => 'h1',
            'sub_heading' => ''
        );
        return sprintf('<%s>%s<br/>%s</%s>', $mod_settings['heading_tag'], $mod_settings['heading'], $mod_settings['sub_heading'], $mod_settings['heading_tag']);
    }

    public static function get_styling_image_fields() : array {
        return [
            'background_image' => ''
        ];
    }

	public static function get_translatable_text_fields( $module ) : array {
		return [ 'heading', 'sub_heading' ];
	}

	public static function get_translatable_link_fields( $module ) : array {
		return [ 'heading_link', 'sub_heading_link', 'image' ];
	}
}
