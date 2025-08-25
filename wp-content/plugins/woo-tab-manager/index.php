<?php
/*
Plugin Name: WooCommerce - Tab Características
Description: Agrega un tab de Características con editor en los productos.
Version: 1.0
Author: Tu nombre
*/

add_action('add_meta_boxes', function() {
    add_meta_box(
        'caracteristicas_producto',
        'Características',
        'mostrar_editor_caracteristicas',
        'product',
        'normal',
        'high'
    );
});

function mostrar_editor_caracteristicas($post) {
    $contenido = get_post_meta($post->ID, '_caracteristicas_producto', true);
    wp_editor($contenido, 'caracteristicas_producto_editor', [
        'textarea_name' => 'caracteristicas_producto',
        'media_buttons' => true,
        'textarea_rows' => 10,
    ]);
}

add_action('save_post_product', function($post_id) {
    if (isset($_POST['caracteristicas_producto'])) {
        update_post_meta($post_id, '_caracteristicas_producto', $_POST['caracteristicas_producto']);
    }
});

add_filter('woocommerce_product_tabs', function($tabs) {
    global $post;
    $contenido = get_post_meta($post->ID, '_caracteristicas_producto', true);

    if (!empty($contenido)) {
        $tabs['caracteristicas_tab'] = [
            'title'    => 'Características',
            'priority' => 25,
            'callback' => function() use ($contenido) {
                echo wpautop(do_shortcode($contenido));
            }
        ];
    }

    return $tabs;
});
