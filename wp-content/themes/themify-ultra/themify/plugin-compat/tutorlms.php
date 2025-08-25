<?php
/**
 * Themify Compatibility Code
 *
 * @package Themify
 */

/**
 * Tutor LMS
 * @link https://wordpress.org/plugins/tutor/
 */
class Themify_Compat_tutorlms {

    static function init() {
        add_filter( 'template_redirect', array( __CLASS__, 'template_redirect' ) );
    }

    public static function template_redirect() {
        if ( is_singular( [ 'lesson', 'courses' ] ) ) {
            add_filter( 'themify_enable_lazyload', '__return_false' );
        }
    }
}