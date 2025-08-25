<?php

/**
 * Gutenberg compatibility for Themify Builder
 *
 * @package Themify
 * @since 3.5.4
 */
if (!class_exists('Themify_Builder_Gutenberg',false)) :

    final class Themify_Builder_Gutenberg {

        private const BLOCK_PATTERNS = '<!-- wp:themify-builder/canvas /-->';

        public static function init():void {
            add_action('wp_loaded', array(__CLASS__, 'load'));
            add_theme_support('align-wide');
        }

        public static function load():void {
            /* add template for all post types that support the editor */
            $post_types = Themify_Builder::builder_post_types_support();
            foreach ($post_types as $type) {
                add_filter('rest_prepare_' . $type, array(__CLASS__, 'enable_block_existing_content'), 10, 3);
                $post_type_object = get_post_type_object($type);
                $post_type_object->template = array(
                    array('themify-builder/canvas')
                );
            }

            add_action('rest_api_init', array(__CLASS__, 'register_builder_content_field'));
            if (is_admin()) {
                add_action('current_screen', array(__CLASS__, 'admin_init'));
            } else {
                self::register_block();
            }
        }

        public static function admin_init():void {
            if (Themify_Builder_Model::is_gutenberg_editor() && function_exists('register_block_type')) {
                self::register_block();
                add_action('enqueue_block_editor_assets', array(__CLASS__, 'enqueue_editor_scripts'));
                add_filter('admin_body_class', array(__CLASS__, 'admin_body_class'));
            }
        }

        private static function register_block():void {
            wp_register_style(
                'builder-styles',
                THEMIFY_BUILDER_URI . '/css/themify-builder-style.css',
                null,
                THEMIFY_VERSION
            );

            register_block_type('themify-builder/canvas', array(
                'render_callback' => array(__CLASS__, 'render_builder_block'),
                'editor_style' => 'themify-builder-style',
                'style' => 'themify-builder-style'
            ));
        }

        public static function enqueue_editor_scripts():void {
            add_filter('themify_defer_js_exclude', array(__CLASS__, 'exclude_defer_script'));
            themify_enque_script('themify-builder-gutenberg-block', THEMIFY_BUILDER_URI . '/js/editor/backend/themify-builder-gutenberg.js', THEMIFY_VERSION, array('wp-blocks', 'wp-i18n', 'wp-element', 'backbone'));
        }

        public static function render_builder_block($attributes):string {
            return self::BLOCK_PATTERNS; // just return custom block tag, let builder the_content filter to render builder output
        }

        public static function exclude_defer_script(array $handles):array {
            return array_merge($handles, array('themify-builder-gutenberg-block'));
        }

        /**
         * Enable builder block on existing content data.
         * 
         * @param object $data 
         * @param object $post 
         * @param object $context 
         * @return object
         */
        public static function enable_block_existing_content($data, $post, $context) {
            if ('edit' === $context['context'] && isset($data->data['content']['raw'])) {
                $content = &$data->data['content']['raw'];

                $builder_placeholder = '<!-- wp:themify-builder/canvas --><!-- /wp:themify-builder/canvas -->';
                if ( Themify_Builder_Model::is_gutenberg_active() && self::has_builder_block( $content ) ) {
                    $content = ThemifyBuilder_Data_Manager::update_static_content_string( '', $content ); // remove static content tag
                    /* convert old Gutenberg placeholder */
                    if ( str_contains( $content, self::BLOCK_PATTERNS ) ) {
                        $content = str_replace( self::BLOCK_PATTERNS, $builder_placeholder, $content );
                    }
                } elseif ( ThemifyBuilder_Data_Manager::has_static_content( $content ) ) {
                    $content = ThemifyBuilder_Data_Manager::update_static_content_string( $builder_placeholder, $content );
                } else {
                    $content .= "\n\n" . $builder_placeholder;
                }

                $builder_data = ThemifyBuilder_Data_Manager::get_data( $post->ID );
                if ( ! empty( $builder_data ) ) {
                    $plain_text = ThemifyBuilder_Data_Manager::_get_all_builder_text_content( $builder_data );
                    $plain_text = ThemifyBuilder_Data_Manager::add_static_content_wrapper( $plain_text );
                    $content = preg_replace_callback( '/(<!-- wp:themify-builder\/canvas -->)[\s\S]*?(<!-- \/wp:themify-builder\/canvas -->)/', function( $matches ) use( $plain_text ) {
                        return $matches[1] . $plain_text . $matches[2];
                    }, $content );
                }
            }

            return $data;
        }

        /**
         * Added body class
         */
        public static function admin_body_class(?string $classes):string {
            $classes .= ' themify-gutenberg-editor';
            $screen=get_current_screen();
            if (!empty($screen) && Themify_Builder_Model::is_builder_disabled_for_post_type( $screen->post_type ) ) {
                $classes .= ' tb_editor_disabled';
            }
            return $classes;
        }

        /**
         * Register builder content meta
         * 
         * @access public
         * @return type
         */
        public static function register_builder_content_field():void {
            $post_types = Themify_Builder::builder_post_types_support();
            foreach ($post_types as $type) {
                register_rest_field($type, 'builder_content', array(
                    'get_callback' => array(__CLASS__, 'get_post_meta_builder'),
                    'schema' => null
                    )
                );
            }
        }

        /**
         * Update builder block tag in the string.
         */
        public static function replace_builder_block_tag(string $replace_string, string $content): string {
            if (self::has_builder_block($content)) {
                $content = str_replace(self::BLOCK_PATTERNS, $replace_string, $content);
                $content = ThemifyBuilder_Data_Manager::remove_empty_p($content);
            }
            return $content;
        }

        /**
         * Check if content has builder block
         */
        public static function has_builder_block(string $content):string {
            return has_block( 'themify-builder/canvas', $content );
        }

        /**
         * Get builder content value.
         * 
         * @access public
         * @param type $object 
         */
        public static function get_post_meta_builder($object):string {
            return ThemifyBuilder_Data_Manager::_get_all_builder_text_content(ThemifyBuilder_Data_Manager::get_data($object['id']));
        }
    }

    Themify_Builder_Gutenberg::init();
endif;
