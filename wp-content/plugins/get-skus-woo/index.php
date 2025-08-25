<?php
/*
Plugin Name: Detector de Imágenes de Productos
Description: Detecta productos publicados sin imagen asignada y permite descargar los SKUs.
Version: 1.0
Author: Tu Nombre
*/

add_action('admin_menu', function () {
    add_menu_page('Detector de Imágenes', 'Detectar Imágenes', 'manage_woocommerce', 'detectar-imagenes', 'dip_render_admin_page');
});

function dip_render_admin_page() {
    ?>
    <div class="wrap">
        <h1>Detector de Imágenes de Productos</h1>
        <button id="dip-detect-btn" class="button button-primary">Detectar imágenes</button>
        <div id="dip-log" style="margin-top:20px; max-height: 300px; overflow:auto; background:#fff; padding:10px; border:1px solid #ccc;"></div>
        <div id="dip-download-container" style="margin-top:20px;"></div>
    </div>
    <script>
        document.getElementById('dip-detect-btn').addEventListener('click', function () {
            const log = document.getElementById('dip-log');
            const downloadContainer = document.getElementById('dip-download-container');
            log.innerHTML = '';
            downloadContainer.innerHTML = '';
            let offset = 0;
            let batch = 50;
            let totalChecked = 0;
            let totalNoImage = 0;
            let skus = [];

            function fetchBatch() {
                fetch(ajaxurl + '?action=dip_check_products&offset=' + offset + '&batch=' + batch)
                    .then(res => res.json())
                    .then(data => {
                        totalChecked += data.checked;
                        totalNoImage += data.no_image_count;
                        skus = skus.concat(data.no_image_skus);
                        log.innerHTML += `<div>Revisados: ${totalChecked} | Sin imagen: ${totalNoImage}</div>`;
                        if (data.has_more) {
                            offset += batch;
                            fetchBatch();
                        } else {
                            log.innerHTML += `<div><strong>Finalizado</strong></div>`;
                            const btn = document.createElement('button');
                            btn.className = 'button';
                            btn.innerText = 'Descargar SKUs';
                            btn.onclick = function () {
                                const blob = new Blob([skus.join(',')], { type: 'text/plain' });
                                const url = URL.createObjectURL(blob);
                                const a = document.createElement('a');
                                a.href = url;
                                a.download = 'skus_sin_imagen.txt';
                                a.click();
                                URL.revokeObjectURL(url);
                            };
                            downloadContainer.appendChild(btn);
                        }
                    });
            }

            fetchBatch();
        });
    </script>
    <?php
}

add_action('wp_ajax_dip_check_products', function () {
    $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
    $batch = isset($_GET['batch']) ? intval($_GET['batch']) : 50;

    $args = [
        'post_type' => 'product',
        'post_status' => 'publish',
        'posts_per_page' => $batch,
        'offset' => $offset,
        'fields' => 'ids',
    ];

    $query = new WP_Query($args);
    $no_image_skus = [];
    foreach ($query->posts as $product_id) {
        if (!has_post_thumbnail($product_id)) {
            $sku = get_post_meta($product_id, '_sku', true);
            if ($sku) {
                $no_image_skus[] = $sku;
            }
        }
    }

    wp_send_json([
        'checked' => count($query->posts),
        'no_image_count' => count($no_image_skus),
        'no_image_skus' => $no_image_skus,
        'has_more' => count($query->posts) === $batch,
    ]);
});
