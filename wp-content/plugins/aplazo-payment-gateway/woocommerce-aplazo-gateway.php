<?php
/*
 * Plugin Name: Aplazo Payment Gateway
 * Description: Aplazo BNPL Payment Gateway plugin
 * Version: 1.4.1
 * Author: Aplaz S.A. de C.V.
 * Text Domain: aplazo-payment-gateway
 * Domain Path: /i18n/languages
 * WC requires at least: 6.0
 * WC tested up to: 8.1.0
 * License: MIT
*/

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';

use Automattic\WooCommerce\Utilities\FeaturesUtil;
use Aplazo\Woocommerce\WoocommerceAplazo;

if ( ! defined( 'APLAZO_PLUGIN_FILE' ) ) {
    define( 'APLAZO_PLUGIN_FILE', __FILE__ );
}

add_action('before_woocommerce_init', function () {
    if (class_exists(FeaturesUtil::class)) {
        FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__);
        FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__);
    }
});

if (!class_exists('WoocommerceAplazo')) {
    new WoocommerceAplazo();
}

register_deactivation_hook( __FILE__, 'aplazo_deactivate' );

function aplazo_deactivate() {
    $timestamp = wp_next_scheduled( 'cancel_unpaid_orders_hook' );
    wp_unschedule_event( $timestamp, 'cancel_unpaid_orders_hook' );
}
?>
