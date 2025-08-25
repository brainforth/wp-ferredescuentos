<?php

namespace Aplazo\Woocommerce\Logs;

use WC_Logger;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Logs
{
    /**
     * Log
     * @var Logs
     */
    public $logger;

    /**
     * Id
     *
     * @var Logs::$id
     */
    public $id;

    /**
     * DebugLog
     *
     * @var Logs::$debug_mode
     */
    public $debug_mode;

    /**
     * @param $id
     * @param $debug_mode
     */
    public function __construct($id = false, $debug_mode = false ) {
        $this->debug_mode = $debug_mode;
        $this->id = $id;
        return $this->init_log();
    }

    /**
     * Init_log function
     *
     * @return WC_Logger|null
     */
    public function init_log() {
        if ( class_exists( 'WC_Logger' )) {
            $this->logger = wc_get_logger();
            return $this->logger;
        } else {
            return null;
        }
    }

    /**
     * Write_log function
     *
     * @param [type] $function .
     * @param [type] $message .
     * @return void
     */
    public function write_log( $function, $message ) {
        if ( ! empty( $this->debug_mode ) ) {
            if(is_array($message)){
                $this->logger->$function(wc_print_r( $message, true ), array( 'source' => $this->id ));
            } else {
                $this->logger->$function($message, array( 'source' => $this->id ));
            }
        }
    }
}