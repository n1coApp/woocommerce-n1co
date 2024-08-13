<?php

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

final class N1co_Gateway_Blocks extends AbstractPaymentMethodType {

    private $gateway;
    protected $name = 'n1co_gateway'; // your payment gateway name

    public function initialize() {
        $this->settings = get_option("woocommerce_{$this->name}_settings", []);
        //$this->gateway = N1co_Gateway::get_instance();
        $this->gateway = new N1co_Gateway();
    }

    public function is_active() {
        return $this->gateway->is_available();
    }

    /* public function is_active() {
      return !empty($this->settings['enabled']) && 'yes' === $this->settings['enabled'];
      } */

    public function get_payment_method_script_handles() {

        wp_register_script(
                'n1co_gateway-blocks-integration',
                plugin_dir_url(__FILE__) . 'checkout.js',
                [
                    'wc-blocks-registry',
                    'wc-settings',
                    'wp-element',
                    'wp-html-entities',
                    'wp-i18n',
                ],
                null,
                true
        );
        if (function_exists('wp_set_script_translations')) {
            wp_set_script_translations('n1co_gateway-blocks-integration');
        }
        return ['n1co_gateway-blocks-integration'];
    }

    public function get_payment_method_data() {
        return [
            'title' => $this->get_setting('title'),
            'description' => $this->get_setting('description'),
            'icon' => plugin_dir_url( __DIR__ ) . 'img/logo-black-n1co.png',
                //'description' => $this->gateway->description,
        ];
    }

}
