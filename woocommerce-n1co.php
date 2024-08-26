<?php
/**
 * WooCommerce n1co
 * 
 * @author            n1co
 * @copyright         2022 n1co
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       wooCommerce payment for n1co
 * Description:       Credit card payments for Woocommerce n1co Gateway
 * Version:           1.28
 * Requires at least: 5.2
 * Requires PHP:      7.0
 * Author:            n1co
 * Author URI:        https://www.n1co.com
 * Text Domain:       woo-n1co
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */
if (!defined('ABSPATH'))
    exit; // Exit if accessed directly



if (!function_exists('is_plugin_active')) {
    include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
}

// multisite 
if (is_multisite()) {


    // this plugin is network activated - Woo must be network activated 
    if (is_plugin_active_for_network(plugin_basename(__FILE__))) {
        $if_woocomerce_inactive = is_plugin_active_for_network('woocommerce/woocommerce.php') ? false : true;
        // this plugin is locally activated - Woo can be network or locally activated 
    } else {
        $if_woocomerce_inactive = is_plugin_active('woocommerce/woocommerce.php') ? false : true;
    }
    // this plugin runs on a single site    
} else {
    $if_woocomerce_inactive = is_plugin_active('woocommerce/woocommerce.php') ? false : true;
}

if ($if_woocomerce_inactive) {
    return;
}

if (!defined('WOOEPAY_N1CO_PLUGIN_FILE')) {
    define('WOOEPAY_N1CO_PLUGIN_FILE', __FILE__);
}

if (!defined('MY_WOOEPAY_N1CO_PATH')) {
    define('MY_WOOEPAY_N1CO_PATH', plugin_dir_path(__FILE__));
}



if ($if_woocomerce_inactive == false) {

    require_once( 'inc/class-n1co.php' );
    require_once( 'inc/functions.php' );
} else {
    add_action('admin_notices', 'antondrob_n1co_add_error_notice', 10);
}

function antondrob_n1co_add_error_notice() {
    ?>
    <div class="notice notice-error is-dismissible">
        <p><?php _e('WooCommerce N1co Plugin needs Woocommerce to be active!', 'woo-n1co'); ?></p>
    </div>
    <?php
}

function n1co_gateway_hpo() {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
}

add_action('before_woocommerce_init', 'n1co_gateway_hpo');

function n1co_cart_checkout_blocks_compatibility() {
    // Check if the required class exists
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        // Declare compatibility for 'cart_checkout_blocks'
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
    }
}

add_action('before_woocommerce_init', 'n1co_cart_checkout_blocks_compatibility');

/**
 * Custom function to register a payment method type

 */
function n1co_gateway_register_order_approval_payment_method_type() {

    if (!class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
        return;
    }

    // Include the custom Blocks Checkout class
    require_once MY_WOOEPAY_N1CO_PATH . '/build/n1co-gateway-block_checkout.php';

    // Hook the registration function to the 'woocommerce_blocks_payment_method_type_registration' action
    add_action(
            'woocommerce_blocks_payment_method_type_registration',
            function (Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry) {
                // Register an instance of My_Custom_Gateway_Blocks
                $payment_method_registry->register(new N1co_Gateway_Blocks);
            }
    );
}

// Hook the custom function to the 'woocommerce_blocks_loaded' action
add_action('woocommerce_blocks_loaded', 'n1co_gateway_register_order_approval_payment_method_type');

