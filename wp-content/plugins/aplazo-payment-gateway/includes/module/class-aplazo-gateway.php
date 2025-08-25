<?php
/**
 * Aplazo Woocommerce Module Gateway
 * Author - Aplazo
 * Developer
 * License - https://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 *
 * @package Aplazo
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if (!defined('API_APLAZO_URL')) {
    define('API_APLAZO_URL', 'https://api.aplazo.mx');
}

/**
 * Aplazo Gateway class
 */
class WC_Gateway_Aplazo extends WC_Payment_Gateway
{
    const SOURCE_LOG = 'aplazo-payment';
    const LOAN_SUCCESS_STATUS = 'OUTSTANDING';

    /**
     * Static instance
     *
     * @var WC_Gateway_Aplazo
     */
    private static $instance = null;

    private $_checkout_url = API_APLAZO_URL . '/api/loan';
    private $_verify_url = API_APLAZO_URL . '/api/auth';
    private $_refund_url = '/api/pos/loan/refund';
    private $_cancel_url = '/api/pos/loan/cancel';
    private $_status_url = '/api/pos/loan/';
    /**
     * @var WC_Aplazo_Log $log
     */
    public $log;
    /**
     * @var WC_Aplazo_Api
     */
    public $aplazo_sevice;

    protected $_supportedCurrencies = array('EUR', 'USD', 'MXN');
    private $merchantId;
    private $apiToken;
    private $lang;
    private $pay_message;
    private $status;
    private $environment;
    private $product_detail_widget;
    private $reserve_stock;
    private $shopping_cart_widget;
    private string $log_environment;

    public function __construct()
    {
        global $woocommerce;

        $this->id = 'aplazo';
        $this->has_fields = false;
        $this->method_title = 'Aplazo';
        $this->method_description = __('Gateway Payment Aplazo', 'aplazo-payment-gateway');
        $this->init_form_fields();
        $this->init_settings();
        $this->merchantId = $this->get_option('merchantId');
        $this->apiToken = $this->get_option('apiToken');
        $this->lang = $this->get_option('lang');
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->pay_message = $this->get_option('pay_message');
        $this->status = $this->get_option('status');
        $this->environment = $this->get_option('environment');
        $this->log_environment = strpos($this->environment, '.mx') !== false ? "https://posbifrost.aplazo.mx/api/v1/merchant/tagging" : "https://posbifrost.aplazo.net/api/v1/merchant/tagging";
        $this->product_detail_widget = $this->get_option('product_detail_widget');
        $this->shopping_cart_widget = $this->get_option('shopping_cart_widget');
        $this->reserve_stock = $this->get_option('reserve_stock');
        $this->supports             = array( 'products', 'refunds' );
        $this->icon = $this->get_checkout_icon();
        // Actions
        //add woocommerce receipt_page (via generate_form)
        add_action('woocommerce_receipt_aplazo', array($this, 'receipt_page'));
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

        //ADD LISTENER FOR HOOK WHITH CHECK RESULT
        add_action('woocommerce_api_wc_gateway_aplazo', array($this, 'check_aplazo_from_api_response'));

        //Check for
        if (!$this->is_valid_for_use()) {
            $this->enabled = false;
        }
        $this->includes();
    }

    //Generate in admin panel info
    public function admin_options()
    { ?>

        <h3><?php _e('Payment Aplazo', 'aplazo-payment-gateway'); ?></h3>

        <?php if ($this->is_valid_for_use()) { ?>
        <table class="form-table"><?php $this->generate_settings_html(); ?></table>
    <?php } else { ?>

        <div class="inline error">
            <p>
                <strong><?php _e('Payment gateway is disabled', 'aplazo-payment-gateway'); ?></strong>: <?php _e('Aplazo dont support currency of your shop.', 'aplazo-payment-gateway'); ?>
            </p>
        </div>

    <?php }
     }

    //Anonce for init form fields
    public function init_form_fields()
    {
        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Enable/Disable', 'aplazo-payment-gateway'),
                'type' => 'checkbox',
                'label' => __('Enable', 'aplazo-payment-gateway'),
                'default' => 'yes',
            ),
            'title' => array(
                'title' => __('Title of service by site', 'aplazo-payment-gateway'),
                'type' => 'textarea',
                'description' => __('Title of service by front-end on site chekout page. Keep empty if want to show Aplazo banner', 'aplazo-payment-gateway'),
                'default' => '<aplazo-banner></aplazo-banner>',
                'desc_tip' => true,
            ),
            'description' => array(
                'title' => __('Description on front page', 'aplazo-payment-gateway'),
                'type' => 'textarea',
                'description' => __('Description on front page when chekout goods', 'aplazo-payment-gateway'),
                'desc_tip' => true,
            ),
            'pay_message' => array(
                'title' => __('Message before pay', 'aplazo-payment-gateway'),
                'type' => 'textarea',
                'description' => __('Message before pay', 'aplazo-payment-gateway'),
                'default' => __('Please, complete the order with Aplazo clicking here:', 'aplazo-payment-gateway'),
                'desc_tip' => true,
            ),
            'merchantId' => array(
                'title' => __('Merchant ID', 'aplazo-payment-gateway'),
                'type' => 'text',
                'description' => __('Merchant ID Aplazo. Required parameter', 'aplazo-payment-gateway'),
                'desc_tip' => true,
            ),
            'apiToken' => array(
                'title' => __('API Token', 'aplazo-payment-gateway'),
                'type' => 'text',
                'description' => __('API Token Aplazo. Required parameter', 'aplazo-payment-gateway'),
                'desc_tip' => true,
            ),
            'lang' => array(
                'title' => __('Language', 'aplazo-payment-gateway'),
                'type' => 'select',
                'default' => 'en',
                'options' => array('en' => 'en_US', 'es' => 'es_MX'),
                'description' => __('Language of interface ', 'aplazo-payment-gateway'),
                'desc_tip' => true,
            ),
            'status' => array(
                'title' => __('Status of order when get OUTSTANDING state from API', 'aplazo-payment-gateway'),
                'type' => 'select',
                'default' => 'processing',
                'options' => array(
                    'pending' => __('pending', 'aplazo-payment-gateway'),
                    'processing' => __('processing', 'aplazo-payment-gateway'),
                    'on-hold' => __('on-hold', 'aplazo-payment-gateway'),
                    'cancelled' => __('cancelled', 'aplazo-payment-gateway'),
                    'completed' => __('completed', 'aplazo-payment-gateway'),
                    'refunded' => __('refunded', 'aplazo-payment-gateway'),
                    'failed' => __('failed', 'aplazo-payment-gateway')
                ),
                'description' => __('Status of order after success pay', 'aplazo-payment-gateway'),
                'desc_tip' => true,
            ),
            'environment' => array(
                'title' => __('Select the Aplazo environment', 'aplazo-payment-gateway'),
                'type' => 'select',
                'default' => 'production',
                'options' => array(
                    'https://api.aplazo.net' => __('stage', 'aplazo-payment-gateway'),
                    'https://api.aplazo.mx' => __('production', 'aplazo-payment-gateway')
                ),
                'description' => __('Aplazo Environmnet', 'aplazo-payment-gateway'),
                'desc_tip' => true,
            ),
            'product_detail_widget' => array(
                'title' => __('Show widget on Product Detail Page', 'aplazo-payment-gateway'),
                'type' => 'select',
                'default' => 'yes',
                'options' => array(
                    'yes' => __('yes', 'aplazo-payment-gateway'),
                    'no' => __('no', 'aplazo-payment-gateway')
                ),
                'description' => __('Show widget on Product Detail Page', 'aplazo-payment-gateway'),
                'desc_tip' => true,
            ),
            'shopping_cart_widget' => array(
                'title' => __('Show widget on Shopping Cart Page', 'aplazo-payment-gateway'),
                'type' => 'select',
                'default' => 'yes',
                'options' => array(
                    'yes' => __('yes', 'aplazo-payment-gateway'),
                    'no' => __('no', 'aplazo-payment-gateway')
                ),
                'description' => __('Show widget on Shopping Cart Page', 'aplazo-payment-gateway'),
                'desc_tip' => true,
            ),
            'reserve_stock' => array(
                'title' => __('Reserve stock when the order is created', 'aplazo-payment-gateway'),
                'type' => 'select',
                'default' => 'no',
                'options' => array(
                    'yes' => __('yes', 'aplazo-payment-gateway'),
                    'no' => __('no', 'aplazo-payment-gateway')
                ),
                'description' => __('Reserve stock when the order is created', 'aplazo-payment-gateway'),
                'desc_tip' => true,
            ),
            'cancel_orders' => array(
                'title' => __('Time to cancel orders', 'aplazo-payment-gateway'),
                'type' => 'select',
                'default' => '24',
                'options' => array(
                    '24' => '24 ' . __('hours', 'aplazo-payment-gateway'),
                    '20' => '20 ' . __('hours', 'aplazo-payment-gateway'),
                    '16' => '16 ' . __('hours', 'aplazo-payment-gateway'),
                    '12' => '12 ' . __('hours', 'aplazo-payment-gateway'),
                    '8' => '8 ' . __('hours', 'aplazo-payment-gateway'),
                    '6' => '6 ' . __('hours', 'aplazo-payment-gateway'),
                    '5' => '5 ' . __('hours', 'aplazo-payment-gateway'),
                    '4' => '4 ' . __('hours', 'aplazo-payment-gateway'),
                    '3' => '3 ' . __('hours', 'aplazo-payment-gateway'),
                    '2' => '2 ' . __('hours', 'aplazo-payment-gateway'),
                    '1' => '1 ' . __('hour', 'aplazo-payment-gateway'),
                    '30m' => '30 ' . __('minutes', 'aplazo-payment-gateway'),
                    '15m' => '15 ' . __('minutes', 'aplazo-payment-gateway'),
                    '0' => __('manual', 'aplazo-payment-gateway'),
                ),
                'description' => __('After this time the orders could be cancelled', 'aplazo-payment-gateway'),
                'desc_tip' => true,
            ),
            'icon' => array(
                'type' => 'hidden',
                'default' => plugin_dir_url(__FILE__) . 'logo.png',
                'desc_tip' => false,
            ),
            'debug_mode' => array(
                'title' => __('Debug', 'aplazo-payment-gateway'),
                'type' => 'checkbox',
                'label' => __('Active', 'aplazo-payment-gateway'),
                'default' => 'yes',
            )
        );
    }

    function is_valid_for_use()
    {
        return true;
    }

    function process_payment($order_id)
    {
        global $woocommerce;

        $this->log->write_log('info', 'Auth: ');
        $auth = $this->auth();
        if(!empty($auth['Authorization'])){
            $order = new WC_Order($order_id);
            $order->update_status('Awaiting payment', 'woocommerce-other-payment-gateway');
            // Reduce Stock
            if($this->reserve_stock === "yes"){
                wc_reduce_stock_levels($order_id);
            }
            // Remove cart
            $woocommerce->cart->empty_cart();
            $this->log->write_log('info', 'Loan: ');
            $loan = $this->loan($order_id, $order, $auth['Authorization']);
            if($loan){
                $order->update_status('pending', __('Order pending payment via APLAZO', 'aplazo-payment-gateway'));
                if(!empty($loan['url'])){
                    $order->add_order_note('Aplazo id > ' . $loan['loanId'] . '. '. __('Client has redirected to APLAZO gateway for pay his goods', 'aplazo-payment-gateway'));
                    return array(
                        'result' => 'success',
                        'redirect' => $loan['url'],
                    );
                } else {
                    $order->add_order_note(__('Client has redirected to APLAZO gateway for pay his goods', 'aplazo-payment-gateway'));
                    return array(
                        'result' => 'success',
                        'redirect' => $loan,
                    );
                }
            } else {
                wc_add_notice( __('Payment error:', 'aplazo-payment-gateway') . __('Communication error', 'aplazo-payment-gateway'), 'error' );
            }
        } else {
            wc_add_notice( __('Payment error:', 'aplazo-payment-gateway') . 'Auth error', 'error' );
        }
    }

    public function auth()
    {
        $environment= isset($this->environment)?$this->environment:'https://api.aplazo.mx';
        $data = [
            "merchantId" => intval($this->get_option('merchantId')),
            "apiToken" => $this->get_option('apiToken'),
            "checkout_url" => $environment . '/api/loan',
            "verify_url" => $environment . '/api/auth'
        ];
        $this->log->write_log('info', $data);
        $response = wp_remote_post( $data['verify_url'], array(
            'body'    => wp_json_encode($data),
            'headers' => array(
                'Content-Type' => 'application/json'
            ),
        ));
        if ( is_wp_error( $response ) ) {
            $error_message = $response->get_error_message();
            $this->log->write_log('error', $error_message);
            $this->aplazo_sevice->sendLog('Error > ' . $error_message, WC_Aplazo_Api::LOGS_CATEGORY_ERROR, WC_Aplazo_Api::LOGS_SUBCATEGORY_AUTH);
            return false;
        } else {
            $response = json_decode(wp_remote_retrieve_body( $response ), true );
            $this->log->write_log('info', $response);
            $this->aplazo_sevice->sendLog('Auth > ' . json_encode($response), WC_Aplazo_Api::LOGS_CATEGORY_INFO, WC_Aplazo_Api::LOGS_SUBCATEGORY_AUTH);
            return $response;
        }
    }

    public function loan($order_id, $order, $auth)
    {
        $environment= $this->environment ?? 'https://api.aplazo.mx';
        $data = $this->get_order_payload($order_id, $order);
        $headers = array(
            'Content-Type' => 'application/json',
            'Authorization' => $auth
        );
        return  $this->aplazo_sevice->apiPost($data, $environment . '/api/loan', $headers);
    }

    public function refund($order_id, $amount, $reason)
    {
        $environment= $this->environment ?? 'https://api.aplazo.mx';
        $data = array(
            "cartId"=> $order_id,
            "totalAmount"=> $amount,
            "reason"=> $reason
        );

        $headers = array(
            'Content-Type' => 'application/json',
            'merchant_id'  => intval($this->get_option('merchantId')),
            'api_token'    => $this->get_option('apiToken')
        );

        return $this->aplazo_sevice->apiPost($data, $environment . $this->_refund_url, $headers);
    }

    public function cancel($order_id, $amount, $reason)
    {
        $environment= $this->environment ?? 'https://api.aplazo.mx';
        $data = array(
            "cartId"=> $order_id,
            "totalAmount"=> $amount,
            "reason"=> $reason
        );

        $headers = array(
            'Content-Type' => 'application/json',
            'merchant_id'  => intval($this->get_option('merchantId')),
            'api_token'    => $this->get_option('apiToken')
        );

        return $this->aplazo_sevice->apiPost($data, $environment . $this->_cancel_url, $headers);
    }

    public function getStatus($order_id)
    {
        $environment= $this->environment ?? 'https://api.aplazo.mx';
        $headers = array(
            'Content-Type' => 'application/json',
            'merchant_id'  => intval($this->get_option('merchantId')),
            'api_token'    => $this->get_option('apiToken')
        );
        $response = $this->aplazo_sevice->apiGet($environment . $this->_status_url . $order_id, $headers);
        if(!$response){
            $this->log->write_log('error', __( 'GetStatus communication failed.', 'aplazo-payment-gateway' ) );
            return false;
        } else {
            foreach($response as $index => $loan){
                if (isset($loan['status'])) {
                    if($loan['status'] === self::LOAN_SUCCESS_STATUS){
                        $message = "Loan status for index [$index] is OUTSTANDING. Cart ID $order_id must not be cancelled.";
                        $this->log->write_log('info',$message);
                        $this->aplazo_sevice->sendLog($message, WC_Aplazo_Api::LOGS_CATEGORY_INFO, WC_Aplazo_Api::LOGS_SUBCATEGORY_ORDER,
                            ['loanId' => $loan['loanId']]);
                        return $loan;
                    }
                    $message = "Loan status is for index [$index] " . $loan['status'] . ". Cart ID $order_id must be cancelled.";
                    $this->log->write_log('info',$message);
                    $this->aplazo_sevice->sendLog($message, WC_Aplazo_Api::LOGS_CATEGORY_INFO, WC_Aplazo_Api::LOGS_SUBCATEGORY_ORDER,
                        ['loanId' => $loan['loanId']]);
                } else {
                    $message = "Loan not found. Cart ID $order_id must be cancelled.";
                    $this->log->write_log('info',$message);
                    $this->aplazo_sevice->sendLog($message, WC_Aplazo_Api::LOGS_CATEGORY_INFO, WC_Aplazo_Api::LOGS_SUBCATEGORY_ORDER);
                }
            }
        }
        return false;
    }

    //CALL FORM WITH PARAMETRES FOR RECEIPT"
    public function receipt_page($order)
    {
        echo '<p>' . esc_attr($this->pay_message) . '</p><br/>';
        echo $this->generate_form($order);
    }

    //GENERATE OF FORM WITH PARAMETRES FOR RECIEPT"
    public function generate_form($order_id)
    {
        $redirect = get_home_url();
        $params = array(
            "merchantId" => intval($this->get_option('merchantId')),
            "apiToken" => $this->get_option('apiToken')
        );
        if
        (isset($this->environment)){
            $environment= isset($this->environment)?$this->environment:'https://api.aplazo.mx';
            $params['verify_url'] = $environment . '/api/auth';
            $params['checkout_url'] = $environment . '/api/loan';
        } else {
            $params['verify_url'] = $this->_verify_url;
            $params['checkout_url'] = $this->_checkout_url;
        }

        wp_register_script('aplazo_script', plugin_dir_url(__FILE__) . '/../../../assets/js/script.js', array(), 1, false);
        wp_localize_script('aplazo_script', 'add_params', $params);

        wp_localize_script(
            'aplazo_script',
            'ajax_url',
            array(
                'admin_url' => admin_url('admin-ajax.php'),
                'redirect_page' => $redirect
            )
        );
        wp_enqueue_script('aplazo_script');

        global $woocommerce;
        $order = new WC_Order($order_id);
        return $this->cnb_form($this->get_order_payload($order_id, $order));
    }

    public function get_order_payload($order_id, $order)
    {
        $result_url = add_query_arg(['wc-api' => 'wc_gateway_aplazo', 'order_id' => $order_id], home_url('/'));
        //HERE IS ADDING  CALLBACK FUNC (IN FUNC CUSTOM WC ADD-ACTION) WC.DOC=PAYMENT-GATEWAY-API

        $currency = get_woocommerce_currency();
        $redirect_page_url = $order->get_checkout_order_received_url();
        $cart_url = wc_get_cart_url();
        $products = [];
        $discount = array(
            "price" => $order->get_discount_total(),
            "title" => 'discount title'
        );
        $shipping = array(
            "price" => $order->get_shipping_total(),
            "title" => $order->get_shipping_method()
        );

        $taxPrice = '';
        $taxTitle = '';

        //          Loop through order tax items
        foreach ($order->get_items('tax') as $item) {
            $taxPrice = $item->get_tax_total(); // Get rate code name (item title)
            $taxTitle = $item->get_name(); // Get rate code name (item title)
        }
        $order_items = $order->get_items();

        foreach ($order_items as $product) {
            $prodObj = wc_get_product($product['product_id']);
            $image_id = $prodObj->get_image_id();
            $image_url = wp_get_attachment_image_url($image_id, 'full');
            $products[] = array(
                'count' => $product['qty'],
                'imageUrl' => $image_url,
                'description' => $product['name'],
                'title' => $product['name'],
                'price' => $product['total'],
                'id' => $product['product_id'],
            );
        }
        $taxes = array('price' => $taxPrice, 'title' => $taxTitle);

        $buyer = array(
            'email' => $order->get_billing_email(),
            'firstName' => $order->get_billing_first_name(),
            'lastName' => $order->get_billing_last_name(),
            'addressLine' => $order->get_billing_address_1(),
            'phone'  => $order->get_billing_phone(),
            'postalCode'   => $order->get_billing_postcode(),
        );

        return array(
            'buyer' => $buyer,
            'cartId' => "" . $order_id . "",
            'cartUrl' => $cart_url,
            'currency' => esc_attr($currency),
            'description' => _("Payment for order - ") . $order_id,
            'discount' => $discount,
            'errorUrl' => $redirect_page_url,
            'language' => $this->lang,
            'merchantId' => intval($this->get_option('merchantId')),
            'order_id' => esc_attr($order_id),
            'products' => $products,
            'shipping' => $shipping,
            'shopId' => $this->merchantId,
            'successUrl' => $result_url,
            'webHookUrl' => $this->get_return_url(),
            'taxes' => $taxes,
            'totalPrice' => esc_attr((float) $order->get_total())
        );
    }

    //Method for use generate form BY
    public function cnb_form($params)
    {
        if (!isset($params['language'])) $language = 'en';
        else $language = $params['language'];
        $params = $this->cnb_params($params);
        $data = base64_encode(json_encode($params));
        $button = '<input type="submit" style="width: 300px" name="btn_text" id="submitBtn" value="Completar pago con Aplazo" disabled/>';
        return sprintf(
            '
            <form method="POST" action="%s" accept-charset="utf-8" id="aplazoSubmitFormId" onsubmit="return onSubmitAplazo(event)" enctype="application/json">
                %s
                %s' . $button . '
            </form>
            ',
            $this->_checkout_url,
            sprintf('<input type="hidden" name="%s" value="%s" />', 'data', $data),
            ''
        );
    }

    //CHECK FOR INSERT PARAMS
    private function cnb_params($params)
    {
        $params['merchantId'] = $this->merchantId;

        if (!isset($params['totalPrice'])) {
            throw new InvalidArgumentException('amount is null');
        }
        if (!isset($params['shopId'])) {
            throw new InvalidArgumentException('shopId is null');
        }
        if (!isset($params['cartId'])) {
            throw new InvalidArgumentException('cartId is null');
        }
        if (!isset($params['currency'])) {
            throw new InvalidArgumentException('currency is null');
        }
        if (!in_array($params['currency'], $this->_supportedCurrencies)) {
            throw new InvalidArgumentException('currency is not supported');
        }
        if ($params['currency'] == 'MXP') {
            $params['currency'] = 'MXN';
        }
        return $params;
    }

    public function show_widget_product_detail(){
        if($this->product_detail_widget == "yes") return true;
        else return false;
    }

    public function show_widget_shopping_cart(){
        if($this->shopping_cart_widget == "yes") return true;
        else return false;
    }

    //To formation signature

    function check_aplazo_from_api_response()
    {
        global $woocommerce;
        $data = json_decode(file_get_contents('php://input'), true);
        $this->log->write_log('info', '**** Aplazo started communication via woocommerce webhook.');
        $this->log->write_log('info', $data);
        $this->aplazo_sevice->sendLog('Webhook started from Aplazo', WC_Aplazo_Api::LOGS_CATEGORY_INFO, WC_Aplazo_Api::LOGS_SUBCATEGORY_WEBHOOK,
        ['data' => file_get_contents('php://input')]);

        if(isset($_GET['order_id'])){
            $order_id = ( int ) sanitize_text_field($_GET['order_id']);
        }
        if (isset($data['status']) && !empty($order_id)) {
            $status = $data['status'];
            $order = new WC_Order($order_id);

            //Check of status from response data
            if ($status == 'New' || $status == 'NEW') {
                $message = 'Order will send to pending.';
                $this->log->write_log('info', $message);
                $this->aplazo_sevice->sendLog($message, WC_Aplazo_Api::LOGS_CATEGORY_INFO, WC_Aplazo_Api::LOGS_SUBCATEGORY_WEBHOOK,
                    ['orderId' => $order_id]);
                $order->add_order_note(__('Client has not payed for his goods (status changed)', 'aplazo-payment-gateway'));
                $order->update_status('pending', __('Order has pending via APLAZO', 'aplazo-payment-gateway'));
                $woocommerce->cart->empty_cart();
            } else if ($status == 'Cancelled' || $status == 'CANCELLED') {
                //Mark order of status and empty cart
                $message = 'Order will be cancelled.';
                $this->log->write_log('info', $message);
                $this->aplazo_sevice->sendLog($message, WC_Aplazo_Api::LOGS_CATEGORY_INFO, WC_Aplazo_Api::LOGS_SUBCATEGORY_WEBHOOK,
                    ['orderId' => $order_id]);
                $order->add_order_note(__('Client has not payed for his goods (status changed)', 'aplazo-payment-gateway'));
                $order->update_status('cancelled', __('Order has cancelled via APLAZO (payment cancelled)', 'aplazo-payment-gateway'));
                $woocommerce->cart->empty_cart();
            } else if ($status == 'Activo' || $status == 'OUTSTANDING') {
                $message = 'Aplazo order was paid. The order should advance.';
                $this->log->write_log('info', $message);
                $this->aplazo_sevice->sendLog($message, WC_Aplazo_Api::LOGS_CATEGORY_INFO, WC_Aplazo_Api::LOGS_SUBCATEGORY_WEBHOOK,
                    ['orderId' => $order_id]);
                $this->updatePaidOrder($data, $order, $order_id);
                $this->log->write_log('info', 'Order was advanced successfully.');
                $woocommerce->cart->empty_cart();
            } else {
                $message = 'API APLAZO sended unknown status';
                $this->aplazo_sevice->sendLog($message, WC_Aplazo_Api::LOGS_CATEGORY_ERROR, WC_Aplazo_Api::LOGS_SUBCATEGORY_WEBHOOK);
                wp_die($message);

            }
        } else {
            wp_redirect(get_home_url());
        }
    }

    public function updatePaidOrder($data, $order, $order_id)
    {
        $new_status = (isset($this->status)) ? $this->status : 'processing';
        $loan_id = (!empty($data['loanId'])) ? $data['loanId'] : '';
        $comment_loan_id = (!empty($data['loanId'])) ? '. Aplazo ID:' .$data['loanId'] : '';

        if(!$order->get_meta('_aplazo_order_paid')){
            $this->log->write_log('info', 'meta data _aplazo_order_paid should be filled with loan id > ' . $loan_id);
            $order->update_meta_data( '_aplazo_order_paid', $loan_id );
        }
        //Mark order of status and empty cart
        $order->update_status($new_status, __('Order in ' . $new_status . ' via APLAZO (status changed)', 'aplazo-payment-gateway') . $comment_loan_id);

        if ( 'yes' === get_option( 'woocommerce_manage_stock' ) && apply_filters( 'woocommerce_can_reduce_order_stock', true, $order )
            && $this->reserve_stock === "no") {
            $this->log->write_log('info', 'Time to reduce stock.');
            wc_reduce_stock_levels($order_id);
        }
    }

    /**
     * Process a refund if supported.
     *
     * @param  int    $order_id Order ID.
     * @param  float  $amount Refund amount.
     * @param  string $reason Refund reason.
     * @return bool|WP_Error
     */
    public function process_refund( $order_id, $amount = null, $reason = '' ) {
        $order = wc_get_order( $order_id );
        $this->log->write_log('info', 'Proceso de reembolso');
        $this->aplazo_sevice->sendLog('Proceso de reembolso', WC_Aplazo_Api::LOGS_CATEGORY_INFO, WC_Aplazo_Api::LOGS_SUBCATEGORY_REFUND,
            ['orderId' => $order_id]);

        if ( ! $this->can_refund_order( $order ) ) {
            $this->log->write_log('error', 'Rembolso fallido. Revisar logs.');
            $this->aplazo_sevice->sendLog('Rembolso fallido. Revisar logs.', WC_Aplazo_Api::LOGS_CATEGORY_ERROR, WC_Aplazo_Api::LOGS_SUBCATEGORY_REFUND,
                ['orderId' => $order_id]);
            return new WP_Error( 'error', __( 'Refund failed.', 'aplazo-payment-gateway' ) );
        }
        $result = $this->refund($order_id, $amount, $reason);

        if ( !$result  ) {
            $this->aplazo_sevice->sendLog('Refund communication failed', WC_Aplazo_Api::LOGS_CATEGORY_ERROR, WC_Aplazo_Api::LOGS_SUBCATEGORY_REFUND,
                ['orderId' => $order_id]);
            return new WP_Error( 'error',  __( 'Refund communication failed.', 'aplazo-payment-gateway' ) );
        }

        $order->add_order_note(  __( 'Refund in process.', 'aplazo-payment-gateway' ) );
        return true;
    }

    public function getProperty($property)
    {
        return $this->$property;
    }

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

    /**
     * Include log
     * @return void
     */
    public function includes() {
        include_once dirname( __FILE__ ) . '/log/class-wc-aplazo-log.php';
        include_once dirname( __FILE__ ) . '/service/class-aplazo-api.php';
        $debugMode = $this->get_option('debug_mode') == 'yes';
        // Init objects
        $this->log = WC_Aplazo_Log::init_aplazo_log( self::SOURCE_LOG, $debugMode);
        $this->aplazo_sevice = WC_Aplazo_Api::init_aplazo_api($this->log, $this);
    }

    public function get_checkout_icon() {
        /**
         * Add Aplazo icon.
         *
         * @since 1.2.3
         */
        return apply_filters( 'woocommerce_aplazo_icon', plugins_url( '../assets/images/logo-raw.png', plugin_dir_path( __FILE__ ) ) );
    }
}
