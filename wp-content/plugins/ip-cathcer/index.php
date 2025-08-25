<?php
/*
Plugin Name: IP Logger con Whitelist
Description: Registra IPs visitantes y permite excluirlas de la vista mediante una whitelist.
Version: 9.11-USA
Author: WordPress
*/

register_activation_hook(__FILE__, 'ip_logger_install');
function ip_logger_install() {
    global $wpdb;
    $p = $wpdb->prefix;
    $c = $wpdb->get_charset_collate();
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    
    $sql1 = "CREATE TABLE IF NOT EXISTS {$p}ip_logger (
        ip VARCHAR(45) NOT NULL,
        visits INT NOT NULL DEFAULT 1,
        last_visit DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY(ip)
    ) $c;";
    
    $sql2 = "CREATE TABLE IF NOT EXISTS {$p}ip_logger_whitelist (
        ip VARCHAR(45) NOT NULL,
        date_added DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY(ip)
    ) $c;";
    
    dbDelta($sql1);
    dbDelta($sql2);
    
    // Verificar si hubo errores
    if (!empty($wpdb->last_error)) {
        error_log('Error al crear tablas IP Logger: ' . $wpdb->last_error);
    }
}

add_action('admin_init', 'ip_logger_check_tables');
function ip_logger_check_tables() {
    global $wpdb;
    $p = $wpdb->prefix;
    
    // Verificar si las tablas existen
    $logger_table = $wpdb->get_var("SHOW TABLES LIKE '{$p}ip_logger'");
    $whitelist_table = $wpdb->get_var("SHOW TABLES LIKE '{$p}ip_logger_whitelist'");
    
    if (!$logger_table || !$whitelist_table) {
        // Si falta alguna tabla, reinstalar
        ip_logger_install();
        
        // Registrar el evento
        error_log('IP Logger: Tablas faltantes detectadas. Reinstalando...');
    }
}

add_action('init', 'ip_logger_track');
function ip_logger_track() {
    if (is_admin()) return;
    global $wpdb;
    $ip = $_SERVER['REMOTE_ADDR'];
    $t1 = $wpdb->prefix . 'ip_logger';
    $t2 = $wpdb->prefix . 'ip_logger_whitelist';
    if ($wpdb->get_var($wpdb->prepare("SELECT ip FROM {$t2} WHERE ip = %s", $ip))) return;
    if ($wpdb->get_var($wpdb->prepare("SELECT ip FROM {$t1} WHERE ip = %s", $ip))) {
        $wpdb->query($wpdb->prepare(
            "UPDATE {$t1} SET visits = visits + 1, last_visit = NOW() WHERE ip = %s",
            $ip
        ));
    } else {
        $wpdb->insert($t1, [
            'ip' => $ip,
            'visits' => 1,
            'last_visit' => current_time('mysql')
        ]);
    }
}

add_action('admin_menu', 'ip_logger_admin_menu');
function ip_logger_admin_menu() {
    add_menu_page(
        'IPs Visitantes',
        'IPs Visitantes',
        'manage_options',
        'ip-logger',
        'ip_logger_admin_page',
        'dashicons-admin-site',
        80
    );
}

function ip_logger_admin_page() {
    global $wpdb;
    $p = $wpdb->prefix;
    $t1 = $p . 'ip_logger';
    $t2 = $p . 'ip_logger_whitelist';
    
    // Verificar tablas nuevamente antes de continuar
    ip_logger_check_tables();
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ip_logger_whitelist_nonce'])) {
        if (wp_verify_nonce($_POST['ip_logger_whitelist_nonce'], 'ip_logger_whitelist_action')) {
            if (!empty($_POST['add_ip'])) {
                $ip = sanitize_text_field($_POST['add_ip']);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    $wpdb->show_errors(); // Mostrar errores SQL
                    $result = $wpdb->replace($t2, [
                        'ip' => $ip,
                        'date_added' => current_time('mysql')
                    ]);
                    
                    if ($result === false) {
                        error_log('Error al agregar IP a whitelist: ' . $wpdb->last_error);
                        echo '<div class="notice notice-error"><p>Error al agregar la IP a la whitelist. Detalles: '.esc_html($wpdb->last_error).'</p></div>';
                    } else {
                        echo '<div class="notice notice-success"><p>IP agregada a la whitelist correctamente.</p></div>';
                        $delete_result = $wpdb->delete($t1, ['ip' => $ip]);
                        if ($delete_result === false) {
                            error_log('Error al eliminar IP de tabla principal: ' . $wpdb->last_error);
                        }
                    }
                } else {
                    echo '<div class="notice notice-error"><p>La IP proporcionada no es válida.</p></div>';
                }
            }
            
            if (!empty($_POST['remove_ip'])) {
                $ip = sanitize_text_field($_POST['remove_ip']);
                $result = $wpdb->delete($t2, ['ip' => $ip]);
                if ($result === false) {
                    error_log('Error al eliminar IP de whitelist: ' . $wpdb->last_error);
                    echo '<div class="notice notice-error"><p>Error al eliminar la IP de la whitelist. Detalles: '.esc_html($wpdb->last_error).'</p></div>';
                } elseif ($result === 0) {
                    echo '<div class="notice notice-warning"><p>La IP no existía en la whitelist.</p></div>';
                } else {
                    echo '<div class="notice notice-success"><p>IP eliminada de la whitelist correctamente.</p></div>';
                }
            }
        } else {
            echo '<div class="notice notice-error"><p>Error de seguridad. Inténtalo de nuevo.</p></div>';
        }
    }
    
    $whitelist_ips = $wpdb->get_results("SELECT * FROM {$t2} ORDER BY date_added DESC");
    $whitelist = $whitelist_ips ? wp_list_pluck($whitelist_ips, 'ip') : [];
    
    if (!empty($whitelist)) {
        $placeholders = implode(',', array_fill(0, count($whitelist), '%s'));
        $sql = $wpdb->prepare(
            "SELECT * FROM {$t1} WHERE ip NOT IN ({$placeholders}) ORDER BY last_visit DESC",
            $whitelist
        );
        $active_ips = $wpdb->get_results($sql);
    } else {
        $active_ips = $wpdb->get_results("SELECT * FROM {$t1} ORDER BY last_visit DESC");
    }
    
    echo '<div class="wrap">';
    echo '<h1>IP Logger con Whitelist</h1>';
    
    echo '<div class="card">';
    echo '<h2>Agregar IP a Whitelist</h2>';
    echo '<form method="post">';
    wp_nonce_field('ip_logger_whitelist_action', 'ip_logger_whitelist_nonce');
    echo '<input type="text" name="add_ip" placeholder="Ej. 192.168.1.1" required>';
    echo '<button type="submit" class="button button-primary">Agregar</button>';
    echo '</form>';
    echo '</div>';
    
    echo '<div class="card">';
    echo '<h2>IPs en Whitelist</h2>';
    
    if (!empty($whitelist_ips)) {
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr>
            <th>IP</th>
            <th>Fecha de registro</th>
            <th>Acciones</th>
        </tr></thead>';
        echo '<tbody>';
        
        foreach ($whitelist_ips as $ip) {
            echo '<tr>';
            echo '<td>' . esc_html($ip->ip) . '</td>';
            echo '<td>' . esc_html($ip->date_added) . '</td>';
            echo '<td>
                <form method="post" style="display:inline;">
                    ' . wp_nonce_field('ip_logger_whitelist_action', 'ip_logger_whitelist_nonce', true, false) . '
                    <input type="hidden" name="remove_ip" value="' . esc_attr($ip->ip) . '">
                    <button type="submit" class="button-link">Eliminar</button>
                </form>
            </td>';
            echo '</tr>';
        }
        
        echo '</tbody>';
        echo '</table>';
    } else {
        echo '<p>No hay IPs en la whitelist.</p>';
    }
    
    echo '</div>';
    
    echo '<div class="card">';
    echo '<h2>IPs Activas</h2>';
    
    if (!empty($active_ips)) {
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr>
            <th>IP</th>
            <th>Visitas</th>
            <th>Última visita</th>
        </tr></thead>';
        echo '<tbody>';
        
        foreach ($active_ips as $ip) {
            echo '<tr>';
            echo '<td>' . esc_html($ip->ip) . '</td>';
            echo '<td>' . esc_html($ip->visits) . '</td>';
            echo '<td>' . esc_html($ip->last_visit) . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody>';
        echo '</table>';
    } else {
        echo '<p>No hay IPs activas registradas.</p>';
    }
    
    echo '</div>';
    echo '</div>';
}