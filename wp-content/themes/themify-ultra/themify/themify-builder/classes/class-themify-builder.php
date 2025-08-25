<?php
defined('ABSPATH') || exit;

if (!class_exists('Themify_Builder',false)) :

    /**
     * Main Themify Builder class
     *
     * @package default
     */
    class Themify_Builder {

        /**
         * @var array
         */
        public static $registered_post_types = array('post', 'page');

        /**
         * Define builder grid active or not
         * @var bool
         */
        public static $frontedit_active = false;

        /**
         * Define builder grid active id
         * @var int
         */
        public static $builder_active_id = null;

        /**
         * Get status of builder content whether inside builder content or not
         */
        public static $is_loop = false;

        /**
         * A list of posts which have been rendered by Builder
         */
        private static $post_ids = array();
        public static $builder_is_saving = false;
        private static $tooltips = [];

        /* flag to indicate when Builder is rendering the output */
        private static $is_rendering = false;

        public $in_the_loop = false;//deprecated use $is_loop

        /**
         * Themify Builder Constructor
         */
        public function __construct() {
            
        }

        /**
         * Class Init
         */
        public static function init():void {
            add_action('init', array(__CLASS__, 'register_deprecated_cpt'));
            // Include required files
            self::includes_always();
            Themify_Builder_Layouts::init();
            Themify_Global_Styles::init();

            // Plugin compatibility
            self::plugins_compatibility();

            if (is_admin() || themify_is_rest()) {
                add_action('wp_loaded', array(__CLASS__, 'wp_loaded'));
            } else {
                add_action('wp', array(__CLASS__, 'wp_loaded'), 100);
            }

            // Login module action for failed login
            add_action('wp_login_failed', array(__CLASS__, 'wp_login_failed'));
        }

        public static function register_deprecated_cpt():void {
            Themify_Builder_Model::builder_cpt_check();
            $post_types = array(
                'portfolio' => array(
                    'plural' => __('Portfolios', 'themify'),
                    'singular' => __('Portfolio', 'themify'),
                    'rewrite' => apply_filters('themify_portfolio_rewrite', 'project'),
                    'menu_icon' => 'dashicons-portfolio'
                ),
                'highlight' => array(
                    'plural' => __('Highlights', 'themify'),
                    'singular' => __('Highlight', 'themify'),
                    'menu_icon' => 'dashicons-welcome-write-blog'
                ),
                'slider' => array(
                    'plural' => __('Slides', 'themify'),
                    'singular' => __('Slide', 'themify'),
                    'supports' => array('title', 'editor', 'author', 'thumbnail', 'custom-fields'),
                    'menu_icon' => 'dashicons-slides'
                ),
                'testimonial' => array(
                    'plural' => __('Testimonials', 'themify'),
                    'singular' => __('Testimonial', 'themify'),
                    'menu_icon' => 'dashicons-testimonial'
                )
            );

            foreach ($post_types as $key => $args) {
                if (Themify_Builder_Model::is_cpt_active($key) && Themify_Builder_Model::is_module_active($key)) {

                    if (!post_type_exists($key)) {
                        $options = array(
                            'labels' => array(
                                'name' => $args['plural'],
                                'singular_name' => $args['singular'],
                                'add_new' => __('Add New', 'themify'),
                                'add_new_item' => sprintf(__('Add New %s', 'themify'), $args['singular']),
                                'edit_item' => sprintf(__('Edit %s', 'themify'), $args['singular']),
                                'new_item' => sprintf(__('New %s', 'themify'), $args['singular']),
                                'view_item' => sprintf(__('View %s', 'themify'), $args['singular']),
                                'search_items' => sprintf(__('Search %s', 'themify'), $args['plural']),
                                'not_found' => sprintf(__('No %s found', 'themify'), $args['plural']),
                                'not_found_in_trash' => sprintf(__('No %s found in Trash', 'themify'), $args['plural']),
                                'menu_name' => $args['plural']
                            ),
                            'supports' => isset($args['supports']) ? $args['supports'] : array('title', 'editor', 'thumbnail', 'custom-fields', 'excerpt'),
                            'hierarchical' => false,
                            'public' => true,
                            'show_ui' => true,
                            'show_in_menu' => true,
                            'show_in_nav_menus' => false,
                            'publicly_queryable' => true,
                            'rewrite' => array('slug' => isset($args['rewrite']) ? $args['rewrite'] : strtolower($args['singular'])),
                            'query_var' => true,
                            'can_export' => true,
                            'capability_type' => 'post',
                            'menu_icon' => isset($args['menu_icon']) ? $args['menu_icon'] : ''
                        );

                        register_post_type($key, $options);
                        self::$registered_post_types[]=$key;
                    }
                    if (!taxonomy_exists($key . '-category')) {
                        $options = array(
                            'labels' => array(
                                'name' => sprintf(__('%s Categories', 'themify'), $args['singular']),
                                'singular_name' => sprintf(__('%s Category', 'themify'), $args['singular']),
                                'search_items' => sprintf(__('Search %s Categories', 'themify'), $args['singular']),
                                'popular_items' => sprintf(__('Popular %s Categories', 'themify'), $args['singular']),
                                'all_items' => sprintf(__('All Categories', 'themify'), $args['singular']),
                                'parent_item' => sprintf(__('Parent %s Category', 'themify'), $args['singular']),
                                'parent_item_colon' => sprintf(__('Parent %s Category:', 'themify'), $args['singular']),
                                'edit_item' => sprintf(__('Edit %s Category', 'themify'), $args['singular']),
                                'update_item' => sprintf(__('Update %s Category', 'themify'), $args['singular']),
                                'add_new_item' => sprintf(__('Add New %s Category', 'themify'), $args['singular']),
                                'new_item_name' => sprintf(__('New %s Category', 'themify'), $args['singular']),
                                'separate_items_with_commas' => sprintf(__('Separate %s Category with commas', 'themify'), $args['singular']),
                                'add_or_remove_items' => sprintf(__('Add or remove %s Category', 'themify'), $args['singular']),
                                'choose_from_most_used' => sprintf(__('Choose from the most used %s Category', 'themify'), $args['singular']),
                                'menu_name' => sprintf(__('%s Category', 'themify'), $args['singular']),
                            ),
                            'public' => true,
                            'show_in_nav_menus' => false,
                            'show_ui' => true,
                            'show_admin_column' => true,
                            'show_tagcloud' => true,
                            'hierarchical' => true,
                            'rewrite' => true,
                            'query_var' => true
                        );

                        register_taxonomy($key . '-category', $key, $options);
                    }
                }
            }
        }

        public static function wp_loaded() {
            add_filter('themify_builder_module_content', array('Themify_Builder_Model', 'format_text'));
            if (Themify_Builder_Model::is_front_builder_activate() === false) {
                $func = function_exists('wp_filter_content_tags') ? 'wp_filter_content_tags' : 'wp_make_content_images_responsive';
                add_filter('themify_builder_module_content', $func);
                add_filter('themify_image_make_responsive_image', $func);
                add_action('themify_builder_background_styling', array(__CLASS__, 'display_tooltip'), 10, 3);
                add_action( 'template_include', [ __CLASS__, 'template_include' ], 1000 );
            }
             elseif (isset($_GET['tb-id']) || isset($_COOKIE['tb_active'])) {
                self::$builder_active_id = isset($_GET['tb-id']) ? (int) $_GET['tb-id'] : (int) $_COOKIE['tb_active'];
                themify_disable_other_lazy();
            }
            // Actions
            self::setup();
            if (did_action('wp') > 0) {
                self::wp_hook();
            } else {
                add_action('wp', array(__CLASS__, 'wp_hook'));
            } 
            $is_ajax=themify_is_ajax(); 
            $is_admin=is_admin();
            $has_access=($is_admin===true && $is_ajax===false) || ($is_ajax===true && isset($_POST['admin']))?Themify_Access_Role::check_access_backend():Themify_Builder_Model::is_frontend_editor_page();
            if ($has_access===true) {
                self::includes_active();
                if($is_admin===true){
                    self::includes_admin();
                }
            }

            // Script Loader
            add_action('wp_enqueue_scripts', array(__CLASS__, 'register_js_css'), 9);

            // Hook to frontend
            add_action('wp_head', array(__CLASS__, 'inline_css'), -1001);
            add_filter('the_content', array(__CLASS__, 'clear_static_content'), 1);
            add_filter('the_content', array(__CLASS__, 'builder_show_on_front'), 11);
            add_filter('body_class', array(__CLASS__, 'body_class'), 10);
            // Add extra protocols like skype: to WordPress allowed protocols.
            if (!has_filter('kses_allowed_protocols', 'themify_allow_extra_protocols') && function_exists('themify_allow_extra_protocols')) {
                add_filter('kses_allowed_protocols', 'themify_allow_extra_protocols');
            }

            Themify_Builder_Stylesheet::init();
            // Visibility controls
            Themify_Builder_Visibility_Controls::init();

            //convert old data to new grid data,can be removed after updates

            add_action('wp_ajax_nopriv_tb_update_old_data', array(__CLASS__, 'convert_data'), 10);
            add_action('wp_ajax_tb_update_old_data', array(__CLASS__, 'convert_data'), 10);
        }

        public static function template_include( $template ) {
            if ( is_singular() && isset( $_GET['post_in_lightbox'] ) ) {
                show_admin_bar(false);
                wp_enqueue_style( 'tb_post_lightbox', THEMIFY_BUILDER_CSS_MODULES . 'post-lightbox.css', null, THEMIFY_VERSION);
				add_filter( 'body_class', [ __CLASS__, 'post_in_lightbox_body_class' ] );
				if ( current_theme_supports( 'themify-builder-lightbox-template' ) ) {
					global $themify;
					$themify->header_enabled = false;
					$themify->footer_enabled = false;
					$themify->layout = 'sidebar-none';
				} else {
					$template = Themify_Builder_Component_Module::locate_template( 'post_in_lightbox.php' );
				}
            }

            return $template;
        }

		public static function post_in_lightbox_body_class( $classes ) {
			$classes[] = 'tb_post_lightbox';
			return $classes;
		}

        /**
         * Return all modules for a post as a two-dimensional array
         * @since 7.5
         * @return bool|array
         */
        public static function get_builder_modules_list(?int $post_id = null,?array $builder_data = null,bool $only_check = false) {
            if ($builder_data === null) {
                $builder_data = ThemifyBuilder_Data_Manager::get_data($post_id);
            }
            if ($only_check !== false) {
                return strpos(json_encode($builder_data), 'mod_settings') !== false;
            }
            $_modules = array();
            // loop through modules in Builder
            if (is_array($builder_data)) {
                foreach ($builder_data as $row) {
                    $_modules = array_merge( $_modules, self::_get_modules_recursive( $row ) );
                }
            }

            return $_modules;
        }

        /**
         * Find modules within the $row, recursively search for submodules
         *
         * @return array
         */
        private static function _get_modules_recursive( $row ) : array {
            $modules = [];
            if ( ! empty( $row['cols'] ) ) {
                foreach ( $row['cols'] as $col ) {
                    if ( ! empty( $col['modules'] ) ) {
                        foreach ( $col['modules'] as $module ) {
                            if ( isset( $module['mod_name'] ) ) {
                                $modules[] = $module;
                            }
                            $modules = array_merge( $modules, self::_get_modules_recursive( $module ) );
                        }
                    }
                }
            }

            return $modules;
        }

        /**
         * Return first not empty text module
         */
        public static function get_first_text(?int $post_id = null,?array $builder_data = null):string {
            if ($builder_data === null) {
                $builder_data = ThemifyBuilder_Data_Manager::get_data($post_id);
            }
            // loop through modules in Builder
            if (is_array($builder_data)) {
                foreach ($builder_data as $row) {
                    if (!empty($row['cols'])) {
                        foreach ($row['cols'] as $col) {
                            if (!empty($col['modules'])) {
                                foreach ($col['modules'] as $mod) {
                                    if (isset($mod['mod_name']) && $mod['mod_name'] === 'text' && !empty($mod['mod_settings']['content_text'])) {
                                        return $mod['mod_settings']['content_text'];
                                    }
                                    // Check for Sub-rows
                                    if (!empty($mod['cols'])) {
                                        foreach ($mod['cols'] as $sub_col) {
                                            if (!empty($sub_col['modules'])) {
                                                foreach ($sub_col['modules'] as $sub_module) {
                                                    if (isset($sub_module['mod_name']) && $sub_module['mod_name'] === 'text' && !empty($sub_module['mod_settings']['content_text'])) {
                                                        return $sub_module['mod_settings']['content_text'];
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

            return '';
        }


        /**
         * Init function
         */
        private static function setup():void {
            do_action('themify_builder_setup_modules');
            if ((!empty($_REQUEST['action']) && !in_array($_REQUEST['action'], array('tb_update_tick', 'tb_load_module_partial', 'tb_render_element', 'tb_module_favorite', 'themify_regenerate_css_files_ajax', 'render_element_shortcode_ajaxify', 'get_ajax_post_types', 'tb_help'), true) && themify_is_ajax()) || Themify_Builder_Model::is_front_builder_activate()) {
                Themify_Builder_Component_Module::load_modules();
            }
        }

        public static function wp_hook():void {
            do_action('themify_builder_run');
        }

        private static function includes_always():void {
            if (Themify_Builder_Model::is_gutenberg_active()) {
                include THEMIFY_BUILDER_CLASSES_DIR . '/class-themify-builder-gutenberg.php';
            }
            include THEMIFY_BUILDER_CLASSES_DIR . '/class-builder-data-manager.php';
            include THEMIFY_BUILDER_CLASSES_DIR . '/class-themify-builder-stylesheet.php';
            include THEMIFY_BUILDER_CLASSES_DIR . '/class-themify-builder-widgets.php';
            include THEMIFY_BUILDER_CLASSES_DIR . '/class-themify-builder-visibility-controls.php';
            if (current_user_can('publish_pages')) {
                include THEMIFY_BUILDER_CLASSES_DIR . '/class-themify-builder-page.php';
            }

            include THEMIFY_BUILDER_INCLUDES_DIR . '/components/base.php';
            include THEMIFY_BUILDER_INCLUDES_DIR . '/components/row.php';
            include THEMIFY_BUILDER_INCLUDES_DIR . '/components/subrow.php';
            include THEMIFY_BUILDER_INCLUDES_DIR . '/components/column.php';
            include THEMIFY_BUILDER_INCLUDES_DIR . '/components/module.php';
        }

        private static function includes_active():void {
            include THEMIFY_BUILDER_CLASSES_DIR . '/class-themify-builder-active.php';
        }

        private static function includes_admin():void {
            include THEMIFY_BUILDER_CLASSES_DIR . '/class-themify-builder-admin.php';
        }

        /**
         * List of post types that support the editor
         *
         * @since 2.4.8
         */
        public static function builder_post_types_support():array {
            $public_post_types = get_post_types(array(
                'public' => true,
                '_builtin' => false,
                'show_ui' => true,
            ));
            $post_types = array_merge($public_post_types, array('post', 'page'));
            foreach ($post_types as $key => $type) {
                if (!post_type_supports($type, 'editor')) {
                    unset($post_types[$key]);
                }
            }

            return apply_filters('themify_builder_post_types_support', $post_types);
        }

        


        

        /**
         * Register styles and scripts necessary for Builder template output.
         * These are enqueued when a user initializes Builder or from a template output.
         *
         * Registered style handlers:
         *
         * Registered script handlers:
         * themify-builder-module-plugins-js
         * themify-builder-script-js
         *
         * @since 2.1.9
         */
        public static function register_js_css():void {
            add_action('wp_footer', array(__CLASS__, 'footer_js'));
        }

        public static function footer_js():void {
            $args = array(
                'fullwidth_support' => Themify_Builder_Model::is_fullwidth_layout_supported(),
                'is_sticky' => Themify_Builder_Model::is_sticky_scroll_active(),
                'is_animation' => Themify_Builder_Model::is_animation_active(),
                'is_parallax' => Themify_Builder_Model::is_parallax_active(),
                'scrollHighlight' => apply_filters('themify_builder_scroll_highlight_vars', array()) //Inject variable values in Scroll-Highlight script
            );
            if (Themify_Builder_Model::is_front_builder_activate()) {
                $modules=Themify_Builder_Component_Module::load_modules();
                foreach ($modules as $id=>$m) {
                    $assets = $m::get_js_css();
                    if (!empty($assets)) {
                        Themify_Builder_Component_Module::add_modules_assets($id, $assets);
                    }
                }
            } 
            elseif (!empty(self::$tooltips)) {
                Themify_Enqueue_Assets::addLocalization('builder_tooltips', self::$tooltips);
                self::$tooltips = null;
            }
            $offset = themify_builder_get('setting-scrollto_offset', 'builder_scrollTo');
            if (!empty($offset)) {
                $args['scrollHighlight']['offset'] = (int) $offset;
            }
            $speed = themify_builder_get('scrollto_speed', 'builder_scrollTo_speed');
            if ($speed === null) {
                $speed = .9;
            }
            $args['scrollHighlight']['speed'] = ( (float) $speed * 1000 ) + .01; /* .01 second is added in case user enters 0 to disable the animation */
            $args['addons'] = Themify_Builder_Component_Module::get_modules_assets();

            $args = apply_filters('themify_builder_script_vars', $args);
            if (true === $args['is_animation']) {
                unset($args['is_animation']);
            }
            if (true === $args['is_parallax']) {
                unset($args['is_parallax']);
            }
            if (true === $args['is_sticky']) {
                unset($args['is_sticky']);
            }
            if ($args['fullwidth_support'] === true) {
                unset($args['fullwidth_support']);
            } else {
                $args['fullwidth_support'] = 1;
            }
            Themify_Enqueue_Assets::localize_script('themify-main-script', 'tbLocalScript', $args);
            $args = null;
        }


        

        /**
         * Only need to save converted old css col padding to new grid css padding
         */
        public static function convert_data() {
            check_ajax_referer('tf_nonce', 'nonce');
            if (!empty($_POST['data']) && !empty($_POST['bid'])) {
                $id = (int) $_POST['bid'];
                $builder_data = ThemifyBuilder_Data_Manager::get_data($id);
                if (is_array($builder_data)) {
                    $convert = json_decode(stripslashes_deep($_POST['data']), true);
                    $update = false;
                    $breakpints = array_reverse(array_keys(themify_get_breakpoints()));
                    $breakpints[] = 'desktop';
                    $bpLength = count($breakpints);
                    $allowed = array('padding', 'padding_top', 'padding_bottom', 'padding_left', 'padding_right', 'padding_left_unit', 'padding_right_unit', 'padding_top_unit', 'padding_bottom_unit', 'margin-bottom', 'margin-top', 'margin-bottom_unit', 'margin-top_unit');
                    foreach ($builder_data as &$row) {
                        if (!empty($row['cols'])) {
                            foreach ($row['cols'] as &$col) {
                                if (isset($convert[$col['element_id']])) {
                                    $hasChange = false;
                                    foreach ($convert[$col['element_id']] as $bp => $props) {
                                        if (in_array($bp, $breakpints, true)) {
                                            foreach ($props as $prop => $v) {
                                                if (in_array($prop, $allowed, true)) {
                                                    if ($bp === 'desktop') {
                                                        if ($v === '%') {//unit proop
                                                            $col['styling'][$prop] = $v;
                                                        } else {
                                                            if (!isset($col['styling'][$prop])) {
                                                                $col['styling'][$prop] = '';
                                                            }
                                                            if (strpos($col['styling'][$prop], ',') === false && is_numeric($v)) {
                                                                //the first value is old value of v5(if user will try to downgrade FW),the second after converting
                                                                $v = (int) $v;
                                                                $col['styling'][$prop] .= ',' . $v;
                                                                $update = true;
                                                            }
                                                        }
                                                    } else {
                                                        if (!isset($col['styling']['breakpoint_' . $bp])) {
                                                            $col['styling']['breakpoint_' . $bp] = array();
                                                        }
                                                        if ($v === '%') {//unit proop
                                                            $col['styling']['breakpoint_' . $bp][$prop] = $v;
                                                        } else {
                                                            if (!isset($col['styling']['breakpoint_' . $bp][$prop])) {
                                                                $col['styling']['breakpoint_' . $bp][$prop] = '';
                                                            }
                                                            if (strpos($col['styling']['breakpoint_' . $bp][$prop], ',') === false && is_numeric($v)) {
                                                                $v = (int) $v;
                                                                $col['styling']['breakpoint_' . $bp][$prop] .= ',' . $v;
                                                                $update = $hasChange = true;
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                    if ($hasChange === true) {
                                        for ($i = 0; $i < $bpLength - 1; ++$i) {
                                            $bp = $breakpints[$i];
                                            if (!empty($col['styling']['breakpoint_' . $bp])) {
                                                $st = $col['styling']['breakpoint_' . $bp];
                                                foreach ($allowed as $p) {
                                                    if (isset($st[$p])) {
                                                        $parentSt = null;
                                                        for ($j = $i + 1; $j < $bpLength; ++$j) {
                                                            $parentBp = $breakpints[$j];
                                                            $parentSt = $parentBp === 'desktop' ? $col['styling'] : (isset($col['styling']['breakpoint_' . $parentBp]) ? $col['styling']['breakpoint_' . $parentBp] : null);
                                                            if (isset($parentSt[$p])) {
                                                                break;
                                                            }
                                                        }
                                                        if (isset($parentSt) && ($parentSt[$p] == $st[$p] || strpos($parentSt[$p], $st[$p]) !== false)) {
                                                            unset($col['styling']['breakpoint_' . $bp][$p]);
                                                        }
                                                    }
                                                }
                                                if (empty($col['styling']['breakpoint_' . $bp])) {
                                                    unset($col['styling']['breakpoint_' . $bp]);
                                                }
                                            }
                                        }
                                    }
                                }
                                if (!empty($col['modules'])) {
                                    foreach ($col['modules'] as &$mod) {
                                        // Check for Sub-rows
                                        if (!empty($mod['cols'])) {
                                            foreach ($mod['cols'] as &$sub_col) {
                                                if (isset($convert[$sub_col['element_id']])) {
                                                    $hasChange = false;
                                                    foreach ($convert[$sub_col['element_id']] as $bp => $props) {
                                                        if (in_array($bp, $breakpints, true)) {
                                                            foreach ($props as $prop => $v) {
                                                                if (in_array($prop, $allowed, true)) {
                                                                    if ($bp === 'desktop') {
                                                                        if ($v === '%') {//unit proop
                                                                            $sub_col['styling'][$prop] = $v;
                                                                        } else {
                                                                            if (!isset($sub_col['styling'][$prop])) {
                                                                                $sub_col['styling'][$prop] = '';
                                                                            }
                                                                            if (strpos($sub_col['styling'][$prop], ',') === false && is_numeric($v)) {
                                                                                $v = (int) $v;
                                                                                $sub_col['styling'][$prop] .= ',' . $v;
                                                                                $update = true;
                                                                            }
                                                                        }
                                                                    } else {
                                                                        if (!isset($sub_col['styling']['breakpoint_' . $bp])) {
                                                                            $sub_col['styling']['breakpoint_' . $bp] = array();
                                                                        }
                                                                        if ($v === '%') {//unit proop
                                                                            $sub_col['styling']['breakpoint_' . $bp][$prop] = $v;
                                                                        } else {
                                                                            if (!isset($sub_col['styling']['breakpoint_' . $bp][$prop])) {
                                                                                $sub_col['styling']['breakpoint_' . $bp][$prop] = '';
                                                                            }
                                                                            if (strpos($sub_col['styling']['breakpoint_' . $bp][$prop], ',') === false && is_numeric($v)) {
                                                                                $v = (int) $v;
                                                                                $sub_col['styling']['breakpoint_' . $bp][$prop] .= ',' . $v;
                                                                                $update = $hasChange = true;
                                                                            }
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }
                                                    if ($hasChange === true) {
                                                        for ($i = 0; $i < $bpLength - 1; ++$i) {
                                                            $bp = $breakpints[$i];
                                                            if (!empty($sub_col['styling']['breakpoint_' . $bp])) {
                                                                $st = $sub_col['styling']['breakpoint_' . $bp];
                                                                foreach ($allowed as $p) {
                                                                    if (isset($st[$p])) {
                                                                        $parentSt = null;
                                                                        for ($j = $i + 1; $j < $bpLength; ++$j) {
                                                                            $parentBp = $breakpints[$j];
                                                                            $parentSt = $parentBp === 'desktop' ? $sub_col['styling'] : (isset($sub_col['styling']['breakpoint_' . $parentBp]) ? $sub_col['styling']['breakpoint_' . $parentBp] : null);
                                                                            if (isset($parentSt[$p])) {
                                                                                break;
                                                                            }
                                                                        }
                                                                        if (isset($parentSt) && ($parentSt[$p] == $st[$p] || strpos($parentSt[$p], $st[$p]) !== false)) {
                                                                            unset($sub_col['styling']['breakpoint_' . $bp][$p]);
                                                                        }
                                                                    }
                                                                }
                                                                if (empty($sub_col['styling']['breakpoint_' . $bp])) {
                                                                    unset($sub_col['styling']['breakpoint_' . $bp]);
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                    unset($convert, $allowed, $breakpints);
                    if ($update === true) {
                        self::$builder_is_saving = true;
                        ThemifyBuilder_Data_Manager::update_builder_meta($id, $builder_data, false);
                    }
                }
            }
            die;
        }


        /**
         * Remove Builder static content, leaving an empty shell to inject Builder output in later.
         *
         * @return string
         */
        public static function clear_static_content(?string $content):?string {
            //skip for excerpt hook
            global $wp_current_filter;

            if (!in_array('get_the_excerpt', $wp_current_filter, true) && !Themify_Builder_Model::is_builder_disabled_for_post_type(get_post_type()) && ThemifyBuilder_Data_Manager::has_static_content($content)) {
                $empty_placeholder = ThemifyBuilder_Data_Manager::add_static_content_wrapper('');
                $content = ThemifyBuilder_Data_Manager::update_static_content_string($empty_placeholder, $content);
            }

            return $content;
        }

        /**
         * Hook to content filter to show builder output
         */
        public static function builder_show_on_front(?string $content=''):?string {

            global $post;
            $post_id = get_the_id();
            $is_gs_admin_page = isset($_GET['page']) && 'themify-global-styles' === $_GET['page'] && is_admin();
            // Exclude builder output in admin post list mode excerpt, Don`t show builder on product single description
            if (
                ($is_gs_admin_page === false && (!is_object($post) || ( is_admin() && !themify_is_ajax() ) || (!Themify_Builder_Model::is_front_builder_activate() && false === apply_filters('themify_builder_display', true, $post_id) ) || post_password_required()
                )) || (themify_is_woocommerce_active() && (themify_is_shop() || is_singular('product')))/* disable Builder display on WC pages. Those are handled in Themify_Builder_Plugin_Compat */
            ) {
                return $content;
            }

            //the_excerpt
            global $wp_current_filter;
            if (in_array('get_the_excerpt', $wp_current_filter, true)) {
                return $content ? $content : self::get_first_text($post_id);
            }

            if (strpos($post->post_content, '<!--more-->') !== false && !is_single($post->ID) && !is_page($post->ID)) {
                return $content;
            }

            return self::render($post_id, $content);
        }

        /**
         * Renders Builder data for a given $post_id
         *
         * If $content is sent, the function will attempt to find the proper place
         * where Builder content should be injected to. Otherwise, raw output is returned.
         */
        public static function render(?int $post_id,?string $content = ''):?string {
            if (!Themify_Builder_Model::is_builder_disabled_for_post_type(get_post_type($post_id))) {
                /* in the frontend editor, render only a container and set the frontend_builder_ids[] property */
                if ($post_id == self::$builder_active_id && Themify_Builder_Model::is_front_builder_activate()) {
                    Themify_Builder_Stylesheet::enqueue_stylesheet(false, $post_id);
                    $builder_output = sprintf('<div id="themify_builder_content-%1$d" data-postid="%1$d" class="tf_clear themify_builder_content themify_builder_content-%1$d themify_builder"></div>', $post_id);
                    Themify_Builder::get_builder_stylesheet('');
                } else {
                    /* Infinite-loop prevention */
                    if (in_array($post_id, self::$post_ids, true)) {
                        /* we have already rendered this, go back. */
                        return $content;
                    }
                    
                    $builder_data = ThemifyBuilder_Data_Manager::get_data($post_id);
                    if(empty($builder_data) && !Themify_Builder_Model::is_frontend_editor_page()){
                        return $content;
                    }
                    self::$post_ids[] = $post_id;
                    $template_args = array();
                    // Check For page break module
                    $page_breaks = self::count_page_break_modules($post_id);
                    if ($page_breaks > 0) {
                        $pb_result = self::load_current_inner_page_content($builder_data, $page_breaks);
                        $builder_data = $pb_result['builder_data'];
                        $template_args['pb_pagination'] = $pb_result['pagination'];
                        $pb_result = null;
                    }
                    $template_args['builder_output'] = $builder_data;
                    $template_args['builder_id'] = $post_id;
                    global $ThemifyBuilder;
                    $isLoop=self::$is_loop===true || $ThemifyBuilder->in_the_loop === true;
                    $template = $isLoop ? 'builder-layout-part-output.php' : 'builder-output.php';
                    self::$is_rendering = true;
                    $builder_output = Themify_Builder_Component_Module::retrieve_template($template, $template_args, THEMIFY_BUILDER_TEMPLATES_DIR, '', false);
                    self::$is_rendering = false;
                    if (strpos($builder_output, 'module_row') !== false) {
                        do_action('themify_builder_before_template_content_render');
                    }
                    if ($isLoop === false) {
                        Themify_Builder_Stylesheet::enqueue_stylesheet(false, $post_id);
                    }
                    Themify_Builder::get_builder_stylesheet($builder_output);

                    /* render finished, make the Builder content of this particular post available to be rendered again */
                    array_pop(self::$post_ids);
                }
                /* if $content parameter is empty, simply return the builder output, no need to replace anything */
                if ($content === '') {
                    return $builder_output;
                }

                /* find where Builder output should be injected to inside $content */
                // Start builder block replacement
                if (Themify_Builder_Model::is_gutenberg_active() && Themify_Builder_Gutenberg::has_builder_block($content)) {
                    $content = ThemifyBuilder_Data_Manager::update_static_content_string('', $content); // remove static content tag
                    $content = Themify_Builder_Gutenberg::replace_builder_block_tag($builder_output, $content);
                }
                elseif (ThemifyBuilder_Data_Manager::has_static_content($content)) {
                    $content = ThemifyBuilder_Data_Manager::update_static_content_string($builder_output, $content);
                } 
                else {
                    $display_position = apply_filters('themify_builder_display_position', 'below', $post_id);
                    if ('above' === $display_position) {
                        $content = $builder_output . $content;
                    } else {
                        $content .= $builder_output;
                    }
                }
            }

            return $content;
        }

        /**
         * Load stylesheet for Builder if necessary.
         */
        public static function get_builder_stylesheet(string $builder_output,bool $force = false) {
            /* in RSS feeds and REST API endpoints, do not output the scripts */
            if (self::$frontedit_active === true || is_feed() || themify_is_rest() || (isset($_GET['tf-scroll']) && $_GET['tf-scroll'] === 'yes' && themify_is_ajax())) {
                return '';
            }
            static $is = null;
            if ($is === null && ($force === true || Themify_Builder_Model::is_front_builder_activate() || strpos($builder_output, 'module_row') !== false )) { // check if builder has any content
                $is = true;
                Themify_Enqueue_Assets::addPreLoadJs(THEMIFY_BUILDER_URI . '/js/themify-builder-script.js', THEMIFY_VERSION);
                if (!themify_is_themify_theme() || !Themify_Enqueue_Assets::addCssToFile('builder-styles-css', THEMIFY_BUILDER_URI . '/css/themify-builder-style.css', THEMIFY_VERSION, 'themify_common')) {
                    themify_enque_style('builder-styles-css', THEMIFY_BUILDER_URI . '/css/themify-builder-style.css', null, THEMIFY_VERSION);
                }
                if (is_rtl() && !Themify_Enqueue_Assets::addCssToFile('builder-styles-rtl', THEMIFY_BUILDER_URI . '/css/themify-builder-style-rtl.css', THEMIFY_VERSION, 'builder-styles-css')) {
                    themify_enque_style('builder-styles-rtl', THEMIFY_BUILDER_URI . '/css/themify-builder-style-rtl.css', null, THEMIFY_VERSION);
                }
                Themify_Enqueue_Assets::addLocalization('done', 'tb_style', true);
            }
            return '';
        }

        

        /**
         * Check Builder can edit current post or not
         */
        public static function builder_is_available():?int {
            $is = true;
            $post_id = null;
            if (themify_is_shop()) {
                $post_id = themify_shop_pageId();
            } elseif (!is_archive() && !is_home() && !is_search() && !is_404()) {
                $p = get_queried_object(); //get_the_ID can back wrong post id
                $post_id = isset($p->ID) ? $p->ID : null;
                unset($p);
            } else {
                $is = false;
            }
            if (!empty($post_id)) {
                $is = Themify_Builder_Model::is_frontend_editor_page($post_id);
                if ($is === true) {
                    $is = !Themify_Builder_Model::is_builder_disabled_for_post_type(get_post_type($post_id));
                }
            }
            $is = apply_filters('themify_builder_admin_bar_is_available', $is);
            return $is === true ? $post_id : null;
        }


        /**
         * Add Builder body class
         */
        public static function body_class(array $classes):array {
            if (Themify_Builder_Model::is_frontend_editor_page()) {
                if (Themify_Builder_Model::is_front_builder_activate()) {
                    $classes[] = 'themify_builder_active builder-breakpoint-desktop';
                }
                if (Themify_Global_Styles::$isGlobalEditPage === true) {
                    $classes[] = 'gs_post';
                }
            }
            if (Themify_Builder_Model::is_animation_active()) {
                $classes[] = 'tb_animation_on';
            }
            return apply_filters('themify_builder_body_class', $classes);
        }

        public static function inline_css():void {
            $is_animation = Themify_Builder_Model::is_animation_active();
            $is_parallax = Themify_Builder_Model::is_parallax_active();
            $is_lax = Themify_Builder_Model::is_scroll_effect_active();
            $is_sticky = Themify_Builder_Model::is_sticky_scroll_active();
            $is_builder_active = Themify_Builder_Model::is_front_builder_activate();
            $bp = themify_get_breakpoints();
            $mobile = $bp['mobile'];
            $tablet = $bp['tablet'][1];
            $st = $noscript = '';
            if ($is_animation !== false) {
                $st = '.tb_animation_on{overflow-x:hidden}.themify_builder .wow{visibility:hidden;animation-fill-mode:both}[data-tf-animation]{will-change:transform,opacity,visibility}';
                if ($is_animation === 'm') {
                    $st = '@media(min-width:' . $tablet . 'px){' . $st . '}';
                }
                if ($is_builder_active === true) {
                    $st .= '.hover-wow.tb_hover_animate{animation-delay:initial!important}';
                } else {
                    $noscript = '.themify_builder .wow,.wow .tf_lazy{visibility:visible!important}';
                }
            }
            if ($is_parallax !== true) {
                $p = '.themify_builder .builder-parallax-scrolling{background-position-y:0!important}';
                if ($is_parallax === 'm') {
                    $p = '@media(max-width:' . $tablet . 'px){' . $p . '}';
                }
                $st .= $p;
            }
            if ($is_lax !== false) {
                $p = '.themify_builder .tf_lax_done{transition-duration:.8s;transition-timing-function:cubic-bezier(.165,.84,.44,1)}';
                if ($is_lax === 'm') {
                    $p = '@media(min-width:' . $tablet . 'px){' . $p . '}';
                    $p .= '@media(max-width:' . ($tablet + 2) . 'px){.themify_builder .tf_lax_done{opacity:unset!important;transform:unset!important;filter:unset!important}}';
                }
                $st .= $p;
            }
            if ($is_sticky !== false) {
                $p = '[data-sticky-active].tb_sticky_scroll_active{z-index:1}[data-sticky-active].tb_sticky_scroll_active .hide-on-stick{display:none}';
                if ($is_sticky === 'm') {
                    $p = '@media(min-width:' . $tablet . 'px){' . $p . '}';
                }
                $st .= $p;
            }
            $bp = array('desktop' => ($bp['tablet_landscape'][1] + 1)) + $bp;
            $visiblity_st = '';
            $p = $is_builder_active === true ? 'display:none!important' : 'width:0!important;height:0!important;padding:0!important;visibility:hidden!important;margin:0!important;display:table-column!important;background:0!important;content-visibility:hidden;overflow:hidden!important';
            foreach ($bp as $k => $v) {
                $visiblity_st .= '@media(';
                if (is_array($v)) {
                    $visiblity_st .= 'min-width:' . $v[0] . 'px) and (max-width:' . $v[1] . 'px)';
                } else {
                    $visiblity_st .= $k === 'desktop' ? 'min' : 'max';
                    $visiblity_st .= '-width:' . $v . 'px)';
                }
                $visiblity_st .= '{.hide-' . $k . '{' . $p . '}}';
            }
            unset($bp);
            $st .= $visiblity_st;
            $gutters = Themify_Builder_Model::get_gutters(false);
            if (!empty($gutters)) {
                $gutter_st = '';
                foreach ($gutters as $k => $v) {
                    $gutter_st .= '--' . $k . ':' . $v . '%;';
                }
                if ($gutter_st !== '') {
                    $st .= 'div.row_inner,div.module_subrow{' . $gutter_st . '}';
                }
            }
            $st .= '@media(max-width:' . $tablet . 'px){div.module-gallery-grid{--galn:var(--galt)}}';
            $st .= '@media(max-width:' . $mobile . 'px){
                .themify_map.tf_map_loaded{width:100%!important}
                .ui.builder_button,.ui.nav li a{padding:.525em 1.15em}
                .fullheight>.row_inner:not(.tb_col_count_1){min-height:0}
                div.module-gallery-grid{--galn:var(--galm);gap:8px}
            }';
            echo '<style id="tb_inline_styles" data-no-optimize="1">', $st, '</style>';

            if ($noscript !== '') {
                echo '<noscript><style>', $noscript, '</style></noscript>';
            }
        }

        /**
         * Reset builder query
         * @param $action
         */
        public static function reset_builder_query(string $action = 'reset'):void {
            if ('reset' === $action) {
                remove_filter('the_content', array(__CLASS__, 'builder_show_on_front'), 11);
            } elseif ('restore' === $action) {
                add_filter('the_content', array(__CLASS__, 'builder_show_on_front'), 11);
            }
        }


        /**
         * Load content of only current inner page
         * @param $builder_data
         * @param $page_breaks count of page break modules
         * @return array
         */
        public static function load_current_inner_page_content(array $builder_data,int $page_breaks):array {
            $p = !empty($_GET['tb-page']) ? (int) $_GET['tb-page'] : 1;
            $temp_data = array();
            $page_num = 1;
            foreach ($builder_data as $row) {
                if (isset($row['styling']['custom_css_row']) && strpos($row['styling']['custom_css_row'], 'tb-page-break') !== false) {
                    ++$page_num;
                } else {
                    $temp_data[$page_num][] = $row;
                }
            }
            unset($page_num);
            ++$page_breaks;
            $p = ($p > $page_breaks || $p < 1) ? 1 : $p;
            return array(
                'pagination' => Themify_Builder_Component_Module::get_pagination('', '', 'tb-page', 0, $page_breaks, $p),
                'builder_data' => isset($temp_data[$p]) ? $temp_data[$p] : $builder_data
            );
        }

        /**
         * Check builder content for page break module
         */
        private static function count_page_break_modules(int $post_id):int {
            $data = ThemifyBuilder_Data_Manager::get_data($post_id, true);
            return preg_match_all('/"mod_name":"page-break"/', $data, $modules);
        }



        /**
         * Actions to perform when login via Login module fails
         *
         * @since 4.5.4
         */
        public static function wp_login_failed($username) {
            if (isset($_SERVER['HTTP_REFERER'])) {
                $referrer = $_SERVER['HTTP_REFERER'];  // where did the post submission come from?
                // if there's a valid referrer, and it's not the default log-in screen
                if (isset($_POST['tb_login'], $_POST['tb_redirect_fail']) && (int) $_POST['tb_login'] === 1 && !empty($referrer) && !strstr($referrer, 'wp-login') && !strstr($referrer, 'wp-admin')) {
                    nocache_headers();
                    wp_safe_redirect($_POST['tb_redirect_fail']);
                    exit;
                }
            }
        }



        private static function plugins_compatibility() {
            $plugins = array(
                'autooptimize' => 'autoptimize/autoptimize.php',
                'bwpminify' => 'bwp-minify/bwp-minify.php',
                'cachepress' => 'sg-cachepress/sg-cachepress.php',
                'dokan' => 'dokan-pro/dokan-pro.php',
                'duplicateposts' => 'duplicate-post/duplicate-post.php',
                'enviragallery' => 'envira-gallery/envira-gallery.php',
                'eventscalendar' => 'the-events-calendar/the-events-calendar.php',
                'gallerycustomlinks' => 'wp-gallery-custom-links/wp-gallery-custom-links.php',
                'maxgalleriamedialibpro' => class_exists('MaxGalleriaMediaLibPro',false),
                'members' => 'members/members.php',
                'pmpro' => 'paid-memberships-pro/paid-memberships-pro.php',
                'rankmath' => 'seo-by-rank-math/rank-math.php',
                'relatedposts' => 'wordpress-23-related-posts-plugin/wp_related_posts.php',
                'smartcookie' => 'smart-cookie-kit/plugin.php',
                'thrive' => 'thrive-visual-editor/thrive-visual-editor.php',
                'wcmembership' => 'woocommerce-membership/woocommerce-membership.php',
                'woocommerce' => 'woocommerce/woocommerce.php',
                'wpml' => 'sitepress-multilingual-cms/sitepress.php',
                'wpjobmanager' => 'wp-job-manager/wp-job-manager.php',
                'eventsmadeeasy' => 'events-made-easy/events-manager.php',
                'essentialgrid' => 'essential-grid/essential-grid.php',
                'armember' => 'armember/armember.php',
                'statcounter' => 'official-statcounter-plugin-for-wordpress/StatCounter-Wordpress-Plugin.php',
                'eventtickets' => 'event-tickets/event-tickets.php',
                'wpcourseware' => 'wp-courseware/wp-courseware.php',
                'facetwp' => 'facetwp/index.php',
                'kadence' => 'kadence-blocks/kadence-blocks.php',
                'polylang2' => 'polylang/polylang.php',
                'tutor' => 'tutor/tutor.php',
            );
            foreach ($plugins as $plugin => $active_check) {
                if ($active_check === true || ( is_string($active_check) && Themify_Builder_Model::is_plugin_active($active_check) )) {
                    include( THEMIFY_BUILDER_INCLUDES_DIR . '/plugin-compat/' . $plugin . '.php' );
                    $classname = "Themify_Builder_Plugin_Compat_{$plugin}";
                    $classname::init();
                }
            }
            unset($plugins);
        }

        /**
         * Add tooltip to ".tb_$element_id" on frontend
         */
        public static function add_tooltip( $builder_id, $element_id, $options ) {
            if ( ! isset( self::$tooltips[ $builder_id ] ) ) {
                self::$tooltips[ $builder_id ] = array();
            }
            self::$tooltips[ $builder_id ][ $element_id ] = $options;
        }

        /**
         * Parse Builder settings for a component and add tooltip data
         */
        public static function display_tooltip($builder_id, $options, $type) {
            if (isset($options['element_id']) && !empty($options['styling']['_tooltip'])) {
                $tooltip = [ 't' => esc_html( $options['styling']['_tooltip'] ) ];
                if (!empty($options['styling']['_tooltip_bg'])) {
                    $tooltip['bg'] = Themify_Builder_Stylesheet::get_rgba_color($options['styling']['_tooltip_bg']);
                }
                if (!empty($options['styling']['_tooltip_w'])) {
                    $unit = empty($options['styling']['_tooltip_w_unit']) ? 'px' : $options['styling']['_tooltip_w_unit'];
                    $tooltip['w'] = $options['styling']['_tooltip_w'] . $unit;
                }
                if (!empty($options['styling']['_tooltip_c'])) {
                    $tooltip['c'] = Themify_Builder_Stylesheet::get_rgba_color($options['styling']['_tooltip_c']);
                }
                self::add_tooltip( $builder_id, $options['element_id'], $tooltip );
            }
        }


        /**
         * Check whether Dynamic Fields are being rendered atm
         */
        public static function is_rendering():bool {
            return self::$is_rendering;
        }


        
        /**
         * Return Builder data for a post
         *
         * @since 1.4.2
         * @return array
         */
        public function get_builder_data($post_id) {//deprecated use ThemifyBuilder_Data_Manager
            return ThemifyBuilder_Data_Manager::get_data($post_id);
        }

        /**
         * Return all modules for a post as a two-dimensional array
         * @deprecated
         * @since 1.4.2
         * @return array
         */
        public function get_flat_modules_list($post_id = null, $builder_data = null, $only_check = false) {//deprecated use get_builder_modules_list
            return self::get_builder_modules_list($post_id, $builder_data, $only_check);
        }

        function get_builder_output(?int $post_id, string $content = ''):?string {//deprecated use render
            return self::render($post_id,$content);
        }

		public static function wpml_load_page_builders_integration() {
			include( THEMIFY_BUILDER_INCLUDES_DIR . '/plugin-compat/wpml-translation.php' );
			Themify_Builder_WPML_Integration::init();
		}
    }

    
endif;