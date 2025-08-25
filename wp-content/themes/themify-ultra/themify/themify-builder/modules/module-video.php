<?php

defined('ABSPATH') || exit;

/**
 * Module Name: Video
 * Description: Display Video content
 */
class TB_Video_Module extends Themify_Builder_Component_Module {

    public static function get_module_name():string {
        return __('Video', 'themify');
    }

    public static function get_module_icon():string {
        return 'video-clapper';
    }


    public static function get_js_css():array {
        return array(
            'async' => 1,
            'css' => 1,
            'js' => 1
        );
    }

    /**
     * Renders the module for Static Content
     */
    public static function get_static_content( array $module ):string {
        $url = ! empty( $module['mod_settings']['url_video'] ) ? esc_url( $module['mod_settings']['url_video'] ) : '';
        $output = '';
        if ( $url !== '' ) {
            if ( strpos( $url, themify_upload_dir( 'baseurl' ) ) === false ) {
                /* external URL, use WP Embeds */
                $output = '[embed]' . $url . '[/embed]';
            } else {
                /* local video, use [video] shortcode which produces more viewer-friendly output */
                $size = '';
                $media_id = themify_get_attachment_id_from_url( $url );
                if ( $media_id ) {
                    $metadata = wp_get_attachment_metadata( $media_id );
                    if ( ! empty( $metadata['width'] ) && ! empty( $metadata['height'] ) ) {
                        $size = sprintf( ' width="%s" height="%s"', $metadata['width'], $metadata['height'] );
                    }
                }
                $output = sprintf( '[video src="%s"%s][/video]', $url, $size );
            }
        }

        return $output;
    }

	public static function get_translatable_text_fields( $module ) : array {
		return [ 'mod_title_video', 'title_video' ];
	}

	public static function get_translatable_textarea_fields( $module ) : array {
		return [ 'caption_video' ];
	}

	public static function get_translatable_link_fields( $module ) : array {
		return [ 'url_video', 'o_i', 'title_link_video' ];
	}
}

