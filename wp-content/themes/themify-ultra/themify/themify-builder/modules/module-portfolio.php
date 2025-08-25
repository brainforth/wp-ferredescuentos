<?php

defined('ABSPATH') || exit;

/**
 * Module Name: Portfolio
 * Description: Display portfolio custom post type
 */
class TB_Portfolio_Module extends Themify_Builder_Component_Module {//deprecated
    const SLUG='portfolio';
    public static function init():void {
        ///////////////////////////////////////
        // Load Post Type
        ///////////////////////////////////////
        include_once ABSPATH . 'wp-admin/includes/plugin.php';
        if (!Themify_Builder_Model::is_plugin_active('themify-portfolio-post/themify-portfolio-post.php')) {
            add_filter('themify_metabox/fields/themify-meta-boxes', array(__CLASS__, 'cpt_meta_boxes'), 100); // requires low priority so that it loads after theme's metaboxes
            if (!shortcode_exists('themify_portfolio_posts')) {
                add_shortcode('themify_portfolio_posts', array(__CLASS__, 'do_shortcode'));
            }
        }
    }

    public static function is_available():bool{
        return Themify_Builder_Model::is_cpt_active(self::SLUG);
    }

    public static function get_module_name():string {
        add_filter( 'themify_builder_active_vars', [ __CLASS__, 'builder_active_enqueue' ] );
        return __('Portfolio', 'themify');
    }

    public static function get_module_icon():string {
        return 'briefcase';
    }

    public static function get_js_css():array {
        return array(
            'css' => array('post' => 'post')
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
        /** Portfolio Meta Box Options */
        return array(
            // Featured Image Size
            Themify_Builder_Model::$featured_image_size,
            // Image Width
            Themify_Builder_Model::$image_width,
            // Image Height
            Themify_Builder_Model::$image_height,
            // Hide Title
            array(
                'name' => 'hide_post_title',
                'title' => __('Hide Post Title', 'themify'),
                'description' => '',
                'type' => 'dropdown',
                'meta' => array(
                    array('value' => 'default', 'name' => '', 'selected' => true),
                    array('value' => 'yes', 'name' => __('Yes', 'themify')),
                    array('value' => 'no', 'name' => __('No', 'themify'))
                )
            ),
            // Unlink Post Title
            array(
                'name' => 'unlink_post_title',
                'title' => __('Unlink Post Title', 'themify'),
                'description' => __('Unlink post title (it will display the post title without link)', 'themify'),
                'type' => 'dropdown',
                'meta' => array(
                    array('value' => 'default', 'name' => '', 'selected' => true),
                    array('value' => 'yes', 'name' => __('Yes', 'themify')),
                    array('value' => 'no', 'name' => __('No', 'themify'))
                )
            ),
            // Hide Post Date
            array(
                'name' => 'hide_post_date',
                'title' => __('Hide Post Date', 'themify'),
                'description' => '',
                'type' => 'dropdown',
                'meta' => array(
                    array('value' => 'default', 'name' => '', 'selected' => true),
                    array('value' => 'yes', 'name' => __('Yes', 'themify')),
                    array('value' => 'no', 'name' => __('No', 'themify'))
                )
            ),
            // Hide Post Meta
            array(
                'name' => 'hide_post_meta',
                'title' => __('Hide Post Meta', 'themify'),
                'description' => '',
                'type' => 'dropdown',
                'meta' => array(
                    array('value' => 'default', 'name' => '', 'selected' => true),
                    array('value' => 'yes', 'name' => __('Yes', 'themify')),
                    array('value' => 'no', 'name' => __('No', 'themify'))
                )
            ),
            // Hide Post Image
            array(
                'name' => 'hide_post_image',
                'title' => __('Hide Featured Image', 'themify'),
                'description' => '',
                'type' => 'dropdown',
                'meta' => array(
                    array('value' => 'default', 'name' => '', 'selected' => true),
                    array('value' => 'yes', 'name' => __('Yes', 'themify')),
                    array('value' => 'no', 'name' => __('No', 'themify'))
                )
            ),
            // Unlink Post Image
            array(
                'name' => 'unlink_post_image',
                'title' => __('Unlink Featured Image', 'themify'),
                'description' => __('Display the Featured Image without link', 'themify'),
                'type' => 'dropdown',
                'meta' => array(
                    array('value' => 'default', 'name' => '', 'selected' => true),
                    array('value' => 'yes', 'name' => __('Yes', 'themify')),
                    array('value' => 'no', 'name' => __('No', 'themify'))
                )
            ),
            // External Link
            Themify_Builder_Model::$external_link,
            // Lightbox Link
            Themify_Builder_Model::$lightbox_link
        );
    }

    public static function do_shortcode($atts) {

        $atts = shortcode_atts(array(
            'id' => '',
            'title' => 'yes',
            'unlink_title' => 'no',
            'image' => 'yes', // no
            'image_w' => '',
            'image_h' => '',
            'display' => 'none', // excerpt, content
            'post_meta' => 'yes', // yes
            'post_date' => 'yes', // yes
            'more_link' => false, // true goes to post type archive, and admits custom link
            'more_text' => __('More &rarr;', 'themify'),
            'limit' => 4,
            'category' => 0, // integer category ID
            'order' => 'DESC', // ASC
            'orderby' => 'date', // title, rand
            'style' => '', // grid3, grid2
            'sorting' => 'no', // yes
            'page_nav' => 'no', // yes
            'paged' => '0', // internal use for pagination, dev: previously was 1
            // slider parameters
            'autoplay' => '',
            'effect' => '',
            'timeout' => '',
            'speed' => ''
            ), $atts);

        $module = array(
            'module_ID' => self::SLUG.'-' . rand(0, 10000),
            'mod_name' =>  self::SLUG,
            'mod_settings' => array(
                'mod_title_portfolio' => '',
                'layout_portfolio' => $atts['style'],
                'category_portfolio' => $atts['category'],
                'post_per_page_portfolio' => $atts['limit'],
                'offset_portfolio' => '',
                'order_portfolio' => $atts['order'],
                'orderby_portfolio' => $atts['orderby'],
                'display_portfolio' => $atts['display'],
                'hide_feat_img_portfolio' => $atts['image'] === 'yes' ? 'no' : 'yes',
                'image_size_portfolio' => '',
                'img_width_portfolio' => $atts['image_w'],
                'img_height_portfolio' => $atts['image_h'],
                'unlink_feat_img_portfolio' => 'no',
                'hide_post_title_portfolio' => $atts['title'] === 'yes' ? 'no' : 'yes',
                'unlink_post_title_portfolio' => $atts['unlink_title'],
                'hide_post_date_portfolio' => $atts['post_date'] === 'yes' ? 'no' : 'yes',
                'hide_post_meta_portfolio' => $atts['post_meta'] === 'yes' ? 'no' : 'yes',
                'hide_page_nav_portfolio' => $atts['page_nav'] === 'no' ? 'yes' : 'no',
                'animation_effect' => '',
                'css_portfolio' => ''
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
		return [ 'mod_title_portfolio' ];
	}
}


TB_Portfolio_Module::init(); //deprecated
