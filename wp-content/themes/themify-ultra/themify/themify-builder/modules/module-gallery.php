<?php

defined('ABSPATH') || exit;

/**
 * Module Name: Gallery
 * Description: Display WP Gallery Images
 */
class TB_Gallery_Module extends Themify_Builder_Component_Module {
    
    public static function init():void {
        if(themify_is_ajax()){
            add_action('wp_ajax_tb_gallery_lightbox_data', array(__CLASS__, 'gallery_lightbox_data'));
        }
    }

    public static function get_module_name():string {
        return __('Gallery', 'themify');
    }

    public static function get_js_css():array {
        return array(
            'css' => 1,
            'js' => 1
        );
    }
    
    public static function gallery_lightbox_data():void{
        check_ajax_referer('tf_nonce', 'nonce');
        if (!empty($_POST['bid']) && !empty($_POST['url']) && is_numeric($_POST['bid']) && Themify_Access_Role::check_access_frontend($_POST['bid'])) {
            $post = get_post(themify_get_attachment_id_from_url(sanitize_url($_POST['url'])));
            $res=array();
            if(!empty($post)){
                $res=array(
                    'title'=>$post->post_title,
                    'caption'=>$post->post_excerpt
                );
            }
            wp_send_json_success($res);
        }
        wp_die();
    }



    /**
     * Render plain content for static content.
     * 
     * @param array $module 
     * @return string
     */
    public static function get_static_content(array $module):string {
        $mod_settings= $module['mod_settings']+array(
            'mod_title_gallery' => '',
            'shortcode_gallery' => ''
        );
        $text = '' !== $mod_settings['mod_title_gallery'] ? sprintf('<h3>%s</h3>', $mod_settings['mod_title_gallery']) : '';
        return $text.$mod_settings['shortcode_gallery'];
    }

    public static function get_styling_image_fields() : array {
        return [
            'background_image' => ''
        ];
    }

	public static function get_translatable_text_fields( $module ) : array {
		return [ 'mod_title_gallery' ];
	}
}

TB_Gallery_Module::init();
