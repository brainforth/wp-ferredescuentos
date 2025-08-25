<?php

namespace Aplazo\Woocommerce;

use Aplazo\Woocommerce\Gateways\AplazoGateway;
use Aplazo\Woocommerce\Hooks\Scripts;
use Aplazo\Woocommerce\Order\Stock;
use Aplazo\Woocommerce\Service\Api;
use Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry;
use Aplazo\Woocommerce\Blocks\AplazoBlock;
use WC_Order;
use WP_Query;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Aplazo Init class
 */
class WoocommerceAplazo
{
    public function __construct()
    {
        $this->whenPluginsLoad();
    }

    public function whenPluginsLoad()
    {
        add_action('plugins_loaded', [$this, 'init']);
    }

    public function init()
    {
        if (!class_exists('WC_Payment_Gateway')) {
            add_action('admin_notices', array($this, 'noWoocommerceAdminMessage'));
            return;
        }
        $this->setPluginActionLinks();

        $this->blockPaymentGateway();
        $this->hookPaymentGateway();
        $this->registerHooks();
    }

    public function setPluginActionLinks()
    {
        add_filter('plugin_action_links_' . plugin_basename(APLAZO_PLUGIN_FILE), array($this, 'aplazoGatewayPluginLinks'));
        add_filter('woocommerce_gateway_title', array($this, 'getPaymentMethodTitle'), 10, 2);
    }

    public function aplazoGatewayPluginLinks($links)
    {
        $plugin_links = array(
            '<a href="' . admin_url('admin.php?page=wc-settings&tab=checkout&section=aplazo') . '">' . __('Configure', 'aplazo-payment-gateway') . '</a>'
        );

        return array_merge($plugin_links, $links);
    }

    public function getPaymentMethodTitle($title, $id)
    {
        if ($id !== 'aplazo') {
            return $title;
        }
        return 'Paga en plazos sin tarjeta de crédito';
    }

    public function blockPaymentGateway()
    {
        if (class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
            add_action(
                'woocommerce_blocks_payment_method_type_registration',
                function (PaymentMethodRegistry $payment_method_registry) {
                    $payment_method_registry->register(new AplazoBlock());
                }
            );
        }
    }

    public function hookPaymentGateway()
    {
        add_filter('woocommerce_payment_gateways', array($this, 'getMethodGateway'));
    }

    public function getMethodGateway($methods)
    {
        $methods[] = AplazoGateway::class;
        return $methods;
    }

    public function registerHooks()
    {
        // Localization
        add_action('init', array($this, 'aplazoTextDomainLoad'));
        // Aplazo Widgets
        add_action('woocommerce_proceed_to_checkout', array($this, 'widgetInCart'));
        add_action('woocommerce_before_add_to_cart_form', array($this, 'widgetInProducts'));
        // Aplazo on payment creation Response
        add_action('wp_ajax_nopriv_check_success_response', array($this, 'checkSuccessResponse'));
        add_action('wp_ajax_check_success_response', array($this, 'checkSuccessResponse'));
        // Order cancel methods
        add_action('restrict_manage_posts', array($this, 'cancelUnpaidOrders'));
        add_filter('cron_schedules', array($this, 'fifteen_minutes_cron_interval'));
        add_action('cancel_unpaid_orders_hook', array($this, 'cancelUnpaidOrdersExec'));
        if (!wp_next_scheduled('cancel_unpaid_orders_hook')) {
            wp_schedule_event(time(), 'fifteen_minutes_cron_interval', 'cancel_unpaid_orders_hook');
        }
        // Inventory management
        new Stock();
        if ((!is_admin() || defined('DOING_AJAX')) && !defined('DOING_CRON') && !WC()->is_rest_api_request()) {
            new Scripts();
//            include_once('class-aplazo-template-functions.php');
        }
    }

    public function noWoocommerceAdminMessage()
    {
        $message = 'Para el funcionamiento de Aplazo, instala el plugin de Woocommerce';
        echo '<div class="notice notice-info is-dismissible"><p>' . esc_html($message) . '</p></div>';
    }

    public function aplazoTextDomainLoad()
    {
        load_plugin_textdomain('aplazo-payment-gateway', false, plugin_basename(dirname(APLAZO_PLUGIN_FILE)) . '/i18n/languages');
    }

    /**
     * Add aplazo widget to shopping_cart
     *
     * @since 1.0.10
     */
    public function widgetInCart()
    {
        $aplazo = AplazoGateway::get_instance();
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
    public function widgetInProducts()
    {
        $aplazo = AplazoGateway::get_instance();
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

    public function checkSuccessResponse()
    {
        $success = isset($_POST['data']);
        if ($success) {
            $order_id = ( int )sanitize_text_field($_POST['data']['order_id']);
            if (is_numeric($order_id) && $order_id > 0) {
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

    public function cancelUnpaidOrders()
    {
        global $pagenow, $post_type;

        if ('shop_order' === $post_type && 'edit.php' === $pagenow) {
            $this->cancelUnpaidOrdersExec();
        }
    }

    public function cancelUnpaidOrdersExec()
    {
        $aplazo = AplazoGateway::get_instance();
        $payment_method = $aplazo->id;

        // Consulta para obtener las órdenes con el método de pago deseado
        $args = array(
            'post_type' => 'shop_order',
            'post_status' => 'wc-pending', // Cambia esto al estado deseado
            'meta_query' => array(
                array(
                    'key' => '_payment_method',
                    'value' => $payment_method,
                ),
            ),
            'posts_per_page' => -1
        );

        $orders = new WP_Query($args);

        if ($orders->have_posts()) {
            $cancel_orders = $aplazo->get_option('cancel_orders');
            if (strpos($cancel_orders, 'm')) {
                $cancel_orders = str_replace("m", "", $cancel_orders);
                $now = strtotime('-' . $cancel_orders . ' minutes');
            } else {
                $now = strtotime('-' . $cancel_orders . ' hours');
            }

            if ($cancel_orders) {
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
                if ($aplazoStatusIsPaid) {
                    $aplazo->updatePaidOrder($aplazoStatusIsPaid, $unpaid_order, $unpaid_order->get_id());
                    $canCancel = false;
                }

                if ($canCancel) {
                    // If $cancel_orders config is set in "manual" the value is 0. All the cancellations must be done manually.
                    if ($cancel_orders) {
                        if ($orderTime < $now) {
                            $message = "Cancelando orden " . $unpaid_order->get_id() . " con total de " . $unpaid_order->get_total();
                            $aplazo->log->write_log('info', $message);
                            $aplazo->aplazo_sevice->sendLog($message, Api::LOGS_CATEGORY_INFO, Api::LOGS_SUBCATEGORY_ORDER);
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

    public function fifteen_minutes_cron_interval($schedules)
    {
        $schedules['fifteen_minutes_cron_interval'] = array(
            'interval' => 900,
            'display' => esc_html__('Every Fifteen Minutes'),);
        return $schedules;
    }
}