<?php
/**
 * Themify Compatibility Code
 *
 * @package Themify
 */

/**
 * WPML
 * @link https://wpml.org/
 */
class Themify_Compat_wpml {

    static function init() {
        if ( class_exists( 'SitePress',false ) ) {
            add_action( 'themify_search_fields', [ __CLASS__, 'themify_search_fields' ] );
        }
    }

    /**
     * Add current language to the search form, fix Ajax search with WPML
     */
    public static function themify_search_fields() {
        global $sitepress;
        $current_language = $sitepress->get_current_language();
        echo '<input type="hidden" name="lang" value="' . esc_attr( $current_language ) . '" />';
    }
}