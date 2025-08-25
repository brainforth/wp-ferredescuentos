<?php
defined('ABSPATH') || exit;

class Themify_Builder_Admin{

    public static function init():void{
        global $pagenow;
        if ($pagenow === 'edit.php' || $pagenow === 'post-new.php') {
            $post_type = isset($_GET['post_type']) ? $_GET['post_type'] : 'post';
        } elseif ('post.php' === $pagenow && isset($_GET['post'])) {
            $post_type = get_post_type($_GET['post']);
        }
        if((empty($post_type) || !Themify_Builder_Model::is_builder_disabled_for_post_type($post_type))){
            self::run();
        }
    }
    
    private static function run():void{
        // Filtered post types
        add_filter('themify_post_types', array(__CLASS__, 'extend_post_types'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'check_admin_interface'), 10);
        add_action('wp_ajax_tb_save_ajax_builder_mutiple_posts', array(__CLASS__, 'save_ajax_builder_mutiple_posts'));
        // Switch to frontend
        add_action('save_post', array(__CLASS__, 'switch_frontend'), 999, 1);
        // Disable WP Editor
        add_filter('is_protected_meta', array(__CLASS__, 'is_protected_meta'), 10, 3);
        add_filter( 'hidden_meta_boxes', [ __CLASS__, 'hidden_meta_boxes' ], 10, 3 );
    }

    
    /**
     * Includes this custom post to array of cpts managed by Themify
     * @param Array
     * @return Array
     */
    public static function extend_post_types(array $types):array {
        static $post_types = null;
        if ($post_types === null) {
            $post_types = array_keys(array_flip(array_merge(
                Themify_Builder::$registered_post_types, array_values(get_post_types(array(
                'public' => true,
                '_builtin' => false,
                'show_ui' => true
                )))
            )));
        }
        return array_keys(array_flip(array_merge($types, $post_types)));
    }

    /**
    * Builder write panels
    *
    * @param $meta_boxes
    *
    * @return array
    */
   public static function builder_write_panels(array $meta_boxes):array {
       if (Themify_Builder_Model::is_gutenberg_editor()) {
           return $meta_boxes;
       }
       // Page builder Options
       $page_builder_options = apply_filters('themify_builder_write_panels_options', array(
           array(
               'name' => 'page_builder',
               'title' => __('Themify Builder', 'themify'),
               'description' => '',
               'type' => 'page_builder'
           )
       ));

       $types = Themify_Builder::builder_post_types_support();
       $all_meta_boxes = array();
       foreach ($types as $type) {
           $all_meta_boxes[] = apply_filters('themify_builder_write_panels_meta_boxes', array(
               'name' => __('Themify Builder', 'themify'),
               'id' => 'page-builder',
               'options' => $page_builder_options,
               'pages' => $type
           ));
       }

       return array_merge($meta_boxes, $all_meta_boxes);
   }
    
    /**
    * Add builder metabox
    */
    public static function add_builder_metabox():void {
        include THEMIFY_BUILDER_INCLUDES_DIR . '/themify-builder-meta.php';
    }

    
        

    /**
     * Load admin js and css
     */
    public static function check_admin_interface(string $hook):void {
        if (in_array($hook, array('post-new.php', 'post.php'), true) && in_array(get_post_type(), themify_post_types(), true) && Themify_Access_Role::check_access_backend( get_the_ID() ) ) {
            add_action('admin_footer', array('Themify_Builder_Active', 'load_javascript_template_admin'), 10);
            add_filter('admin_body_class', array(__CLASS__, 'admin_body_class'), 10, 1);
            add_filter('mce_css', array(__CLASS__, 'static_badge_css'));
            add_filter('themify_do_metaboxes', array(__CLASS__, 'builder_write_panels'), 11);
            add_action('themify_builder_metabox', array(__CLASS__, 'add_builder_metabox'), 10);
            add_action('edit_form_after_title', array(__CLASS__, 'disable_wp_editor'), 99);
        }
    }

    

    /**
     * Switch to frontend
     */
    public static function switch_frontend(?int $post_id) {
        //verify post is not a revision
        if (isset($_POST['tb_switch_frontend']) && $_POST['tb_switch_frontend'] === 'yes' && Themify_Builder::$builder_is_saving !== true && !wp_is_post_revision($post_id)) {
            $post_url = get_permalink($post_id);
            wp_redirect(themify_https_esc($post_url) . '#builder_active');
            exit;
        }
    }

    
    /**
     * Disable WP Editor
     */
    public static function disable_wp_editor():void {
        if (themify_builder_get('setting-page_builder_disable_wp_editor', 'builder_disable_wp_editor') && themify_builder_get('setting-page_builder_is_active') !== 'disable') {
            echo '<div class="tb_wp_editor_holder">
                <a href="' . get_permalink() . '#builder_active">' . esc_html__('Edit With Themify Builder', 'themify') . '</a>
            </div>';
        }
    }

    /**
     * Hide Builder meta fields from Custom Fields admin panel
     *
     * @hooked to "is_protected_meta"
     */
    public static function is_protected_meta(bool $protected, $meta_key, $meta_type):bool {
        if ($meta_key === 'tbp_custom_css') {
            $protected = true;
        }
        return $protected;
    }


    /**
     * Register css in tinymce editor.
     */
    public static function static_badge_css(string $mce_css):string {
        $mce_css .= ', ' . THEMIFY_BUILDER_URI . '/css/editor/backend/themify-builder-static-badge.css';
        return $mce_css;
    }

    public static function admin_body_class(?string $classes): string {
        return $classes . ' builder-breakpoint-desktop tb_panel_closed';
    }

    public static function save_ajax_builder_mutiple_posts() {
        check_ajax_referer('tf_nonce', 'nonce');
        if (current_user_can('edit_pages')) {
            if (isset($_POST['data'])) {
                $data = stripslashes_deep($_POST['data']);
            } elseif (isset($_FILES['data'])) {
                $data = file_get_contents($_FILES['data']['tmp_name']);
            }
            if (!empty($data)) {
                $data = json_decode($data, true);
                $results = array();
                foreach ($data as $post_id => $builder) {
                    if (current_user_can('edit_post', $post_id)) {
                        $res = ThemifyBuilder_Data_Manager::save_data($builder, $post_id);
                        $results[$post_id] = !empty($res['mid']) ? 1 : sprintf(__('Can`t save builder data of post "%s"', 'themify'), get_the_title($post_id));
                    } else {
                        $results[$post_id] = sprintf(__('You don`t have permission to edit post "%s"', 'themify'), get_the_title($post_id));
                    }
                }
                wp_send_json_success($results);
            }
        } else {
            wp_send_json_error(__('You don`t have permission to edit pages', 'themify'));
        }
        wp_send_json_error();
    }

    /**
     * prevent hiding the Themify Custom Panel on Builder's templating post types
     */
    public static function hidden_meta_boxes( $hidden, $screen, $use_defaults ) : array {
        if ( $screen->base === 'post' && in_array( $screen->post_type, [ Themify_Builder_Layouts::LAYOUT_PART_SLUG, Themify_Builder_Layouts::LAYOUT_SLUG, 'tbp_template' ], true ) ) {
            $themify_metabox = array_search( 'themify-meta-boxes', $hidden, true );
            if ( $themify_metabox !== false ) {
                unset( $hidden[ $themify_metabox ] );
            }
        }

        return $hidden;
    }
}
Themify_Builder_Admin::init();