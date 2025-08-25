<?php

namespace Aplazo\Woocommerce\Hooks;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Scripts
{
    public function __construct()
    {
        add_action('wp_enqueue_scripts', array( $this, 'aplazoScripts'));
    }

    public static function aplazoScripts()
    {
        wp_enqueue_script( 'aplazo-widget', plugins_url( '../assets/js/aplazo-widget/aplazo-widgets.min.js', plugin_dir_path( __FILE__ )));
        $images_path = plugins_url('/assets/images/', APLAZO_PLUGIN_FILE) ;
        wp_localize_script( 'aplazo-widget', 'images', array(
            'step1' => $images_path . 'step-1.png',
            'step2' => $images_path . 'step-2.png',
            'step3' => $images_path . 'step-3.png',
            'logoraw' => $images_path . 'logo-raw.png',
            'descmovil' => $images_path . 'aplazo-desc-movil',
            'aplazodescription' => $images_path . 'aplazo-description.png'
        ));
        // add css
        wp_enqueue_style(
            'woocommerce-aplazo-checkout',
            plugins_url( '../assets/css/checkout_aplazo.css', plugin_dir_path( __FILE__ ) )
        );
    }
}