<?php 
/**
 * Plugin Name: Envia Shipping and Fulfillment
 * Plugin URI:  https://woocommerce.com/es-es/products/envia-shipping-and-fulfillment/
 * Description: The Best Shipping Solution for your business. Connect to 150+ couriers worldwide and get over 70% discounts for domestic and international shipments!
 * Version: 4.4.2
 * Author: Tendecys Innovations
 * Author URI: https://tendencys.com
 * Text Domain: Envia Shipping and Fulfillment
 * Requires at least: 6.5
 * Tested up to: 6.6.2
 * WC requires at least: 8.9
 * WC tested up to: 9.3.3
 * Requires PHP: 7.4
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Woo: 18734001052053:dc29297b72b473f0e1f97e671bb3f370
 */
require_once plugin_dir_path(__FILE__) . 'source/classes/actions/envia-actions.php';
use Envia\Classes\Module\Envia_Shipping;
class Enviacom { 
	use Envia\Classes\Actions\Envia_Actions;
	public static $pluginVersion;
	public static $wcVersion;
	public static $wpVersion;
	public static $phpVersion;
	const MDABSPATH =  __DIR__ ;
	const MAINFILE   = __DIR__ . '/enviacom.php';
	const ENVIA_HOSTNAME   = 'https://ship.envia.com/';
	const ENVIA_QUERIES_HOSTNAME   = 'https://queries.envia.com';
	const ENVIA_APP_HOSTNAME   = 'https://api-clients.envia.com';
	const ENVIA_S3   = 'https://s3.us-east-2.amazonaws.com/enviapaqueteria';

	public static function launch() {
		require_once ABSPATH . '/wp-admin/includes/plugin.php';
		if ( ! self::system_status() ) {
			deactivate_plugins( plugin_basename( __FILE__ ) ); //lanzar alerta
			return false;
		} 
		 self::file_loader();
	}

	public static function file_loader() {
		spl_autoload_register( function( $class ) {
			if ( str_contains( $class, 'Envia' ) ) {
				$elements = explode( '\\', $class );
				if ( count( $elements ) < 2 ) {
					return;
				}
			} else {
				return;
			}
			$folders = strtolower( $elements[1] );
			$classFile = strtolower( str_replace( '_', '-', $elements[ count($elements) - 1 ] ) );
			if ( file_exists( plugin_dir_path( __FILE__ ) . 'source/' . $folders . '/' . $classFile . '.php' ) ) { 
				require_once plugin_dir_path( __FILE__ ) . 'source/' . $folders . '/' . $classFile . '.php';
			} else {
				$nextFolder = explode( '-', $classFile, -1 );
				require_once plugin_dir_path( __FILE__ ) . 'source/' . $folders . '/' . $nextFolder[0] . '/' . $classFile . '.php';
			}
		});
		include_once self::MDABSPATH . '/source/triggers/ajaxActions.php';
		include_once self::MDABSPATH . '/source/triggers/hookActions.php';
		self::load_actions();

	}

	public static function system_status() {
		$compatiblity = self::compatiblity();
		if ( ! $compatiblity['status'] ) {
			return false;
		}
		return true;
	}

	public static function compatiblity() {
		global $wp_version;
		$issues = array();
		if ( ! version_compare( $wp_version, '6.3', '>=' ) ) {
			$issues[] = array(
				'Wordpress' => $wp_version,
			);
		}
		if ( ! version_compare( PHP_VERSION, '7.4', '>=' ) ) {
			$issues[] = array(
				'PHP' => PHP_VERSION,
			);
		}
		if ( ! defined( 'WC_VERSION' ) ) {
			$issues[] = array(
				'WooCommerce' => false,
			);
		} elseif ( ! version_compare( WC_VERSION, '7.4.1', '>=' ) ) {
			$issues[] = array(
				'WooCommerce' => WC_VERSION,
			);
		}
		self::$wpVersion = $wp_version;
		self::$wcVersion = WC_VERSION;
		self::$phpVersion = PHP_VERSION;
		return array(
			'status' => ! count( $issues ) > 0,
			'errors' => $issues,
		);
	}

	public static function add_envia_shipping_module( $methods ) {
		//Keep value of declaration with namespace
		$methods['envia_shipping'] = 'Envia\Classes\Module\Envia_Shipping';
		return $methods;
	}

	public static function add_envia_pickup_module( $methods ) {
		$methods['envia_pickup'] = 'Envia\Classes\Module\Envia_Pickup';
		return $methods;
	}

	public static function register_envia_pickup() {
		if ( self::is_new_blocks( 'cart' ) || self::is_new_blocks( 'checkout' ) ) {
			wc()->shipping->register_shipping_method( 'Envia\Classes\Module\Envia_Pickup' );
			$pickupSettings =  get_option( 'woocommerce_pickup_location_settings' );
			$pickupEnabled = isset( $pickupSettings['enabled'] ) ? $pickupSettings['enabled'] : null;
			if ( 'no' == $pickupEnabled ) {
				$pickupSettings['enabled'] = 'yes';
				update_option( 'woocommerce_pickup_location_settings', $pickupSettings );
			}
		}
	}

	public static function load_actions() {
		Envia_Shipping::oauth_action();
		Envia_Shipping::orders_action();
		Envia_Shipping::templates_action();
		add_filter( 'plugin_action_links_' . plugin_basename( self::MAINFILE ), array( __CLASS__, 'add_plugin_page_settings_link' ) );
		self::set_declared_plugin_version();
	}

	public static function add_plugin_page_settings_link( $links ) {
		$links += array(
			'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=shipping&section=envia_shipping' ) . '">' . __( 'Settings' ) . '</a>', 
			'<a href="' . admin_url( 'admin.php?page=envia-order-manager' ) . '">' . __( 'Page' ) . '</a>' 
		);
		return $links;
	}

	public static function is_new_blocks( $pageId ) {
		return WC_Blocks_Utils::has_block_in_page( wc_get_page_id( $pageId ), 'woocommerce/' . $pageId );
	}


	public static function set_declared_plugin_version() {
		$pluginInfo = get_plugin_data( self::MAINFILE );
		self::$pluginVersion = $pluginInfo['Version'];
	}

	public static function get_current_version() {
		return self::$pluginVersion;
	}

	public static function get_system_vars() {
		return array(
			"woocommerce" =>  self::$wcVersion,
			'wordpress'=> self::$wpVersion,
			"php"=> self::$phpVersion
		);
	}

	public static function print_exception( $err ) {
		wp_enqueue_script( 'printErrors', plugins_url( 'admin/js/errors.js', __FILE__ ), array(), '1.0', 'error' );
		wp_localize_script(
			'printErrors',
			'errorData',
			array(
				'message'=> $err->getMessage(),
				'code' => $err->getCode(),
				'line' => $err->getLine(),
				'file' => $err->getFile(),
				'trace' => $err->getTrace(),
				'previous' => $err->getPrevious(),
			)
		);
	}
}

add_action( 'plugins_loaded', array( 'Enviacom', 'launch' ) );

/**
 * Declaration for HPOS compatiblity 
 */ 
add_action( 'before_woocommerce_init', function () {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
} );

/**
 * Declaration for new blocks compatiblity 
 */ 
add_action( 'before_woocommerce_init', function() {
	if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
	}
} );
