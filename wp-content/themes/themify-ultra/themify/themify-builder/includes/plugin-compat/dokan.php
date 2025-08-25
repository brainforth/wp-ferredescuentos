<?php
/**
 * Builder Plugin Compatibility Code
 *
 * @package    Themify_Builder
 * @subpackage Themify_Builder/classes
 */

/**
 * Dokan Pro
 * @link https://wedevs.com/dokan/
 */
class Themify_Builder_Plugin_Compat_dokan {

    static function init() {
        add_action( 'dokan_before_refund_policy', array( __CLASS__, 'dokan_before_refund_policy' ) );
        add_action( 'dokan_after_refund_policy', array( __CLASS__, 'dokan_after_refund_policy' ) );
    }

    public static function dokan_before_refund_policy():void {
        remove_filter( 'the_content', array( 'Themify_Builder', 'builder_show_on_front' ), 11 );
    }

    public static function dokan_after_refund_policy():void {
        add_filter( 'the_content', array( 'Themify_Builder', 'builder_show_on_front' ), 11 );
    }
}