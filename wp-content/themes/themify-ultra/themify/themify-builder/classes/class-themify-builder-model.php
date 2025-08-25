<?php

/**
 * Class for interact with DB or data resource and state.
 *
 * @package    Themify_Builder
 * @subpackage Themify_Builder/classes
 */
final class Themify_Builder_Model {

    /**
     * Feature Image Size
     * @var array
     */
    public static $featured_image_size = array();

    /**
     * Image Width
     * @var array
     */
    public static $image_width = array();

    /**
     * Image Height
     * @var array
     */
    public static $image_height = array();

    /**
     * External Link
     * @var array
     */
    public static $external_link = array();

    /**
     * Lightbox Link
     * @var array
     */
    public static $lightbox_link = array();
    public static $modules = array();//deprecated use Themify_Builder_Component_Module::load_modules
    private const TRANSIENT_NAME = 'tb_edit_';

    /**
     * Active custom post types registered by Builder.
     *
     * @var array
     */
    public static $builder_cpt = array();

    /**
     * Directory Registry
     */
    private static $modules_registry = array();

    /**
     * Hook Content cache, used by Post modules
     */
    private static $hook_contents=array();

    private function __construct() {

    }

    /**
     * Get favorite option to module instance
     * @return array
     */
    public static function get_favorite_modules():array {
        $fv = get_user_option('themify_module_favorite', get_current_user_id());
        if(!empty($fv)){
            $fv=json_decode($fv,true);
            if(!array_key_exists(0, $fv)){
                $fv=array_keys($fv);
            }
        }
        else{
            $fv=array();
        }
        return $fv;
    }

    /**
     * Check whether builder is active or not
     * @return bool
     */
    public static function builder_check():bool {
        static $is = NULL;
        if ($is === null) {
            $is = apply_filters('themify_enable_builder', themify_builder_get('setting-page_builder_is_active', 'builder_is_active'))!=='disable';
        }
        return $is;
    }


    /**
     * Check is the frontend editor page
     */
    public static function is_frontend_editor_page(?int $post_id = null):bool {
        $post_id = $post_id ??self::get_ID();
        return apply_filters('themify_builder_is_frontend_editor', Themify_Access_Role::check_access_frontend($post_id), $post_id);
    }

    /**
     * Check if builder frontend edit being invoked
     */
    public static function is_front_builder_activate():bool {
        static $is = NULL;
        if ($is === null) {
            $is = Themify_Builder::$frontedit_active === true || ((isset($_GET['tb-preview']) || (isset($_COOKIE['tb_active']) && !is_admin() && !themify_is_ajax()) && self::is_frontend_editor_page()));
            if ($is === true) {
                add_filter('lazyload_is_enabled', '__return_false', 1, 100); //disable jetpack lazy load
                add_filter('rocket_use_native_lazyload', '__return_false', 1, 100);
            }
        }
        return $is;
    }


    /**
     * Load general metabox fields
     */
    public static function load_general_metabox():void {
        // Featured Image Size
        self::$featured_image_size = apply_filters('themify_builder_metabox_featured_image_size', array(
            'name' => 'feature_size',
            'title' => __('Image Size', 'themify'),
            'description' => sprintf(__('Image sizes can be set at <a href="%s">Media Settings</a>', 'themify'), admin_url('options-media.php')),
            'type' => 'featimgdropdown'
        ));
        // Image Width
        self::$image_width = apply_filters('themify_builder_metabox_image_width', array(
            'name' => 'image_width',
            'title' => __('Image Width', 'themify'),
            'description' => '',
            'type' => 'textbox',
            'meta' => array('size' => 'small')
        ));
        // Image Height
        self::$image_height = apply_filters('themify_builder_metabox_image_height', array(
            'name' => 'image_height',
            'title' => __('Image Height', 'themify'),
            'description' => '',
            'type' => 'textbox',
            'meta' => array('size' => 'small'),
            'class' => self::is_img_php_disabled() ? 'builder_show_if_enabled_img_php' : '',
        ));
        // External Link
        self::$external_link = apply_filters('themify_builder_metabox_external_link', array(
            'name' => 'external_link',
            'title' => __('External Link', 'themify'),
            'description' => __('Link Featured Image and Post Title to external URL', 'themify'),
            'type' => 'textbox',
            'meta' => array()
        ));
        // Lightbox Link
        self::$lightbox_link = apply_filters('themify_builder_metabox_lightbox_link', array(
            'name' => 'lightbox_link',
            'title' => __('Lightbox Link', 'themify'),
            'description' => __('Link Featured Image to lightbox image, video or external iframe', 'themify'),
            'type' => 'textbox',
            'meta' => array()
        ));
    }



    /**
     * Return frame layout
     */
    public static function get_frame_layout():array {
        $path = THEMIFY_BUILDER_URI . '/img/row-frame/';
        return array(
            array('value' => 'none', 'label' => 'none', 'img' => $path . 'none.png'),
            array('value' => 'corner1', 'label' => 'Rounded Corners', 'img' => $path . 'corner1.svg'),
            array('value' => 'corner2', 'label' => 'Bracket Corners', 'img' => $path . 'corner2.svg'),
            array('value' => 'corner3', 'label' => 'Flower Corners', 'img' => $path . 'corner3.svg'),
            array('value' => 'corner4', 'label' => 'Slant Corners', 'img' => $path . 'corner4.svg'),
            array('value' => 'slant1', 'label' => 'Slant 1', 'img' => $path . 'slant1.svg'),
            array('value' => 'slant2', 'label' => 'Slant 2', 'img' => $path . 'slant2.svg'),
            array('value' => 'slant3', 'label' => 'Slant 3', 'img' => $path . 'slant3.svg'),
            array('value' => 'arrow1', 'label' => 'Arrow 1', 'img' => $path . 'arrow1.svg'),
            array('value' => 'arrow2', 'label' => 'Arrow 2', 'img' => $path . 'arrow2.svg'),
            array('value' => 'arrow3', 'label' => 'Arrow 3', 'img' => $path . 'arrow3.svg'),
            array('value' => 'arrow4', 'label' => 'Arrow 4', 'img' => $path . 'arrow4.svg'),
            array('value' => 'arrow5', 'label' => 'Arrow 5', 'img' => $path . 'arrow5.svg'),
            array('value' => 'arrow6', 'label' => 'Arrow 6', 'img' => $path . 'arrow6.svg'),
            array('value' => 'cloud1', 'label' => 'Cloud 1', 'img' => $path . 'cloud1.svg'),
            array('value' => 'cloud2', 'label' => 'Cloud 2', 'img' => $path . 'cloud2.svg'),
            array('value' => 'curve1', 'label' => 'Curve 1', 'img' => $path . 'curve1.svg'),
            array('value' => 'curve2', 'label' => 'Curve 2', 'img' => $path . 'curve2.svg'),
            array('value' => 'mountain1', 'label' => 'Mountain 1', 'img' => $path . 'mountain1.svg'),
            array('value' => 'mountain2', 'label' => 'Mountain 2', 'img' => $path . 'mountain2.svg'),
            array('value' => 'mountain3', 'label' => 'Mountain 3', 'img' => $path . 'mountain3.svg'),
            array('value' => 'wave1', 'label' => 'Wave 1', 'img' => $path . 'wave1.svg'),
            array('value' => 'wave2', 'label' => 'Wave 2', 'img' => $path . 'wave2.svg'),
            array('value' => 'wave3', 'label' => 'Wave 3', 'img' => $path . 'wave3.svg'),
            array('value' => 'wave4', 'label' => 'Wave 4', 'img' => $path . 'wave4.svg'),
            array('value' => 'ink-splash1', 'label' => 'Ink Splash 1', 'img' => $path . 'ink-splash1.svg'),
            array('value' => 'ink-splash2', 'label' => 'Ink Splash 2', 'img' => $path . 'ink-splash2.svg'),
            array('value' => 'zig-zag', 'label' => 'Zig Zag', 'img' => $path . 'zig-zag.svg'),
            array('value' => 'grass', 'label' => 'Grass', 'img' => $path . 'grass.svg'),
            array('value' => 'melting', 'label' => 'Melting', 'img' => $path . 'melting.svg'),
            array('value' => 'lace', 'label' => 'Lace', 'img' => $path . 'lace.svg'),
        );
    }


    /**
     * Get Post Types, which ready for an operation
     * @return array
     */
    public static function get_post_types():array {

        // If it's not a product search, proceed: retrieve the post types.
        $types = get_post_types(array('exclude_from_search' => false));
        if (themify_is_themify_theme()) {
            // Exclude pages /////////////////
            $exclude_pages = themify_builder_get('setting-search_settings_exclude');
            if (!empty($exclude_pages)) {
                unset($types['page']);
            }
            // Exclude posts /////////////////
            $exclude_posts = themify_builder_get('setting-search_exclude_post');
            if (!empty($exclude_posts)) {
                unset($types['post']);
            }
            // Exclude custom post types /////
            $exclude_types = apply_filters('themify_types_excluded_in_search', get_post_types(array(
                '_builtin' => false,
                'public' => true,
                'exclude_from_search' => false
            )));

            foreach (array_keys($exclude_types) as $type) {
                $check = \themify_builder_get('setting-search_exclude_' . $type);
                if (!empty($check)) {
                    unset($types[$type]);
                }
            }
        }
        // Exclude Layout and Layout Part custom post types /////
        unset($types['section'], $types[Themify_Builder_Layouts::LAYOUT_SLUG], $types[Themify_Builder_Layouts::LAYOUT_PART_SLUG], $types['elementor_library']);

        return $types;
    }

    /**
     * Check whether builder animation is active
     * @return boolean|string
     */
    public static function is_animation_active() {
        static $is = NULL;
        if ($is === null) {
            $val = \themify_builder_get('setting-page_builder_animation_appearance', 'builder_animation_appearance');
            $is = $val === 'all' || self::is_front_builder_activate()? false : ('mobile' === $val ? 'm' : true);
        }
        return $is;
    }

    /**
     * Check whether builder parallax is active
     * @return boolean|string
     */
    public static function is_parallax_active() {
        static $is = NULL;
        if ($is === null) {
            $val = \themify_builder_get('setting-page_builder_animation_parallax_bg', 'builder_animation_parallax_bg');
            $is = self::is_front_builder_activate() ? true : ($val === 'all'? false : ('mobile' === $val ? 'm' : true));
        }
        return $is;
    }

    /**
     * Check whether builder scroll effect is active
     * @return boolean|string
     */
    public static function is_scroll_effect_active() {
        static $is = NULL;
        if ($is === null) {
            // check if mobile excludes disabled OR disabled all transitions
            $val = \themify_builder_get('setting-page_builder_animation_scroll_effect', 'builder_animation_scroll_effect');
            $is = $val === 'all' || self::is_front_builder_activate()? false : ('mobile' === $val ? 'm' : true);
        }
        return $is;
    }

    /**
     * Check whether builder sticky scroll is active
     * @return boolean|string
     */
    public static function is_sticky_scroll_active() {
        static $is = NULL;
        if ($is === null) {
            $val = \themify_builder_get('setting-page_builder_animation_sticky_scroll', 'builder_animation_sticky_scroll');
            $is = $val === 'all' || self::is_front_builder_activate()? false : ('mobile' === $val ? 'm' : true);
            $is = apply_filters('tb_sticky_scroll_active', $is);
        }
        return $is;
    }


    /**
     * Returns list of colors and thumbnails
     *
     * @return array
     */
    public static function get_gutters(bool $def=true):array {
        $gutters=array(
            'gutter'=>3.2,
            'narrow'=>1.6,
            'none'=>0
        );
        foreach($gutters as $k=>$v){
            $val=\themify_builder_get( 'setting-'.$k,'setting-'.$k);
            if($val!==null && $val!==''){
                if($v!=$val){
                    $gutters[$k]=$val;
                }
                elseif($def===false){
                    unset($gutters[$k]);
                }
            }
        }
        return $gutters;
    }



    /**
     * Check whether an image script is use or not
     *
     * @since 2.4.2 Check if it's a Themify theme or not. If it's not, it's Builder standalone plugin.
     *
     * @return boolean
     */
    public static function is_img_php_disabled():bool {
        static $is = NULL;
        if ($is === null) {
            $is = \themify_builder_get('setting-img_settings_use', 'image_setting-img_settings_use') ? true : false;
        }
        return $is;
    }

    public static function is_fullwidth_layout_supported():bool {
        return apply_filters('themify_builder_fullwidth_layout_support', false);
    }

    /**
     * Get alt text defined in WP Media attachment by a given URL
     *
     * @since 2.2.5
     *
     * @param string $image_url
     *
     * @return string
     */
    public static function get_alt_by_url(string $image_url):string {
        $attachment_id = themify_get_attachment_id_from_url($image_url);
        if ($attachment_id && ($alt = get_post_meta($attachment_id, '_wp_attachment_image_alt', true))) {
            return $alt;
        }
        return '';
    }

    /**
     * Get all modules settings for used in localize script.
     *
     * @access public
     * @return array
     */
    public static function get_modules_localize_settings():array {
        $return = array();
        $modules=Themify_Builder_Component_Module::load_modules();
        foreach ($modules as $id=>$module) {
            $return[$id]=array();
            
            if(is_string($module)){
                $icon = $module::get_module_icon();
                $name= $module::get_module_name();
            }else{
                $icon = $module->get_icon();
                $name= $module->get_name();
            }
            $return[$id]['name']=$name;
            if ($icon !== '-1') {
                if ($icon === '') {
                    $icon = $id;
                }
                $return[$id]['icon'] = $icon;
                \themify_get_icon($icon, 'ti');
            }
        }

        return $return;
    }


    public static function format_text(string $content):string {
        global $wp_embed;

        $isLoop = Themify_Builder::$is_loop;
        Themify_Builder::$is_loop = true;
        $content = wptexturize($content);

        $pattern = '|<p>\s*(https?://[^\s"]+)\s*</p>|im'; // pattern to check embed url
        $to = '<p>' . PHP_EOL . '$1' . PHP_EOL . '</p>'; // add line break
        $content = $wp_embed->run_shortcode($content);
        $content = preg_replace($pattern, $to, $content);
        $content = $wp_embed->autoembed($content);
        $content = do_shortcode(shortcode_unautop($content));
        Themify_Builder::$is_loop = $isLoop;
        $content = convert_smilies($content);
        return self::generate_read_more($content);

    }

    /*
     * Generate read more link for text module
     *
     * @param string $content
     * @return string generated load more link in the text.
     */

    public static function generate_read_more(string $content):string {
        if (!empty($content) && \strpos($content, '!--more') !== false && \preg_match('/(<|&lt;)!--more(.*?)?--(>|&gt;)/', $content, $matches)) {
            $text = \trim($matches[2]);
            $read_more_text = !empty($text) ? $text : apply_filters('themify_builder_more_text', __('More ', 'themify'));
            $content = \str_replace($matches[0], '<div class="more-text" style="display: none">', $content);
            $content .= '</div><a href="#" class="module-text-more">' . $read_more_text . '</a>';
        }
        return $content;
    }


    public static function is_module_active(string $mod_name):bool {
        if (themify_is_themify_theme()) {
            $data = \themify_get_data();
            $pre = 'setting-page_builder_exc_';
        } else {
            $pre = 'builder_exclude_module_';
            $data = self::get_builder_settings();
        }
        return empty($data[$pre . $mod_name]);
    }

    /**
     * Get module php files data
     */
    public static function get_modules(string $type = 'active',bool $ignore=false) {
        $directories = self::$modules_registry;
        $defaultModules=array(
            'accordion'=>true,
            'alert'=>true,
            'box'=>true,
            'buttons'=>true,
            'callout'=>true,
            'code'=>true,
            'copyright'=>true,
            'divider'=>true,
            'fancy-heading'=>true,
            'feature'=>true,
            'gallery'=>true,
            'icon'=>true,
            'image'=>true,
            'layout-part'=>true,
            'link-block'=>true,
            'login'=>true,
            'lottie'=>true,
            'map'=>true,
            'menu'=>true,
            'optin'=>true,
            'overlay-content'=>true,
            'page-break'=>true,
            'plain-text'=>true,
            'post'=>true,
            'service-menu'=>true,
            'signup-form'=>true,
            'slider'=>true,
            'social-share'=>true,
            'star'=>true,
            'tab'=>true,
            'testimonial-slider'=>true,
            'text'=>true,
            'toc'=>true,
            'video'=>true,
            'widget'=>true,
            'widgetized'=>true
        );
        $deprecated=array(
            'highlight'=>true,
            'portfolio'=>true,
            'testimonial'=>true
        );
        $isNotSingle=$type === 'active' || $type === 'all';
        if($isNotSingle===true || isset($deprecated[$type])){
            foreach($deprecated as $id=>$dir){
                if(self::is_cpt_active( $id )){
                    $defaultModules[$id]=$dir;
                }
            }
        }
        unset($deprecated);
        if($isNotSingle===true){
            $modules = array();
            $defaultModules+=$directories;
            foreach($defaultModules as $id=>$dir){
                if ($type !== 'active' || self::is_module_active($id)) {
                    $modules[$id] = $dir;
                }
            }
            return $modules;
        }
        elseif((isset($defaultModules[$type]) || isset($directories[$type])) && ($ignore===true || self::is_module_active($type))){
            return isset($defaultModules[$type])?$defaultModules[$type]:$directories[$type];
        }
        return '';
    }

    /**
     * Check whether theme loop template exist
     * @param string $template_name
     * @param string $template_path
     * @return boolean
     */
    public static function is_loop_template_exist(string $template_name, string $template_path):bool {
        return !locate_template(array(trailingslashit($template_path) . $template_name)) ? false : true;
    }

    public static function add_module(string $path) {

        if(\strpos($path, '.php') !== false){
            $path_info = \pathinfo($path);
            $id = \str_replace('module-', '', $path_info['filename']);
            self::$modules_registry[$id]=$path_info['dirname'];
        }
        elseif (is_dir($path)) {//backward
            $d = dir($path);
            while (( false !== ( $entry = $d->read() ))) {
                if ($entry !== '.' && $entry !== '..' && $entry !== '.svn' && strpos($entry, 'module-') === 0) {
                    $id = str_replace(array('module-','.php'), '', $entry);
                    self::$modules_registry[$id] =rtrim($d->path,'/');
                }
            }
        } 
    }

    public static function get_directory_path($context=''):array {
        return self::$modules_registry;
    }


    public static function is_cpt_active(string $post_type):bool {
        return apply_filters("builder_is_{$post_type}_active", \in_array($post_type, self::$builder_cpt, true));
    }

    public static function builder_cpt_check():void {
        if(empty(self::$builder_cpt)){
            global $wpdb;
            foreach (array('slider', 'highlight', 'testimonial') as $cpt) {
                if (post_type_exists($cpt)) {
                    self::$builder_cpt[] = $cpt;
                }
                 else {
                    $post=$wpdb->get_row('SELECT 1 FROM ' .  $wpdb->posts . ' WHERE `post_type`="' . $cpt . '" LIMIT 1');
                    if (!empty($post)) {
                        self::$builder_cpt[] = $cpt;
                    }
                }
            }
        }
    }

    /**
     * Get a list of post types that can be accessed publicly
     *
     * does not include attachments, Builder layouts and layout parts,
     * and also custom post types in Builder that have their own module.
     *
     * @return array of key => label pairs
     */
    public static function get_public_post_types():array {
        $excluded = array('attachment'=>true, Themify_Builder_Layouts::LAYOUT_SLUG=>true, Themify_Builder_Layouts::LAYOUT_PART_SLUG=>true,Themify_Global_Styles::SLUG=>true, Themify_Custom_Fonts::SLUG=>true,  'section'=>true, 'elementor_library'=>true);
        $result=[];
        foreach (get_post_types(array('public' => true, 'publicly_queryable' => 'true'), 'objects') as $key => $value) {
            if (!isset($excluded[$key])) {
                $result[$key] = $value->labels->singular_name;
            }
        }
        return apply_filters('builder_get_public_post_types', $result);
    }

    /**
     * Get a list of taxonomies that can be accessed publicly
     *
     * does not include post formats, section categories (used by some themes),
     * and also custom post types in Builder that have their own module.
     *
     * @return array of key => label pairs
     */
    public static function get_public_taxonomies():array {
        $excluded = array('post_format'=>true, 'section-category'=>true,'product_shipping_class'=>true);
        $result=[];
        foreach (get_taxonomies(array('public' => true), 'objects') as $key => $value) {
            if (!isset($excluded[$key])) {
                $result[$key] = $value->labels->name;
            }
        }

        return apply_filters('builder_get_public_taxonomies', $result);
    }

    public static function parse_slug_to_ids(string $slug_string, string $post_type = 'post'):array {
        $slug_arr = \explode(',', $slug_string);
        $return = [];
        if (!empty($slug_arr)) {
            foreach ($slug_arr as $slug) {
                $return[] = \is_numeric( $slug ) ? $slug : self::get_id_by_slug(trim($slug), $post_type);
            }
        }
        return $return;
    }

    public static function get_id_by_slug(string $slug,string $post_type = 'post'):?int{
        $args = array(
            'name' => $slug,
            'post_type' => $post_type,
            'post_status' => 'publish',
            'numberposts' => 1,
            'no_found_rows' => true,
            'cache_results' => false,
            'ignore_sticky_posts' => true,
            'update_post_term_cache' => false,
            'update_post_meta_cache' => false,
            'orderby' => 'none'
        );
        $my_posts = get_posts($args);
        return $my_posts ? $my_posts[0]->ID : null;
    }

    public static function getMapKey():?string {
        return \themify_builder_get('setting-google_map_key', 'builder_settings_google_map_key');
    }

    /**
     * Get Builder Settings
     */
    public static function get_builder_settings():array {
        static $data = null;
        if ($data === null) {
            $data = get_option('themify_builder_setting');
            if (!empty($data) && is_array($data)) {
                foreach ($data as $name => $value) {
                    $data[$name] = stripslashes($value);
                }
            } 
            else {
                $data = array();
            }
        }
        return $data;
    }

    /**
     * Get ID
     */
    public static function get_ID() {
        return \themify_is_shop() ? \themify_shop_pageId() : \get_the_id();
    }


    public static function get_transient_time() {
        return apply_filters('themify_builder_ticks', MINUTE_IN_SECONDS / 2);
    }

    public static function set_edit_transient($post_id, $value) {
        return Themify_Storage::set(self::TRANSIENT_NAME . $post_id, $value, self::get_transient_time());
    }

    public static function get_edit_transient($post_id) {
        return Themify_Storage::get(self::TRANSIENT_NAME . $post_id);
    }

    public static function remove_edit_transient($post_id) {
        return Themify_Storage::delete(self::TRANSIENT_NAME . $post_id);
    }


    /**
     * Check if gutenberg active
     * @return boolean
     */
    public static function is_gutenberg_active():bool {
        static $is = null;
        if ($is === null) {
            $is = !self::is_plugin_active('disable-gutenberg/disable-gutenberg.php') && !self::is_plugin_active('classic-editor/classic-editor.php');
        }
        return $is;
    }


    /**
     * Check if we are gutenberg editor
     * !IMPORTANT can be used only after action "get_current_screen"
     * @return boolean
     */
    public static function is_gutenberg_editor():bool {
        static $is = null;
        if ($is === null) {
            $is = !isset($_GET['classic-editor']) && is_admin() && self::is_gutenberg_active() && get_current_screen()->is_block_editor();
        }
        return $is;
    }

    /**
     * Plugin Active checking
     *
     * @access public
     * @param string $plugin
     * @return bool
     */
    public static function is_plugin_active(string $plugin):bool {
        static $plugins = null;
        static $active_plugins = array();
        if ($plugins === null) {
            $plugins = is_multisite() ? get_site_option('active_sitewide_plugins') : false;
            $active_plugins = (array) apply_filters('active_plugins', get_option('active_plugins'));
        }
        return ( $plugins !== false && isset($plugins[$plugin]) ) || in_array($plugin, $active_plugins, true);
    }


    public static function checkUniqId($id):string{
        static $ids=array();
        if($id!==null && !isset($ids[$id])){
            $ids[$id] = true;
            return $id;
        }
        $id=self::generateID();
        if(isset($ids[$id])){
            while(isset($ids[$id])){
                $id = self::generateID();
            }
        }
        $ids[$id] = true;
        return $id;
    }

    public static function generateID():string {
        $hash = '';
        $alpha_numeric = 'abcdefghijklmnopqrstuvwxyz0123456789';
        for ($i = 0; $i < 4; ++$i) {
            $hash .= '' . $alpha_numeric[rand(0, 35)];
        }
        $m = microtime();
        $len = strlen($m);
        if ($len > 10) {
            $len = floor($len / 2);
        }
        --$len;
        for ($i = 0; $i < 3; ++$i) {
            $h = $m[rand(2, $len)];
            if ($h === '') {
                $h = $m[rand(2, ( $len - 1))];
            }
            $hash .= $h;
        }
        return $hash;
    }


    public static function removeElementIds(array &$data):void {
        //save sticky/unsticky ids
        $elementIds=$sticky=array();
        foreach ($data as $i=>&$r) {
            if(isset($r['element_id'])){
                $elementIds[$r['element_id']]=$i;
            }
            if(isset($r['styling']['unstick_when_el_row_id'])){
                $sticky[$i.'r']=$r['styling']['unstick_when_el_row_id'];
            }
            if(isset($r['styling']['unstick_when_el_mod_id'])){
                $sticky[$i.'m']=$r['styling']['unstick_when_el_mod_id'];
            }
            unset($r['cid'], $r['element_id']);

            if (!empty($r['cols'])) {

                foreach ($r['cols'] as $j=>&$c) {

                    unset($c['cid'], $c['element_id']);

                    if (!empty($c['modules'])) {

                        foreach ($c['modules'] as $mk=>&$m) {
                            if(isset($m['element_id'])){
                                $elementIds[$m['element_id']]=$i.'-'.$j.'-'.$mk;
                                unset($m['element_id']);
                            }
                            if (isset($m['mod_settings']['cid'])) {
                                unset($m['mod_settings']['cid']);
                            }
                            if(isset($m['mod_settings']['unstick_when_el_row_id'])){
                                $sticky[$i.'-'.$j.'-'.$mk.'r']=$m['mod_settings']['unstick_when_el_row_id'];
                            }
                            if(isset($m['mod_settings']['unstick_when_el_mod_id'])){
                                $sticky[$i.'-'.$j.'-'.$mk.'m']=$m['mod_settings']['unstick_when_el_mod_id'];
                            }
                            if (!empty($m['cols'])) {
                                if(isset($m['styling']['unstick_when_el_row_id'])){
                                    $sticky[$i.'-'.$j.'-'.$mk.'r']=$m['styling']['unstick_when_el_row_id'];
                                }
                                if(isset($m['styling']['unstick_when_el_mod_id'])){
                                    $sticky[$i.'-'.$j.'-'.$mk.'m']=$m['styling']['unstick_when_el_mod_id'];
                                }
                                foreach ($m['cols'] as $sb=>&$sub_col) {

                                    unset($sub_col['cid'], $sub_col['element_id']);

                                    if (!empty($sub_col['modules'])) {

                                        foreach ($sub_col['modules'] as $sm=>&$sub_m) {
                                            if(isset($sub_m['element_id'])){
                                                $elementIds[$sub_m['element_id']]=$i.'-'.$j.'-'.$mk.'-'.$sb.'-'.$sm;
                                                unset($sub_m['element_id']);
                                            }
                                            if(isset($sub_m['mod_settings']['unstick_when_el_row_id'])){
                                                $sticky[$i.'-'.$j.'-'.$mk.'-'.$sb.'-'.$sm.'r']=$sub_m['mod_settings']['unstick_when_el_row_id'];
                                            }
                                            if(isset($sub_m['mod_settings']['unstick_when_el_mod_id'])){
                                                $sticky[$i.'-'.$j.'-'.$mk.'-'.$sb.'-'.$sm.'m']=$sub_m['mod_settings']['unstick_when_el_mod_id'];
                                            }
                                            if (isset($sub_m['mod_settings']['cid'])) {
                                                unset($sub_m['mod_settings']['cid']);
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
        if(!empty($sticky)){
            foreach($sticky as $v=>$id){
                if(isset($elementIds[$id])){
                    $newId=self::generateID();
                    $path1=explode('-',$elementIds[$id]);
                    $key=strpos($v,'r',1)!==false?'unstick_when_el_row_id':'unstick_when_el_mod_id';
                    $path2=explode('-',strtr($v,array('r'=>'','m'=>'')));
                    if(isset($path1[4])){
                        if(!isset($data[$path1[0]]['cols'][$path1[1]]['modules'][$path1[2]]['cols'][$path1[3]]['modules'][$path1[4]]['element_id'])){
                            $data[$path1[0]]['cols'][$path1[1]]['modules'][$path1[2]]['cols'][$path1[3]]['modules'][$path1[4]]['element_id']=$newId;
                        }
                        else{
                            $newId=$data[$path1[0]]['cols'][$path1[1]]['modules'][$path1[2]]['cols'][$path1[3]]['modules'][$path1[4]]['element_id'];
                        }
                    }
                    elseif(isset($path1[1])){
                        if(!isset($data[$path1[0]]['cols'][$path1[1]]['modules'][$path1[2]]['element_id'])){
                            $data[$path1[0]]['cols'][$path1[1]]['modules'][$path1[2]]['element_id']=$newId;
                        }
                        else{
                            $newId=$data[$path1[0]]['cols'][$path1[1]]['modules'][$path1[2]]['element_id'];
                        }
                    }
                    else{
                        if(!isset($data[$path1[0]]['element_id'])){
                            $data[$path1[0]]['element_id']=$newId;
                        }
                        else{
                            $newId=$data[$path1[0]]['element_id'];
                        }
                    }

                    if(isset($path2[4])){
                        $data[$path2[0]]['cols'][$path2[1]]['modules'][$path2[2]]['cols'][$path2[3]]['modules'][$path2[4]]['mod_settings'][$key]=$newId;
                    }
                    elseif(isset($path2[1])){
                        $mKey=!empty($data[$path2[0]]['cols'][$path2[1]]['modules'][$path2[2]]['cols'])?'styling':'mod_settings';
                        $data[$path2[0]]['cols'][$path2[1]]['modules'][$path2[2]][$mKey][$key]=$newId;
                    }
                    else{
                        $data[$path2[0]]['styling'][$key]=$newId;
                    }
                }
            }
        }
        unset($sticky,$elementIds);
    }


    /**
     * Generate an unique Id for each component if it doesn't have and check unique in the builder
     */
    public static function generateElementsIds(array &$data):void {
        foreach ($data as &$r) {
            $r['element_id'] = self::checkUniqId((isset($r['element_id']) ? $r['element_id'] : null));
            unset($r['row_order'],$r['cid']);
            if (!empty($r['cols'])) {
                foreach ($r['cols'] as &$c) {
                    $c['element_id'] = self::checkUniqId((isset($c['element_id']) ? $c['element_id'] : null));
                    unset($c['column_order'],$c['cid']);
                    if (!empty($c['modules'])) {
                        foreach ($c['modules'] as &$m) {
                            if (!is_array($m)) {
                                continue;
                            }
                            $m['element_id'] = self::checkUniqId((isset($m['element_id']) ? $m['element_id'] : null));
                            unset($m['row_order'],$m['mod_settings']['cid']);
                            if (!empty($m['cols'])) {
                                foreach ($m['cols'] as &$sub_col) {
                                    $sub_col['element_id'] = self::checkUniqId((isset($sub_col['element_id']) ? $sub_col['element_id'] : null));
                                    unset($sub_col['column_order'],$sub_col['cid']);
                                    if (!empty($sub_col['modules'])) {
                                        foreach ($sub_col['modules'] as &$sub_m) {
                                            $sub_m['element_id'] = self::checkUniqId((isset($sub_m['element_id']) ? $sub_m['element_id'] : null));
                                            unset($sub_m['mod_settings']['cid']);
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

    public static function parseTerms(string $terms, $taxonomy=''):array {
        $include_by_id = $exclude_by_id = $include_by_slug = $exclude_by_slug = array();
        // deal with how category fields are saved
        $terms = \preg_replace('/\|[multiple|single]*$/', '', $terms);

        if ( $terms === '0' || $terms === '' ) {
            return array();
        }

        $temp_terms = explode(',', $terms);

        foreach ($temp_terms as $t) {
            $t = trim($t);
            $isNumeric = is_numeric($t);
            $exclude = $t[0] === '-';
            if ($isNumeric === false) {
                if ($exclude===true) {
                    $exclude_by_slug[] = ltrim($t, '-');
                } else {
                    $include_by_slug[] = $t;
                }
            } else {
                if ($exclude===true) {
                    $exclude_by_id[] = ltrim($t, '-');
                } else {
                    $include_by_id[] = $t;
                }
            }
        }
        return array_filter(compact('include_by_id', 'exclude_by_id', 'include_by_slug', 'exclude_by_slug'));
    }

    public static function parseTermsQuery(array &$args,string $term,string $taxonomy):void {
        $terms = self::parseTerms($term);
        if (!empty( $terms ) ) {
            $args['tax_query'] = array();
            if (!empty($terms['include_by_id']) && !in_array('0', $terms['include_by_id'])) {
                $args['tax_query'][] = array(
                    array(
                        'taxonomy' => $taxonomy,
                        'field' => 'id',
                        'terms' => $terms['include_by_id']
                    )
                );
            }
            if (!empty($terms['include_by_slug']) && !in_array('0', $terms['include_by_slug'])) {
                $args['tax_query'][] = array(
                    array(
                        'taxonomy' => $taxonomy,
                        'field' => 'slug',
                        'terms' => $terms['include_by_slug']
                    )
                );
            }
            if (!empty($terms['exclude_by_id'])) {
                $args['tax_query'][] = array(
                    'taxonomy' => $taxonomy,
                    'field' => 'id',
                    'terms' => $terms['exclude_by_id'],
                    'operator' => 'NOT IN'
                );
            }
            if (!empty($terms['exclude_by_slug'])) {
                $args['tax_query'][] = array(
                    'taxonomy' => $taxonomy,
                    'field' => 'slug',
                    'terms' => $terms['exclude_by_slug'],
                    'operator' => 'NOT IN'
                );
            }
        } 
    }

    public static function load_appearance_css(string $data):void {
        static $is=null;
        if ($is===null && $data !== '' && $data != 'bordered' && $data !== 'circle') {
            if(Themify_Builder::$frontedit_active === true){
                $is=true;
            }
            else{
                $data=trim($data);
                if($data!==''){
                    $arr=array('glossy','rounded','shadow','gradient','embossed');
                    foreach($arr as $v){
                        if(\strpos($data,$v)!==false){
                            $is=true;
                            self::loadCssModules('app',THEMIFY_BUILDER_CSS_MODULES . 'appearance.css');
                            break;
                        }
                    }
                }
            }
        }
    }

    public static function load_color_css(string $color) {
        static $is=null;
        if ($is===null && $color != '' && $color !== 'tb_default_color' && $color !== 'default' && $color !== 'transparent'  && $color !== 'white' && $color !== 'outline' && Themify_Builder::$frontedit_active === false) {
            $is=true;
            self::loadCssModules('color',THEMIFY_BUILDER_CSS_MODULES.'colors.css');
        }
    }

    public static function load_module_self_style(string $slug, string $css, string $alter_url = '', string $media='all') {
        if (Themify_Builder::$frontedit_active === false) {
            $key = $slug . '_' . str_replace('/', '_', $css);
            if ($alter_url === '') {
                $alter_url = THEMIFY_BUILDER_CSS_MODULES . $slug . '_styles/' . $css;
            }
            self::loadCssModules($key, $alter_url . '.css',THEMIFY_VERSION,$media);
        }
    }

    public static function loadCssModules(string $key, string $url, string $v=THEMIFY_VERSION, string $media = 'all') {
        if(!is_admin() || themify_is_ajax()){
            $key='tb_' . $key;
            \themify_enque_style($key, $url, null, $v, $media);
            \Themify_Enqueue_Assets::addLocalization('done', $key, true);
        }
    }

    public static function check_plugins_compatible():void {//check compatible of plugins
        if (isset($_GET['page']) && $_GET['page'] === 'themify-license') {
            return;
        }
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $plugins = get_plugins();
        $plugin_root = WP_PLUGIN_DIR;
        $needUpdate = false;
        $hasUpdater = $updaterUrl = null;
        $_messages = array();
        $fw = THEMIFY_VERSION;
        $dependceFW = array('announcement-bar', 'themify-audio-dock', 'themify-builder-pro', 'themify-icons', 'themify-shortcodes', 'themify-popup', 'themify-portfolio-post', 'themify-event-post', 'themify-store-locator', 'themify-tiles');
        foreach ($plugins as $k => $p) {
            if (isset($p['Author']) && $p['Author'] === 'Themify') {
                $slug = dirname($k);
                if (strpos($slug, 'builder-') === 0 || $slug === 'themify-updater' || in_array($slug, $dependceFW, true)) {
                    if ($slug === 'themify-updater') {
                        if ($hasUpdater === null) {
                            $hasUpdater = is_plugin_active($k);
                            $updaterUrl = $k;
                        }
                    } else {
                        if (!isset($p['Compatibility'])) {
                            $data = get_file_data($plugin_root . '/' . $k, array('v' => 'Compatibility'), false);
                            $v = $p['Compatibility'] = $data['v'];
                            $needUpdate = true;
                        } else {
                            $v = $p['Compatibility'];
                        }
                        $up = '';
                        if($v){
                            $v=explode(',',$v);
                        }
                        if (!$v) { // Compatibility header missing, plugin is older than FW 5.0.0 update
                            $up = 'plugin';
                        }
                        elseif (version_compare(trim($v[0]), $fw, '>') || (!empty($v[1]) && version_compare($fw, trim($v[1]), '>='))){ // plugin requires a higher version of FW
                            $up = 'theme';
                        }
                        if ($up !== '') {
                            if (!isset($_messages[$up])) {
                                $_messages[$up] = array();
                            }
                            $_messages[$up][] = $p['Name'];
                        }
                    }
                }
            }
        }
        if ($needUpdate === true) {
            wp_cache_set('plugins', $plugins, 'plugins');
        }
        if ($hasUpdater === false && $updaterUrl !== null && !empty($_GET['tf-activate-updater'])) {
            $tab = !empty($_messages['theme']) ? 1 : 2;
            $hasUpdater = activate_plugins($updaterUrl, add_query_arg(array('page' => 'themify-license', 'promotion' => $tab), admin_url()));
        }
        unset($needUpdate, $plugins, $dependceFW);

        if (!empty($_messages)) {
            foreach ($_messages as $k => $msg):?>
                <div class="notice notice-error tf_compatible_erros tf_<?php echo $k ?>_erros">
                    <p><strong><?php echo $k === 'plugin' ? __('The following plugin(s) are not compatible with the activated theme. Please update your plugins:', 'themify') : __('Please update your activated Themify theme or Builder plugin. The following plugin(s) are incompatible:', 'themify'); ?></strong></p>
                    <p><?php echo implode(', ', $msg); ?></p>
                    <p>
                    <?php if ($hasUpdater === true): ?>
                        <?php $tab = $k === 'plugin' ? 2 : 1; ?>
                        <a role="button" class="button button-primary" href="<?php echo add_query_arg(array('page' => 'themify-license', 'promotion' => $tab), admin_url()) ?>"><?php _e('Update them', 'themify') ?></a>
                        <?php elseif ($hasUpdater === false): ?>
                            <?php printf(__('%s', 'themify'), '<a role="button" class="button" href="' . add_query_arg(array('tf-activate-updater' => 1)) . '">' . __('Activate Themify Updater', 'themify') . '</a>') ?></a>
                        <?php else: ?>
                            <?php printf(__('Install %s plugin to auto update them.', 'themify'), '<a href="' . add_query_arg(array('page' => 'themify-install-plugins'), admin_url('admin.php')) . '">' . __('Themify Updater', 'themify') . '</a>') ?></a>
                        <?php endif; ?>
                    </p>
                </div>
            <?php
            endforeach;
        }
    }

    /**
     * Checks if Builder is disabled for a given post type
     */
    public static function is_builder_disabled_for_post_type( string $post_type ):bool {
        static $cache =array();
        if ( ! isset( $cache[ $post_type ] ) ) {
            $cache[ $post_type ] = apply_filters( "tb_is_disabled_for_{$post_type}", null );
            if ( !isset($cache[ $post_type ]) ) {
                $cache[ $post_type ] = themify_builder_check( 'setting-page_builder_disable_' . $post_type, 'builder_disable_tb_' . $post_type );
            }
        }

        return $cache[ $post_type ];
    }

    /**
     * Parses the settings from a module and applies Advanced Query settings on the $query_arg
     *
     * @return void
     */
    public static function parse_query_filter(array $module_settings,array &$query_args ) {
        if ( ! empty( $module_settings['query_date_to'] ) ) {
            $query_args['date_query']['inclusive'] = true;
            $query_args['date_query']['before'] = $module_settings['query_date_to'];
        }
        if ( ! empty( $module_settings['query_date_from'] ) ) {
            $query_args['date_query']['inclusive'] = true;
            $query_args['date_query']['after'] = $module_settings['query_date_from'];
        }

        if ( ! empty( $module_settings['query_authors'] ) ) {
            $authors_ids = [];
            $authors = \explode( ',', $module_settings['query_authors'] );
            foreach ( $authors as $author ) {
                $author=trim($author);
                if ( is_numeric( $author ) ) {
                    $authors_ids[] = (int) $author;
                }
                elseif ( $user = get_user_by( 'login', $author ) ) {
                    $authors_ids[] = $user->ID;
                }
            }
            if ( ! empty( $authors_ids ) ) {
                $query_args['author__in'] = $authors_ids;
            }
        }

        if ( ! empty( $module_settings['query_cf_key'] ) ) {
            $meta_query = [
                'compare' => empty( $module_settings['query_cf_c'] ) ? 'LIKE' : $module_settings['query_cf_c'],
                'key' => $module_settings['query_cf_key']
            ];
            if ( $meta_query['compare'] !== 'NOT EXISTS' && $meta_query['compare'] !== 'EXISTS' && ! empty( $module_settings['query_cf_value'] ) ) {
                $meta_query['value'] = $module_settings['query_cf_value'];
            }

            if ( ! isset( $query_args['meta_query'] ) ) {
                $query_args['meta_query'] = [];
            }
            $query_args['meta_query'][] = $meta_query;
        }
    }

    /**
     * Setup Hook Content feature for the module
     *
     * @return void
     */
    public static function hook_content_start( array $settings ) {
        if (!empty( $settings['hook_content'] ) ) {
            foreach ( $settings['hook_content'] as $hook ) {
                if (!empty( $hook['c'] ) ) {
                    if(!isset(self::$hook_contents[ $hook['h'] ])){
                        self::$hook_contents[ $hook['h'] ]=array();
                    }
                    self::$hook_contents[ $hook['h'] ][] = $hook['c'];
                    add_action( $hook['h'], array( __CLASS__, 'hook_content_output' ) );
                }
            }
        }
    }

    /**
     * Remove hooks added by self::hook_content_start and reset cache
     *
     * @return void
     */
    public static function hook_content_end(array $settings ) {
        self::$hook_contents = null;
        if (!empty( $settings['hook_content'] ) ) {
            foreach ( $settings['hook_content'] as $hook ) {
                if (!empty( $hook['c'] ) ) {
                    remove_action( $hook['h'], array( __CLASS__, 'hook_content_output' ) );
                }
            }
        }
    }

    /**
     * Display the contents of a hook added in Post modules
     *
     * @return void
     */
    public static function hook_content_output() {
        $current_filter = \current_filter();
        if ( isset( self::$hook_contents[ $current_filter ] ) ) {
            foreach ( self::$hook_contents[ $current_filter ] as $content ) {
                echo '<!-- post hook:' , $current_filter , ' -->' , do_shortcode( $content ) , '<!-- /post hook:' , $current_filter , ' -->';
            }
        }
    }


    /**
     * Returns an array containing paths to different assets loade by Builder editor
     */
    public static function get_layouts():array {
        $themeLayoutsPath=get_parent_theme_file_path('builder-layouts/layouts.php');
        $arr= array(
            // Pre-designed layouts
            'predesigned' => array(
                'title'=>__( 'Pre-designed', 'themify' ),
                'url'=>'https://themify.org/public-api/builder-layouts/'
            )
        );
        if(is_file($themeLayoutsPath)){
            $themeLayouts=include $themeLayoutsPath;
            $arr['theme']=array(
                'title'=>__( 'Theme', 'themify' ),
                'data'=>$themeLayouts
            );
        }
        return $arr;
    }

    public static function getReCaptchaOption(string $name,?string $default=''):string{
        if($name==='version'){
            $contact_key='recapthca_version';
            $builder_key='builder_settings_recaptcha_version';
            $tf_name='setting-recaptcha_version';
        }
        elseif($name==='public_key'){
            $contact_key='recapthca_public_key';
            $builder_key='builder_settings_recaptcha_site_key';
            $tf_name='setting-recaptcha_site_key';
        }
        elseif($name==='private_key'){
            $contact_key='recapthca_private_key';
            $builder_key='builder_settings_recaptcha_secret_key';
            $tf_name='setting-recaptcha_secret_key';
        }
        if(isset($tf_name)){
            $val=\themify_builder_get($tf_name, $builder_key);
            if(!empty($val)){
                return $val;
            }
        }
        $options = class_exists('Builder_Contact',false)?get_option('builder_contact'):array();
        return $options[$contact_key]??$default??'';
    }


    public static function get_captcha_keys(?string $provider ):array {
        if ( $provider === 'recaptcha' ) {
            return [
                'public' => self::getReCaptchaOption( 'public_key' ),
                'private' => self::getReCaptchaOption( 'private_key' ),
                'version' => self::getReCaptchaOption( 'version', 'v2' )
            ];
        } elseif ( $provider === 'hcaptcha' ) {
            return [
                'public' => \themify_builder_get( 'setting-hcaptcha_site', 'hcaptcha_site' ),
                'private' => \themify_builder_get( 'setting-hcaptcha_secret', 'hcaptcha_secret' )
            ];
        }
    }

    public static function get_captcha_field(?string $provider, string $before = '',string  $after = '' ):string {
        $keys = self::get_captcha_keys( $provider );
        $output = '';
        if ( empty( $keys['public'] ) || empty( $keys['private'] ) ) {
            if ( current_user_can( 'manage_options' ) ) {
                $output = sprintf( __('Requires Captcha keys entered at: <a target="_blank" href="%s">Integration API</a>.', 'themify'), admin_url('admin.php?page=themify#setting-integration-api') );
            }
        } else {
            if ( $provider === 'recaptcha' ) {
                $output .= '<div class="themify_captcha_field ' . ( 'v2' === $keys['version'] ? 'g-recaptcha' : '' ) .'" data-sitekey="' . esc_attr($keys['public'] ) . '" data-ver="' . esc_attr( $keys['version'] ) . '"></div>';
            } else if( $provider === 'hcaptcha' ) {
                $output .= '<div class="themify_captcha_field h-captcha" data-sitekey="' . esc_attr( $keys['public'] ) . '"></div>';
            }
        }

        return $before . $output . $after;
    }

    /**
     * Validate a captcha form
     *
     * @return true|WP_Error
     */
    public static function validate_captcha( $provider, $response ) {
        $keys = self::get_captcha_keys( $provider );
        if ( empty( $keys['public'] ) || empty( $keys['private'] ) ) {
            return new WP_Error( 'missing_captcha_key', __( 'Captcha API keys are not provided.', 'themify' ) );
        }

        $url = $provider === 'recaptcha' ? 'https://www.google.com/recaptcha/api/siteverify' : 'https://hcaptcha.com/siteverify';
        $url = add_query_arg( [
            'secret' => $keys['private'],
            'response' => $response
        ], $url );
        $result = wp_remote_get( $url );
        if ( is_wp_error( $result ) ) {
            return $result;
        } else if ( ! isset( $result['body'] ) ) {
            return new WP_Error( 'captcha_connection_fail', __( 'Trouble verifying captcha. Please try again.', 'themify' ) );
        }
        $result = json_decode( $result['body'], true );
        if ( ! $result['success'] ) {
            return new WP_Error( 'captcha_test_fail', __( 'Trouble verifying captcha. Please try again.', 'themify' ) );
        }

        return true;
    }


    

    //deprecated functions


    /**
     * Set Pre-built Layout version
     */
    public static function set_current_layouts_version($version) {//deprecated
        $key='tbuilder_layouts_version';
        delete_transient($key);
        return Themify_Storage::set($key,$version);
    }

    /**
     * Get current Pre-built Layout version
     */
    public static function get_current_layouts_version() {//deprecated
        $key='tbuilder_layouts_version';
        delete_transient($key);
        $current_layouts_version = Themify_Storage::get($key);
        if (false === $current_layouts_version) {
            self::set_current_layouts_version('0');
            $current_layouts_version = '0';
        }
        return $current_layouts_version;
    }

    /**
     * Check whether layout is pre-built layout or custom
     */
    public static function is_prebuilt_layout($id) {//deprecated
        $protected = get_post_meta($id, '_themify_builder_prebuilt_layout', true);
        return isset($protected) && 'yes' === $protected;
    }


    public static function get_images_from_gallery_shortcode($shortcode) {//deprecated from 2020.06.02,instead of use themify_get_gallery_shortcode
        return themify_get_gallery_shortcode($shortcode);
    }


    public static function get_icon($icon) {//deprecated
        return $icon;
    }


    public static function register_directory($context, $path) {//deprecated use add_module
        if($context==='modules'){
            self::add_module($path);
        }
    }

    public static function remove_cache($post_id, $tag = false, array $args = array()) {//deprecated
        //TFCache::remove_cache($tag, $post_id, $args);
    }


    public static function register_module($module_class) {//deprecated
        $instance = new $module_class();
        self::$modules[$instance->slug] = $instance;
    }


    public static function hasAccess() {//deprecated
        return Themify_Access_Role::check_access_backend();
    }

    public static function localize_js($object_name, $l10n) {//deprecated
        foreach ((array) $l10n as $key => $value) {
            if (is_scalar($value)) {
                $l10n[$key] = html_entity_decode((string) $value, ENT_QUOTES, 'UTF-8');
            }
        }
        $l10n = apply_filters("tb_localize_js_{$object_name}", $l10n);

        return $l10n ? "var $object_name = " . wp_json_encode($l10n) . ';' : '';
    }

    
    /**
     * Returns list of colors and thumbnails
     *
     * @return array
     */
    public static function get_colors() {//deprecated moved to js
        return array();
    }

    /**
     * Check whether module is active
     * @param $name
     * @return boolean
     */
    public static function check_module_active(string $name):bool {//deprecated use Themify_Builder_Component_Module::load_modules
        return isset(self::$modules[$name]);
    }
}
