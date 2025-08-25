<?php

class Themify_Hooks {

    /**
     * Multi-dimensional array of hooks in a theme
     */
    private static $hook_locations=array();

    /**
     * list of hooks, visible to the current page context
     */
    private static $action_map;
    private CONST PRE = 'setting-hooks';
    private static $data;

    /**
     * Count the number of occurances of a hook
     * @type array
     */
    private static $counter;

    public static function init() {
        if ( is_admin() ) {
            if ( current_user_can( 'manage_options' ) ) {
                add_filter( 'themify_theme_config_setup', array( __CLASS__, 'config_setup' ), 12 );
                add_action( 'wp_ajax_themify_hooks_add_item', array( __CLASS__, 'ajax_add_button' ));
                add_action( 'wp_ajax_themify_get_visibility_options', array( __CLASS__, 'ajax_get_visibility_options' ) );
                add_action( 'wp_ajax_themify_create_inner_page', array( __CLASS__, 'ajax_create_inner_page' ) );
                add_action( 'wp_ajax_themify_hc_conditions', array( __CLASS__, 'ajax_themify_hc_conditions' ) );
            }
        } else {
            add_action( 'template_redirect', array( __CLASS__, 'hooks_setup' ) );
            add_filter( 'themify_hooks_item_content', array(__CLASS__,'themify_do_shortcode_wp') );
            if ( current_user_can( 'manage_options' ) ) {
                add_action( 'template_redirect', array( __CLASS__, 'hook_locations_view_setup' ), 9 );
            }
        }
        add_action( 'init', array( __CLASS__, 'register_default_hook_locations' ) );
    }

    public static function hooks_setup() {
        self::$data = themify_get_data();
        $pre=self::PRE;
        if ( isset( self::$data["{$pre}_field_ids"] ) ) {
            $ids = json_decode( self::$data["{$pre}_field_ids"] );
            if ( ! empty( $ids ) ) {
                foreach ( $ids as $id ) {
                    if ( self::check_visibility( $id ) ) {
                        $location = self::$data["{$pre}-{$id}-location"];
                        /* cache the ID of the item we have to display, so we don't have to re-run the conditional tags */
                        self::$action_map[$location][] = $id;
                        add_action( $location, array( __CLASS__, 'output_item' ) );
                    }
                }

                if ( is_singular() && ( has_filter( 'tf_after_nth_p' ) || has_filter( 'tf_after_every_nth_p' ) ) ) {
                    add_filter( 'the_content', [ __CLASS__, 'between_content_filter' ] );
                }
            }
        }
    }

    /**
     * Check if an item is visible for the current context
     *
     * @param int $id
     *
     * @return bool
     */
    private static function check_visibility( $id ) {
        $pre=self::PRE;
        if ( ! isset( self::$data["{$pre}-{$id}-visibility"] ) ){
            return true;
        }
        $logic = self::$data["{$pre}-{$id}-visibility"];
        parse_str( $logic, $logic );
        $query_object = get_queried_object();

        // Logged-in check
        if ( isset( $logic['general']['logged'] ) ) {
            if( ! is_user_logged_in() ) {
                return false;
            }
            unset( $logic['general']['logged'] );
            if( empty( $logic['general'] ) ) {
                unset( $logic['general'] );
            }
        }

        // User role check
        if ( ! empty( $logic['roles'] )
            // check if *any* of user's role(s) matches
            && ! count( array_intersect( wp_get_current_user()->roles, array_keys( $logic['roles'], true ) ) )
        ) {
            return false; // bail early.
        }
        unset( $logic['roles'] );

        if ( ! empty( $logic ) ) {
            $post_type = get_post_type();
            if (
                ( isset($logic['general']['home']) && is_front_page())
                || ( isset($logic['general']['blog']) && is_home())
                || ( isset($logic['general']['404']) && ( is_404() || Themify_Custom_404::is_custom_404() ) )
                || ( isset( $logic['general']['page'] ) &&  is_page() && ! is_front_page() && ! Themify_Custom_404::is_custom_404() )
                || ( $post_type === 'post' && isset($logic['general']['single']) && is_single())
                || ( isset($logic['general']['search']) && is_search() )
                || ( isset($logic['general']['author']) && is_author())
                || ( isset($logic['general']['category']) && is_category() )
                || ( isset($logic['general']['tag'])  && is_tag())
                || ( isset($logic['general']['date']) && is_date() )
                || ( isset($logic['general']['year']) && is_year())
                || ( isset($logic['general']['month']) && is_month())
                || ( isset($logic['general']['day']) && is_day())
                || (isset($query_object) && (( $post_type !== 'page' && $post_type !== 'post' && isset($logic['general'][$post_type]) && is_singular()  )
                || ( isset( $query_object->name ) && $query_object->name !== 'page' && $query_object->name !== 'post' && isset( $logic['post_type_archive'][$query_object->name] ) && is_post_type_archive()  )
                || ( is_tax() && isset($logic['general'][$query_object->taxonomy]))))
            ) {
                return true;
            } else { // let's dig deeper into more specific visibility rules
                if ( ! empty( $logic['tax'] ) ) {
                    if ( is_singular() ) {
                        if( !empty($logic['tax']['category_single'])){
                            // Backward compatibility
                            reset($logic['tax']['category_single']);
                            $first_key = key($logic['tax']['category_single']);
                            if(!is_array($logic['tax']['category_single'][$first_key])){
                                $logic['tax']['category_single'] = array('category'=> $logic['tax']['category_single']);
                            }
                            if ( empty( $logic['tax']['category_single']['category'] ) ) {
                                $cat = get_the_category();
                                if(!empty($cat)){
                                    foreach($cat as $c){
                                        if($c->taxonomy === 'category' && isset($logic['tax']['category_single']['category'][$c->slug])){
                                            return true;
                                        }
                                    }
                                }
                                unset($logic['tax']['category_single']['category']);
                            }
                            foreach ($logic['tax']['category_single'] as $key => $tax) {
                                $terms = get_the_terms( get_the_ID(), $key);
                                if ( $terms !== false && !is_wp_error($terms) && is_array($terms) ) {
                                    foreach ( $terms as $term ) {
                                        $term->slug = urldecode( $term->slug ); /* for non-ascii term slugs */
                                        if( isset($logic['tax']['category_single'][$key][$term->slug]) ){
                                            return true;
                                        }
                                    }
                                }
                            }
                        }
                    } else {
                        foreach ( $logic['tax'] as $tax => $terms ) {
                            $terms = array_keys( $terms );
                            self::update_non_ascii_slugs( $terms );
                            if ( ( $tax === 'category' && is_category($terms) ) || ( $tax === 'post_tag' && is_tag( $terms ) ) || ( is_tax( $tax, $terms ) )
                            ) {
                                return true;
                            }
                        }
                    }
                }
                if (! empty( $logic['post_type'] ) ) {
                    foreach ( $logic['post_type'] as $post_type => $posts ) {
                        $posts = array_keys( $posts );
                        self::update_non_ascii_slugs( $posts );
                        if (
                            // Post single
                            ( $post_type === 'post' && is_single( $posts ) )
                            // Page view
                            || ( $post_type === 'page' && (
                                    (
                                    ( ( isset( $query_object->post_parent ) && $query_object->post_parent <= 0 && is_page( $posts ) )
                                        // check for pages that have a Parent, the slug for these pages are stored differently.
                                        || ( isset( $query_object->post_parent ) && $query_object->post_parent > 0 &&
                                            ( in_array( '/' . str_replace( strtok( get_home_url(), '?'), '', remove_query_arg( 'lang', get_permalink( $query_object->ID ) ) ), $posts ) ||
                                                in_array( str_replace( strtok( get_home_url(), '?'), '', remove_query_arg( 'lang', get_permalink( $query_object->ID ) ) ), $posts ) ||
                                                in_array( '/'.self::child_post_name($query_object).'/', $posts ) )
                                        )
                                    ) )
                                    || ( ! is_front_page() && is_home() && in_array( get_post_field( 'post_name', get_option( 'page_for_posts' ) ), $posts,true ) ) // check for Posts page
                                    || ( themify_is_shop() && in_array( get_post_field( 'post_name', themify_shop_pageId() ), $posts,true )  ) // check for WC Shop page
                                ) )
                            // Custom Post Types single view check
                            || ( isset( $query_object->post_parent ) && $query_object->post_parent <= 0 && is_singular( $post_type ) && in_array( $query_object->post_name, $posts,true ) )
                            || ( isset( $query_object->post_parent ) && $query_object->post_parent > 0 && is_singular( $post_type ) && in_array( '/'.self::child_post_name($query_object).'/', $posts,true ) )
                            // for all posts of a post type.
                            || ( is_singular( $post_type ) && in_array( 'E_ALL', $posts,true ) )
                        ) {
                            return true;
                        }
                    }
                }
                if(themify_is_shop() && ( $shop_page_slug = get_post_field( 'post_name', themify_shop_pageId() ) ) && isset( $logic['post_type']['page'][ $shop_page_slug ] ) ) {
                    return true;
                }
                if ( isset( $logic['wc'] ) && themify_is_woocommerce_active()) {
                    foreach( array_keys( $logic['wc'] ) as $endpoint ) {
                        if ( is_wc_endpoint_url( $endpoint ) ) {
                            return true;
                        }
                    }
                }
            }
        }

        return false;
    }

    private static function update_non_ascii_slugs(array &$array ) {
        if(function_exists('mb_check_encoding')){
            foreach ( $array as &$value ) {
                if ( isset($value) && ! mb_check_encoding( $value, 'ASCII' ) ) {
                    /* revert "/" character, needed to check for nested posts */
                    $value= str_replace( '%2f', '/', strtolower( urlencode( $value ) ));
                }
            }
        }
    }

    public static function output_item() {
        $hook = current_filter();
        $pre = self::PRE;
        foreach ( self::$action_map[ $hook ] as $id ) {

            if ( ! empty( self::$data["{$pre}-{$id}-r"] ) ) {
                if ( ! isset( self::$counter[ $id ] ) ) {
                    self::$counter[ $id ] = 0;
                }
                self::$counter[ $id ]++;
                if ( self::$counter[ $id ] % (int) self::$data["{$pre}-{$id}-r"] !== 0 ) {
                    /* skip showing the content of the hook */
                    continue;
                }
            }

            /* do_shortcode is applied via the themify_hooks_item_content filter */
            if ( ! empty( self::$data["{$pre}-{$id}-code"] ) ) {
                echo apply_filters( 'themify_hooks_item_content', '<!-- hook content: ' . $hook . ' -->' . self::$data["{$pre}-{$id}-code"] . '<!-- /hook content: ' . $hook . ' -->', __CLASS__ );
            }
        }
    }

    public static function between_content_filter( $content ) {
        if ( get_the_ID() !== get_queried_object_id() ) {
            return $content;
        }

        $pre = self::PRE;
        $content_block = explode( '<p>', $content );

        if ( isset( self::$action_map['tf_after_nth_p'] ) ) {
            foreach ( self::$action_map['tf_after_nth_p'] as $id ) {
                $n = ! empty( self::$data["{$pre}-{$id}-r"] ) ? (int) self::$data["{$pre}-{$id}-r"] : 1;
                if ( ! empty( $content_block[ $n ] ) ) {
                    $content_block[ $n ] .= self::$data["{$pre}-{$id}-code"];
                }
            }
        }
        $count=count( $content_block );

        if ( isset( self::$action_map['tf_after_every_nth_p'] ) ) {
            foreach ( self::$action_map['tf_after_every_nth_p'] as $id ) {
                $n = ! empty( self::$data["{$pre}-{$id}-r"] ) ? (int) self::$data["{$pre}-{$id}-r"] : 1;
                for ( $i = 1; $i < $count; $i++ ) {
                    if ( $i % $n === 0 ) {
                        $content_block[ $i ] .= self::$data["{$pre}-{$id}-code"];
                    }
                }
            }
        }

        /* stich the content back together */
        for ( $i = 1; $i < $count; $i++ ) {
            $content_block[ $i ] = '<p>' . $content_block[ $i ];
        }

        return implode( '', $content_block );
    }

    /**
     * Returns a list of available hooks for the current theme.
     *
     * @return mixed
     */
    public static function get_locations() {
        return self::$hook_locations;
    }

    public static function register_location( $id, $label, $group = 'layout' ) {
        self::$hook_locations[$group][$id] = $label;
    }

    public static function unregister_location($id) {
        foreach ( self::$hook_locations as $group => $hooks ) {
            unset( self::$hook_locations[$group][$id] );
        }
    }

    private static function get_location_groups() {
        return array(
            'layout' => __( 'Layout', 'themify' ),
            'general' => __( 'General', 'themify' ),
            'post' => __( 'Post', 'themify' ),
            'post_module' => __( 'Builder Post Module', 'themify' ),
            'comments' => __( 'Comments', 'themify' ),
            'ecommerce' => __( 'WooCommerce Pages', 'themify' ),
            'wc_loop' => __( 'WooCommerce Products', 'themify' ),
            'ptb' => __( 'Post Type Builder', 'themify' ),
            'post_content' => __( 'In Between Post Content', 'themify' ),
        );
    }

    public static function register_default_hook_locations() {
        foreach ( array(
            array( 'wp_head', 'wp_head', 'general' ),
            array( 'wp_footer', 'wp_footer', 'general' ),
            array( 'themify_body_start', 'body_start', 'layout' ),
            array( 'themify_header_before', 'header_before', 'layout' ),
            array( 'themify_header_start', 'header_start', 'layout' ),
            array( 'themify_header_end', 'header_end', 'layout' ),
            array( 'themify_header_after', 'header_after', 'layout' ),
            array( 'themify_mobile_menu_start', 'mobile_menu_start', 'layout' ),
            array( 'themify_mobile_menu_end', 'mobile_menu_end', 'layout' ),
            array( 'themify_layout_before', 'layout_before', 'layout' ),
            array( 'themify_content_before', 'content_before', 'layout' ),
            array( 'themify_content_start', 'content_start', 'layout' ),
            array( 'themify_post_before', 'post_before', 'post' ),
            array( 'themify_post_start', 'post_start', 'post' ),
            array( 'themify_before_post_image', 'before_post_image', 'post' ),
            array( 'themify_after_post_image', 'after_post_image', 'post' ),
            array( 'themify_before_post_title', 'before_post_title', 'post' ),
            array( 'themify_after_post_title', 'after_post_title', 'post' ),
            array( 'themify_post_end', 'post_end', 'post' ),
            array( 'themify_post_after', 'post_after', 'post' ),
            array( 'themify_post_before_module', 'post_before', 'post_module' ),
            array( 'themify_post_start_module', 'post_start', 'post_module' ),
            array( 'themify_before_post_image_module', 'before_post_image', 'post_module' ),
            array( 'themify_after_post_image_module', 'after_post_image', 'post_module' ),
            array( 'themify_before_post_title_module', 'before_post_title', 'post_module' ),
            array( 'themify_after_post_title_module', 'after_post_title', 'post_module' ),
            array( 'themify_post_end_module', 'post_end', 'post_module' ),
            array( 'themify_post_after_module', 'post_after', 'post_module' ),
            array( 'themify_comment_before', 'comment_before', 'comments' ),
            array( 'themify_comment_start', 'comment_start', 'comments' ),
            array( 'themify_comment_end', 'comment_end', 'comments' ),
            array( 'themify_comment_after', 'comment_after', 'comments' ),
            array( 'themify_content_end', 'content_end', 'layout' ),
            array( 'themify_content_after', 'content_after', 'layout' ),
            array( 'themify_sidebar_before', 'sidebar_before', 'layout' ),
            array( 'themify_sidebar_start', 'sidebar_start', 'layout' ),
            array( 'themify_sidebar_end', 'sidebar_end', 'layout' ),
            array( 'themify_sidebar_after', 'sidebar_after', 'layout' ),
            array( 'themify_layout_after', 'layout_after', 'layout' ),
            array( 'themify_footer_before', 'footer_before', 'layout' ),
            array( 'themify_footer_start', 'footer_start', 'layout' ),
            array( 'themify_footer_end', 'footer_end', 'layout' ),
            array( 'themify_footer_after', 'footer_after', 'layout' ),
            array( 'themify_body_end', 'body_end', 'layout' ),
        ) as $key => $value ) {
            self::register_location( $value[0], $value[1], $value[2] );
        }

        /* register ecommerce hooks group only if current theme supports WooCommerce */
        if ( themify_is_woocommerce_active() ) {
            foreach ( array(
                array( 'woocommerce_before_single_product', 'before_single_product', 'ecommerce' ),
                array( 'themify_product_image_start', 'product_image_start', 'wc_loop' ),
                array( 'themify_product_image_end', 'product_image_end', 'wc_loop' ),
                array( 'themify_product_title_start', 'product_title_start', 'wc_loop' ),
                array( 'themify_product_title_end', 'product_title_end', 'wc_loop' ),
                array( 'themify_product_price_start', 'product_price_start', 'wc_loop' ),
                array( 'themify_product_price_end', 'product_price_end', 'wc_loop' ),
                array( 'woocommerce_before_add_to_cart_form', 'before_add_to_cart_form', 'wc_loop' ),
                array( 'woocommerce_after_add_to_cart_form', 'after_add_to_cart_form', 'wc_loop' ),
                array( 'woocommerce_before_variations_form', 'before_variations_form', 'wc_loop' ),
                array( 'woocommerce_after_variations_form', 'after_variations_form', 'wc_loop' ),
                array( 'woocommerce_before_add_to_cart_button', 'before_add_to_cart_button', 'wc_loop' ),
                array( 'woocommerce_after_add_to_cart_button', 'after_add_to_cart_button', 'wc_loop' ),
                array( 'woocommerce_product_meta_start', 'product_meta_start', 'wc_loop' ),
                array( 'woocommerce_product_meta_end', 'product_meta_end', 'wc_loop' ),
                array( 'themify_before_product_tabs', 'before_product_tabs', 'ecommerce' ),
                array( 'themify_after_product_tabs', 'after_product_tabs', 'ecommerce' ),
                array( 'woocommerce_after_single_product', 'after_single_product', 'ecommerce' ),
                array( 'themify_checkout_start', 'checkout_start', 'ecommerce' ),
                array( 'themify_checkout_end', 'checkout_end', 'ecommerce' ),
                array( 'themify_ecommerce_sidebar_before', 'woocommerce_sidebar_before', 'ecommerce' ),
                array( 'themify_ecommerce_sidebar_after', 'woocommerce_sidebar_after', 'ecommerce' ),
            ) as $key => $value ) {
                self::register_location( $value[0], $value[1], $value[2] );
            }
        }

        /* register hook locations for PTB plugin */
        if ( class_exists( 'PTB',false ) ) {
            foreach ( array(
                array( 'ptb_before_author', 'before_author', 'ptb' ),
                array( 'ptb_after_author', 'after_author', 'ptb' ),
                array( 'ptb_before_category', 'before_category', 'ptb' ),
                array( 'ptb_after_category', 'after_category', 'ptb' ),
                array( 'ptb_before_comment_count', 'before_comment_count', 'ptb' ),
                array( 'ptb_after_comment_count', 'after_comment_count', 'ptb' ),
                array( 'ptb_before_comments', 'before_comments', 'ptb' ),
                array( 'ptb_after_comments', 'after_comments', 'ptb' ),
                array( 'ptb_before_custom_image', 'before_custom_image', 'ptb' ),
                array( 'ptb_after_custom_image', 'after_custom_image', 'ptb' ),
                array( 'ptb_before_custom_text', 'before_custom_text', 'ptb' ),
                array( 'ptb_after_custom_text', 'after_custom_text', 'ptb' ),
                array( 'ptb_before_date', 'before_date', 'ptb' ),
                array( 'ptb_after_date', 'after_date', 'ptb' ),
                array( 'ptb_before_editor', 'before_content', 'ptb' ),
                array( 'ptb_after_editor', 'after_content', 'ptb' ),
                array( 'ptb_before_excerpt', 'before_excerpt', 'ptb' ),
                array( 'ptb_after_excerpt', 'after_excerpt', 'ptb' ),
                array( 'ptb_before_permalink', 'before_permalink', 'ptb' ),
                array( 'ptb_after_permalink', 'after_permalink', 'ptb' ),
                array( 'ptb_before_post_tag', 'before_post_tag', 'ptb' ),
                array( 'ptb_after_post_tag', 'after_post_tag', 'ptb' ),
                array( 'ptb_before_taxonomies', 'before_taxonomies', 'ptb' ),
                array( 'ptb_after_taxonomies', 'after_taxonomies', 'ptb' ),
                array( 'ptb_before_thumbnail', 'before_thumbnail', 'ptb' ),
                array( 'ptb_after_thumbnail', 'after_thumbnail', 'ptb' ),
                array( 'ptb_before_title', 'before_title', 'ptb' ),
                array( 'ptb_after_title', 'after_title', 'ptb' ),
            ) as $key => $value ) {
                self::register_location( $value[0], $value[1], $value[2] );
            }
        }

        self::register_location( 'tf_after_nth_p', __( 'After nth paragraph', 'themify' ), 'post_content' );
        self::register_location( 'tf_after_every_nth_p', __( 'After every nth paragraph', 'themify' ), 'post_content' );
    }

    public static function config_setup($themify_theme_config) {
        $themify_theme_config['panel']['settings']['tab']['hook-content'] = array(
            'title' => __( 'Hook Content', 'themify' ),
            'id' => 'hooks',
            'custom-module' => array(
                array(
                    'title' => __( 'Hook Content', 'themify' ),
                    'function' => array( __CLASS__, 'config_view' ),
                ),
            )
        );

        return $themify_theme_config;
    }

    public static function config_view($data = array()) {
        $data = themify_get_data();
        $pre=self::PRE;
        $field_ids_json = isset( $data["{$pre}_field_ids"] ) ? $data["{$pre}_field_ids"] : '';
                unset($data);
        $field_ids = json_decode( $field_ids_json );
        if ( ! is_array( $field_ids ) ) {
            $field_ids = array();
        }
        $field_ids = array_values( array_filter( $field_ids ) );

        $out = '<div class="themify-info-link">' . sprintf( __( 'Use <a href="%s" target="_blank">Hook Content</a> to add content to the theme without editing any template file.', 'themify' ), 'https://themify.me/docs/hook-content' ) . '</div>';

        $out .= '<ul id="hook-content-list">';
        if ( ! empty( $field_ids ) ){ 
            foreach ( $field_ids as $value ){
                $out .= self::item_template( $value );
            }
        }
        $out .= '</ul>';
        $out .= '<p class="add-link themify-add-hook alignleft"><a href="#">' . __( 'Add item', 'themify' ) . '</a></p>';
        $out .= '<input type="hidden" id="themify-hooks-field-ids" name="' .  $pre . '_field_ids" value=\'' . json_encode( $field_ids ) . '\' />';
        return $out;
    }

    public static function ajax_add_button() {
        check_ajax_referer( 'tf_nonce', 'nonce' );
        if (isset( $_POST['field_id'] ) && current_user_can( 'manage_options' )) {
            echo self::item_template( $_POST['field_id'], true );
        }
        die;
    }

    private static function item_template( $id, $new = false ) {
        $pre=self::PRE;
        $output = '<li class="social-link-item" data-id="' . $id . '">';
        $output .= '<div class="social-drag">' . esc_html__( 'Drag to Sort', 'themify' ) . '<i class="ti-arrows-vertical"></i></div>';
        $output .= '<div class="tf_hide notice notice-error wp_head_notice"><p>' . esc_html__( 'Only use wp_header to insert HTML metadata (scripts, styles, or meta tags) in the HTML <head> area. To add content in the top/header area, please select body_start.', 'themify' ) . '</p></div>';
        $output .= '<div class="row"><select class="tf_hook_location" name="' .  $pre . '-' . $id . '-location" class="width7">';
        $locations = self::get_locations();
        $current=themify_get( "{$pre}-{$id}-location",false,true );
        foreach ( self::get_location_groups() as $group => $label ) {
            if ( ! empty( $locations[$group] ) ) {
                $output .= '<optgroup data-key="' . $group . '" label="' . esc_attr( $label ) . '">';
                foreach ( $locations[$group] as $key => $value ) {
                    $output .= '<option value="' . $key . '" ' . selected( $current, $key, false ) . '>' . esc_html( $value ) . '</option>';
                }
                $output .= '</optgroup>';
            }
        }
        $output .= '</select>';
        //Backward compatibility
        $selected = array();
        $value = $new ? '' : themify_get( $pre . '-' . $id . '-visibility',false,true );
        parse_str( $value, $selected );
        if(!empty($selected['tax']) && !empty($selected['tax']['category_single'])){
            reset($selected['tax']['category_single']);
            $first_key = key($selected['tax']['category_single']);
            if(!is_array($selected['tax']['category_single'][$first_key])){
                $values = explode('&',$value);
                foreach ($values as $k=>$i){
                    if(0 === strpos($i,'tax%5Bcategory_single%5D')){
                        unset($values[$k]);
                    }
                }
                foreach ($selected['tax']['category_single'] as $k=>$v){
                    $values[] = urlencode("tax[category_single][category][$k]").'=on';
                }
                $value = implode('&',$values);
            }
        }

        $output .= '<a class="button button-secondary see-hook-locations themify_link_btn" href="' . add_query_arg(array( 'tp' => 1), home_url()) . '">' . __( 'Select Hook Locations', 'themify' ) . '</a>';

        $output .= '<div class="row"><textarea class="widthfull" name="' . $pre . '-' . $id . '-code" rows="6" cols="73">' . esc_html( themify_get( "{$pre}-{$id}-code",false,true ) ) . '</textarea></div>';
        $output .= '<div class="row tf_hook_repeat"><p>
            <span> ' . __( 'Show after # calls of the same hook in the same page', 'themify' ) . '</span>
            <span> ' . __( 'Show after # Paragraph', 'themify' ) . '</span>' .
            ': <input type="number" min="1" step="1" name="' . $pre . '-' . $id . '-r" value="' . themify_get( "{$pre}-{$id}-r", false, true ) . '" class="width4" /></p></div>';
        $output .= '<a href="#" class="remove-item"><span class="tf_close"></span></a>';

        $output .= '<a class="themify-visibility-toggle" href="#" data-target="#' . $pre . '-' . $id . '-visibility" data-item="' . $id . '" data-text="' . __( '+ Display Conditions', 'themify' ) . '">' . __( 'Edit/Add Conditions', 'themify' ) . '</a> <input type="hidden" id="' . $pre . '-' . $id . '-visibility" name="' . $pre . '-' . $id . '-visibility" value="' . esc_attr( $value ) . '" /></div>';
        $output .= self::parse_conditions( $selected );

        $output .= '</li>';

        return $output;
    }

    public static function ajax_create_inner_page() {
        check_ajax_referer( 'tf_nonce', 'nonce' );
        if ( empty( $_POST['type'] ) ) {
            die;
        }

        $type = explode( ':', $_POST['type'] );
        $paged = isset( $_POST['paged'] ) ? (int) $_POST['paged'] : 1;
        echo self::create_inner_page( $type[0], $type[1], $paged );
        die;
    }

    /**
     * Renders pages, posts types and categories items based on current page.
     *
     * @param string $type The type of items to render.
     * @param array $selected The array of all selected options.
     *
     * @return string The HTML to render items as HTML.
     */
    private static function create_inner_page( $item_type, $type, $paged = 1 ) {
        $posts_per_page = 26;
        $output = '';
        if ( 'post_type' === $item_type ) {
            $query = new WP_Query( array( 'post_type' => $type, 'posts_per_page' => $posts_per_page, 'post_status' => 'publish', 'order' => 'ASC', 'orderby' => 'title', 'paged' => $paged ) );
            if ( $query->have_posts() ) {
                $num_of_single_pages = $query->found_posts;
                $num_of_pages        = (int) ceil( $num_of_single_pages / $posts_per_page );
                $output .= '<div class="themify-visibility-items-page themify-visibility-items-page-' . $paged . '">';
                foreach ( $query->posts as $post ) :
                    $post->post_name = self::child_post_name($post);
                    if ( $post->post_parent > 0 ) {
                        $post->post_name = '/' . $post->post_name . '/';
                    }
                    /* note: slugs are more reliable than IDs, they stay unique after export/import */
                    $output .= '<label><input type="checkbox" name="' . esc_attr( 'post_type[' . $type . '][' . $post->post_name . ']' ) . '" /><span data-tf_tooltip="'.get_permalink($post->ID).'">' . esc_html( $post->post_title ) . '</span></label>';
                endforeach;

                if ( $num_of_pages > 1 ) {
                    $output .= '<div class="themify-visibility-pagination">';
                    $output .= self::create_page_pagination( $paged, $num_of_pages );
                    $output .= '</div>';
                }
                $output .= '</div><!-- .themify-visibility-items-page -->';
            }
        } else if ( 'tax' === $item_type || 'in_tax' === $item_type ) {
            $total = wp_count_terms( [ 'taxonomy' => $type, 'hide_empty' => false ] );
            if ( ! is_wp_error( $total ) && ! empty( $total ) ) {
                $prefix = 'tax' === $item_type ? "tax[{$type}]" : "tax[category_single][{$type}]";
                $terms = get_terms( array( 'taxonomy' => $type, 'hide_empty' => false, 'number' => $posts_per_page, 'offset' => ( $paged - 1 ) * $posts_per_page ) );
                $num_of_pages = (int) ceil( $total / $posts_per_page );
                $output .= '<div class="themify-visibility-items-page themify-visibility-items-page-' . $paged . '">';
                foreach ( $terms as $term ) :
                    $data = ' data-slug="'.$term->slug.'"';
                    if ( $term->parent != '0' ) {
                        $parent  = get_term( $term->parent, $type );
                        $data .= ' data-parent="'.$parent->slug.'"';
                    }
                    $output  .= '<label><input'.$data.' type="checkbox" name="' . $prefix . '[' . $term->slug . ']" /><span data-tf_tooltip="'.get_term_link($term).'">' . $term->name . '</span></label>';
                endforeach;
                if ( $num_of_pages > 1 ) {
                    $output .= '<div class="themify-visibility-pagination">';
                    $output .= self::create_page_pagination( $paged, $num_of_pages );
                    $output .= '</div>';
                }
                $output .= '</div><!-- .themify-visibility-items-page -->';
            }
        }

        return $output;
    }

    /**
     * Render pagination for specific page.
     *
     * @param Integer $current_page The current page that needs to be rendered.
     * @param Integer $num_of_pages The number of all pages.
     *
     * @return String The HTML with pagination.
     */
    private static function create_page_pagination( $current_page, $num_of_pages ) {
        $links_in_the_middle = 4;
        $links_in_the_middle_min_1 = $links_in_the_middle - 1;
        $first_link_in_the_middle   = $current_page - floor( $links_in_the_middle_min_1 / 2 );
        $last_link_in_the_middle    = $current_page + ceil( $links_in_the_middle_min_1 / 2 );
        if ( $first_link_in_the_middle <= 0 ) {
            $first_link_in_the_middle = 1;
        }
        if ( ( $last_link_in_the_middle - $first_link_in_the_middle ) != $links_in_the_middle_min_1 ) {
            $last_link_in_the_middle = $first_link_in_the_middle + $links_in_the_middle_min_1;
        }
        if ( $last_link_in_the_middle > $num_of_pages ) {
            $first_link_in_the_middle = $num_of_pages - $links_in_the_middle_min_1;
            $last_link_in_the_middle  = (int) $num_of_pages;
        }
        if ( $first_link_in_the_middle <= 0 ) {
            $first_link_in_the_middle = 1;
        }
        $pagination = '';
        if ( $current_page !== 1 ) {
            $pagination .= '<a href="' . ( $current_page - 1 ) . '" class="prev page-numbers ti-angle-left"/>';
        }
        if ( $first_link_in_the_middle >= 3 && $links_in_the_middle < $num_of_pages ) {
            $pagination .= '<a href="1" class="page-numbers">1</a><span class="page-numbers extend">...</span>';
        }
        for ( $i = $first_link_in_the_middle; $i <= $last_link_in_the_middle; ++$i ) {
            if ( $i === $current_page ) {
                $pagination .= '<span class="page-numbers current">' . $i . '</span>';
            } else {
                $pagination .= '<a href="' . $i . '" class="page-numbers">' . $i . '</a>';
            }
        }
        if ( $last_link_in_the_middle < $num_of_pages ) {
            if ( $last_link_in_the_middle != ( $num_of_pages - 1 ) ) {
                $pagination .= '<span class="page-numbers extend">...</span>';
            }
            $pagination .= '<a href="' . $num_of_pages . '" class="page-numbers">' . $num_of_pages . '</a>';
        }
        if ( $current_page != $last_link_in_the_middle ) {
            $pagination .= '<a href="' . ( $current_page + $i ) . '" class="next page-numbers ti-angle-right"></a>';
        }

        return $pagination;
    }

    public static function ajax_get_visibility_options() {
        check_ajax_referer( 'tf_nonce', 'nonce' );
        if( ! current_user_can( 'manage_options' ) ) {
            die;
        }

        include THEMIFY_DIR . '/includes/conditions.php';
        die;
    }

    /**
     * Handles Ajax request to update the preview list of conditions
     *
     * @since 7.2.5
     */
    public static function ajax_themify_hc_conditions() {
        check_ajax_referer( 'tf_nonce', 'nonce' );
        parse_str( $_POST['selected'], $selected );
        echo self::parse_conditions( $selected );
        die;
    }

    private static function parse_conditions( $selected ) {
        $output = '';
        foreach ( $selected as $key => $value ) {
            switch ( $key ) {
                case 'general' :
                    if ( isset( $value['home'] ) ) {
                        $output .= '<li data-id="general[home]">' . __( 'Home Page', 'themify' ) . '</li>';
                    }
                    if ( isset( $value['blog'] ) ) {
                        $output .= '<li data-id="general[blog]">' . __( 'Blog Page', 'themify' ) . '</li>';
                    }
                    if ( isset( $value['page'] ) ) {
                        $output .= '<li data-id="general[page]">' . __( 'Page views', 'themify' ) . '</li>';
                    }
                    if ( isset( $value['single'] ) ) {
                        $output .= '<li data-id="general[single]">' . __( 'Single post views', 'themify' ) . '</li>';
                    }
                    if ( isset( $value['search'] ) ) {
                        $output .= '<li data-id="general[search]">' . __( 'Search pages', 'themify' ) . '</li>';
                    }
                    if ( isset( $value['category'] ) ) {
                        $output .= '<li data-id="general[category]">' . __( 'Category archive', 'themify' ) . '</li>';
                    }
                    if ( isset( $value['tag'] ) ) {
                        $output .= '<li data-id="general[tag]">' . __( 'Tag archive', 'themify' ) . '</li>';
                    }
                    if ( isset( $value['author'] ) ) {
                        $output .= '<li data-id="general[author]">' . __( 'Author pages', 'themify' ) . '</li>';
                    }
                    if ( isset( $value['date'] ) ) {
                        $output .= '<li data-id="general[date]">' . __( 'Date archive pages', 'themify' ) . '</li>';
                    }
                    if ( isset( $value['year'] ) ) {
                        $output .= '<li data-id="general[year]">' . __( 'Year based archive', 'themify' ) . '</li>';
                    }
                    if ( isset( $value['month'] ) ) {
                        $output .= '<li data-id="general[month]">' . __( 'Month based archive', 'themify' ) . '</li>';
                    }
                    if ( isset( $value['day'] ) ) {
                        $output .= '<li data-id="general[day]">' . __( 'Day based archive', 'themify' ) . '</li>';
                    }
                    if ( isset( $value['logged'] ) ) {
                        $output .= '<li data-id="general[logged]">' . __( 'logged', 'themify' ) . '</li>';
                    }
                    if ( isset( $value['404'] ) ) {
                        $output .= '<li data-id="general[404]">' . __( '404 page', 'themify' ) . '</li>';
                    }
                    foreach ( get_post_types( array( 'public' => true, 'exclude_from_search' => false, '_builtin' => false ), 'objects' ) as $post_type_key => $post_type_object ) {
                        if ( isset( $value[ $post_type_key ] ) ) {
                            $output .= '<li data-id="general[' . $post_type_key . ']">' . sprintf( __( 'Single %s View', 'themify' ), $post_type_object->labels->singular_name ) . '</li>';
                        }
                    }
                    foreach ( get_taxonomies( array( 'public' => true, '_builtin' => false ), 'objects' ) as $taxonomy_key => $tax ) {
                        if ( isset( $value[ $taxonomy_key ] ) ) {
                            $output .= '<li data-id="general[' . $taxonomy_key . ']">' . sprintf( __( '%s Archive View', 'themify' ), $tax->labels->singular_name ) . '</li>';
                        }
                    }
                    break;

                case 'post_type_archive' :
                    foreach ( array_keys( $value ) as $post_type ) {
                        $post_type_object = get_post_type_object( $post_type );
                        if ( $post_type_object ) {
                            $output .= '<li data-id="post_type_archive[' . $post_type . ']">' . sprintf( __( '%s Archive View', 'themify' ), $post_type_object->labels->singular_name ) . '</li>';
                        }
                    }
                    break;

                case 'post_type' :
                    foreach ( $value as $post_type_key => $post_slugs ) {
                        $post_type_object = get_post_type_object( $post_type_key );
                        if ( ! $post_type_object ) {
                            continue;
                        }
                        foreach ( array_keys( $post_slugs ) as $post_path ) {
                            $post = get_page_by_path( $post_path, OBJECT, $post_type_key );
                            if ( $post ) {
                                $url = get_permalink( $post->ID );
                                $output .= '<li data-id="post_type[' . $post_type_key . '][' . $post_path . ']"><span data-tf_tooltip="' . esc_attr( $post_type_object->labels->singular_name ) . '">' . $post->post_title . '</span></li>';
                            }
                        }
                    }
                    break;

                case 'tax' :
                    /* "in-category" options */
                    if ( isset( $value['category_single'] ) ) {
                        foreach ( $value['category_single'] as $taxonomy_key => $term_slugs ) {
                            if ( ! taxonomy_exists( $taxonomy_key ) ) {
                                continue;
                            }
                            foreach ( array_keys( $term_slugs ) as $term_slug ) {
                                $term = get_term_by( 'slug', $term_slug, $taxonomy_key );
                                if ( $term ) {
                                    $output .= "<li data-id='tax[category_single][{$taxonomy_key}][{$term_slug}]'><span data-tf_tooltip='" . esc_attr( sprintf( __( 'Posts with %s term', 'themify' ), $term->name ) ) . "'>{$term->name}</span></li>";
                                }
                            }
                        }
                        unset( $value['category_single'] );
                    }

                    foreach ( $value as $taxonomy_key => $term_slugs ) {
                        $taxonomy_object = get_taxonomy( $taxonomy_key );
                        if ( ! $taxonomy_object ) {
                            continue;
                        }
                        foreach ( array_keys( $term_slugs ) as $term_slug ) {
                            $term = get_term_by( 'slug', $term_slug, $taxonomy_key );
                            if ( $term ) {
                                $url = get_term_link( $term->term_id, $taxonomy_key );
                                $output .= "<li data-id='tax[{$taxonomy_key}][{$term_slug}]'><span data-tf_tooltip='" . esc_attr( sprintf( __( '%s Term Archive', 'themify' ), $taxonomy_object->labels->singular_name ) ) . "'>{$term->name}</span></li>";
                            }
                        }
                    }
                    break;

                case 'wc' :
                    if ( isset( $value['orders'] ) ) {
                        $output .= '<li data-id="wc[orders]"><span>' . __( 'WooCommerce > Orders', 'themify' ) . '</span></li>';
                    }
                    if ( isset( $value['view-order'] ) ) {
                        $output .= '<li data-id="wc[view-order]"><span>' . __( 'WooCommerce > View Order', 'themify' ) . '</span></li>';
                    }
                    if ( isset( $value['downloads'] ) ) {
                        $output .= '<li data-id="wc[downloads]"><span>' . __( 'WooCommerce > Downloads', 'themify' ) . '</span></li>';
                    }
                    if ( isset( $value['edit-account'] ) ) {
                        $output .= '<li data-id="wc[edit-account]"><span>' . __( 'WooCommerce > Edit Account', 'themify' ) . '</span></li>';
                    }
                    if ( isset( $value['edit-address'] ) ) {
                        $output .= '<li data-id="wc[edit-address]"><span>' . __( 'WooCommerce > Addresses', 'themify' ) . '</span></li>';
                    }
                    if ( isset( $value['lost-password'] ) ) {
                        $output .= '<li data-id="wc[lost-password]"><span>' . __( 'WooCommerce > Lost Password', 'themify' ) . '</span></li>';
                    }
                    if ( isset( $value['order-pay'] ) ) {
                        $output .= '<li data-id="wc[order-pay]"><span>' . __( 'WooCommerce > Pay', 'themify' ) . '</span></li>';
                    }
                    if ( isset( $value['order-received'] ) ) {
                        $output .= '<li data-id="wc[order-received]"><span>' . __( 'WooCommerce > Order received', 'themify' ) . '</span></li>';
                    }
                    if ( isset( $value['payment-methods'] ) ) {
                        $output .= '<li data-id="wc[order-pay]"><span>' . __( 'WooCommerce > Payment methods', 'themify' ) . '</span></li>';
                    }
                    break;

                case 'roles' :
                    foreach ( $GLOBALS['wp_roles']->roles as $key => $role ) {
                        if ( isset( $value[ $key ] ) ) {
                            $output .= '<li data-id="roles[' . $key . ']"><span data-tf_tooltip="' . esc_attr__( 'User Roles', 'themify' ) . '">' . $role['name'] . '</span></li>';
                        }
                    }
                    break;
            }
        }

        return '<ul class="tb_hook_conditions">' . $output . '</ul>';
    }

    public static function hook_locations_view_setup() {
        if ( isset( $_GET['tp'] ) && $_GET['tp'] == 1 ) {
            show_admin_bar( false );

            add_action( 'wp_head', array( __CLASS__, 'wp_head' ) );

            /* enqueue url fix script */
            themify_enque_script('hook-locations-urlfix', THEMIFY_URI . '/js/admin/hook-locations-urlfix.min.js',THEMIFY_VERSION, array( 'jquery' ));

            foreach ( self::get_locations() as $group_key => $group_items ) {
                if ( $group_key !== 'general' ) {
                    foreach ( $group_items as $location => $label ) {
                        add_action( $location, array( __CLASS__, 'print_hook_label' ) );
                    }
                }
            }
        }
    }

    public static function wp_head() {
        ?>
        <style>
        .hook-location-hint{
                    padding:7px 15px;
                    background:#fbffcd;
                    border:solid 1px rgba(0,0,0,.1);
                    color:#555;
                    font:11px/1em normal Arial, Helvetica, sans-serif;
                    line-height:1;
                    margin:2px;
                    display:block;
                    clear:both;
                    cursor:pointer
        }
        .hook-location-hint:hover{
                    outline:1px solid rgba(233,143,143,.9);
                    outline-offset:-1px
        }
        #layout{
            display:block
        }
        .transparent-header #headerwrap{
            position:relative;
            color:inherit
        }
        </style>
        <?php
    }

    public static function print_hook_label() {
        $hook = current_filter();
        echo '<div class="hook-location-hint" data-id="' . esc_attr( $hook ) . '">' . esc_html( preg_replace( '/^themify_/', '', $hook ) ) . '</div>';
    }

    private static function child_post_name($post) {
        $str = $post->post_name;

        if ( $post->post_parent > 0 ) {
            $parent = get_post($post->post_parent);
            $str = self::child_post_name($parent) . '/' . $str;
        }

        return $str;
    }

    /**
     * Run shortcode with same functionality as WP prior to 4.2.3 update and
     * this ticket: https://core.trac.wordpress.org/ticket/15694
     * Similar to do_shortcode, however will not encode html entities
     *
     * @return string
     */
    public static function themify_do_shortcode_wp( $content ) {
        global $shortcode_tags;

        if (empty($shortcode_tags) || !is_array($shortcode_tags) || false === strpos( $content, '[' )) {
            return $content;
        }
        // Find all registered tag names in $content.
        preg_match_all( '@\[([^<>&/\[\]\x00-\x20=]++)@', $content, $matches );
        $tagnames = array_intersect( array_keys( $shortcode_tags ), $matches[1] );

        if ( empty( $tagnames ) ) {
            return $content;
        }

        $pattern = get_shortcode_regex( $tagnames );
        $content = preg_replace_callback( "/$pattern/", 'do_shortcode_tag', $content );

        // Always restore square braces so we don't break things like <!--[if IE ]>
        return unescape_invalid_shortcodes( $content );
    }

}
Themify_Hooks::init();