<?php

namespace Aplazo\Woocommerce\Blocks;

use Aplazo\Woocommerce\Gateways\AplazoGateway;
use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

class AplazoBlock extends AbstractPaymentMethodType
{
    /**
     * The gateway instance.
     *
     * @var AplazoGateway
     */
    private $gateway;
    /**
     * @var string
     */
    protected $name = 'aplazo';

    public function initialize()
    {
        $this->settings = get_option("woocommerce_{$this->name}_settings", []);
        $gateways       = WC()->payment_gateways->payment_gateways();
        $this->gateway  = $gateways[ $this->name ];
    }

    /**
     * Returns if this payment method should be active. If false, the scripts will not be enqueued.
     *
     * @return boolean
     */
    public function is_active() {
        return $this->gateway->is_available();
    }

    /**
     * Returns an array of script handles to enqueue for this payment method in
     * the frontend context
     *
     * @return string[]
     */
    public function get_payment_method_script_handles() {
        $script_path       = plugin_dir_url(__FILE__) . '/../../../build/blocks.js';
        $script_asset_path = dirname(APLAZO_PLUGIN_FILE) . '/build/blocks.asset.php';
        $script_asset      = file_exists( $script_asset_path )
            ? require( $script_asset_path )
            : array(
                'dependencies' => array(),
                'version'      => '1.2.0'
            );

        wp_register_script(
            'aplazo.block',
            $script_path,
            $script_asset[ 'dependencies' ],
            $script_asset[ 'version' ],
            true
        );

        return ['aplazo.block'];
    }

    public function get_payment_method_data(): array
    {
        return [
            'title'       => $this->get_setting( 'title' ),
            'description' => $this->get_setting( 'description' ),
            'supports'    => array_filter( $this->gateway->supports, [ $this->gateway, 'supports' ] )
        ];
    }
}