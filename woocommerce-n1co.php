<?php
/**
 * WooCommerce n1co
 * 
 * @author            n1co
 * @copyright         2022 N1co
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       wooCommerce payment for n1co
 * Description:       Credit card payments for Woocommerce n1co Gateway
 * Version:           0.9.6.1
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

if (!defined('WOOEPAY_N1CO_PLUGIN_FILE')) {
    define('WOOEPAY_N1CO_PLUGIN_FILE', __FILE__);
}

define('WOOEPAY_N1CO_DIR', plugin_dir_path(__FILE__));


if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {

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
