<?php
/**
 * Plugin Name: Bitexpay for WooCommerce.
 * Plugin URI: http://woocommerce.com/products/woocommerce-extension/
 * Description: Bitexpay for WooCommerce.
 * Version: 1.0.0
 * Author: Team Bitexpay
 * Author URI: http://yourdomain.com/
 * Developer: Your Name
 * Developer URI: http://yourdomain.com/
 * Text Domain: Bitexpay-WooCommerce
 * Domain Path: /languages
 *
 * Woo: 12345:342928dfsfhsf8429842374wdf4234sfd
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

defined( 'ABSPATH' ) || exit;
add_action( 'plugins_loaded', 'bitexpay_gateway_load', 0 );


function bitexpay_gateway_load(){
    
    if(!class_exists('WC_Payment_Gateway')) return;

    /**
     * Add method payment
    */
    add_filter('woocommerce_payment_gateways', 'wc_bitexpay_add_gateway');
     
    function wc_bitexpay_add_gateway($methods){
        
        if(!in_array('WC_Gateway_Bitexpay', $methods)){
            $methods[] = 'WC_Gateway_Bitexpay';
        }

        return $methods;
    }

    // Call class WC_Gateway_Bitexpay
    include_once __DIR__.'/src/gateway-bitexpay.php';
}