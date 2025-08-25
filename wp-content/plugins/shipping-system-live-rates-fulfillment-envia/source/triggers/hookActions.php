<?php
/**
 * Init envia shipping and pickup classes 
 */
add_filter( 'woocommerce_shipping_methods', array( 'Enviacom', 'add_envia_shipping_module' ) );

add_action( 'woocommerce_load_shipping_methods', array( 'Enviacom', 'register_envia_pickup' ) );

/**
 * Set a css file for store front cart and checkout - legacy actions.
 */
add_action('wp_enqueue_scripts', array( 'Enviacom', 'wp_enqueue_scripts_envia' ) );

/**
 * Load assets to use in cart and checkout - legacy actions
 */
add_action('woocommerce_after_cart_contents', array( 'Enviacom', 'assets_loader' ) );

add_action( 'woocommerce_after_checkout_form', array( 'Enviacom', 'assets_loader' ), 20 , 1 );

/**
 *Remakes the html title label of a shipping rate element - legacy actions.
 */ 
add_action( 'woocommerce_after_shipping_rate', array( 'Enviacom', 'action_after_shipping_rate' ), 20, 2 );

/**
 * Use prev rates when an option is selected
 */ 
add_action( 'woocommerce_store_api_cart_select_shipping_rate', array( 'Enviacom', 'use_prev_rates' ) );

/**
 *  Block the refresh of quote when apply or remove a discount coupon.  
 */ 
add_action( 'woocommerce_applied_coupon', array( 'Enviacom', 'envia_coupon_action') ); 
add_action( 'woocommerce_removed_coupon', array( 'Enviacom', 'envia_coupon_action') );

/**
 * Block the refresh of quote when the cart is updated
 */
add_filter( 'woocommerce_update_cart_action_cart_updated', array( 'Enviacom', 'envia_update_cart_action' ) );

/**
 * Catch the branchCode and save in the order meta data when the checkout is finished and the pickup method is active - legacy actions.
 */
add_action( 'woocommerce_checkout_order_processed', array( 'Enviacom', 'afterFinishOrder' ), 1, 1 );
