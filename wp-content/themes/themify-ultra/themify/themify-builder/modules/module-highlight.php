<?php

defined('ABSPATH') || exit;

/**
 * Module Name: Highlight
 * Description: Display highlight custom post type
 */
class TB_Highlight_Module extends Themify_Builder_Component_Module {//deprecated
    const SLUG='highlight';
    public static function init():void {
        add_filter('themify_metabox/fields/themify-meta-boxes', array(__CLASS__, 'cpt_meta_boxes'), 100); // requires low priority so that it loads after theme's metaboxes
        if (!shortcode_exists('themify_highlight_posts')) {
            add_shortcode('themify_highlight_posts', array(__CLASS__, 'do_shortcode'));
        }
    }

    public static function get_module_name():string {
        add_filter( 'themify_builder_active_vars', [ __CLASS__, 'builder_active_enqueue' ] );
        return __('Highlight', 'themify');
    }

    public static function get_module_icon():string {
        return 'view-list-alt';
    }

    public static function get_js_css():array {
        return array(
            'css' => array('post' => 'post', self::SLUG => 1)
        );
    }

    public static function get_json_file():array{
        return ['f'=>THEMIFY_BUILDER_URI.'/json/'.self::SLUG.'.json','v'=>THEMIFY_VERSION];
    }

    public static function builder_active_enqueue(array $vars ):array {
        $vars['addons'][THEMIFY_BUILDER_URI . '/js/editor/deprecated/'.self::SLUG.'.js']=THEMIFY_VERSION;
        return $vars;
    }

    public static function get_metabox() {
        // Highlight Meta Box Options
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
            Themify_Builder_Model::$lightbox_link
        );
    }

    public static function do_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => '',
            'title' => 'yes', // no
            'image' => 'yes', // no
            'image_w' => 68,
            'image_h' => 68,
            'display' => 'content', // excerpt, none
            'more_link' => false, // true goes to post type archive, and admits custom link
            'more_text' => __('More &rarr;', 'themify'),
            'limit' => 6,
            'category' => 0, // integer category ID
            'order' => 'DESC', // ASC
            'orderby' => 'date', // title, rand
            'style' => 'grid3', // grid4, grid2, list-post
            'section_link' => false // true goes to post type archive, and admits custom link
            ), $atts);

        $module = array(
            'module_ID' => self::SLUG.'-' . rand(0, 10000),
            'mod_name' => self::SLUG,
            'mod_settings' => array(
                'mod_title_highlight' => '',
                'layout_highlight' => $atts['style'],
                'category_highlight' => $atts['category'],
                'post_per_page_highlight' => $atts['limit'],
                'offset_highlight' => '',
                'order_highlight' => $atts['order'],
                'orderby_highlight' => $atts['orderby'],
                'display_highlight' => $atts['display'],
                'hide_feat_img_highlight' => $atts['image'] === 'yes' ? 'no' : 'yes',
                'image_size_highlight' => '',
                'img_width_highlight' => $atts['image_w'],
                'img_height_highlight' => $atts['image_h'],
                'hide_post_title_highlight' => $atts['title'] === 'yes' ? 'no' : 'yes',
                'hide_post_date_highlight' => '',
                'hide_post_meta_highlight' => '',
                'hide_page_nav_highlight' => 'yes',
                'animation_effect' => '',
                'css_highlight' => ''
            )
        );

        return self::retrieve_template('template-blog.php', $module, THEMIFY_BUILDER_TEMPLATES_DIR, '', false);
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
		return [ 'mod_title_highlight' ];
	}
}
TB_Highlight_Module::init();