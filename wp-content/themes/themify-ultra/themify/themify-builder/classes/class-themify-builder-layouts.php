<?php
/**
 * This file defines Builder Layouts and Layout Parts
 *
 * Themify_Builder_Layouts class register post type for Layouts and Layout Parts
 * Custom metabox, shortcode, and load layout / layout part.
 *
 *
 * @package    Themify_Builder
 * @subpackage Themify_Builder/classes
 */
if (!class_exists('Themify_Builder_Layouts',false)) {

    /**
     * The Builder Layouts class.
     *
     * This class register post type for Layouts and Layout Parts
     * Custom metabox, shortcode, and load layout / layout part.
     *
     *
     * @package    Themify_Builder
     * @subpackage Themify_Builder/classes
     * @author     Themify
     */
    class Themify_Builder_Layouts {

        /**
         * Post Type Layout Object.
         *
         * @access public
         * @var object $layout .
         */
        const LAYOUT_SLUG = 'tbuilder_layout';

        /**
         * Post Type Layout Part Object.
         *
         * @access public
         * @var string $layout_part_slug .
         */
        const LAYOUT_PART_SLUG = 'tbuilder_layout_part';

        /**
         * Store registered layout / part post types.
         *
         * @access public
         * @var array $post_types .
         */
        private static $post_types = array();

        /**
         * Holds a list of layout provider instances
         */
        private static $provider_instances = array();

        /**
         * Constructor
         *
         * @access public
         */
        public static function init() {
            self::register_layout();
            if (is_admin()) {
                // Builder write panel
                add_filter('themify_post_types', array(__CLASS__, 'extend_post_types'));
                add_filter('themify_builder_post_types_support', array(__CLASS__, 'add_builder_support'));
                add_action('add_meta_boxes_tbuilder_layout_part', array(__CLASS__, 'custom_meta_boxes'));
                add_action('add_meta_boxes_tbuilder_layout', array(__CLASS__, 'custom_meta_boxes'));

            //  add_action('wp_ajax_set_layout_action', array(__CLASS__, 'set_layout_action'), 10);
                add_action('wp_ajax_tb_save_custom_layout', array(__CLASS__, 'save_custom_layout_ajaxify'), 10);
                add_action('wp_ajax_tb_get_save_custom_layout', array(__CLASS__, 'get_custom_layout_ajaxify'), 10);

                // Quick Edit Links
                add_filter('post_row_actions', array(__CLASS__, 'row_actions'));
                add_filter('page_row_actions', array(__CLASS__, 'row_actions'));
                add_filter('bulk_actions-edit-tbuilder_layout_part', array(__CLASS__, 'row_bulk_actions'));
                add_filter('bulk_actions-edit-tbuilder_layout', array(__CLASS__, 'row_bulk_actions'));
                add_filter('handle_bulk_actions-edit-tbuilder_layout_part', array(__CLASS__, 'export_row_bulk'), 10, 3);
                add_filter('handle_bulk_actions-edit-tbuilder_layout', array(__CLASS__, 'export_row_bulk'), 10, 3);
                add_action('admin_init', array(__CLASS__, 'duplicate_action'));
                add_action('admin_init', array(__CLASS__, 'export_row'));

                // Ajax hook for Layout and Layout Parts import file.
                add_action('wp_ajax_tb_bulk_import', array(__CLASS__, 'row_bulk_import'));
                add_action('admin_enqueue_scripts', array(__CLASS__, 'admin_enqueue'));
            }
            else{
                add_filter('template_include', array(__CLASS__, 'template_singular_layout'));
            }
            add_shortcode('themify_layout_part', array(__CLASS__, 'layout_part_shortcode'));
        }

        /**
         * Registers providers for layouts in Builder
         *
         * @since 2.0.0
         */
        private static function register_providers() {
            $providers = apply_filters('themify_builder_layout_providers', array(
                'Themify_Builder_Layouts_Provider_Custom'
            ));
            foreach ($providers as $provider) {
                if (class_exists($provider,false)) {
                    $instance = new $provider();
                    self::$provider_instances[$instance->get_id()] = $instance;
                }
            }
        }

        /**
         * Get a single layout provider instance
         *
         * @since 2.0.0
         */
        public static function get_provider($id) {
            return isset(self::$provider_instances[$id]) ? self::$provider_instances[$id] : false;
        }

        private static function register_layout_post_type() {
            return new CPT(array(
                'post_type_name' => self::LAYOUT_SLUG,
                'singular' => __('Layout', 'themify'),
                'plural' => __('Layouts', 'themify')
                ), array(
                'supports' => array('title', 'thumbnail'),
                'exclude_from_search' => true,
                'show_in_nav_menus' => false,
                'show_in_menu' => false,
                'public' => true
            ));
        }

        private static function register_layout_part_post_type() {
            return new CPT(array(
                'post_type_name' => self::LAYOUT_PART_SLUG,
                'singular' => __('Layout Part', 'themify'),
                'plural' => __('Layout Parts', 'themify'),
                'slug' => 'tbuilder-layout-part'
                ), array(
                'supports' => array('title', 'thumbnail'),
                'exclude_from_search' => true,
                'show_in_nav_menus' => false,
                'show_in_admin_bar' => true,
                'show_in_menu' => false,
                'public' => true
            ));
        }

        /**
         * Register Layout and Layout Part Custom Post Type
         *
         * @access public
         */
        private static function register_layout() {
            if (!class_exists('CPT',false)) {
                include THEMIFY_DIR . '/CPT.php';
            }

            // create a template custom post type
            $layout = self::register_layout_post_type();

            // define the columns to appear on the admin edit screen
            $layout->columns(array(
                'cb' => '<input type="checkbox" />',
                'title' => __('Title', 'themify'),
                'thumbnail' => __('Thumbnail', 'themify'),
                'author' => __('Author', 'themify'),
                'date' => __('Date', 'themify')
            ));

            // populate the thumbnail column
            $layout->populate_column('thumbnail', array(__CLASS__, 'populate_column_layout_thumbnail'));

            // use "pages" icon for post type
            $layout->menu_icon('dashicons-admin-page');

            // create a template custom post type
            $layout_part = self::register_layout_part_post_type();

            // define the columns to appear on the admin edit screen
            $layout_part->columns(array(
                'cb' => '<input type="checkbox" />',
                'title' => __('Title', 'themify'),
                'shortcode' => __('Shortcode', 'themify'),
                'author' => __('Author', 'themify'),
                'date' => __('Date', 'themify')
            ));

            // populate the thumbnail column
            $layout_part->populate_column('shortcode', array(__CLASS__, 'populate_column_layout_part_shortcode'));

            // use "pages" icon for post type
            $layout_part->menu_icon('dashicons-screenoptions');

            self::set_post_type_var($layout->post_type_name);
            self::set_post_type_var($layout_part->post_type_name);

            add_post_type_support($layout->post_type_name, 'revisions');
            add_post_type_support($layout_part->post_type_name, 'revisions');
            if (is_admin()) {
                self::register_providers();
            }
        }

        /**
         * Set the post type variable.
         *
         * @access public
         * @param string $name
         */
        public static function set_post_type_var(string $name) {
            self::$post_types[] = $name;
        }

        /**
         * Custom column thumbnail.
         *
         * @access public
         * @param array $column
         * @param object $post
         */
        public static function populate_column_layout_thumbnail($column, $post) {
            echo get_the_post_thumbnail($post->ID, 'thumbnail');
        }

        /**
         * Custom column for shortcode.
         *
         * @access public
         * @param array $column
         * @param object $post
         */
        public static function populate_column_layout_part_shortcode($column, $post) {
            echo
            '<input readonly size="30" type="text" onclick="this.select();" value="' . esc_attr(sprintf('[themify_layout_part id="%d"]', $post->ID)) . '">',
            '<br/>',
            '<input readonly size="30" type="text" onclick="this.select();" value="' . esc_attr(sprintf('[themify_layout_part slug="%s"]', $post->post_name)) . '">';
        }

        /**
         * Includes this custom post to array of cpts managed by Themify
         *
         * @access public
         * @param Array $types
         * @return Array
         */
        public static function extend_post_types(array $types):array {
            $types[]=self::LAYOUT_SLUG;
            $types[]=self::LAYOUT_PART_SLUG;
            return $types;
        }

        /**
         * Add meta boxes to layout and/or layout part screens.
         *
         * @access public
         * @param object $post
         */
        public static function custom_meta_boxes($post) {
            add_meta_box('layout-part-info', __('Using this Layout Part', 'themify'), array(__CLASS__, 'layout_part_info'), self::LAYOUT_PART_SLUG, 'side', 'default');
        }

        /**
         * Displays information about this layout part.
         *
         * @access public
         */
        public static function layout_part_info() {
            $layout_part = get_post();
            echo '<div>', __('To display this Layout Part, insert this shortcode:', 'themify'), '<br/>
        <input type="text" readonly="readonly" class="widefat" onclick="this.select()" value="' . esc_attr('[themify_layout_part id="' . $layout_part->ID . '"]') . '" />';
            if (!empty($layout_part->post_name)) {
                echo '<input type="text" readonly="readonly" class="widefat" onclick="this.select()" value="' . esc_attr('[themify_layout_part slug="' . $layout_part->post_name . '"]') . '" />';
            }
            echo '</div>';
        }

        /**
         * Custom layout for Template / Template Part Builder Editor.
         */
        public static function template_singular_layout(?string $original_template):?string{
            if (is_singular(array(self::LAYOUT_SLUG, self::LAYOUT_PART_SLUG))) {
                $templatefilename = 'template-builder-editor.php';

                $return_template = locate_template(
                    array(
                        trailingslashit('themify-builder/templates') . $templatefilename
                    )
                );

                // Get default template
                if (!$return_template){
                    $return_template = THEMIFY_BUILDER_TEMPLATES_DIR . '/' . $templatefilename;
                }
                return $return_template;
            } else {
                return $original_template;
            }
        }
        /*
        public static function set_layout_action() {
            check_ajax_referer('tf_nonce', 'nonce');
            if ( ! empty( $_POST['bid'] ) && current_user_can( 'edit_post', $_POST['bid'] ) ) {
                $mode = !empty($_POST['mode']) ? 'themify_builder_layout_appended' : 'themify_builder_layout_loaded';
                do_action($mode, array('template_slug' => '', 'current_builder_id' => (int) $_POST['bid'], 'layout_group' => '', 'builder_data' => ''));
            }
            die;
        } 
         */

        /**
         * Layout Part Shortcode
         *
         * @access public
         * @param array $atts
         * @return string
         */
        public static function layout_part_shortcode(array $atts):string {
            if ( ! empty( $atts['id'] ) ) {
                $id = $atts['id'];
            } else if ( ! empty( $atts['slug'] ) ) {
                $post = get_page_by_path( $atts['slug'], OBJECT, self::LAYOUT_PART_SLUG );
                if ( ! $post ) {
                    return '';
                }
                $id = $post->ID;
            } else {
                return '';
            }

            $id = themify_maybe_translate_object_id( $id );
            $post = get_post( $id );
            if ( ! $post ) {
                return '';
            }
            $id = $post->ID;
            if ($id == Themify_Builder::$builder_active_id && Themify_Builder_Model::is_front_builder_activate()) {
                static $isDone = false; //return only for first element
                if ($isDone === false) {
                    $isDone = true;
                    return Themify_Builder::render($id);
                }
            }
            // infinite-loop prevention
            static $stack = array();
            if (isset($stack[$id])) {
                $message = sprintf(__('Layout Part %s is in an infinite loop.', 'themify'), $id);
                return "<!-- {$message} -->";
            } else {
                $stack[$id] = true;
            }

            $output = '';
            $builder_data = ThemifyBuilder_Data_Manager::get_data($id);
            // Check For page break module
            if (!Themify_Builder::$frontedit_active) {
                $module_list = Themify_Builder::get_builder_modules_list($id);
                $page_breaks = 0;
                foreach ($module_list as $module) {
                    if (isset($module['mod_name']) && 'page-break' === $module['mod_name']) {
                        ++$page_breaks;
                    }
                }
                unset($module_list);
                $template_args = array();
                if ($page_breaks > 0) {
                    $pb_result = Themify_Builder::load_current_inner_page_content($builder_data, $page_breaks);
                    $builder_data = $pb_result['builder_data'];
                    $template_args['pb_pagination'] = $pb_result['pagination'];
                    $pb_result = null;
                }
            }
            if (!empty($builder_data)) {
                $template_args['builder_output'] = $builder_data;
                $template_args['builder_id'] = $id;
                $template_args['l_p'] = true;
                if (Themify_Builder::$frontedit_active === false) {
                    $isActive = isset($_POST['action']) && $_POST['action'] === 'tb_render_element_shortcode';
                    Themify_Builder::$frontedit_active = $isActive;
                }
                $output = Themify_Builder_Component_Module::retrieve_template('builder-layout-part-output.php', $template_args, THEMIFY_BUILDER_TEMPLATES_DIR, '', false);
                if (isset($isActive)) {
                    Themify_Builder::$frontedit_active = false;
                }
                if (!themify_is_ajax()) {
                    Themify_Builder::get_builder_stylesheet($output);
                }
                unset($template_args);
            }

            unset($stack[$id]);

            return $output;
        }

        /**
         * Save as Layout
         *
         * @access public
         */
        public static function save_custom_layout_ajaxify() {
            check_ajax_referer('tf_nonce', 'nonce');
            $response = array(
                'status' => 'failed',
                'msg' => __('Something went wrong', 'themify')
            );
            if (!empty($_POST['postid']) && current_user_can( 'edit_posts' )) {
                $template = get_post((int) $_POST['postid']);
                $title = !empty($_POST['layout_title_field']) ? sanitize_text_field($_POST['layout_title_field']) : $template->post_title . ' Layout';
                $builder_data = ThemifyBuilder_Data_Manager::get_data($template->ID);
                if (!empty($builder_data)) {
                    $new_id = wp_insert_post(array(
                        'post_status' => current_user_can( 'publish_posts' ) ? 'publish' : 'draft',
                        'post_type' => self::LAYOUT_SLUG,
                        'post_author' => $template->post_author,
                        'post_title' => $title
                    ));

                    ThemifyBuilder_Data_Manager::save_data($builder_data, $new_id);

                    // Set image as Featured Image
                    if (!empty($_POST['layout_img_field_id'])) {
                        set_post_thumbnail($new_id, (int) $_POST['layout_img_field_id']);
                    }
                    $response['status'] = 'success';
                    $response['msg'] = '';
                }
            }
            wp_send_json($response);
        }

        public static function get_custom_layout_ajaxify() {
            check_ajax_referer('tf_nonce', 'nonce');
            if ( ! current_user_can( 'edit_posts' ) ) {
                die;
            }

            $slug = !empty($_POST['slug']) ? sanitize_text_field($_POST['slug']) : '';
            if ($slug !== '') {
                $args = array(
                    'name' => $slug,
                    'post_type' => self::LAYOUT_SLUG,
                    'post_status' => 'publish',
                    'no_found_rows' => true,
                    'cache_results' => false,
                    'numberposts' => 1
                );
                $template = get_posts($args);
                if ($template) {
                    $layouts = ThemifyBuilder_Data_Manager::get_data($template[0]->ID);
                } else {
                    wp_send_json_error(__('Requested layout not found.', 'themify'));
                }
            } else {
                $layouts = self::get_saved_layouts();
            }
            wp_send_json($layouts);
        }

        /**
         * Get a list of "custom" layouts, each post from the "tbuilder_layout" post type
         * is a Custom layout, this returns a list of them all
         *
         * @return array
         */
        public static function get_saved_layouts(int $limit = -1) {
            global $post;
            $layouts = array();
            $posts = new WP_Query(array(
                'post_type' => self::LAYOUT_SLUG,
                'post_status' => 'publish',
                'posts_per_page' => $limit,
                'orderby' => 'title',
                'order' => 'ASC',
                'ignore_sticky_posts' => true,
                'no_found_rows' => true,
                'update_post_term_cache' => false,
                'update_post_meta_cache' => false,
                'cache_results' => false
            ));
            while ($posts->have_posts()) {
                $posts->the_post();
                $url = get_the_post_thumbnail_url($post, 'thumbnail');
                $layouts[] = array(
                    'title' => get_the_title(),
                    'slug' => $post->post_name,
                    'thumbnail' => !empty($url) ? $url : THEMIFY_BUILDER_URI . '/img/image-placeholder.png',
                    'url' => get_permalink( $post->ID )
                );
            }
            wp_reset_postdata();
            return $layouts;
        }

        /**
         * Add custom link actions in post / page rows
         *
         * @access public
         * @param array $actions
         * @return array
         */
        public static function row_actions(array $actions):array {
            global $post;
            if (Themify_Access_Role::check_access_frontend($post->ID)) {
                $post_type = get_post_type();
                $builder_link = sprintf('<a href="%s" target="_blank">%s</a>', esc_url(get_permalink($post->ID) . '#builder_active'), __('Themify Builder', 'themify'));
                if (self::LAYOUT_SLUG === $post_type || self::LAYOUT_PART_SLUG === $post_type) {
                    $actions['themify-builder-duplicate'] = sprintf('<a href="%s">%s</a>', wp_nonce_url(admin_url('post.php?post=' . $post->ID . '&action=duplicate_tbuilder'), 'duplicate_themify_builder'), __('Duplicate', 'themify'));
                    $actions['tbuilder-export'] = sprintf('<a href="%s">%s</a>', wp_nonce_url(admin_url('post.php?post=' . $post->ID . '&action=tbuilder_export'), 'tbuilder_layout_export'), __('Export', 'themify'));
                    $actions['themify-builder'] = $builder_link;
                } elseif (in_array($post_type, themify_post_types(), true)) {
                    $actions['themify-builder'] = $builder_link;
                }
            }
            return $actions;
        }

        /**
         * Add custom link actions in Layout / Layout Part rows bulk action
         *
         * @access public
         * @param array $actions
         * @return array
         */
        public static function row_bulk_actions(array $actions):array {

            $actions['tbuilder-bulk-export'] = __('Export', 'themify');

            return $actions;
        }

        /**
         * Export Layouts and Layout Parts.
         *
         * @access public
         */
        public static function export_row() {
            if (isset($_GET['action']) && 'tbuilder_export' === $_GET['action'] && wp_verify_nonce($_GET['_wpnonce'], 'tbuilder_layout_export')) {
                $postid = array((int) $_GET['post']);
                if (!self::export_row_bulk('', 'tbuilder-bulk-export', $postid))
                    wp_redirect(admin_url('edit.php?post_type=' . get_post_type($postid[0])));
                exit;
            }
        }

        /**
         * Export Layouts and Layout Parts.
         *
         * @access public
         */
        public static function export_row_bulk($redirect_to, $action, $pIds) {
            if ($action !== 'tbuilder-bulk-export' || empty($pIds)) {
                return $redirect_to;
            }

            $type = get_post_type($pIds[0]);
            $data = array('import' => ($type === self::LAYOUT_PART_SLUG ? 'Layout Parts' : 'Layouts'), 'content' => array());
            $usedGS = array();
            foreach ($pIds as $pId) {

                    $data['content'][] = array(
                    'title' => get_the_title($pId),
                    'settings' =>ThemifyBuilder_Data_Manager::get_data($pId,true)
                );
                // Check for attached GS
                $usedGS+=Themify_Global_Styles::used_global_styles($pId);
            }

            if (class_exists('ZipArchive',false)) {
                $datafile = 'export_file.txt';
                Themify_Filesystem::put_contents($datafile, serialize($data));
                $files_to_zip = array($datafile);
                // Export used global styles
                if (!empty($usedGS)) {
                    foreach ($usedGS as $gsID => $gsPost) {
                        unset($usedGS[$gsID]['id']);
                        unset($usedGS[$gsID]['url']);
                        $styling = Themify_Builder_Import_Export::prepare_builder_data($gsPost['data']);
                        $styling = $styling[0];
                        if ($gsPost['type'] === 'row' || $gsPost['type'] === 'subrow') {
                            $styling = $styling['styling'];
                        } elseif ($gsPost['type'] === 'column') {
                            $styling = $styling['cols'][0]['styling'];
                        } else {
                            $styling = $styling['cols'][0]['modules'][0]['mod_settings'];
                        }
                        $usedGS[$gsID]['data'] = $styling;
                    }
                    $gs_data = json_encode($usedGS);
                    $gs_datafile = 'builder_gs_data_export.txt';
                    Themify_Filesystem::put_contents($gs_datafile, $gs_data);
                    $files_to_zip[] = $gs_datafile;
                }
                $file = 'themify_' . $data['import'] . '_export_' . date('Y_m_d') . '.zip';
                $result = self::themify_create_zip($files_to_zip, $file, true);
            }
            if (isset($result) && $result) {
                if (( isset($file) ) && ( Themify_Filesystem::exists($file) )) {
                    ob_start();
                    header('Pragma: public');
                    header('Expires: 0');
                    header('Content-type: application/force-download');
                    header('Content-Disposition: attachment; filename="' . $file . '"');
                    header('Content-Transfer-Encoding: Binary');
                    header('Content-length: ' . filesize($file));
                    header('Connection: close');
                    ob_clean();
                    flush();
                    echo Themify_Filesystem::get_contents($file);
                    Themify_Filesystem::delete($datafile, 'f');
                    Themify_Filesystem::delete($file, 'f');
                    exit();
                }
            } else {
                if (ini_get('zlib.output_compression')) {
                    ini_set('zlib.output_compression', 'Off');
                }
                ob_start();
                header('Content-Type: application/force-download');
                header('Pragma: public');
                header('Expires: 0');
                header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                header('Cache-Control: private', false);
                header('Content-Disposition: attachment; filename="themify_' . $data['import'] . '_export_' . date("Y_m_d") . '.txt"');
                header('Content-Transfer-Encoding: binary');
                ob_clean();
                flush();
                echo serialize($data);
                exit();
            }
        }

        /**
         * Import Layout and Layout Parts.
         *
         * @access public
         */
        public static function row_bulk_import() {
            check_ajax_referer( 'tb_bulk_import' );
            if ( ! current_user_can( 'edit_posts' ) ) {
                die;
            }

            $data = unserialize( stripslashes( $_POST['data'] ), [ 'allowed_classes' => false ] );
            if ( ! empty( $data ) ) {
                self::set_data( $data );
                if ( ! empty( $_POST['gs_data'] ) ) {
                    $gs_data = stripslashes( $_POST['gs_data'] );
                    $gs_data = is_serialized( $gs_data ) ? unserialize( $gs_data, [ 'allowed_classes' => false ] ) : json_decode( $gs_data );
                    if ( $gs_data ) {
                        Themify_Global_Styles::builder_import( $gs_data );
                    }
                }
            }

            wp_send_json_success();
        }

        public static function admin_enqueue( $screen_id ) {
            $post_type = get_current_screen()->post_type;
            if ( $screen_id !== 'edit.php' || ( self::LAYOUT_SLUG !== $post_type && self::LAYOUT_PART_SLUG !== $post_type ) )
                return;

            wp_enqueue_script( 'tb_bulk_import', THEMIFY_BUILDER_URI . '/js/editor/backend/themify-builder-bulk-import.js', array('themify-main-script'), THEMIFY_VERSION, true );
            wp_localize_script( 'tb_bulk_import', 'tbBulkImport', [
                'nonce' => wp_create_nonce( 'tb_bulk_import' ),
                'label' => __( 'Import', 'themify' ),
                'start' => __( 'Importing...', 'themify' ),
                'error' => __( 'Error connecting to server.', 'themify' ),
                'invalid' => __( 'File is invalid or does not contain exported Builder Layouts. Please try again.', 'themify' ),
            ] );
        }

        private static function set_data($data) {
            $error = false;

            if (!isset($data['import']) || !isset($data['content']) || !is_array($data['content'])) {
                $error = __('Incorrect Import File', 'themify');
            } else {

                if ($data['import'] === 'Layouts')
                    $type = self::LAYOUT_SLUG;
                elseif ($data['import'] === 'Layout Parts') {
                    $type = self::LAYOUT_PART_SLUG;
                } else {
                    $error = __('Failed to import. Unknown data.', 'themify');
                }

                if (!$error) {

                    foreach ($data['content'] as $psot) {
                        $new_id = wp_insert_post(array(
                            'post_status' => 'publish',
                            'post_type' => $type,
                            'post_author' => get_current_user_id(),
                            'post_title' => $psot['title'],
                            'post_content' => ''
                        ));
                        if (!empty($psot['settings'])) {
                            ThemifyBuilder_Data_Manager::save_data(json_decode($psot['settings'], true), $new_id);
                        }
                    }
                }
            }

            return $error;
        }

        /**
         * Duplicate Post in Admin Edit page.
         *
         * @access public
         */
        public static function duplicate_action() {
            if (isset($_GET['action']) && 'duplicate_tbuilder' === $_GET['action'] && wp_verify_nonce($_GET['_wpnonce'], 'duplicate_themify_builder')) {
                $postid = (int) $_GET['post'];
                $layout = get_post($postid);
                if (null === $layout) {
                    exit;
                }
                $new_id = Themify_Builder_Duplicate_Page::duplicate($layout);
                delete_post_meta($new_id, '_themify_builder_prebuilt_layout');
                wp_redirect(admin_url('edit.php?post_type=' . get_post_type($postid)));
                exit;
            }
        }

        /**
         * Add Builder support to Layout and Layout Part post types.
         *
         * @access public
         * @since 2.4.8
         */
        public static function add_builder_support(array $post_types):array {
            $post_types[self::LAYOUT_SLUG] = self::LAYOUT_SLUG;
            $post_types[self::LAYOUT_PART_SLUG] = self::LAYOUT_PART_SLUG;
            return $post_types;
        }
        

        public static  function themify_create_zip($files = array(), $destination = "", $overwrite = false) {//deprecated use js for ceating zip
            if (!class_exists('ZipArchive',false) || (!$overwrite && is_file($destination))) {
                return false;
            }
            $valid_files = array();
            if (is_array($files)) {
                foreach ($files as $file) {
                    if (is_file($file)) {
                        $valid_files[] = $file;
                    }
                }
            }
            if (!empty($valid_files)) {
                $zip = new ZipArchive();
                $zip_opened = $overwrite ? $zip->open($destination, ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE) : $zip->open($destination, ZIPARCHIVE::CREATE);
                if ($zip_opened !== true) {
                    return false;
                }
                foreach ($valid_files as $file) {
                    $zip->addFile($file, pathinfo($file, PATHINFO_BASENAME));
                }
                $zip->close();
                return is_file($destination);
            } else {
                return false;
            }
        }
    }
}

if (!class_exists('Themify_Builder_Layouts_Provider',false)) {//05/20/22 deprecated will be removed in the future
    /**
     * Base class for Builder layout provider
     *
     * Different types of layouts that can be imported in Builder must each extend this base class
     *
     * @since 2.0.0
     */

    class Themify_Builder_Layouts_Provider {

        /**
         * Get the ID of provider
         *
         * @return string
         */
        public function get_id() {
            
        }

        /**
         * Get the label of provider
         *
         * @return string
         */
        public function get_label() {
            
        }

        /**
         * Get a list of available layouts provided by this class
         *
         * @return array
         */
        public function get_layouts() {
            return array();
        }

        /**
         * Check if the layout provider has any layouts available
         *
         * @return bool
         */
        public function has_layouts() {
            return false;
        }
    }

}
