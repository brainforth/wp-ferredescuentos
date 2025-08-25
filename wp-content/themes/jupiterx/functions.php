<?php
/**
 * Jupiter X Framework.
 * This core file should only be overwritten via your child theme.
 *
 * We strongly recommend to read the Jupiter documentation to find out more about
 * how to customize the Jupiter theme.
 *
 * @author JupiterX
 * @link   https://artbees.net
 * @package JupiterX\Framework
 */

/**
 * Initialize Jupiter theme framework.
 *
 * @author JupiterX
 * @link   https://artbees.net
 */

require_once dirname( __FILE__ ) . '/lib/init.php';

function registrar_sidebar_tienda() {
    register_sidebar( array(
        'name'          => __('Sidebar Tienda', 'tu-textdomain'),
        'id'            => 'sidebar-tienda',
        'description'   => __('Widgets para la página de la tienda', 'tu-textdomain'),
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
        'after_widget'  => '</section>',
        'before_title'  => '<h2 class="widget-title">',
        'after_title'   => '</h2>',
    ));
}
add_action('widgets_init', 'registrar_sidebar_tienda');

function nombre_usuario_elementor() {
    if (is_user_logged_in()) {
        $current_user = wp_get_current_user();
        $first_name = explode(' ', trim($current_user->display_name))[0];  
        
        return '<a id="user-link-menu" href="/mi-cuenta"><div class="user-profile-wrap"><i class="user-icon-menu fas fa-user"></i> <span class="user-text-menu">Bienvenido, ' . esc_html($first_name) . '</span></div></a>';
    } else {
        return '<a href="/mi-cuenta"><div class="user-profile-wrap"><i class="user-icon-menu fas fa-sign-in-alt"></i> <span class="user-text-menu">Iniciar sesión</span></div></a>';
    }
}
add_shortcode('usuario_menu', 'nombre_usuario_elementor');











// Agregar metabox en la pantalla de pedidos
add_action('add_meta_boxes', function() {
	$post_types_orders = array ( 'woocommerce_page_wc-orders', 'shop_order');

	add_meta_box(
		'argo_sync_metabox',
		__('Estado en Argo','woocommerce'),
		'argo_sync_render_metabox',
		$post_types_orders,
		'side',
		'default'
	);
});
function argo_sync_render_metabox($post) {
    $argo_id = get_post_meta($post->ID, '_argo_order_id', true);
    $logs = get_post_meta($post->ID, '_argo_log', true);

	// If not assigned show a button to force sync
	if (!$argo_id) {
		echo '<p><a href="' . esc_url(admin_url('admin-ajax.php?action=argo_sync_force&post_id=' . $post->ID)) . '">Sincronizar</a></p>';
	}
	else {
		echo '<p><strong>ID en Argo:</strong> ' . $argo_id . '</p>';
	}

    if (!empty($logs)) {
        echo '<p><strong>Historial:</strong></p><ul>';
        foreach (array_reverse($logs) as $log) {
            echo '<li style="margin-bottom: 5px;">' . esc_html($log) . '</li>';
        }
        echo '</ul>';
    } else {
        echo '<p>Sin registros aún.</p>';
    }
}

add_action('wp_ajax_argo_sync_force', 'argo_sync_force_callback');
function argo_sync_force_callback() {
    // Seguridad básica
    if (!current_user_can('manage_woocommerce')) {
        wp_die('No autorizado');
    }

    $order_id = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;

    if (!$order_id) {
		error_log("[debug_log] - ID de orden inválido");
        wp_die('ID de orden inválido');
    }

    $order = wc_get_order($order_id);
    if (!$order) {
		error_log("[debug_log] - Orden no encontrada");
        wp_die('Orden no encontrada');
    }

    $status = $order->get_status();

    // Solo continuar si el estado es válido
    if (in_array($status, ['processing', 'completed'])) {
        argo_sync_enviar_a_api($order_id);
        wp_redirect(admin_url('post.php?post=' . $order_id . '&action=edit&argo_sync=ok'));
        exit;
    } else {
		error_log("[debug_log] - No valid status");
        wp_redirect(admin_url('post.php?post=' . $order_id . '&action=edit&argo_sync=invalid_status'));
        exit;
    }
}







// Hooks
// Hook cuando cambia el estado del pedido
add_action('woocommerce_order_status_changed', function($order_id, $old_status, $new_status, $order) {
    // echo 'Old Status: <pre>'. print_r($old_status, true) .'</pre>';
	// echo 'New Status: <pre>'. print_r($new_status, true) .'</pre>';
	
	error_log("[debug_log] - New order status: {$new_status}");

	if ($old_status === $new_status) return;

	$toUpdate = ['processing']; // , 'completed'];
	$toCancel = ['cancelled', 'failed', 'refunded'];

	if (in_array($new_status, $toUpdate)) {
		argo_sync_enviar_a_api($order_id);
		return;
	}

	if (in_array($new_status, $toCancel)) {
		argo_sync_cancelar_en_api($order_id);
		return;
	}

    /* $relevantes = ['processing', 'completed', 'cancelled'];
    if (in_array($new_status, $relevantes)) {
        argo_sync_enviar_a_api($order_id, $new_status, $order);
    } */
}, 10, 4);

// Hook cuando se crea un nuevo pedido
/* add_action('woocommerce_new_order', function($order_id) {
    $order = wc_get_order($order_id);
	
	// $estado = $order->get_status();
	// echo 'Status creation: <pre>'. print_r($estado, true) .'</pre>';

    if ($order->get_status() === 'processing') {
        argo_sync_enviar_a_api($order_id, 'processing', $order);
    }
}); */

// Hook cuando se crea un nuevo pedido desde el front
add_action('woocommerce_checkout_order_processed', function($order_id, $posted_data, $order) {
	$status = $order->get_status();
	error_log("[debug_log] - New checkout order ID: {$order_id} with status: {$status}");
	
    if ($status === 'processing') {
        argo_sync_enviar_a_api($order_id);
    } else {
        error_log("Orden creada con estado: " . $order->get_status());
    }
}, 10, 3);







// Api handlers
function argo_sync_log($order_id, $message) {
    $logs = get_post_meta($order_id, '_argo_log', true);
    if (!is_array($logs)) $logs = [];
    $logs[] = current_time('mysql') . ' - ' . $message;
    update_post_meta($order_id, '_argo_log', $logs);
}

function argo_sync_enviar_a_api($order_id) {
    $order_data = argo_build_order_payload($order_id);

	$json = wp_json_encode($order_data);
	error_log("[debug_log] - {$json}");
	// return;
	// echo 'Payload to argo: <pre>'. print_r($json, true) .'</pre><br /><br />';
	// die();

	$response = wp_remote_post('https://api.ferredescuentos.com/api/v1/orders/sendOrder', [
		'method'    => 'POST',
		'headers'   => ['Content-Type' => 'application/json'],
		'body'      => wp_json_encode($order_data),
		'timeout'   => 20,
	]);

    // Si ya tenemos el ID de Argo y es cancelación, inclúyelo
    /* $argo_id = get_post_meta($order_id, '_argo_order_id', true);
    if ($estado === 'cancelled' && $argo_id) {
        $data['argo_id'] = $argo_id;
    } */

    if (is_wp_error($response)) {
        argo_sync_log($order_id, 'Error de conexión: ' . $response->get_error_message());
        return;
    }

// Response from swagger
/*
{
  "mensaje": "Pedido insertado con exito",
  "proceso": true
  "order_id": 15016
}
*/
	$body = json_decode(wp_remote_retrieve_body($response), true);

    if (isset($body['data']['proceso']) && $body['data']['proceso'] === true) {
		$argo_id = get_post_meta($order_id, '_argo_order_id', true);

        if (isset($body['data']['idPedido']) && !$argo_id) {
            update_post_meta($order_id, '_argo_order_id', $body['data']['idPedido']);
            argo_sync_log($order_id, 'Pedido creado exitosamente en Argo. ID: ' . $body['data']['idPedido']);
        }
    } else {
        $header_message = 'STATUS: ' . ($body['header']['status'] ?? 'Error') . '  -  HeaderMessage: ' . ($body['header']['message'] ?? 'Respuesta desconocida');
		$data_message = "\nBodyMessage: " . ($body['data']['mensaje'] ?? 'Respuesta desconocida');
        argo_sync_log($order_id, 'Error en Argo: ' . $header_message . $data_message);
    }
}

function argo_sync_cancelar_en_api($order_id) {
	$argo_id = get_post_meta($order_id, '_argo_order_id', true);

	if (!$argo_id) return;

	$order = wc_get_order($order_id);
	$currentStatus = $order->get_status();
	$statuses = ['cancelled', 'failed', 'refunded'];

	// If already cancelled, do nothing
	if (!in_array($currentStatus, $statuses)) return;

	// If not cancelled, cancel it
	$response = wp_remote_post('https://api.ferredescuentos.com/api/v1/orders/cancelOrder', [
		'method'    => 'POST',
		'headers'   => ['Content-Type' => 'application/json'],
		'body'      => wp_json_encode(['order_id' => $argo_id]),
		// 'body'      => wp_json_encode(['order_id' => 10]),
		'timeout'   => 20,
	]);

	if (is_wp_error($response)) {
        argo_sync_log($order_id, 'Error de conexión: ' . $response->get_error_message());
        return;
    }

// Response from swagger
/*
{
  "mensaje": "string",
  "proceso": boolean
}
*/
	$body = json_decode(wp_remote_retrieve_body($response), true);
// echo '<pre>'.print_r($body, true).'</pre>';
// die();
    if (isset($body['data']['proceso']) && $body['data']['proceso'] === true) {
        argo_sync_log($order_id, 'Pedido cancelado exitosamente en Argo.');
    } else {
		$header_message = 'STATUS: ' . ($body['header']['status'] ?? 'Error') . '  -  HeaderMessage: ' . ($body['header']['message'] ?? 'Respuesta desconocida');
		$data_message = "\nBodyMessage: " . ($body['data']['mensaje'] ?? 'Respuesta desconocida');
        argo_sync_log($order_id, 'Error en Argo: ' . $header_message . $data_message);
    }
}

function argo_build_order_payload($order_id) {
// Error response from swagger
/* 
{
  "header": {
    "status": "CONFLICT",
    "code": 400,
    "message": "Ocurrio un error: "
  },
  "data": {
    "mensaje": "El total del pedido es diferente al permitido",
    "proceso": false
  }
}
*/




    $order = wc_get_order($order_id);

    $billing = $order->get_address('billing');
    $items = [];

    foreach ($order->get_items() as $item) {
        $product = $item->get_product();
		$subtotal = $item->get_subtotal();
		$sku = $product->get_sku();

        if(!$product || $subtotal <= 0 ) continue;
		
		if(!str_contains($sku, 'COMBO')) {
			// Categories
			$categories = wp_get_post_terms($product->get_id(), 'product_cat', ['fields' => 'names']);
			$hasFlashCategory = in_array('FLASH', $categories);
			$hasIndividualCategory = in_array('INDIVIDUAL', $categories);

			// Prices
			$regular_price = floatval($product->get_regular_price());
			$sale_price = floatval($product->get_sale_price());
			$price_used = floatval($item->get_total()) / $item->get_quantity();

			$soldWithSalePrice = $sale_price > 0 && abs($price_used - $sale_price) < 0.01;
			$soldWithRegularPrice = !$soldWithSalePrice && abs($price_used - $regular_price) < 0.01;

			// ✅ Meta key a nivel de producto
			$argoOfferId = $product->get_meta('_argoOfferId');
			$hasArgoOffer = !empty($argoOfferId); // Puede ser entero o null

			// ✅ Si cumple alguna condición especial, modificamos el SKU
			$isSpecial = ($hasFlashCategory || $hasIndividualCategory || $soldWithSalePrice || $hasArgoOffer);

			if($isSpecial) {
				$sku = 'COMBO' . $argoOfferId;
			}
			
			// echo 'Product: <pre>'. print_r($product, true) .'</pre><br /><br />';
			// echo 'Regular price: <pre>'. print_r($regular_price, true) .'</pre><br /><br />';
			// echo 'Sale price: <pre>'. print_r($sale_price, true) .'</pre><br /><br />';
			// echo 'Price used: <pre>'. print_r($price_used, true) .'</pre><br /><br />';
			// echo 'Sold with sale price: <pre>'. print_r($soldWithSalePrice, true) .'</pre><br /><br />';
			// echo 'Sold with regular price: <pre>'. print_r($soldWithRegularPrice, true) .'</pre><br /><br />';
			// echo 'Has flash category: <pre>'. print_r($hasFlashCategory, true) .'</pre><br /><br />';
			// echo 'Has individual category: <pre>'. print_r($hasIndividualCategory, true) .'</pre><br /><br />';
			// echo 'Has argo offer: <pre>'. print_r($hasArgoOffer, true) .'</pre><br /><br />';
			// echo 'Is special: <pre>'. print_r($isSpecial, true) .'</pre><br /><br />';
			// echo 'SKU: <pre>'. print_r($sku, true) .'</pre><br /><br />';
			// die();
		}

        $items[] = [
            'sku'       => $sku,
            'quantity'  => $item->get_quantity(),
            'price'     => $product->get_price(),
            'subtotal'  => $subtotal,
            'subtotal_tax' => $item->get_subtotal_tax(),
        ];
    }

    $payload = [
        'id' => $order->get_id(),
        'total' => $order->get_total(),
		'shipping_total' => $order->get_shipping_total(),
        'total_tax' => $order->get_total_tax(),
        'billing' => [
            'first_name' => $billing['first_name'],
            'last_name' => $billing['last_name'],
            'email' => $billing['email'],
            'phone' => $billing['phone'],
            'company' => $billing['company'],
            'address_1' => $billing['address_1'],
            'address_2' => $billing['address_2'],
            'postcode' => $billing['postcode'],
            'state' => $billing['state']
        ],
        'meta_data' => array_map(function($meta){
            return [
                'key' => $meta->key,
                'value' => $meta->value
            ];
        }, $order->get_meta_data()),
        'line_items' => $items
    ];

    return $payload;
}






// Custom endpoint
add_action('rest_api_init', function () {
	register_rest_route('custom-api/v1', '/assign-brand/', [
		'methods' => 'POST',
		'callback' => 'assign_brand_to_product',
		'permission_callback' => function () {
			return current_user_can('edit_products');
		}
	]);
});

function assign_brand_to_product(WP_REST_Request $request) {
	$product_id = $request->get_param('product_id');
	$brand_id = $request->get_param('brand_id');

	if (!$product_id || !$brand_id) {
		return new WP_REST_Response(['error' => 'Missing parameters'], 400);
	}

	$result = wp_set_object_terms($product_id, [(int)$brand_id], 'product_brand', false);

	if (is_wp_error($result)) {
		return new WP_REST_Response(['error' => $result->get_error_message()], 500);
	}

	return new WP_REST_Response(['success' => true], 200);
}






// --- INICIO autenticación básica temporal en functions.php ---
add_filter( 'determine_current_user', function( $user ) {
	global $wp_json_basic_auth_error;
	$wp_json_basic_auth_error = null;

	if ( ! empty( $user ) ) return $user;
	if ( ! isset( $_SERVER['PHP_AUTH_USER'] ) ) return $user;

	$username = $_SERVER['PHP_AUTH_USER'];
	$password = $_SERVER['PHP_AUTH_PW'];

	remove_filter( 'determine_current_user', 'json_basic_auth_handler', 20 );
	$user = wp_authenticate( $username, $password );
	add_filter( 'determine_current_user', 'json_basic_auth_handler', 20 );

	if ( is_wp_error( $user ) ) {
		$wp_json_basic_auth_error = $user;
		return null;
	}

	$wp_json_basic_auth_error = true;
	return $user->ID;
}, 20 );

add_filter( 'rest_authentication_errors', function( $error ) {
	global $wp_json_basic_auth_error;
	return ! empty( $error ) ? $error : $wp_json_basic_auth_error;
});
// --- FIN autenticación básica temporal ---
// 
// 
// 
// 
function crear_directorio_imgs_combos() {
    $upload_dir = wp_upload_dir();
    $folder_path = $upload_dir['basedir'] . '/imgs_combos';

    if (!file_exists($folder_path)) {
        wp_mkdir_p($folder_path);
        // Opcional: Añade un archivo .htaccess para seguridad
        file_put_contents($folder_path . '/.htaccess', 'Options -Indexes');
    }
}
add_action('init', 'crear_directorio_imgs_combos');

// No mosrtar productos con status diferente de publicado a menos que sea un preview
add_action('template_redirect', function () {
    if (is_singular('product')) {
        global $post;

        // Solo permitir si el producto está publicado
        if ($post->post_status !== 'publish') {
            // Permitir vista previa solo si viene desde el panel con un nonce de preview válido
            if (!isset($_GET['preview']) || !current_user_can('edit_post', $post->ID)) {
                wp_redirect(home_url('/')); // o wp_die('Producto no disponible.', '404');
                exit;
            }
        }
    }
});




// View logs
add_action('admin_menu', function () {
    if (current_user_can('manage_options')) {
        add_menu_page(
            'Ver debug.log',
            'Ver Logs',
            'manage_options',
            'ver-debug-log',
            'mostrar_debug_log_mejorado',
            'dashicons-media-text',
            99
        );
    }
});

function mostrar_debug_log_mejorado() {
    $log_path = WP_CONTENT_DIR . '/debug.log';
    $filtro = isset($_GET['filtro']) ? sanitize_text_field($_GET['filtro']) : '';
    $limite = isset($_GET['limite']) ? max(1, intval($_GET['limite'])) : 200;
    $offset = isset($_GET['offset']) ? max(0, intval($_GET['offset'])) : 0;

    echo '<div class="wrap">';
    echo '<h1>Visor de debug.log</h1>';
    echo '<form method="get" style="margin-bottom: 20px;">';
	echo '<input type="hidden" name="page" value="ver-debug-log">';
	echo '<input type="text" name="filtro" placeholder="Filtrar por texto (opcional)" value="' . esc_attr($filtro) . '" style="width:300px;"> ';
	echo '<input type="number" name="limite" placeholder="Cantidad de líneas" value="' . esc_attr($limite) . '" style="width:150px;"> ';
	echo '<input type="hidden" name="offset" value="0">';
	echo '<input type="submit" class="button button-primary" value="Filtrar"> ';
	echo '<a href="' . esc_url(admin_url('admin.php?page=ver-debug-log')) . '" class="button">Refrescar</a> ';
	echo '<a href="' . esc_url(admin_url('admin.php?page=ver-debug-log&accion=vaciar')) . '" class="button button-danger" onclick="return confirm(\'¿Estás seguro de que deseas vaciar el archivo debug.log?\')">Vaciar Log</a>';
	echo '</form>';

    if (!file_exists($log_path)) {
        echo '<p>El archivo <code>debug.log</code> no existe.</p>';
        echo '</div>';
        return;
    }

    $lineas = file($log_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $lineas = array_reverse($lineas); // Mostrar últimas primero

    if ($filtro) {
        $lineas = array_filter($lineas, function ($linea) use ($filtro) {
            return stripos($linea, $filtro) !== false;
        });
        $lineas = array_values($lineas); // reindexar tras filtrar
    }

    $total = count($lineas);
    $bloque = array_slice($lineas, $offset, $limite);

	// Acción para vaciar
	if (isset($_GET['accion']) && $_GET['accion'] === 'vaciar') {
		file_put_contents($log_path, '');
		echo '<div class="notice notice-success"><p>debug.log vaciado correctamente.</p></div>';
	}
	
    echo '<pre style="white-space: pre-wrap; background: #fff; padding: 1em; border: 1px solid #ccc; max-height: 600px; overflow-y: scroll;">';
    echo esc_html(implode("\n", $bloque));
    echo '</pre>';

    // Navegación
    $prev = max(0, $offset - $limite);
    $next = $offset + $limite;
    $base_url = admin_url('admin.php?page=ver-debug-log&filtro=' . urlencode($filtro) . '&limite=' . $limite);

    echo '<div style="margin-top: 20px;">';
    if ($offset > 0) {
        echo '<a href="' . esc_url($base_url . '&offset=' . $prev) . '" class="button">⬅ Anterior</a> ';
    }
    if ($next < $total) {
        echo '<a href="' . esc_url($base_url . '&offset=' . $next) . '" class="button">Siguiente ➡</a>';
    }
    echo '</div>';
    echo '</div>';
}





/* add_action( 'wp_enqueue_scripts', function() {
    if ( is_product() ) {
        wp_enqueue_script( 'selectWoo', WC()->plugin_url() . '/assets/js/selectWoo/selectWoo.full.min.js', array( 'jquery' ), '1.0.0', true );
    }
}); */

/* add_action('wp_footer', function () {
    if (is_product()) {
        global $post;
        $product = wc_get_product($post->ID);

        if ($product && $product instanceof WC_Product) {
            echo '<!--';
            echo "\nID: " . $product->get_id();
            echo "\nNombre: " . $product->get_name();
            echo "\nTipo: " . $product->get_type();
            echo "\nPrecio: " . $product->get_price();
            echo "\nPrecio regular: " . $product->get_regular_price();
            echo "\nPrecio en oferta: " . $product->get_sale_price();
            echo "\nStock gestionado?: " . ($product->managing_stock() ? 'Sí' : 'No');
            echo "\nStock actual: " . $product->get_stock_quantity();
            echo "\n¿Tiene stock?: " . ($product->is_in_stock() ? 'Sí' : 'No');
            echo "\n¿Es comprable?: " . ($product->is_purchasable() ? 'Sí' : 'No');
            echo "\n¿Está visible?: " . ($product->is_visible() ? 'Sí' : 'No');
            echo "\n¿Está publicado?: " . get_post_status($product->get_id());
            echo "\n-->"; // Fin del comentario
        } else {
            echo "<!-- Producto no encontrado o no válido -->";
        }
    }
}); */

/* add_action('wp_footer', function() {
    if (is_product()) {
        global $product;
        echo '<pre style="background:#fff;color:#000;padding:1rem;border:2px solid red;">';
        if ($product) {
            print_r($product->get_data());
        } else {
            echo "No se cargó \$product";
        }
        echo '</pre>';
    }
}); */



// Fix _price missing meta_key
/* add_action('admin_init', function() {
    if (current_user_can('manage_woocommerce') && isset($_GET['reparar_precios']) && $_GET['reparar_precios'] == '1') {
        reparar_precios_productos_woocommerce();
        wp_die('Proceso de reparación de precios terminado. Puedes borrar el parámetro de la URL.');
    }
});

function reparar_precios_productos_woocommerce() {
    $args = [
        'post_type' => 'product',
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'fields' => 'ids',
    ];
    $product_ids = get_posts($args);

    if (empty($product_ids)) {
        error_log("[reparar_precios] No se encontraron productos.");
        return;
    }

    foreach ($product_ids as $product_id) {
        // Obtener precio regular directamente del meta o de alguna fuente correcta
        $regular_price = get_post_meta($product_id, '_regular_price', true);

        if ($regular_price === '') {
            error_log("[reparar_precios] Producto ID {$product_id} no tiene precio regular definido.");
            continue;
        }

        // Obtener precio actual
        $current_price = get_post_meta($product_id, '_price', true);

        if ($current_price != $regular_price) {
            update_post_meta($product_id, '_regular_price', $regular_price);
            update_post_meta($product_id, '_price', $regular_price);

            // También asegúrate que no haya precio en oferta
            update_post_meta($product_id, '_sale_price', '');

            error_log("[reparar_precios] Producto ID {$product_id} actualizado. Precio puesto a: {$regular_price}");
        } else {
            error_log("[reparar_precios] Producto ID {$product_id} ya tenía precio correcto.");
        }
    }
} */


// Fix post_name to slugify version
/* add_action('admin_init', function () {
    if (current_user_can('manage_woocommerce') && isset($_GET['reparar_slugs']) && $_GET['reparar_slugs'] == '1') {
        reparar_slugs_productos_woocommerce();
        wp_die('Proceso de reparación de slugs terminado. Puedes borrar el parámetro de la URL.');
    }
});

function reparar_slugs_productos_woocommerce() {
    $args = [
        'post_type'      => 'product',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'fields'         => 'ids',
    ];

    $product_ids = get_posts($args);

    if (empty($product_ids)) {
        error_log("[reparar_slugs] No se encontraron productos.");
        return;
    }

    foreach ($product_ids as $product_id) {
        $post = get_post($product_id);
        if (!$post) continue;

        $title = $post->post_title;
        if (empty($title)) {
            error_log("[reparar_slugs] Producto ID {$product_id} no tiene título.");
            continue;
        }

        $slug = sanitize_title($title);

        if ($post->post_name !== $slug) {
            wp_update_post([
                'ID'        => $product_id,
                'post_name' => $slug,
            ]);
            error_log("[reparar_slugs] Producto ID {$product_id} slug actualizado a '{$slug}'.");
        } else {
            error_log("[reparar_slugs] Producto ID {$product_id} ya tenía el slug correcto.");
        }
    }
} */