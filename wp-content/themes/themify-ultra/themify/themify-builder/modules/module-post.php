<?php

defined('ABSPATH') || exit;

/**
 * Module Name: Post
 * Description: Display Posts
 */
class TB_Post_Module extends Themify_Builder_Component_Module {

    public static function get_module_name():string {
        return __('Post', 'themify');
    }

    public static function get_module_icon():string {
        return 'layers';
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
        return ''; // no static content for dynamic content
    }

	public static function get_translatable_text_fields( $module ) : array {
		return [ 'mod_title_post' ];
	}

	public static function get_translatable_textarea_fields( $module ) : array {
		return [ 'no_posts_msg' ];
	}
}

