<?php

defined('ABSPATH') || exit;

/**
 * Module Name: Slider
 * Description: Display slider content
 */
class TB_Slider_Module extends Themify_Builder_Component_Module {
    const SLUG='slider';
    public static function init():void {
        if (Themify_Builder_Model::is_cpt_active(self::SLUG)) {
            add_filter('themify_metabox/fields/themify-meta-boxes', array(__CLASS__, 'cpt_meta_boxes'), 100); // requires low priority so that it loads after theme's metaboxes
            if (!shortcode_exists('themify_slider_posts')) {
                add_shortcode('themify_slider_posts', array(__CLASS__, 'do_shortcode'));
            }
        }
    }

    public static function get_module_name():string {
        add_filter('themify_builder_active_vars',array(__CLASS__,'set_cpt_active'));
        return __('Slider', 'themify');
    }

    public static function get_module_icon():string {
        return 'layout-slider';
    }


    public static function get_metabox() {

        /** Slider Meta Box Options */
        return array(
            // Featured Image Size
            Themify_Builder_Model::$featured_image_size,
            // Image Width
            Themify_Builder_Model::$image_width,
            // Image Height
            Themify_Builder_Model::$image_height,
            // External Link
            Themify_Builder_Model::$external_link,
            // Lightbox Link
            Themify_Builder_Model::$lightbox_link,
            array(
                'name' => 'video_url',
                'title' => __('Video URL', 'themify'),
                'description' => __('URL to embed a video instead of featured image', 'themify'),
                'type' => 'textbox',
                'meta' => array()
            )
        );
    }

    public static function do_shortcode($atts) {

        $atts = shortcode_atts(array(
            'visible' => '1',
            'scroll' => '1',
            'auto' => 0,
            'pause_hover' => 'no',
            'play_control' => 'no',
            'wrap' => 'yes',
            'excerpt_length' => '20',
            'speed' => 'normal',
            'slider_nav' => 'yes',
            'pager' => 'yes',
            'limit' => 5,
            'category' => 0,
            'image' => 'yes',
            'image_w' => '240px',
            'image_fullwidth' => '',
            'image_h' => '180px',
            'more_text' => __('More...', 'themify'),
            'title' => 'yes',
            'display' => 'none',
            'post_meta' => 'no',
            'post_date' => 'no',
            'width' => '',
            'height' => '',
            'class' => '',
            'unlink_title' => 'no',
            'unlink_image' => 'no',
            'image_size' => 'thumbnail',
            'post_type' => 'post',
            'taxonomy' => 'category',
            'order' => 'DESC',
            'orderby' => 'date',
            'effect' => 'scroll',
            'style' => 'slider-default'
            ), $atts);

        $module = array(
            'module_ID' =>  self::SLUG.'-' . rand(0, 10000),
            'mod_name' =>  self::SLUG,
            'mod_settings' => array(
                'mod_title_slider' => '',
                'layout_display_slider' => 'slider',
                'slider_category_slider' => $atts['category'],
                'posts_per_page_slider' => $atts['limit'],
                'offset_slider' => '',
                'order_slider' => $atts['order'],
                'orderby_slider' => $atts['orderby'],
                'display_slider' => $atts['display'],
                'hide_post_title_slider' => $atts['title'] === 'yes' ? 'no' : 'yes',
                'unlink_post_title_slider' => $atts['unlink_title'],
                'hide_feat_img_slider' => '',
                'unlink_feat_img_slider' => $atts['unlink_image'],
                'layout_slider' => $atts['style'],
                'image_size_slider' => $atts['image_size'],
                'img_w_slider' => $atts['image_w'],
                'img_fullwidth_slider' => $atts['image_fullwidth'],
                'img_h_slider' => $atts['image_h'],
                'visible_opt_slider' => $atts['visible'],
                'auto_scroll_opt_slider' => $atts['auto'],
                'scroll_opt_slider' => $atts['scroll'],
                'speed_opt_slider' => $atts['speed'],
                'effect_slider' => $atts['effect'],
                'pause_on_hover_slider' => $atts['pause_hover'],
                'play_pause_control' => $atts['play_control'],
                'wrap_slider' => $atts['wrap'],
                'show_nav_slider' => $atts['pager'],
                'show_arrow_slider' => $atts['slider_nav'],
                'left_margin_slider' => '',
                'right_margin_slider' => '',
                'css_slider' => $atts['class']
            )
        );

        return self::retrieve_template('template-'.self::SLUG.'.php', $module, THEMIFY_BUILDER_TEMPLATES_DIR, '', false);
    }

    /**
     * Render plain content for static content.
     * 
     * @param array $module 
     * @return string
     */
    public static function get_static_content(array $module):string {
        $mod_settings = $module['mod_settings']+array(
            'layout_display_slider' => 'blog'
        );
        return 'blog' === $mod_settings['layout_display_slider'] ? '' : parent::get_static_content($module);
    }

    public static function set_cpt_active(array $arr){
        if(Themify_Builder_Model::is_cpt_active('slider')){
            $arr['slider_active']=1;
        }
        if(Themify_Builder_Model::is_cpt_active('portfolio')){
            $arr['portfolio_active']=1;
        }
        if(Themify_Builder_Model::is_cpt_active('testimonial')){
            $arr['testimonial_active']=1;
        }
        return $arr;
    }

	public static function get_translatable_text_fields( $module ) : array {
		return [ 'mod_title_slider' ];
	}

	public static function get_translatable_repeatable_fields( $module ) {
		return [
			'img_content_slider' => [
				'img_url_slider' => 'LINK',
				'img_title_slider' => 'LINE',
				'img_link_slider' => 'LINK',
				'img_caption_slider' => 'TEXTAREA'
			],
			'video_content_slider' => [
				'video_url_slider' => 'LINK',
				'video_title_slider' => 'LINE',
				'video_title_link_slider' => 'LINK',
				'video_caption_slider' => 'TEXTAREA'
			],
			'text_content_slider' => [
				'text_caption_slider' => 'TEXTAREA'
			]
		];
	}
}

TB_Slider_Module::init();
