<?php
/**
 * Builder Plugin Compatibility Code
 *
 * @package    Themify_Builder
 * @subpackage Themify_Builder/classes
 */

/**
 * Tutor LMS
 * @link https://www.themeum.com/product/tutor-lms/
 */
class Themify_Builder_Plugin_Compat_Tutor {

    static function init() {
        add_action( 'template_redirect', array( __CLASS__, 'template_redirect' ) );
    }

    /**
     * Early call the wp_enqueue_editor hook to prevent Tutor from running
     * wp_enqueue_editor() running in Builder's frontend editor page.
     */
    public static function template_redirect() {
        if ( Themify_Builder_Model::is_front_builder_activate() ) {
            remove_all_filters( 'wp_enqueue_editor' );
            do_action( 'wp_enqueue_editor' );
        }
    }
}