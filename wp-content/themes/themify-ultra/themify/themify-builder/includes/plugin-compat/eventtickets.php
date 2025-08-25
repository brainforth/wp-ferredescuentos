<?php
/**
 * Builder Plugin Compatibility Code
 *
 * @package    Themify_Builder
 * @subpackage Themify_Builder/classes
 */

/**
 * @link https://wordpress.org/plugins/event-tickets/
 */
class Themify_Builder_Plugin_Compat_EventTickets {

    static function init() {
        add_action( 'wp_footer', [ __CLASS__, 'wp_footer' ], 18 );
    }

    static function wp_footer():void {
        themify_enque_style( 'tf_event_tickets', THEMIFY_BUILDER_URI .'/includes/plugin-compat/css/event-tickets.css', null, THEMIFY_VERSION,'all',true );
    }
}