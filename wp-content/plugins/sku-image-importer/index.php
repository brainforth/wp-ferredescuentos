<?php
/*
Plugin Name: SKU Image Importer
Description: Importa imágenes desde un ZIP y asignalas a productos WooCommerce por SKU
Version: 1.1.0
Author: liion
Requires PHP: 7.4
*/

defined('ABSPATH') || exit;

class SKU_Image_Importer {
    private $temp_dir;
    private $processed = 0;
    private $total_files = 0;
    private $current_sku = '';
    private $log_messages = [];
    private $prefix_to_remove = 'SKU-';

    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('wp_ajax_process_zip', [$this, 'process_zip_handler']);
    }

    public function add_admin_menu() {
        add_menu_page(
            'Importar Imágenes por SKU',
            'SKU Importer',
            'manage_options',
            'sku-importer',
            [$this, 'render_admin_page'],
            'dashicons-images-alt2'
        );
    }

    public function enqueue_scripts($hook) {
        if ('toplevel_page_sku-importer' !== $hook) return;

        wp_enqueue_style('sku-importer-css', plugin_dir_url(__FILE__) . 'style.css');
        wp_enqueue_script('sku-importer-js', plugin_dir_url(__FILE__) . 'script.js', ['jquery'], null, true);
        wp_localize_script('sku-importer-js', 'sku_importer_vars', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('sku_importer_nonce')
        ]);
    }

    public function render_admin_page() {
        ?>
        <div class="wrap">
            <h1>Importador de Imágenes por SKU</h1>
            <div id="upload-container">
                <input type="file" id="zip-file" accept=".zip">
                <button id="start-import" class="button button-primary">Iniciar Importación</button>
            </div>
            
            <div id="progress-container" style="display:none;">
                <h2>Progreso</h2>
                <div id="progress-bar">
                    <div id="progress-fill"></div>
                </div>
                <div id="progress-text">0%</div>
                <div id="current-sku"></div>
            </div>
            
            <div id="log-container" style="display:none;">
                <h2>Registro de Actividad</h2>
                <pre id="log-output"></pre>
            </div>
        </div>
        <?php
    }

    public function process_zip_handler() {
        if (!check_ajax_referer('sku_importer_nonce', 'nonce', false)) {
            wp_send_json_error('Error de seguridad: Nonce inválido');
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permisos insuficientes');
        }

        if (empty($_FILES['zip_file'])) {
            wp_send_json_error('No se subió ningún archivo');
        }

        ignore_user_abort(true);
        set_time_limit(0);
        ini_set('output_buffering', 'off');
        ini_set('zlib.output_compression', false);
        ini_set('implicit_flush', true);
        ob_implicit_flush(true);
        
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        header('Content-Type: application/json');
        header('Cache-Control: no-cache');
        header('X-Accel-Buffering: no');

        $this->setup_temp_dir();
        $zip_path = $_FILES['zip_file']['tmp_name'];
        $this->log("Iniciando procesamiento del archivo: " . $_FILES['zip_file']['name']);

        if (!class_exists('ZipArchive')) {
            $this->log_error("Error: Extensión Zip no disponible en el servidor");
            wp_send_json_error('Extensión Zip no disponible');
        }

        $zip = new ZipArchive;
        if ($zip->open($zip_path) !== true) {
            $this->log_error("Error al abrir el archivo ZIP: " . $zip->getStatusString());
            wp_send_json_error('Archivo ZIP inválido o dañado');
        }

        $image_files = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);
            if (preg_match('/\.(jpg|jpeg|png|webp)$/i', $filename)) {
                $image_files[] = $filename;
                $this->log("Imagen encontrada: $filename");
            }
        }

        $this->total_files = count($image_files);
        $this->log("Total de imágenes encontradas: " . $this->total_files);

        if ($this->total_files === 0) {
            $this->log_error("No se encontraron imágenes válidas en el ZIP");
            wp_send_json_error('No hay imágenes válidas (formatos permitidos: jpg, jpeg, png, webp)');
        }

        try {
            if (!$zip->extractTo($this->temp_dir, $image_files)) {
                throw new Exception("Error al extraer archivos del ZIP");
            }
            $zip->close();

            $this->send_progress();

            foreach ($image_files as $index => $file) {
                $filename = pathinfo($file, PATHINFO_FILENAME);
                $this->current_sku = $filename;
                
                $possible_skus = [
                    $filename,
                    str_replace($this->prefix_to_remove, '', $filename)
                ];
                
                $this->log("Procesando imagen: $file");
                $this->log("Posibles SKUs: " . implode(', ', $possible_skus));
                
                $product_ids = [];
                foreach ($possible_skus as $sku) {
                    $found_ids = $this->find_products_by_sku($sku);
                    if (!empty($found_ids)) {
                        $product_ids = array_merge($product_ids, $found_ids);
                        $this->log("Productos encontrados usando SKU: $sku - IDs: " . implode(',', $found_ids));
                    }
                }

                $product_ids = array_unique($product_ids);

                if (!empty($product_ids)) {
                    $this->log("Productos encontrados IDs: " . implode(',', $product_ids));
                                        
                    $attachment_id = null;
                    $first_product_id = reset($product_ids);
                    $image_path = $this->temp_dir . '/' . $file;
                    
                    foreach ($product_ids as $product_id) {
                        if ($attachment_id === null) {
                            $attachment_id = $this->attach_image_to_product($image_path, $product_id);
                        } else {
                            $this->assign_image_to_product($attachment_id, $product_id);
                        }
                    }
                } else {
                    $this->log_error("Producto no encontrado para ningún SKU posible");
                }
                
                $this->processed = $index + 1;
                $this->send_progress();
            }

            $this->cleanup();
            wp_send_json_success('Proceso completado exitosamente');
            
        } catch (Exception $e) {
            $this->log_error("EXCEPCIÓN: " . $e->getMessage());
            $this->cleanup();
            wp_send_json_error('Error durante el procesamiento: ' . $e->getMessage());
        }
    }

    private function find_products_by_sku($sku) {
        global $wpdb;
        
        $product_ids = [];
        
        $ids = $wpdb->get_col($wpdb->prepare(
            "SELECT post_id 
            FROM {$wpdb->postmeta} 
            WHERE meta_key = '_sku' 
            AND meta_value = %s",
            $sku
        ));
        
        if ($ids) {
            $product_ids = array_merge($product_ids, $ids);
        }
        
        $variation_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT posts.post_parent 
            FROM {$wpdb->posts} AS posts
            LEFT JOIN {$wpdb->postmeta} AS meta ON posts.ID = meta.post_id
            WHERE meta.meta_key = '_sku' 
            AND meta.meta_value = %s 
            AND posts.post_type = 'product_variation'",
            $sku
        ));
        
        if ($variation_ids) {
            $product_ids = array_merge($product_ids, $variation_ids);
        }
        
        $valid_ids = [];
        foreach ($product_ids as $id) {
            if (get_post_status($id) !== false) {
                $valid_ids[] = $id;
            }
        }
        
        return array_unique($valid_ids);
    }

    private function assign_image_to_product($attachment_id, $product_id) {
        $product = wc_get_product($product_id);
        if (!$product) {
            $this->log_error("El producto ID $product_id no existe");
            return;
        }

        $current_thumb_id = get_post_thumbnail_id($product_id);
        if ($current_thumb_id && $current_thumb_id != $attachment_id) {
            delete_post_thumbnail($product_id);
        }

        if (set_post_thumbnail($product_id, $attachment_id)) {
            $this->log("Imagen asignada al producto ID: $product_id");
        } else {
            throw new Exception("Error al asignar imagen al producto ID: $product_id");
        }
    }

    private function attach_image_to_product($image_path, $product_id) {
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        $product = wc_get_product($product_id);
        if (!$product) {
            $this->log_error("El producto ID $product_id no existe");
            return null;
        }

        $current_thumb_id = get_post_thumbnail_id($product_id);
        if ($current_thumb_id) {
            delete_post_thumbnail($product_id);
        }

        $file_array = [
            'name'     => basename($image_path),
            'tmp_name' => $image_path
        ];

        $this->log("Subiendo imagen: " . $file_array['name']);
        $attachment_id = media_handle_sideload($file_array, $product_id);
        
        if (is_wp_error($attachment_id)) {
            throw new Exception("Error al subir imagen: " . $attachment_id->get_error_message());
        }

        if (set_post_thumbnail($product_id, $attachment_id)) {
            $this->log("Imagen subida y asignada al producto ID: $product_id");
        } else {
            throw new Exception("Error al asignar imagen destacada");
        }

        return $attachment_id;
    }

    private function setup_temp_dir() {
        $upload_dir = wp_upload_dir();
        $this->temp_dir = $upload_dir['basedir'] . '/sku_importer_temp_' . time();
        
        if (!file_exists($this->temp_dir)) {
            if (!wp_mkdir_p($this->temp_dir)) {
                throw new Exception("No se pudo crear directorio temporal");
            }
        }
    }

    private function cleanup() {
        if (file_exists($this->temp_dir)) {
            $files = glob($this->temp_dir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) unlink($file);
            }
            rmdir($this->temp_dir);
        }
    }

    private function send_progress() {
        $progress = ($this->processed / $this->total_files) * 100;
        $response = [
            'progress' => round($progress),
            'current'  => $this->current_sku,
            'log'      => $this->get_log_messages()
        ];
        echo json_encode($response) . PHP_EOL;
        flush();
    }

    private function log($message) {
        $this->log_messages[] = date('[H:i:s] ') . $message;
        error_log("[SKU Importer] " . $message);
    }

    private function log_error($message) {
        $this->log_messages[] = date('[H:i:s] ') . 'ERROR: ' . $message;
        error_log("[SKU Importer ERROR] " . $message);
    }

    private function get_log_messages() {
        $logs = implode("\n", $this->log_messages);
        $this->log_messages = [];
        return $logs;
    }
}

new SKU_Image_Importer();