<?php

defined( 'ABSPATH' ) || exit;

class Themify_Builder_Builder_Page {

    static function init() {
        if (current_user_can( 'publish_pages' ) ) {
            add_action( 'admin_bar_menu', [ __CLASS__, 'admin_bar_menu' ], 999 );
            if ( is_admin() ) {
                add_action( 'admin_menu', [ __CLASS__, 'admin_menu' ] );
                add_action( 'admin_init', [ __CLASS__, 'loader_script' ] );
                add_action( 'wp_ajax_tb_builder_page_dropdown', [ __CLASS__, 'ajax_dropdown' ] );
                add_action( 'wp_ajax_tb_builder_page_publish', [ __CLASS__, 'wp_ajax_tb_builder_page_publish' ] );
            } else {
                add_action( 'wp_footer', [ __CLASS__, 'loader_script' ] );
            }
        }
    }

    static function admin_menu() {
        add_submenu_page( 'edit.php?post_type=page', __( 'Add Builder Page', 'themify' ), __( 'Add Builder Page', 'themify' ), 'publish_pages', '#tb_builder_page', null );
    }

    static function admin_bar_menu( $admin_bar ) {
        $args = array(
            'parent' => 'new-page',
            'id'     => 'tb_builder_page',
            'title'  => __( ' Builder Page', 'themify' ), /* space before the title is for the tf_loader element */
            'href'   => '#tb_builder_page',
            'meta'   => false
        );
        $admin_bar->add_node( $args );       
    }

    static function ajax_dropdown() {
        check_ajax_referer( 'tf_nonce', 'nonce' );
        wp_dropdown_pages( [
            'post_type'        => 'page',
            'name'             => 'parent',
            'class'=>'tf_scrollbar',
            'show_option_none' => __( '(no parent)', 'themify' ),
            'sort_column'      => 'menu_order, post_title',
        ] );
        die;
    }

    /**
     * Publish a new page and import a chosen Builder layout
     *
     * @hooked to wp_ajax_tb_builder_page_publish
     */
    static function wp_ajax_tb_builder_page_publish() {
        check_ajax_referer( 'tf_nonce', 'nonce' );
        if ( ! current_user_can( 'publish_pages' ) ) {
            die;
        }

        $title = isset( $_POST['post_title'] ) ? sanitize_text_field( $_POST['post_title'] ) : '';
        $layout = isset( $_POST['layout'] ) ? json_decode(stripslashes_deep($_POST['layout']),true) : '';
        $parent = isset( $_POST['parent'] ) ? (int) $_POST['parent'] : 0;
        $new_page = wp_insert_post( [
            'post_type' => 'page',
            'post_status' => 'publish',
            'post_title' => $title,
            'post_parent' => $parent
        ] );
        if ( is_wp_error( $new_page ) ) {
            wp_send_json_error( $new_page );
        }
        if ( isset( $_POST['slug'] ) ) {
            $get_posts = new WP_Query();
            $template= $get_posts->query( [
                'post_type' => Themify_Builder_Layouts::LAYOUT_SLUG,
                'posts_per_page' => 1,
                'no_found_rows'=>true,
                'ignore_sticky_posts'=>true,
                'name' => sanitize_text_field( $_POST['slug'] ),
                'orderby'=>'none',
                'cache_results'=>false,
                'update_post_meta_cache'=>false,
                'update_post_term_cache'=>false
            ]  );
            if ( isset( $template[0]->ID ) ) {
                $builder_data =ThemifyBuilder_Data_Manager::get_data( $template[0]->ID ); // get builder data from original post
                Themify_Builder_Model::removeElementIds( $builder_data );
                ThemifyBuilder_Data_Manager::save_data( $builder_data, $new_page );
            }
        } elseif ( ! empty( $layout ) ) {
            ThemifyBuilder_Data_Manager::save_data( $layout['builder_data'], $new_page );
            if(!empty($layout['used_gs'])){
                Themify_Global_Styles::builder_import($layout['used_gs'],true);
            }
        }
        if ( themify_is_themify_theme() ) {
            update_post_meta( $new_page, 'page_layout', 'full_width' );
            update_post_meta( $new_page, 'hide_page_title', 'yes' );
        }
        wp_send_json_success( themify_https_esc( get_permalink( $new_page ) ) . '#builder_active' );
    }

    /**
     * Adds necessary script & style for loading the modal box
     *
     * @return void
     */
    static function loader_script() {
        if(!Themify_Builder_Model::is_front_builder_activate()){
            $data = [
                'nonce' => wp_create_nonce( 'tf_nonce' ),
                'layouts' => Themify_Builder_Model::get_layouts(),
                'add_layout_link' => admin_url( 'post-new.php?post_type='.Themify_Builder_Layouts::LAYOUT_SLUG ),
                'manage_layout_link' => admin_url( 'edit.php?post_type='.Themify_Builder_Layouts::LAYOUT_SLUG ),
                'i18n'=>array(
                    'layout_error' => __( 'There was an error in loading layout, please try again later, or you can download this file: ({FILE}) and then import manually (https://themify.me/docs/builder#import-export).', 'themify' ),
                    'preview'=>__( 'Preview', 'themify' ),
                    'cancel'=>__( 'Cancel', 'themify' ),
                    'title'=> __( 'Add title', 'themify' ),
                    'all'=>__( 'All', 'themify' ),
                    'publish'=> __( 'Publish', 'themify' ),
                    'search'=> __( 'Search', 'themify' ),
                    'blank'=> __( 'Blank', 'themify' ),
                    'predesigned' => __( 'Pre-designed', 'themify' ),
                    'saved' => __( 'Saved', 'themify' ),
                    'add_layout' => __( 'Add New', 'themify' ),
                    'manage_layout' => __( 'Manage Layouts', 'themify' ),
                )
            ];

            themify_enque_script( 'themify-builder-page-loader', THEMIFY_BUILDER_URI . '/js/editor/themify-builder-page-loader.js');
            wp_localize_script( 'themify-builder-page-loader', 'tbBuilderPage', $data );
        }
    }
}
Themify_Builder_Builder_Page::init();
