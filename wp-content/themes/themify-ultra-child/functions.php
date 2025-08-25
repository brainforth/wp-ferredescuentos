<?php

/* To enable child-theme-scripts.js file, remove the PHP comment below: */
/* remove this line

function custom_child_theme_scripts() {
	wp_enqueue_script( 'themify-child-theme-js', get_stylesheet_directory_uri() . '/child-theme-scripts.js', [ 'jquery' ], '1.0', true );
}
add_action( 'wp_enqueue_scripts', 'custom_child_theme_scripts' );

remove this line too */

/* Custom functions can be added below. */

function action_woocommerce_created_customer( $customer_id, $new_customer_data, $password_generated ) {
    // Link past orders to this newly created customer
    wc_update_new_customer_past_orders( $customer_id );
}
add_action( 'woocommerce_created_customer', 'action_woocommerce_created_customer', 10, 3 ); 

function plugin_registration_redirect() {
    return home_url( '/registro-de-usuario' );
}

add_filter( 'registration_redirect', 'plugin_registration_redirect' );


add_filter('woocommerce_states', 'custom_mexican_states');

function custom_mexican_states($states) {
    $states['MX'] = array(
        '1' => 'Aguascalientes',
        '2' => 'Baja California',
        '3' => 'Baja California Sur',
        '4' => 'Campeche',
        '7' => 'Chiapas',
        '8' => 'Chihuahua',
        '5' => 'Coahuila',
        '6' => 'Colima',
        '9' => 'Distrito Federal',
        '10' => 'Durango',
        '11' => 'Guanajuato',
        '12' => 'Guerrero',
        '13' => 'Hidalgo',
        '14' => 'Jalisco',
        '15' => 'Estado de México',
        '16' => 'Michoacán',
        '17' => 'Morelos',
        '18' => 'Nayarit',
        '19' => 'Nuevo León',
        '20' => 'Oaxaca',
        '21' => 'Puebla',
        '22' => 'Querétaro',
        '23' => 'Quintana Roo',
        '24' => 'San Luis Potosí',
        '25' => 'Sinaloa',
        '26' => 'Sonora',
        '27' => 'Tabasco',
        '28' => 'Tamaulipas',
        '29' => 'Tlaxcala',
        '30' => 'Veracruz',
        '31' => 'Yucatán',
        '32' => 'Zacatecas',
    );

    return $states;
}


function mostrar_atributos_producto_shortcode($atts) {
    // Asegúrate de que WooCommerce esté activo
    if (!class_exists('WooCommerce')) {
        return '<p>WooCommerce no está activo.</p>';
    }

    // Atributos por defecto del shortcode
    $atts = shortcode_atts(
        [
            'id' => null, // ID del producto (si no se proporciona, usa el producto actual)
        ],
        $atts
    );

    // Obtén el producto
    $product_id = $atts['id'] ? $atts['id'] : get_the_ID();
    $product = wc_get_product($product_id);

    if (!$product) {
        return '<p>Producto no encontrado.</p>';
    }

    // Obtén los atributos
    $attributes = $product->get_attributes();

    if (empty($attributes)) {
        return '<p>Atributos no disponibles por el momento.</p>';
    }

    // Construir la salida HTML
    $output = '<div class="product-attributes">';
    $output .= '<h3>Atributos del Producto</h3>';
    foreach ($attributes as $attribute) {
        $output .= '<p><strong>' . esc_html($attribute->get_name()) . '</strong>: ' . esc_html(implode(', ', $attribute->get_options())) . '</p>';
    }
    $output .= '</div>';

    return $output;
}
add_shortcode('product_attributes', 'mostrar_atributos_producto_shortcode');
