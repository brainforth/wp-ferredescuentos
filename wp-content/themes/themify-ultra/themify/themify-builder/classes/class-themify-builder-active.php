<?php
defined('ABSPATH') || exit;

class Themify_Builder_Active{
        
        public static function init():void{
            self::includes();
            self::run();
        }
        
        private static function run():void{
            if (Themify_Builder_Model::is_front_builder_activate()) {
                // load module panel frontend
                add_action('wp_footer', array(__CLASS__, 'load_javascript_template_front'), 1);
                add_filter('show_admin_bar', '__return_false'); // Custom CSS
                add_filter('themify_dev_mode', '__return_false');
                global $wp_actions;
                $wp_actions['wp_enqueue_media'] = true;
                themify_set_headers(array(
                    'X-FRAME-OPTIONS' => 'SAMEORIGIN',
                    'CONTENT-SECURITY-POLICY' => array(
                        'img-src' => "https://themify.org https://placehold.co 'unsafe-inline' blob:",
                        'default-src' => "https://placehold.co 'unsafe-inline' blob:"
                    )
                ));
            } 
            else {
                $is_admin = is_admin();
                $is_ajax=themify_is_ajax();
                // Ajax Actions
                if ($is_ajax===true) {
                    add_action('wp_ajax_tb_load_editor', array(__CLASS__, 'load_editor'), 10);

                    add_action('wp_ajax_tb_load_module_partial', array(__CLASS__, 'load_module_partial_ajaxify'), 10);
                    add_action('wp_ajax_tb_render_element', array(__CLASS__, 'render_element_ajaxify'), 10);
                    add_action('wp_ajax_tb_get_post_types', array(__CLASS__, 'get_ajax_post_types'), 10);
                    add_action('wp_ajax_tb_render_element_shortcode', array(__CLASS__, 'render_element_shortcode_ajaxify'), 10);
                    // Builder Save Data
                    add_action('wp_ajax_tb_save_data', array(__CLASS__, 'save_data_builder'), 10);
                    add_action('wp_ajax_tb_save_css', array(__CLASS__, 'save_builder_css'), 10);
                    // AJAX Action Save Module Favorite Data
                    add_action('wp_ajax_tb_module_favorite', array(__CLASS__, 'save_module_favorite_data'));
                    //AJAX Action update ticks and TakeOver
                    add_action('wp_ajax_tb_update_tick', array(__CLASS__, 'update_tick'));
                    add_action('wp_ajax_tb_help', array(__CLASS__, 'help'));
                    // Replace URL
                    add_action('wp_ajax_tb_get_ajax_builder_posts', array(__CLASS__, 'get_ajax_builder_posts'));
                    add_action('wp_ajax_tb_get_ajax_data', array(__CLASS__, 'get_ajax_data'));
                    add_action('wp_ajax_tb_validate_captcha', array(__CLASS__, 'ajax_validate_captcha'));
                    add_action('wp_ajax_tb_get_pages', array(__CLASS__, 'tb_get_pages'));
                } 
                if ($is_admin === false || $is_ajax===true) {
                    add_action('admin_bar_menu', array(__CLASS__, 'builder_admin_bar_menu'), 100);
                    if ($is_admin === false) {
                        add_action('wp_footer', array(__CLASS__, 'async_footer'));
                    }
                }
                // Import Export
                Themify_Builder_Import_Export::init();
            }

            // Library Module, Rows and Layout Parts
            Themify_Builder_Library_Items::init();

            // Themify Builder Revisions
            Themify_Builder_Revisions::init();

            // Fix security restrictions
            add_filter('user_can_richedit', '__return_true');
        }

        /**
         * Load interface js and css
         */
        private static function load_frontend_interface():void {

            // load only when builder is turn on
            $editorUrl = THEMIFY_BUILDER_URI . '/css/editor/';
            themify_enque_style('themify-builder-admin-ui', $editorUrl . 'themify-builder-admin-ui.css', array(), THEMIFY_VERSION, '', true);
            $grids = array_diff(scandir(THEMIFY_DIR . '/css/grids/', SCANDIR_SORT_NONE), array('..', '.'));
            foreach ($grids as $g) {
                Themify_Enqueue_Assets::loadGridCss(str_replace('.css', '', $g), true);
            }
            unset($grids);

            $editorUrl = THEMIFY_BUILDER_URI . '/js/editor/';
            Themify_Icon_Font::enqueue();

            themify_enque_script('themify-colorpicker', THEMIFY_METABOX_URI . 'js/themify.minicolors.js');
            themify_enque_script('themify-combobox', $editorUrl . 'themify-combobox.min.js');

            themify_enque_script('themify-builder-js', THEMIFY_BUILDER_URI . '/js/themify-builder-script.js');
            themify_enque_script('tb_builder_js_style', THEMIFY_URI . '/js/generate-style.js');
            themify_enque_script('themify-builder-app-js', $editorUrl . 'build/components.min.js', THEMIFY_VERSION, array('tb_builder_js_style'));
            themify_enque_script('themify-builder-modules-js', $editorUrl . 'build/modules.min.js', THEMIFY_VERSION, array('themify-builder-app-js'));

            themify_enque_script('themify-builder-front-ui-js', $editorUrl . 'frontend/themify-builder-visual.js', THEMIFY_VERSION, array('themify-builder-app-js'));

            global $shortcode_tags;
            $builderData = self::get_active_builder_vars();
            $builderData['upload_url'] = themify_upload_dir('baseurl');
            $builderData['available_shortcodes'] = array_keys($shortcode_tags);

            do_action('themify_builder_active_enqueue', 'visual');
            wp_localize_script('themify-colorpicker', 'themifyCM', Themify_Metabox::themify_localize_cm_data());
            wp_localize_script('themify-builder-app-js', 'themifyBuilder', $builderData);
        }

        private static function load_admin_interface():void {
            $editorUrl = THEMIFY_BUILDER_URI . '/css/editor/';
            themify_enque_style('tf_base', THEMIFY_URI . '/css/base.min.css', null, THEMIFY_VERSION, '', true);
            themify_enque_style('themify-builder-loader', $editorUrl . 'themify-builder-loader.css', null, THEMIFY_VERSION, '', true);
            themify_enque_style('themify-builder-style', THEMIFY_BUILDER_URI . '/css/themify-builder-style.css', null, THEMIFY_VERSION, '', true);
            themify_enque_style('themify-builder-lightbox', $editorUrl . 'components/lightbox.css', null, THEMIFY_VERSION, '', true);
            themify_enque_style('themify-builder-admin-ui', $editorUrl . 'themify-builder-admin-ui.css', null, THEMIFY_VERSION, '', true);
            themify_enque_style('themify-backend-mode', $editorUrl . 'backend-mode.css', null, THEMIFY_VERSION, '', true);
            themify_enque_style('themify-backend-ui', $editorUrl . 'backend/backend-ui.css', null, THEMIFY_VERSION, '', true);
            $editorUrl = THEMIFY_BUILDER_URI . '/js/editor/';

            Themify_Enqueue_Assets::loadMainScript();

            themify_enque_script('themify-combobox', $editorUrl . 'themify-combobox.min.js');

            themify_enque_script('tb_builder_js_style', THEMIFY_URI . '/js/generate-style.js');

            themify_enque_script('themify-builder-app-js', $editorUrl . 'build/components.min.js', THEMIFY_VERSION, array('tb_builder_js_style'));
            themify_enque_script('themify-builder-modules-js', $editorUrl . 'build/modules.min.js', THEMIFY_VERSION, array('themify-builder-app-js'));

            themify_enque_script('themify-builder-backend-js', $editorUrl . 'backend/themify-builder-backend.js', THEMIFY_VERSION, array('themify-builder-app-js'));

            themify_enque_script('themify-static-badge', THEMIFY_BUILDER_URI . '/js/editor/backend/themify-builder-static-badge.js', THEMIFY_VERSION, array('mce-view', 'themify-builder-backend-js'));



            $builderData = self::get_active_builder_vars();
            $builderData['post_ID'] = get_the_ID();
            $builderData['is_gutenberg_editor'] = Themify_Builder_Model::is_gutenberg_editor();
            do_action('themify_builder_active_enqueue', 'admin');

            wp_localize_script('themify-builder-app-js', 'themifyBuilder', $builderData);
        }

        
        private static function get_active_builder_vars():array {
            Themify_Builder_Component_Module::load_modules();
            $video=$audio=$image=[];
            if(current_user_can('upload_files')){
                $tmp_extensions = get_allowed_mime_types();
                $allowed_extensions=[];
                foreach($tmp_extensions as $ext=>$v){
                    if(strpos($ext,'|')!==false){
                        $ext=explode('|',$ext);
                        foreach($ext as $ex){
                            $allowed_extensions[$ex]=$v;
                        }
                    }else{
                        $allowed_extensions[$ext]=$v;
                    }
                }
                $extensions=wp_get_ext_types();
                $_video= $extensions['video'];
                $_audio=$extensions['audio'];
                $_image= $extensions['image'];
                $tmp_extensions=$extensions=null;
                foreach($_video as $v){
                    if ( isset($allowed_extensions[$v])  ) {
                        $mime=explode('/',$allowed_extensions[$v])[1];
                        if(!isset($video[$mime])){
                            $video[$mime]='';
                        }
                        $video[$mime].=$v.'|';
                    }
                }
                foreach($_audio as $v){
                    if ( isset($allowed_extensions[$v])  ) {
                        $mime=explode('/',$allowed_extensions[$v])[1];
                        if(!isset($audio[$mime])){
                            $audio[$mime]='';
                        }
                        $audio[$mime].=$v.'|';
                    }
                }
                foreach($_image as $v){
                    if ( isset($allowed_extensions[$v])  ) {
                        $mime=explode('/',$allowed_extensions[$v])[1];
                        if(!isset($image[$mime])){
                            $image[$mime]='';
                        }
                        $image[$mime].=$v.'|';
                    }
                }
                $allowed_extensions=$_video=$_audio=$_image=null;
            }
            global $wp_styles;
            $id = is_admin() ? get_the_ID() : Themify_Builder::$builder_active_id;
            $i18n=include THEMIFY_BUILDER_INCLUDES_DIR . '/i18n.php';
            $vars=apply_filters('themify_builder_active_vars',[
                'builder_data' => ThemifyBuilder_Data_Manager::get_data($id),
                'addons'=>[],
                'site_url' => get_site_url(),
                'nonce' => wp_create_nonce('tf_nonce'),
                'disableShortcuts' => themify_builder_get('setting-page_builder_disable_shortcuts', 'builder_disable_shortcuts'),
                'widget_css' => array(home_url($wp_styles->registered['widgets']->src), home_url($wp_styles->registered['customize-widgets']->src)),
                'modules' => Themify_Builder_Model::get_modules_localize_settings(),
                'favorite' => Themify_Builder_Model::get_favorite_modules(),
                'gutters' => Themify_Builder_Model::get_gutters(),
                'i18n' => $i18n,
                'layouts' => Themify_Builder_Model::get_layouts(),
                'blocks' => 'https://themify.org/public-api/predesigned-rows/',
                'custom_css' => get_post_meta($id, 'tbp_custom_css', true),
                'post_title' => get_the_title($id),
                'cf_api_url' => Themify_Custom_Fonts::$api_url,
                'safe' => themify_get_web_safe_font_list(),
                'google' => themify_get_google_web_fonts_list(),
                'cf' => Themify_Custom_Fonts::get_list(),
                'ticks' => Themify_Builder_Model::get_transient_time(),
                'memory' => (int) (wp_convert_hr_to_bytes(WP_MEMORY_LIMIT) * MB_IN_BYTES),
                'imgphp'=>Themify_Builder_Model::is_img_php_disabled(),
                'admin_url'=>rtrim( get_admin_url(), '/' ),
                'ext'=>['video'=>$video,'audio'=>$audio,'image'=>$image]
            ], is_admin() ? 'admin' : 'visual' );
            unset($image,$video,$audio);
            $jsonFiles=Themify_Builder_Component_Module::get_styles_json();
            $vars['style_json']=$jsonFiles;
            if(!is_admin()){
                foreach($jsonFiles as $f){
                    Themify_Enqueue_Assets::addPreLoadMedia($f,'prefetch','json');
                }
            }
            if(!current_user_can('upload_files')){
                $vars['upload_disable']=1;
            }
            if(!function_exists('gzdecode')){
                $vars['gzip_disabled']=1;
            }
            if(empty($vars['custom_css'])){
                unset($vars['custom_css']);
            }
            if(empty($vars['imgphp'])){
                unset($vars['imgphp']);
            }
            if(empty($vars['favorite'])){
                unset($vars['favorite']);
            }
            if(empty($vars['addons'])){
                unset($vars['addons']);
            }
            if(empty($vars['disableShortcuts'])){
                unset($vars['disableShortcuts']);
            }
            if(empty($vars['style_json'])){
                unset($vars['style_json']);
            }
            return $vars;
        }

        private static function includes():void {
            include THEMIFY_BUILDER_CLASSES_DIR . '/class-themify-builder-revisions.php';
            include THEMIFY_BUILDER_CLASSES_DIR . '/class-themify-builder-library-item.php';
            include THEMIFY_BUILDER_CLASSES_DIR . '/class-builder-duplicate-page.php';
            include THEMIFY_BUILDER_CLASSES_DIR . '/class-themify-builder-import-export.php';
        }

        
        /**
         * Loads JS templates for front-end editor.
         */
        public static function load_javascript_template_front():void {
            add_filter( 'wp_inline_script_attributes', array(__CLASS__, 'exclude_cloudfare_js'), 11,1);

            self::load_frontend_interface();
            include THEMIFY_BUILDER_INCLUDES_DIR . '/tpl/tmpl-common.php';
            include THEMIFY_BUILDER_INCLUDES_DIR . '/tpl/tmpl-front.php';
        }

        /**
         * Loads JS templates for WordPress admin dashboard editor.
         */
        public static function load_javascript_template_admin():void {
            self::load_admin_interface();
            self::print_static_content_badge_templates();
            include THEMIFY_BUILDER_INCLUDES_DIR . '/tpl/tmpl-common.php';
            include THEMIFY_BUILDER_INCLUDES_DIR . '/tpl/tmpl-admin.php';
        }

        
        
        public static function exclude_cloudfare_js(array $attr=array()):array {
            if(isset($attr['id']) && ($attr['id']==='themify-builder-app-js-js-extra' || $attr['id']==='themify-colorpicker-js-extra' || $attr['id']==='tf-icon-picker-js-extra')){
                $attr['data-cfasync']='false';
                $attr['data-no-optimize']=$attr['data-noptimize']=1;
            }
            return $attr;
        }

        public static function load_editor() {
            global $wp_scripts, $wp_styles, $concatenate_scripts, $wp_actions;
            if (!defined('CONCATENATE_SCRIPTS')) {
                define('CONCATENATE_SCRIPTS', false);
            }
            $concatenate_scripts = false;

            /* ensure $wp_scripts and $wp_styles globals have been initialized */
            wp_styles();
            wp_scripts();

            wp_enqueue_script( 'jquery-ui-sortable' );
            wp_enqueue_script( 'shortcode' );

            /* force a load uncompressed TinyMCE script, fix script issues on frontend editor */
            $wp_scripts->remove('wp-tinymce');
            wp_register_tinymce_scripts($wp_scripts, true); // true: $force_uncompressed
            //store original
            if (is_object($wp_styles)) {
                $tmp_styles = clone $wp_styles;
            }
            $tmp_scripts = clone $wp_scripts;
            $new = array();

            foreach ($tmp_scripts->registered as $k => $v) {
                $new[$k] = clone $v;
            }

            $scripts_registered = $new;
            if (isset($tmp_styles)) {
                $new = array();
                foreach ($tmp_styles->registered as $k => $v) {
                    $new[$k] = clone $v;
                }
                $styles_registered = $new;
            }
            $new = null;
            //don't allow loading wp_enqueue_media thirdy party plugins
            $wp_actions['wp_enqueue_media'] = true;

            //for thirdy party plugins, maybe they use wp_enqueue_scripts hook to add localize data in tinymce, the js files we don't need to check by wp standart they should use mce_external_plugins
            do_action('wp_enqueue_scripts');
            $data = !empty($wp_scripts->registered['editor']->extra['data']) ? $wp_scripts->registered['editor']->extra['data'] : null;

            //restore original because we only need the js/css related with wp editor only,otherwise they will be loaded in wp_footer
            if (isset($tmp_styles)) {
                $tmp_styles->registered = $styles_registered;
                $wp_styles = clone $tmp_styles;
                $tmp_styles = $styles_registered = null;
            }
            $tmp_scripts->registered = $scripts_registered;

            $wp_scripts = clone $tmp_scripts;
            $tmp_scripts = $scripts_registered = null;

            $wp_scripts->done[] = 'jquery';
            $wp_scripts->done[] = 'jquery-core';
            unset($wp_actions['wp_enqueue_media']);
            echo '<div id="tb_tinymce_wrap">';
            if (current_user_can('upload_files')) {
                wp_enqueue_media();
            }
            echo '<div style="display:none;">';
            wp_editor(' ', 'tb_lb_hidden_editor');
            echo '</div>';
            if (!empty($wp_scripts->registered['editor']->deps)) {
                $wp_scripts->registered['editor']->deps[] = 'wp-tinymce-root';
            } else {
                $wp_scripts->registered['editor']->deps = array('wp-tinymce-root');
            }
            //wp is returing is_admin() true on the ajax call,that is why we have to add these hooks manually
            add_action('wp_print_footer_scripts', array('_WP_Editors', 'editor_js'), 50);
            add_action('wp_print_footer_scripts', array('_WP_Editors', 'force_uncompressed_tinymce'), 1);
            add_action('wp_print_footer_scripts', array('_WP_Editors', 'enqueue_scripts'), 1);
            wp_footer();

            if ($data !== null) {
                echo '<script>', $data, '</script>';
            }
            echo '</div>';
            die;
        }

        /**
         * Load module partial when update live content
         */
        public static function load_module_partial_ajaxify() {
            check_ajax_referer('tf_nonce', 'nonce');
            themify_disable_other_lazy();
            Themify_Builder::$frontedit_active = true;
            Themify_Builder::$builder_active_id = $_POST['bid'];
            $new_modules = apply_filters('themify_builder_load_module_partial', array(
                'mod_name' => $_POST['tb_module_slug'],
                'mod_settings' => json_decode(stripslashes($_POST['tb_module_data']), true),
                'element_id' => $_POST['element_id']
            ));

            Themify_Builder_Component_Module::template($new_modules, Themify_Builder::$builder_active_id);
            $css = Themify_Enqueue_Assets::get_css();
            if (!empty($css)) {
                echo '<script type="text/template" id="tb_module_styles">', json_encode($css), '</script>';
            }
            wp_die();
        }

        public static function render_element_ajaxify() {
            check_ajax_referer('tf_nonce', 'nonce');
            themify_disable_other_lazy();
            $response = array();
            $batch = json_decode(stripslashes($_POST['batch']), true);
            Themify_Builder::$frontedit_active = true;
            $batch = apply_filters('themify_builder_load_module_partial', $batch);
            Themify_Builder::$builder_active_id =$activeId= $_POST['bid'];
            if (!empty($_POST['tmpGS'])) {
                Themify_Global_Styles::$used_styles[$activeId] = Themify_Global_Styles::addGS($activeId, json_decode(stripslashes($_POST['tmpGS']), true));
            }
            if (!empty($batch)) {
                $used_gs = array();
                foreach ($batch as $b) {
                    $type = $b['elType'];
                    $element_id = $b['element_id'];
                    switch ($type) {
                        case 'module':
                            if (isset($_POST['element_id'])) {
                                $element_id = $b['element_id'] = $_POST['element_id'];
                            }
                            $markup = Themify_Builder_Component_Module::template($b, $activeId, false);
                            break;

                        case 'subrow':
                            unset($b['cols']);
                            $markup = Themify_Builder_Component_SubRow::template($b, $activeId, false);
                            break;

                        case 'column':
                            unset($b['modules']);
                            $markup = Themify_Builder_Component_Column::template($b, $activeId, false);
                            break;

                        case 'row':
                            unset($b['cols']);
                            $markup = Themify_Builder_Component_Row::template($b, $activeId, false);
                            break;
                    }
                    $response[$element_id] = $markup;
                    if (!empty($b['attached_gs'])) {
                        $used_gs = array_merge($used_gs, $b['attached_gs']);
                    }
                }
            }
            $batch = null;
            if (!empty($used_gs)) {
                $used_gs = array_keys(array_flip($used_gs));
                // Return used gs
                $args = array(
                    'exclude' => empty($_POST['loadedGS']) ? array() : $_POST['loadedGS'],
                    'include' => $used_gs,
                    'limit' => -1,
                    'data' => true
                );

                $used_gs = Themify_Global_Styles::get_global_styles($args);
                if (!empty($used_gs)) {
                    $response['gs'] = $used_gs;
                }
            }
            $css = Themify_Enqueue_Assets::get_css();
            if (!empty($css)) {
                $response['tb_module_styles'] = $css;
            }
            //don't use wp_send_json it's very heavy,this array can be very large
            header('Content-Type: application/json; charset=' . get_option('blog_charset'));
            die(json_encode($response));
        }

        public static function render_element_shortcode_ajaxify() {
            check_ajax_referer('tf_nonce', 'nonce');
            $shortcodes = $styles = array();
            $shortcode_data = json_decode(stripslashes_deep($_POST['shortcode_data']), true);
    
            if (is_array($shortcode_data)) {
                if(!empty($_POST['bid'])){
                    $p=get_post((int)$_POST['bid']);
                    if(!empty($p)){
                        $arr=array(
                            'no_found_rows'=>false,
                            'ignore_sticky_posts'=>true,
                            'orderby'=>'none',
                            'post_status'=>$p->post_status,
                            'post_type'=>$p->post_type
                        );
                        if($p->post_type==='page'){
                            $arr['page_id']=$p->ID;
                        }else{
                            $arr['p']=$p->ID;
                        }
                        query_posts($arr);
                        unset($arr);
                        global $post;
                        $post=$p;
                        setup_postdata( $post );
                    }
                }
                foreach ($shortcode_data as $shortcode) {
                    $shortcodes[] = array('key' => $shortcode, 'html' => Themify_Builder_Model::format_text($shortcode));
                }
            }

            global $wp_styles;
            if (isset($wp_styles) && !empty($shortcodes)) {
                ob_start();
                $tmp = $wp_styles->do_items();
                ob_end_clean();
                foreach ($tmp as $handler) {
                    if (isset($wp_styles->registered[$handler])) {
                        $src = $wp_styles->registered[$handler]->src;
                        if (strpos($src, 'http') === false) {
                            $src = home_url($src);
                        }
                        $styles[] = array(
                            's' => $src,
                            'v' => $wp_styles->registered[$handler]->ver,
                            'm' => isset($wp_styles->registered[$handler]->args) ? $wp_styles->registered[$handler]->args : 'all'
                        );
                    }
                }
                unset($tmp);
            }

            wp_send_json_success(array(
                'shortcodes' => $shortcodes,
                'styles' => $styles
            ));
        }

        /**
         * Save builder main data
         */
        public static function save_data_builder() {
            check_ajax_referer('tf_nonce', 'nonce');
            if (!empty($_POST['bid'])) {
                // Information about a writing process.
                $results = array();
                if (isset($_POST['data'])) {
                    if(isset($_POST['mode']) && $_POST['mode']==='gzip'){
                        if(!function_exists('gzdecode')){
                            wp_send_json_error(__('gzdecode is disabled', 'themify'));
                        }
                        $data=gzdecode(base64_decode($_POST['data']));
                    }
                    else{
                        $data = stripslashes_deep($_POST['data']);
                    }
                } 
                elseif (isset($_FILES['data'])) {
                    $data = file_get_contents($_FILES['data']['tmp_name']);
                }
                if (isset($data)) {//don't use empty, when builder is empty need to remove builder data
                    $post_id = (int) $_POST['bid'];
                    if (current_user_can('edit_post', $post_id)) {
                        if (!empty($_POST['images'])) {
                            if(isset($_POST['mode']) && $_POST['mode']==='gzip'){
                                $images = gzdecode(base64_decode($_POST['images']));                                
                            }
                            else{
                                $images = stripslashes_deep($_POST['images']);
                            }
                            if(!empty($images)){
                                $images=json_decode($images,true);
                            }
                            if (!empty($images) && is_array($images)) {
                                foreach ($images as $img) {
                                    $ext=strtolower(strtok(pathinfo($img,PATHINFO_EXTENSION ),'?'));
                                    if($ext==='png' || $ext==='jpg' || $ext==='jpeg' || $ext==='webp' || $ext==='gif' ||$ext==='bmp' ){
                                        themify_get_image_size($img);
                                        themify_create_webp($img);
                                    }
                                }
                            }
                            unset($images);
                        }
                        $data = !empty($data) ? json_decode($data, true) : array();
                        if (empty($data) || !is_array($data)) {
                            $data = array();
                        }
                        Themify_Builder::$builder_is_saving = true;
                        $custom_css = isset($_POST['custom_css']) ? $_POST['custom_css'] : null;
                        $results = ThemifyBuilder_Data_Manager::save_data($data, $post_id, $_POST['sourceEditor'], $custom_css);
                        if (!empty($results['mid'])) {
                            $data = $post_id = null;
                            $results['builder_data'] = json_decode($results['builder_data'], true);
                        } else {
                            wp_send_json_error(__('Can`t Save Builder Data', 'themify'));
                        }
                        Themify_Builder::$builder_is_saving = false;
                    } else {
                        wp_send_json_error(__('You Don`t have permission to edit this post', 'themify'));
                    }
                }
                wp_send_json_success($results);
            }
        }

        
        public static function save_builder_css() {
            if (!empty($_POST['bid']) && current_user_can('edit_post', (int) $_POST['bid'])) {
                Themify_Builder_Stylesheet::save_builder_css(true);
            } 
            else {
                wp_send_json_error(__('You Don`t have permission to edit this post', 'themify'));
            }
        }

        /**
         * Static badge js template
         */
        private static function print_static_content_badge_templates():void {
            ?>
            <script type="text/html" id="tmpl-tb-static-badge">
                <div class="tb_static_badge_box">
            <?php if (!Themify_Builder_Model::is_gutenberg_editor()): ?>
                        <h4><?php esc_html_e('Themify Builder Placeholder', 'themify'); ?></h4>
                        <p><?php esc_html_e('This badge represents where the Builder content will append on the frontend. You can move this placeholder anywhere within the editor or add content before or after.', 'themify'); ?></p>
                        <p><?php echo sprintf('%s <a href="#" class="tb_mce_view_frontend_btn">%s</a> | <a href="#" class="tb_mce_view_backend_btn">%s</a>', esc_html__('Edit Builder:', 'themify'), esc_html__('Frontend', 'themify'), esc_html__('Backend', 'themify')); ?></p>
            <?php endif; ?>
                </div>
            </script>
            <?php if (Themify_Builder_Model::is_gutenberg_editor()): ?>
                <div style="display: none;"><?php wp_editor(' ', 'tb_lb_hidden_editor'); ?></div>
            <?php endif; ?>
            <?php
        }

        
        public static function update_tick() {
            check_ajax_referer('tf_nonce', 'nonce');
            Themify_Builder::$frontedit_active = true;
            if (!empty($_POST['bid'])) {
                if (!empty($_POST['count'])) {
                    global $wp_roles;
                    $roles = $wp_roles->get_names();
                    unset($roles['subscriber']);
                    $users = get_users(array('role_in' => array_keys($roles), 'orderby' => 'ID', 'number' => 2, 'fields' => array('ID')));
                    if (count($users) < 2) {
                        wp_die('cancel');
                    }
                }
                $id = (int) $_POST['bid'];
                $uid = Themify_Builder_Model::get_edit_transient($id);
                $current = get_current_user_id();
                //print_r($roles);
                if (!$uid || $uid == $current || !empty($_POST['take'])) {
                    Themify_Builder_Model::set_edit_transient($id, $current);
                } else {
                    include( THEMIFY_BUILDER_INCLUDES_DIR . '/tpl/tmpl-locked.php' );
                }
            }
            wp_die();
        }

        public static function help():void {
            check_ajax_referer('tf_nonce', 'nonce');
            include THEMIFY_BUILDER_INCLUDES_DIR . '/tpl/tmpl-help.php';
            wp_die();
        }
        
        
        /**
         * Save Module Favorite Data
         *
         * @return void
         */
        public static function save_module_favorite_data() {
            check_ajax_referer('tf_nonce', 'nonce');
            if (isset($_POST['module_state'], $_POST['module_name'])) {
                $module = $_POST['module_name'];
                $module_state = (int) $_POST['module_state'];
                $user_id = get_current_user_id();
                $key = 'themify_module_favorite';
                $user_favorite_modules = Themify_Builder_Model::get_favorite_modules();
                if ($module_state === 1) {
                    $user_favorite_modules[] = $module;
                    $user_favorite_modules = array_keys(array_flip($user_favorite_modules));
                } elseif (!empty($user_favorite_modules)) {
                    $index = array_search($module, $user_favorite_modules);
                    if ($index !== false) {
                        array_splice($user_favorite_modules, $index, 1);
                    }
                }
                if (!empty($user_favorite_modules)) {
                    update_user_option($user_id, $key, json_encode($user_favorite_modules));
                } else {
                    delete_user_option($user_id, $key);
                }
            }
            die('1');
        }

        public static function get_ajax_post_types() {
            check_ajax_referer('tf_nonce', 'nonce');
            if (isset($_POST['type'])) {
                $result = array();
                $post_types = false;
                if ($_POST['type'] === 'post_types') {
                    if (!empty($_POST['all']) && 'true' === $_POST['all']) {
                        $result['any'] = array('name' => __('All', 'themify'), 'options' => '');
                    }
                    $taxes = Themify_Builder_Model::get_public_taxonomies();
                    $post_types = Themify_Builder_Model::get_public_post_types();
                    foreach ($post_types as $k => $v) {
                        $result[$k] = array('name' => $v);
                        $post_type_tax = get_object_taxonomies($k);
                        foreach ($post_type_tax as $t) {
                            if (isset($taxes[$t])) {
                                if (!isset($result[$k]['options'])) {
                                    $result[$k]['options'] = array();
                                }
                                $result[$k]['options'][$t] = array('name' => $taxes[$t]);
                            }
                        }
                    }
                    unset($taxes, $exclude);
                } 
                elseif ($_POST['type'] === 'terms' && !empty($_POST['v'])) {
                    $tax = get_taxonomy($_POST['v']);
                    if(!empty($tax)){
                        $args = array(
                            'hide_empty' => true,
                            'no_found_rows' => true,
                            'orderby' => 'name',
                            'order' => 'ASC',
                            'taxonomy' => $tax->name
                        );
                        if (!empty($_POST['s'])) {
                            $args['name__like'] = sanitize_text_field($_POST['s']);
                        } else {
                            $args['number'] = 50;
                        }
                        $terms_by_tax = get_terms($args);
                        unset($args);
                        $result['0'] = $tax->labels->all_items;
                        foreach ($terms_by_tax as $v) {
                            $result[$v->slug] = $v->name;
                        }
                        unset($tax);
                    }
                }
                wp_send_json(apply_filters('themify_builder_query_post', $result, $_POST['type'], $post_types));
            }
            wp_die();
        }

        
        /**
         * Get all posts from all post type that has builder data as post meta
         * @return array posts id
         * @since 4.1.2
         */
        public static function get_ajax_builder_posts() {
            check_ajax_referer('tf_nonce', 'nonce');
            if (current_user_can('edit_pages')) {
                $result = array();
                $page = !empty($_POST['page']) ? (int) $_POST['page'] : 1;
                $limit = 8;
                $args = array(
                    'post_type' => get_post_types(),
                    'posts_per_page' => $limit,
                    'fields' => 'ids',
                    'post_status' => 'any',
                    'paged' => $page,
                    'order' => 'ASC',
                    'orderby' => 'ID',
                    'update_post_term_cache' => false,
                    'ignore_sticky_posts' => true,
                    'update_post_meta_cache' => false,
                    'lazy_load_term_meta' => false,
                    'cache_results' => false,
                    'meta_query' => array(
                        array(
                            'key' => ThemifyBuilder_Data_Manager::META_KEY,
                            'compare_key' => '=',
                            'compare' => 'EXISTS'
                        )
                    )
                );
                $query = new WP_Query($args);
                if ($page === 1) {
                    $result['pages'] = $query->max_num_pages;
                    $result['total'] = $query->found_posts;
                    $result['labels'] = array(
                        'same_url' => __('Same Url', 'themify'),
                        'searching' => __('Searching "%find%" in posts(%count%/%total%): %posts%', 'themify'),
                        'found' => __('Found "%find%" in posts(%count%): %posts%', 'themify'),
                        'saving' => __('Saving posts(%count%/%total%): %posts%', 'themify'),
                        'no_found' => __('There is no builder containg "%find%"', 'themify'),
                        'error' => __('There are some errors: %posts%', 'themify'),
                        'wrong_url' => __('Please type url', 'themify'),
                        'done' => __('Done', 'themify')
                    );
                }
                if (!empty($query->posts)) {
                    foreach ($query->posts as $id) {
                        $result['posts'][] = array('data' => ThemifyBuilder_Data_Manager::get_data($id), 'title' => get_the_title($id), 'id' => $id);
                    }
                }
                wp_send_json_success($result);
            } else {
                wp_send_json_error(__('You Don`t have permission to edit pages', 'themify'));
            }
            wp_send_json_error();
        }

        

        
        /**
         * Handles Ajax request to get dynamic values 
         *
         * Calls "tb_select_dataset_{$dataset}" filter
         *
         * @since 4.6.5
         */
        public static function get_ajax_data() {
            check_ajax_referer('tf_nonce', 'nonce');
            if (empty($_POST['dataset']) && empty($_POST['mode'])) {
                wp_send_json_error();
            }
            $pid = (int) $_POST['bid'];
            $dataset = isset($_POST['dataset']) ? sanitize_text_field($_POST['dataset']) : '';
            $mode = isset($_POST['mode']) ? $_POST['mode'] : null;

            if ($dataset === 'taxonomy') {
                $result = Themify_Builder_Model::get_public_taxonomies();
            } 
            elseif ($dataset === 'post_type') {
                $result =Themify_Builder_Model::get_public_post_types();
            }
            elseif ($dataset === 'menu') {
                $menu = get_terms(array('taxonomy' => 'nav_menu', 'hide_empty' => false));
                $result = array('' => __('Select a Menu...', 'themify'));
                foreach ($menu as $m) {
                    $result[$m->slug] = $m->name;
                }
                unset($menu);
            } 
            elseif ($dataset === 'gallery_shortcode') {
                if (empty($_POST['val'])) {
                    wp_send_json_error();
                }
                $images = themify_get_gallery_shortcode(sanitize_text_field($_POST['val']));
                $result = array();
                if (!empty($images)) {
                    foreach ($images as $image) {
                        $full=wp_get_attachment_image_src($image->ID, 'full');
                        $large=wp_get_attachment_image_src($image->ID, 'large');
                        $thumb=wp_get_attachment_image_src($image->ID, 'thumbnail');
                        $link=wp_get_attachment_url($image->ID);
                        $arr=[
                            'id' => $image->ID
                        ];
                        if(!empty($large)){
                            array_pop($large);
                            $arr['large']=$large;
                        }
                        if(!empty($full)){
                            array_pop($full);
                            $arr['full']=$full;
                        }
                        if(!empty($thumb)){
                            $arr['thumbnail']=$thumb[0];
                        }
                        if(!empty($image->post_excerpt)){
                            $arr['caption']=wp_get_attachment_caption($image->ID);
                        }
                        if(!empty($image->post_title)){
                            $arr['title']=$image->post_title;
                        }
                        if(!empty($link)){
                            $arr['link']=$link;
                        }
                        $result[] = $arr;
                    }
                }
                unset($images);
            }
            elseif ($mode === 'autocomplete') {
                if (empty($_POST['value'])) {
                    wp_send_json_error();
                }
                $value = sanitize_text_field($_POST['value']);
                if ($dataset === 'authors') {
                    $users = get_users(array(
                        'search' => '*' . $value . '*',
                        'number' => 50,
                        'search_columns' => ['user_login'],
                        'fields' => ['user_login'],
                        ));
                    $logins = wp_list_pluck($users, 'user_login');
                    $result = array_combine($logins, $logins);
                } elseif ($dataset === 'custom_fields') {
                    global $wpdb;
                    $arr = $wpdb->get_results(
                        $wpdb->prepare(
                            "SELECT DISTINCT BINARY meta_key FROM {$wpdb->prefix}postmeta WHERE meta_key like %s LIMIT 50",
                            esc_sql($value) . '%'
                        ),
                        OBJECT);
                    $result = wp_list_pluck($arr, 'BINARY meta_key', 'BINARY meta_key');
                } elseif ($dataset !== '') {
                    $result = apply_filters("tb_autocomplete_dataset_{$dataset}", array(), $value, $pid);
                }
            }
            else {
                $result = apply_filters("tb_select_dataset_{$dataset}", array(), $pid);
            }
            /**
             * The return value should be in the format of:
             *
             *     array(
             *         'options' => array(
             *             {value} => {label},
             *         )
             *     )
             *
             * Or for select fields with multiple groups:
             *
             *     array(
             *         'optgroup' => true,
             *         'options' => array(
             *             {group_key} => array(
             *                 'label' => {group_label},
             *                 'options' => array(
             *                     {option_value} => {option_label},
             *                 )
             *             ),
             *         )
             *     )
             *
             */
            wp_send_json_success($result);
        }

        public static function ajax_validate_captcha() {
            check_ajax_referer( 'tf_nonce', 'nonce' );
            $keys = Themify_Builder_Model::get_captcha_keys( $_POST['provider'] );
            if ( ! empty( $keys['public'] ) && ! empty( $keys['private'] ) ) {
                wp_send_json_success();
            }

            wp_send_json_error( sprintf( __('Requires Captcha keys entered at: <a target="_blank" href="%s">Integration API</a>.', 'themify'), admin_url('admin.php?page=themify#setting-integration-api') ) );
        }

        
        /**
         * Display Toggle themify builder
         * wp admin bar
         */
        public static function builder_admin_bar_menu($wp_admin_bar):void {
            if (is_admin_bar_showing()) {
                $post_id = Themify_Builder::builder_is_available();
                $isAvailable = $post_id !== null;
                $args = array(
                    array(
                        'id' => 'themify_builder',
                        'title' => '',
                        'href' => '#',
                        'meta' => array(
                            'class' => 'toggle_tb_builder',
                            'onclick' => 'javascript:;'
                        )
                    )
                );
                $args = apply_filters('themify_builder_admin_bar_menu', $args, $isAvailable);
                if ($isAvailable === false) {
                    if (isset($args[1])) {
                        foreach ($args as $b) {
                            if (isset($b['id']) && is_numeric($b['id'])) {
                                $post_id = $b['id'];
                                break;
                            }
                        }
                    }
                    if (!$post_id) {
                        $args[0]['meta']['class'] .= ' tb_disabled_turn_on';
                    }
                }
                $args[0]['title'] = '<span data-id="' . ($post_id ? $post_id : '') . '" class="tb_front_icon">' . themify_get_icon('ti-themify-favicon-alt', 'ti', false, false, array('style' => 'width:18px;height:18px;color:#ffcc08')) . '</span>';
                $args[0]['title'] .= '<span class="tb_tooltip tf_hide">' . __('Builder is not available on this page', 'themify') . '</span>' . esc_html__('Turn On Builder', 'themify');
                foreach ($args as $arg) {
                    $wp_admin_bar->add_node($arg);
                }
            }
        }

        /**
         * Load JS and CSs for async loader.
         *
         * @since 2.1.9
         */
        public static function async_footer():void {
            wp_deregister_script('wp-embed');
            $editorUrl = THEMIFY_BUILDER_URI . '/css/editor/';
            themify_enque_style('themify-builder-loader', $editorUrl . 'themify-builder-loader.css', array(), THEMIFY_VERSION, 'all', true);
            themify_enque_script('themify-builder-loader', THEMIFY_BUILDER_URI . '/js/editor/frontend/themify-builder-loader.js', THEMIFY_VERSION, array('jquery'));

            $st = array(
                THEMIFY_URI . '/css/base.min.css',
                $editorUrl . 'workspace.css',
                $editorUrl . 'components/lightbox.css'
            );
            $styles = array();
            foreach ($st as $s) {
                $styles[$s] = THEMIFY_VERSION;
            }
            $st = null;
            wp_localize_script('themify-builder-loader', 'tbLoaderVars', array(
                'styles' => apply_filters('themify_styles_top_frame', array_reverse($styles)),
                'turnOnBuilder' => __('Turn On Builder', 'themify'),
                'turnOnLpBuilder' => __('Edit Layout Part', 'themify'),
                'editTemplate' => __('Edit Template', 'themify'),
                'isGlobalStylePost' => Themify_Global_Styles::$isGlobalEditPage
            ));
            $styles = null;
        }

		public static function tb_get_pages():void {
			check_ajax_referer('tf_nonce', 'nonce');
			if ( ! empty( $_POST['bid'] ) && current_user_can( 'edit_post', $_POST['bid'] ) ) {
				$data = '<div class="tb_import_result">';
				$page = ! empty( $_POST['page'] ) ? (int) $_POST['page'] : 1;
				$limit = 20;
				$args = array(
					'fields' => 'ids',
					'post_type' => array_keys( Themify_Builder_Model::get_public_post_types() + [ 'page' => 0 ] ),
					'post_status' => 'publish',
					'posts_per_page' => $limit,
					'paged' => $page,
					'ignore_sticky_posts' => true,
					'cache_results' => false,
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
					'lazy_load_term_meta' => false,
					'orderby' => 'none',
					'order' => 'DESC'
				);
				if ( ! empty( $_POST['s'] ) ) {
					$args['s'] = sanitize_text_field( $_POST['s'] );
				}
				$query = new WP_Query( $args );
				if ( $query->have_posts() ) {
					$data .= '<ul>';
					while ( $query->have_posts() ) {
						$query->the_post();
						$post_type_object = get_post_type_object( get_post_type() );
						$data .= '<li><a href="#" data-id="' . get_the_ID() . '" data-permalink="' . get_permalink( get_the_ID() ) . '">' . get_the_title() . '<span>' . $post_type_object->labels->singular_name . '</span></a></li>';
					}
					$data .= '</ul>';
					$data .= '<div class="tb_pagination tf_textc">' . paginate_links( array(
						'base' => '',
						'format' => '?',
						'total' => ceil( $query->found_posts / $limit ),
						'current' => $page,
						'prev_next' => false
					) ) . '</div>';
				}
				$data .= '</div>';

				die($data);
			}
			wp_die();
		}
}
Themify_Builder_Active::init();

