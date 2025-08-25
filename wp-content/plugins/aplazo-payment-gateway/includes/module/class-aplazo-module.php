<?php
/**
 * Aplazo Woocommerce Module Init
 * Author - Aplazo
 * Developer
 * License - https://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 *
 * @package Aplazo
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Aplazo Init class
 */
class Aplazo_Module
{
    /**
     * Static instance
     *
     * @var Aplazo_Module
     */
    private static $instance = null;
    private $log;

    const SOURCE_LOG = 'aplazo-payment';
    /**
     *
     * Init Options
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct()
    {
        $this->includes();
        $this->initHooks();
    }

    public function includes()
    {
        include_once('manager/class-aplazo-stock.php');
        // If is a frontend request
        if ((!is_admin() || defined('DOING_AJAX')) && !defined('DOING_CRON') && !WC()->is_rest_api_request()) {
            include_once('class-aplazo-frontend-scripts.php');
            include_once('class-aplazo-template-functions.php');
        }
        $this->include_log();
    }

    public function initHooks()
    {
        add_action('woocommerce_proceed_to_checkout', array($this, 'wp_extracode_for_shopping_cart'));
        add_action('woocommerce_before_add_to_cart_form', array($this, 'wp_extracode_for_products'));
        add_action('woocommerce_order_status_changed', array($this, 'aplazo_order_stock_reduction'), 20, 4);

        //ADD LISTENER FOR HOOK WITH CHECK RESULT
        add_action('wp_ajax_nopriv_check_success_response', array($this, 'check_success_response'));
        add_action('wp_ajax_check_success_response', array($this, 'check_success_response'));

        //Localization
        add_action('init', array($this, 'aplazo_text_domain_load'));

        add_filter('plugin_action_links_' . plugin_basename(APLAZO_PLUGIN_FILE),  array($this,'aplazo_gateway_plugin_links'));
        add_filter('woocommerce_payment_gateways', array($this, 'simple_aplazo'));
        add_filter( 'woocommerce_gateway_title', array( $this, 'get_payment_method_title' ), 10, 2 );

        add_action('restrict_manage_posts', array($this, 'cancel_unpaid_orders'));

        add_filter( 'cron_schedules', array($this, 'fifteen_minutes_cron_interval' ));
        add_action( 'cancel_unpaid_orders_hook', array($this, 'cancel_unpaid_orders_exec'));

        if ( ! wp_next_scheduled( 'cancel_unpaid_orders_hook' ) ) {
            wp_schedule_event( time(), 'fifteen_minutes_cron_interval', 'cancel_unpaid_orders_hook');
        }
    }

    public function cancel_unpaid_orders()
    {
        global $pagenow, $post_type;

        $aplazo = WC_Gateway_Aplazo::get_instance();
        $cancel_orders = $aplazo->get_option('cancel_orders');

        if ('shop_order' === $post_type && 'edit.php' === $pagenow) {
            $this->cancel_unpaid_orders_exec();
        }
    }

    public function cancel_unpaid_orders_exec()
    {
        $aplazo = WC_Gateway_Aplazo::get_instance();
        $payment_method = $aplazo->id;

        // Consulta para obtener las órdenes con el método de pago deseado
        $args = array(
            'post_type'   => 'shop_order',
            'post_status' => 'wc-pending', // Cambia esto al estado deseado
            'meta_query'  => array(
                array(
                    'key'   => '_payment_method',
                    'value' => $payment_method,
                ),
            ),
            'posts_per_page' => -1
        );

        $orders = new WP_Query( $args );

        if ( $orders->have_posts() ) {
            $cancel_orders = $aplazo->get_option('cancel_orders');
            if(strpos($cancel_orders, 'm')){
                $cancel_orders = str_replace("m", "", $cancel_orders);
                $now = strtotime('-'. $cancel_orders .' minutes');
            }else{
                $now = strtotime('-'. $cancel_orders .' hours');
            }

            if($cancel_orders){
                $aplazo->log->write_log('info', "**** Cancelando ordenes aplazo ****");
                $cancelled_text = __("The order was cancelled due to no payment from customer.", "aplazo-payment-gateway");
            }
            $aplazo->log->write_log('info', "Se encontraron " . $orders->found_posts . ' para procesar.');
            while ($orders->have_posts()) {
                $orders->the_post();
                $order_id = $orders->post->ID;
                $unpaid_order = wc_get_order($order_id);
                $orderTime = strtotime($unpaid_order->get_date_created());
                $canCancel = true;

                $aplazo->log->write_log('info', "Procesando orden con id: " . $unpaid_order->get_id());
                $aplazoStatusIsPaid = $aplazo->getStatus($unpaid_order->get_id());
                if($aplazoStatusIsPaid) {
                    $aplazo->updatePaidOrder($aplazoStatusIsPaid, $unpaid_order, $unpaid_order->get_id());
                    $canCancel = false;
                }

                if($canCancel) {
                    // If $cancel_orders config is set in "manual" the value is 0. All the cancellations must be done manually.
                    if ($cancel_orders) {
                        if ($orderTime < $now) {
                            $message = "Cancelando orden " . $unpaid_order->get_id() . " con total de " . $unpaid_order->get_total();
                            $aplazo->log->write_log('info', $message);
                            $aplazo->aplazo_sevice->sendLog($message, WC_Aplazo_Api::LOGS_CATEGORY_INFO, WC_Aplazo_Api::LOGS_SUBCATEGORY_ORDER);
                            $unpaid_order->update_status('cancelled', $cancelled_text);
                            $result = $aplazo->cancel($unpaid_order->get_id(), $unpaid_order->get_total(), $cancelled_text);
                            if (!$result) {
                                $aplazo->log->write_log('error', __('Cancel communication failed.', 'aplazo-payment-gateway'));
                            }
                        } else {
                            $aplazo->log->write_log('info', "La orden con id: " . $unpaid_order->get_id() . " aun no cumple el tiempo para ser cancelada.");
                        }
                    } else {
                        $aplazo->log->write_log('info', "cancel_orders config is set in 'manual'. All the cancellations must be done manually.");
                    }
                }
            }
        }
    }

    /**
     * Add aplazo widget to shopping_cart
     *
     * @since 1.0.10
     */
    public function wp_extracode_for_shopping_cart()
    {
        $aplazo = WC_Gateway_Aplazo::get_instance();
        if ($aplazo->show_widget_shopping_cart()) {
            $cart = WC()->cart;
            $totalcart = $cart->total * 100;
            echo '<aplazo-placement product-price = "' . esc_attr($totalcart) . '"></aplazo-placement>';
        }
    }

    /**
     * Add aplazo widget to product
     *
     * @since 1.0.10
     */
    public function wp_extracode_for_products()
    {
        $aplazo = WC_Gateway_Aplazo::get_instance();
        if ($aplazo->show_widget_product_detail()) {
            global $product;
            $id = $product->get_id();
            $product = wc_get_product($id);
            if (isset($product) && $product != "") {
                if (is_numeric($product->get_price())) {
                    $price = floatval($product->get_price());
                } else {
                    $price = 0;
                }
                $price = $price * 100;
                echo '<aplazo-placement product-price = "' . esc_attr($price) . '"></aplazo-placement>';
            }
        }
    }

    public function check_success_response()
    {
        global $woocommerce;
        $success = isset($_POST['data']);
        if ($success) {
            $order_id = ( int ) sanitize_text_field($_POST['data']['order_id']);
            if ( is_numeric( $order_id ) && $order_id > 0 ) {
                $order = new WC_Order($order_id);
                //Check of status from response data
                //Mark order of status and empty cart
                $order->update_status('pending', __('Order pending payment via APLAZO', 'aplazo-payment-gateway'));
                $order->add_order_note(__('Client has redirected to APLAZO gateway for pay his goods', 'aplazo-payment-gateway'));
            }
            wp_die('success');
        } else {
            wp_die('Check Request Failure');
        }
    }

    public function simple_aplazo($methods)
    {
        $methods[] = 'WC_Gateway_Aplazo';
        return $methods;
    }

    /**
     * Adds plugin page links
     *
     * @param array $links all plugin links
     * @return array $links all plugin links + our custom links (i.e., "Settings")
     * @since 1.0.0
     */
    public function aplazo_gateway_plugin_links($links)
    {
        $plugin_links = array(
            '<a href="' . admin_url('admin.php?page=wc-settings&tab=checkout&section=aplazo') . '">' . __('Configure', 'aplazo-payment-gateway') . '</a>'
        );

        return array_merge($plugin_links, $links);
    }

    public function aplazo_order_stock_reduction($order_id, $old_status, $new_status, $order)
    {
        if ($new_status == 'cancelled') {
            $stock_reduced = get_post_meta($order_id, '_order_stock_reduced', true);
            if (empty($stock_reduced) && $order->get_payment_method() == 'aplazo') {
                wc_increase_stock_levels($order_id);
            }
        }
    }

    public function get_payment_method_title( $title, $id ) {
        if ($id !== 'aplazo') {
            return $title;
        }
        return 'Paga en plazos sin tarjeta de crédito';
    }

    public function aplazo_text_domain_load() {
        load_plugin_textdomain( 'aplazo-payment-gateway', false, plugin_basename( dirname( APLAZO_PLUGIN_FILE ) ) . '/i18n/languages' );
    }

    /**
     * Include log
     * @return void
     */
    public function include_log() {
        $aplazo = WC_Gateway_Aplazo::get_instance();
        include_once dirname( __FILE__ ) . '/log/class-wc-aplazo-log.php';
        $debugMode = $aplazo->get_option('debug_mode') == 'yes';
        $this->log = WC_Aplazo_Log::init_aplazo_log( self::SOURCE_LOG, $debugMode);
    }

   public function fifteen_minutes_cron_interval( $schedules ) {
        $schedules['fifteen_minutes_cron_interval'] = array(
            'interval' => 900,
            'display'  => esc_html__( 'Every Fifteen Minutes' ), );
        return $schedules;
    }
}
