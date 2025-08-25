<?php

defined('ABSPATH') || exit;

/**
 * Module Name: Testimonial Posts
 * Description: Display testimonial custom post type
 */
class TB_Testimonial_Module extends Themify_Builder_Component_Module {//deprecated
    const SLUG='testimonial';
    public static function init():void {
        add_filter('themify_metabox/fields/themify-meta-boxes', array(__CLASS__, 'cpt_meta_boxes'), 100); // requires low priority so that it loads after theme's metaboxes
        if (!shortcode_exists('themify_testimonial_posts')) {
            add_shortcode('themify_testimonial_posts', array(__CLASS__, 'do_shortcode'));
        }
    }

    public static function get_module_name():string {
        add_filter( 'themify_builder_active_vars', [ __CLASS__, 'builder_active_enqueue' ] );
        return __('Testimonial Posts', 'themify');
    }

    public static function get_module_icon():string {
        return 'clipboard';
    }

    public static function get_js_css():array {
        return array(
            'css' => 1
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
        // Testimonial Meta Box Options
        return array(
            // Featured Image Size
            Themify_Builder_Model::$featured_image_size,
            // Image Width
            Themify_Builder_Model::$image_width,
            // Image Height
            Themify_Builder_Model::$image_height,
            // Testimonial Author Name
            array(
                'name' => '_testimonial_name',
                'title' => __('Testimonial Author Name', 'themify'),
                'description' => '',
                'type' => 'textbox',
                'meta' => array()
            ),
            // Testimonial Author Link
            array(
                'name' => '_testimonial_link',
                'title' => __('Testimonial Author Link', 'themify'),
                'description' => '',
                'type' => 'textbox',
                'meta' => array()
            ),
            // Testimonial Author Company
            array(
                'name' => '_testimonial_company',
                'title' => __('Testimonial Author Company', 'themify'),
                'description' => '',
                'type' => 'textbox',
                'meta' => array()
            ),
            // Testimonial Author Position
            array(
                'name' => '_testimonial_position',
                'title' => __('Testimonial Author Position', 'themify'),
                'description' => '',
                'type' => 'textbox',
                'meta' => array()
            )
        );
    }

    public static function do_shortcode($atts) {

        $atts = shortcode_atts(array(
            'id' => '',
            'title' => 'no', // no
            'image' => 'yes', // no
            'image_w' => 80,
            'image_h' => 80,
            'display' => 'content', // excerpt, none
            'more_link' => false, // true goes to post type archive, and admits custom link
            'more_text' => __('More &rarr;', 'themify'),
            'limit' => 4,
            'category' => 0, // integer category ID
            'order' => 'DESC', // ASC
            'orderby' => 'date', // title, rand
            'style' => 'grid2', // grid3, grid4, list-post
            'show_author' => 'yes', // no
            'section_link' => false // true goes to post type archive, and admits custom link
            ), $atts);

        $module = array(
            'module_ID' => self::SLUG.'-' . rand(0, 10000),
            'mod_name' => self::SLUG,
            'mod_settings' => array(
                'mod_title_testimonial' => '',
                'layout_testimonial' => $atts['style'],
                'category_testimonial' => $atts['category'],
                'post_per_page_testimonial' => $atts['limit'],
                'offset_testimonial' => '',
                'order_testimonial' => $atts['order'],
                'orderby_testimonial' => $atts['orderby'],
                'display_testimonial' => $atts['display'],
                'hide_feat_img_testimonial' => '',
                'image_size_testimonial' => '',
                'img_width_testimonial' => $atts['image_w'],
                'img_height_testimonial' => $atts['image_h'],
                'unlink_feat_img_testimonial' => 'no',
                'hide_post_title_testimonial' => $atts['title'] === 'yes' ? 'no' : 'yes',
                'unlink_post_title_testimonial' => 'no',
                'hide_post_date_testimonial' => 'no',
                'hide_post_meta_testimonial' => 'no',
                'hide_page_nav_testimonial' => 'yes',
                'animation_effect' => '',
                'css_testimonial' => ''
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
		return [ 'mod_title_testimonial' ];
	}
}

///////////////////////////////////////
// Module Options
///////////////////////////////////////

TB_Testimonial_Module::init(); //deprecated
