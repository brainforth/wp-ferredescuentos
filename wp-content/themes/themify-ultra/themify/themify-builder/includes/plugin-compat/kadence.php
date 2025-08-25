<?php
/**
 * Builder Plugin Compatibility Code
 *
 * @package    Themify_Builder
 * @subpackage Themify_Builder/classes
 */

/**
 * @link https://wordpress.org/plugins/kadence-blocks/
 */
class Themify_Builder_Plugin_Compat_Kadence {

    static function init() {
        if ( themify_is_ajax() && isset( $_POST['action'] ) && $_POST['action'] === 'tb_load_editor' ) {
            remove_action( 'init', 'kadence_gutenberg_editor_assets', 10 );
        }
    }
}