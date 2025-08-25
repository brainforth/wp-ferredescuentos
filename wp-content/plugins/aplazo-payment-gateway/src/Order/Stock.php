<?php

namespace Aplazo\Woocommerce\Order;

use Aplazo\Woocommerce\Gateways\AplazoGateway;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Stock
{
    public function __construct()
    {
        add_action('woocommerce_order_status_pending_to_cancelled', array($this, 'restoreStockItem'), 10, 1);
        add_action('woocommerce_order_status_pending_to_failed', array($this, 'restoreStockItem'), 10, 1);
        add_action('woocommerce_order_status_processing_to_refunded', array($this, 'restoreStockItem'), 10, 1);
        add_action('woocommerce_order_status_on-hold_to_refunded', array($this, 'restoreStockItem'), 10, 1);
        add_action('woocommerce_order_status_changed', array($this, 'orderStockReduction'), 20, 4);
    }

    /**
     * Restore Stock Item
     *
     * @param int $order_id Order ID.
     */
    public function restoreStockItem($order_id)
    {
        $aplazoGateway = AplazoGateway::get_instance();
        $order = wc_get_order($order_id);

        $reserveStock = $aplazoGateway->get_option('reserve_stock');

        if (!$order || 'yes' !== get_option('woocommerce_manage_stock') || !apply_filters('woocommerce_can_reduce_order_stock', true, $order) || $order->get_payment_method() !== 'aplazo'
            || $reserveStock !== "yes") {
            return;
        }

        foreach ($order->get_items() as $item) {
            if (!$item->is_type('line_item')) {
                continue;
            }

            $_product = $item->get_product();
            if ($_product && $_product->managing_stock()) {
                $item_name = $_product->get_formatted_name();
                $qty = apply_filters('woocommerce_order_item_quantity', $item->get_quantity(), $order, $item);
                $new_stock = wc_update_product_stock($_product, $qty, 'increase');
                if (!is_wp_error($new_stock)) {
                    /* translators: 1: item name 2: old stock quantity 3: new stock quantity */
                    $order->add_order_note(sprintf(__('%1$s stock increased from %2$s to %3$s.', 'woocommerce'), $item_name, $new_stock - $qty, $new_stock));
                    do_action('woocommerce_auto_stock_restored', $_product, $item);
                }
            }
        }
    }

    public function orderStockReduction($order_id, $old_status, $new_status, $order)
    {
        if ($new_status == 'cancelled') {
            $stock_reduced = get_post_meta($order_id, '_order_stock_reduced', true);
            if (empty($stock_reduced) && $order->get_payment_method() == 'aplazo') {
                wc_increase_stock_levels($order_id);
            }
        }
    }
}
