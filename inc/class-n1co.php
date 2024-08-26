<?php
if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

    /*
     * This action hook registers our PHP class as a WooCommerce payment gateway
     */

function n1co_add_gateway_class($gateways) {
    $gateways[] = 'N1co_Gateway'; // your class name is here
    return $gateways;
}

add_filter('woocommerce_payment_gateways', 'n1co_add_gateway_class');

/*
 * The class itself, please note that it is inside plugins_loaded action hook
 */
add_action('plugins_loaded', 'n1co_init_gateway_class');

function n1co_init_gateway_class() {

    class N1co_Gateway extends WC_Payment_Gateway {

        /**
         * Class constructor, more about it in Step 3
         */
        public function __construct() {

            $this->id = 'n1co_gateway'; // payment gateway plugin ID
            $this->icon = plugins_url('/woocommerce-n1co/img/logo-black-n1co.png'); //plugin_dir_url(__FILE__) . '../assets/img/n1co.jpg'; // URL of the icon that will be displayed on checkout page near your gateway name
            $this->has_fields = true; // in case you need a custom credit card form
            $this->method_title = 'n1co ';
            $this->method_description = 'Acepta tus pagos por medio de n1co'; // will be displayed on the options page
            // Initialize the logger
            $this->logger = wc_get_logger();
            $this->logger_context = array('source' => $this->id);

            // Method with all the options fields
            $this->init_form_fields();

            // Load the settings.
            $this->init_settings();

            $this->title = $this->get_option('title');
            $this->description = $this->get_option('description');
            $this->enabled = $this->get_option('enabled');
            $this->n1co_log_enabled = $this->get_option('n1co_log_enabled');
            $this->n1co_testmode = 'yes' === $this->get_option('n1co_testmode');
            $this->n1co_url_live = $this->get_option('n1co_url_live');
            $this->n1co_url_sandbox = $this->get_option('n1co_url_sandbox');
            $this->n1co_redirect_process = $this->get_option('n1co_redirect_process');
            $this->n1co_test_webhook_key = $this->get_option('n1co_test_webhook_key');
            $this->n1co_live_webhook_key = $this->get_option('n1co_live_webhook_key');
            $this->n1co_link_checkout = $this->get_option('n1co_link_checkout');
            $this->n1co_link_test_checkout = $this->get_option('n1co_link_test_checkout');

            //  $this->live_public_key = $this->get_option('live_public_key');
            //  $this->live_secret_key = $this->get_option('live_secret_key');
            // This action hook saves the settings
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
            add_action('wp_enqueue_scripts', array($this, 'payment_scripts'));
            add_action('admin_enqueue_scripts', array($this, 'admin_payment_scripts'));
            add_action('woocommerce_api_n1co_webhook', array($this, 'webhook'));
            add_action("woocommerce_receipt_{$this->id}", array($this, 'n1co_receipt_page'));
        }

        /**
         * Plugin options, we deal with it in Step 3 too
         */
        public function init_form_fields() {



            $label = __('Habilite Logging', 'woocommerce');
            $description = __('Habilite el registro de errores.', 'woocommerce');

            if (defined('WC_LOG_DIR')) {
                $log_url = add_query_arg('tab', 'logs', add_query_arg('page', 'wc-status', admin_url('admin.php')));
                $log_key = 'n1co-' . sanitize_file_name(wp_hash('n1co')) . '-log';
                $log_url = add_query_arg('log_file', $log_key, $log_url);

                $label .= ' | ' . sprintf(__('%1$sView Log%2$s', 'woocommerce'), '<a href="' . esc_url($log_url) . '">', '</a>');
            }


            $this->general_settings = array(
                'n1co_settings_steps' => array(
                    'title' => 'Pasos para configuración n1co plugin',
                    'type' => 'title',
                    'description' => __('<p>1. Para procesar pagos con n1co es necesario configurar la siguiente URL de acceso al Webhook <br> <code>' .
                            home_url('/wc-api/n1co_webhook') . '</code> en la sección Configuración->Webhook en su cuenta del <a href="https://portal.n1co.shop" target="_blank">portal n1co</a>  <br>contacte al ejecutivo de ventas, o cx al Tel.: '
                            . '<a href="https://wa.me/50324086126/" target="_blank">+50324086126</a> para solventar cualquier duda</p>'
                    )
                ),
                'enabled' => array(
                    'title' => 'Habilitado/Deshabilitado',
                    'label' => 'Habilite la pasarela de pago n1co',
                    'type' => 'checkbox',
                    'description' => '',
                    'default' => 'no'
                ),
                'title' => array(
                    'title' => 'Titulo',
                    'type' => 'text',
                    'description' => 'Defina el título que los clientes verán duranta el proceso de pago <br>',
                    'default' => 'n1co',
                    'desc_tip' => false
                ),
                'description' => array(
                    'title' => 'Descripción',
                    'type' => 'textarea',
                    'description' => 'Defina la descripción que veran los clientes durante el proceso de pago.',
                    'default' => 'Paga con tu tarjeta de crédito o débito vía n1co',
                    'desc_tip' => true
                ),
                'n1co_log_enabled' => array(
                    'title' => __('Debug Log', 'woocommerce'),
                    'label' => $label,
                    'description' => $description,
                    'type' => 'checkbox',
                    'default' => 'no'
                ),
                'n1co_testmode' => array(
                    'title' => 'Modo Test',
                    'label' => 'Habilite modo pruebas',
                    'type' => 'checkbox',
                    'description' => 'Coloque la pasarela en modo sandbox usando el link de Checkout.',
                    'default' => 'yes',
                ),
                'n1co_redirect_process' => array(
                    'title' => 'Modo de redirigir N1co',
                    'type' => 'select',
                    'description' => 'Seleccione forma para redirigir clientes al método de pago',
                    'required' => true,
                    'options' => array(
                        'redirect' => 'Redirigir pagina n1co',
                        'iframe' => 'Iframe en la pagina',
                    ),
                    'default_value' => 'redirect',
                ),
                'n1co_url_live' => array(
                    'title' => 'URL API Produccion',
                    'type' => 'text',
                    'description' => 'API Base Live <br>',
                    'default' => 'https://api-pay.n1co.shop/api/paymentlink/checkout',
                    'desc_tip' => false
                ),
                'n1co_url_sandbox' => array(
                    'title' => 'URL API Sandbox',
                    'type' => 'text',
                    'description' => 'API Base Sandbox <br>',
                    'default' => 'https://api-pay-sandbox.n1co.shop/api/paymentlink/checkout',
                    'desc_tip' => false
                )
            );

            $this->live_mode = array(
                'n1co_link_checkout' => array(
                    'title' => 'Link de Checkout',
                    'type' => 'text',
                    'description' => 'Llave generada en la sección Configuracion->Link de Checkout <br>',
                    'default' => '',
                    'desc_tip' => false
                ),
                'n1co_live_webhook_key' => array(
                    'title' => 'Token',
                    'type' => 'text',
                    'label' => 'Label',
                    'description' => 'Llave secreta generada en la sección Configuracion->Webhook ',
                )
            );
            $this->sandbox_mode = array(
                'n1co_link_test_checkout' => array(
                    'title' => 'Link de Checkout',
                    'type' => 'text',
                    'description' => 'Llave generada en la sección Configuracion->Link de Checkout <br>',
                    'default' => '',
                    'desc_tip' => false
                ),
                'n1co_test_webhook_key' => array(
                    'title' => 'Token',
                    'type' => 'text',
                    'label' => 'Label',
                    'description' => 'Llave secreta generada en la sección Configuracion->Webhook ',
                )
            );

            $this->form_fields = array_merge($this->general_settings, $this->live_mode, $this->sandbox_mode);
        }

        /**
         * Get gateway icon.
         *
         * @access public
                  * @return string
                  */
        public function get_icon() {
            $icon = '<img src="' . WC_HTTPS::force_https_url(plugins_url('/woocommerce-n1co/img/logo-black-n1co.png')) . '" alt="n1co" />';
            $icon .= '<img src="' . WC_HTTPS::force_https_url(WC()->plugin_url() . '/assets/images/icons/credit-cards/visa.png') . '" alt="Visa" />';
            $icon .= '<img src="' . WC_HTTPS::force_https_url(WC()->plugin_url() . '/assets/images/icons/credit-cards/mastercard.png') . '" alt="MasterCard" />';

            return apply_filters('woocommerce_gateway_icon', $icon, $this->id);
        }

        /*
         * Custom CSS and JS, in most cases required only when you decided to go with a custom credit card form
         */

        public function payment_scripts() {

            // we need JavaScript to process a token only on cart/checkout pages, right?
            if (!is_cart() && !is_checkout())
                return;

            // if our payment gateway is disabled, we do not have to enqueue JS too
            if ('no' === $this->enabled)
                return;

            // do not work with card detailes without SSL unless your website is in a test mode
            if (!is_ssl())
                return;
        }

        public function admin_payment_scripts() {
            //wp_enqueue_script('admin-n1co', plugins_url('assets/js/admin-n1co.js', WOOEPAY_n1co_PLUGIN_FILE), array('jquery'), '1.0.0', true);
        }

        /**
         * You will need it if you want your custom credit card form, Step 4 is about it
         */
        public function payment_fields() {

            echo '<div>';

            if ($this->description) {
                // ok, let's display some description before the payment form
                $this->description;
                $this->description .= '<section class="payment-info" style="display: block;margin-bottom: 32px;">
			<div class="info" style="row;align-items: center;font-size: 14px;">
				En la página siguiente, su pago se procesa a través de <b style="color: #4275F2;">n1co</b>
				</p>			</div>
			</section>';
                $this->description = trim($this->description);

                // display the description with <p> tags etc.
                echo wpautop(wp_kses_post($this->description));
            }

            echo '</div>';
        }

        /**
         * Thankyou Page
         * */
        //function n1co_thankyou_page($order_id) {			
        function n1co_receipt_page($order_id) {

            $order = wc_get_order($order_id);

            // Obtener información del pedido
            $total = $order->get_total();
            $this->n1coEndpoint = $this->get_option('n1co_code');
            $redirectUrl = get_post_meta($order_id, 'n1coCheckoutURL', true);
            $redirect = $this->get_option('n1co_redirect_process');

            if ($redirect == 'iframe') {

                // Construir URL de redireccionamiento con los parámetros necesarios
                // 
                if ($this->n1co_log_enabled == 'yes') {
                    $this->logger->debug("****** Receipt for n1co Gateway Payment:", $this->logger_context);
                    $this->logger->debug(wc_print_r('No. Orden ' . $order->get_id(), true), $this->logger_context);
                    $this->logger->debug(wc_print_r('Checkout URL N1co ' . $redirectUrl, true), $this->logger_context);
                    $this->logger->debug(wc_print_r('Redirect Method ' . $redirect, true), $this->logger_context);
                }


                echo '<p>' . __('Gracias por su compra, complete los datos en el formulario de abajo para pagar de forma segura por medio de n1co.', 'payabbhi') . '</p>';

                if ($this->n1co_log_enabled == 'yes') {
                    $this->logger->debug("****** Receipt Redirect for n1co Payment Frame", $this->logger_context);
                }


                $iframe = '<iframe src="' . htmlentities($redirectUrl) . '" frameborder="0" width="600" height="650"></iframe>';

                echo $iframe;
            }
        }

        /*
         * We're processing the payments here, everything about it is in Step 5
         */

        public function process_payment($order_id) {

            $order = wc_get_order($order_id);

            if ($this->n1co_testmode == 'yes') {
                $n1coEndpoint = $this->n1co_url_sandbox;
            } else {
                $n1coEndpoint = $this->n1co_url_live;
            }


            $redirect = $this->get_option('n1co_redirect_process');

            $currencyOrder = $order->get_currency();
            $total = $order->get_total();
            $cantidadProductos = count(WC()->cart->get_cart());

            if ($this->n1co_testmode == 'yes') {
                $token = $this->n1co_link_test_checkout;
            } else {
                $token = $this->n1co_link_checkout;
            }

            //$baseUrl = $this->get_return_url($order). 'woon1co=true&order_id=' . $order_id;

            if ($this->n1co_log_enabled == 'yes') {
                $this->logger->debug("****** Numero de Orden for n1co Payment:", $this->logger_context);
                $this->logger->debug(wc_print_r('No. Orden ' . $order->get_id(), true), $this->logger_context);
            }

            $product_name = 'Pedido No Orden: ' . $order->get_id();
            $baseUrl = $this->get_return_url($order);

            if (strpos($baseUrl, '?') !== false) {
                $baseUrl .= '&';
            } else {
                $baseUrl .= '?';
            }

            if ($redirect == 'redirect') {
                $successUrl = $baseUrl . 'woon1co=true&order_id=' . $order_id;
                $cancelUrl = wc_get_checkout_url() . '?woon1co=cancel&order_id=' . $order_id;
            } else {
                $successUrl = '';
                $cancelUrl = '';
            }

            $item = array(
                'orderName' => $product_name,
                'orderDescription' => $product_name,
                'amount' => $total,
                'successUrl' => $successUrl,
                'cancelUrl' => $cancelUrl,
                'metadata' => array([
                        'name' => 'order_id',
                        'value' => "$order_id"
                    ]),
            );

            try {

                $result = wp_remote_post($n1coEndpoint,
                        array(
                            'method' => 'POST',
                            'body' => json_encode($item),
                            'headers' => array(
                                'Authorization' => 'Bearer ' . $token,
                                'Content-Type' => 'application/json'
                            ),
                        )
                );

                $response = json_decode($result['body'], true);

                $n1coOrderCode = $response['orderCode'];
                $n1coOrderId = $response['orderId'];
                $n1coCheckout = $response['paymentLinkUrl'];

                update_post_meta($order_id, 'n1coOrderCode', $n1coOrderCode);
                update_post_meta($order_id, 'n1coOrderId', $n1coOrderId);
                update_post_meta($order_id, 'n1coCheckoutURL', $n1coCheckout);

                if ($this->n1co_log_enabled == 'yes') {
                    $this->logger->debug("****** Envio de Orden for n1co Payment:", $this->logger_context);
                    $this->logger->debug(wc_print_r('No. Orden ' . $order->get_id(), true), $this->logger_context);
                    $this->logger->debug(wc_print_r('Checkout URL N1co ' . $n1coCheckout, true), $this->logger_context);
                }

                $note = 'Orden fue enviada correctamente a n1co!' . "<br>";
                $note .= ' OrderId: ' . $n1coOrderId . "<br>";
                $note .= ' CheckoutURL: ' . $n1coCheckout . "<br>";

                $order->update_status('pending');
                $order->add_order_note($note, false);

                if ($redirect == 'redirect') {
                    return array(
                        'result' => 'success',
                        'redirect' => $n1coCheckout
                    );
                } else {
                    return array(
                        'result' => 'success',
                        'redirect' => $order->get_checkout_payment_url(true)//$baseUrl
                    );
                }
            } catch (Exception $ex) {
                wc_add_notice($ex->getMessage(), 'error');
                wc_add_notice('No fue posible procesar la transacción, intente nuevamente', 'error');
                wp_delete_post($order_id, true);
            }
        }

        public function webhook() {
            global $woocommerce;
            global $url;
            global $wpdb;

            if ($this->n1co_testmode == 'yes') {
                $n1coWebhookKey = $this->get_option('n1co_test_webhook_key');
            } else {
                $n1coWebhookKey = $this->get_option('n1co_live_webhook_key');
            }

            $hmacHeader = $_SERVER["HTTP_X_H4B_HMAC_SHA256"];
            $content = file_get_contents("php://input");

            // Generar el hash
            $hash = hash_hmac('sha256', $content, $n1coWebhookKey, true);
            $base64Hash = base64_encode($hash);

            if ($base64Hash === $hmacHeader) {

                $validaWebhook = 'SI';

                $post = json_decode($content, true);

                $orderId = isset($post['metadata']['order_id']) ? sanitize_text_field($post['metadata']['order_id']) : '';
                $n1coOrderId = isset($post['orderId']) ? sanitize_text_field($post['orderId']) : '';
                $status = isset($post['metadata']['Status']) ? sanitize_text_field($post['metadata']['Status']) : '';
                $authorizationCode = isset($post['metadata']['AuthorizationCode']) ? sanitize_text_field($post['metadata']['AuthorizationCode']) : '';
                $description = isset($post['description']) ? sanitize_text_field($post['description']) : '';
                $type = isset($post['type']) ? sanitize_text_field($post['type']) : '';

                if ($this->n1co_log_enabled == 'yes') {
                    $this->logger->debug("****** WebHook for n1co Payment:", $this->logger_context);
                    $this->logger->debug(wc_print_r('authorization Code ' . $authorizationCode, true), $this->logger_context);
                    $this->logger->debug(wc_print_r('type ' . $type, true), $this->logger_context);
                    $this->logger->debug(wc_print_r('OrderId ' . $orderId, true), $this->logger_context);
                    $this->logger->debug(wc_print_r('Status ' . $status, true), $this->logger_context);
                    $this->logger->debug(wc_print_r('n1coOrderId ' . $n1coOrderId, true), $this->logger_context);
                    $this->logger->debug(wc_print_r('validaWebhook ' . $validaWebhook, true), $this->logger_context);
                }

                $statusPago = $type;

                // Return to home page if empty data.

                if (!empty($orderId)) {

                    $redirect = $this->get_option('n1co_redirect_process');

                    $order = new WC_Order($orderId);
                    $url = $this->get_return_url($order);

                    if ($order->has_status('completed') || $order->has_status('processing')) {
                        return;
                    }

                    update_post_meta($orderId, 'Status_Pago', $status);
                    update_post_meta($orderId, 'checkoutId', $n1coOrderId);

                    $respuestaNico = $post['metadata'];

                    foreach ($respuestaNico as $key => $value) {
                        update_post_meta($orderId, $key, $value);
                    }

                    if ($statusPago == 'PaymentError') {

                        //El proceso de pago se encuentra en proceso
                        $note = $description . ' desde portal n1co ';
                        $order->add_order_note($note, false);
                        $order->update_status('wc-failed', 'Falllido');
                        return;
                    } else if ($statusPago == 'SuccessPayment') {


                        //El proceso de pago fue completado
                        $note = $description . ' desde portal n1co ' . '<br>';
                        $note .= ' Estatus de pago: ' . $statusPago . '<br>';
                        $note .= ' CheckouId: ' . $n1coOrderId . '<br>';
                        $note .= ' Nota del pedido n1co: ' . $description;

                        /* $order->update_status('wc-processprocessing');
                          $order->add_order_note($note, false);
                          $order->payment_complete();
                          $woocommerce->cart->empty_cart();
                          wc_reduce_stock_levels($order); */

                        $woocommerce->cart->empty_cart();
                        $order->payment_complete();
                        $order->update_status('processing');
                        wc_reduce_stock_levels($order->get_id());
                        $order->add_order_note($note, false);

                        if ($redirect == 'iframe') {
                            if ($this->n1co_log_enabled == 'yes') {
                                $this->logger->debug("****** WebHook Redirect for n1co Payment2:", $this->logger_context);
                                $this->logger->debug(wc_print_r('Url ' . $url, true), $this->logger_context);
                            }
                            //return wp_safe_redirect($url);
                            //return wp_safe_redirect($url);   

                            echo '<script>';
                            echo 'alert("Transaccipon aprobada correctamente por medio de la pasarela segur n1co.");';
                            echo 'window.top.location.href = "' . esc_url($url) . '";'; // Redirección
                            echo '</script>';

                            //  echo '<script>window.top.location.href = "' . $url . '";</script>';
                            exit;
                        }
                    } else {
                        $note = 'Pedido sigue en revision en la plataforma n1co';
                        $order->add_order_note($note, false);
                        return;
                    }
                } else {


                    $querystr = "SELECT $wpdb->postmeta.post_id FROM $wpdb->postmeta WHERE $wpdb->postmeta.meta_key = 'n1coOrderId' AND $wpdb->postmeta.meta_value = '$n1coOrderId' limit 1 ";
                    $results = $wpdb->get_results($querystr);
                    if (isset($results[0]->post_id) AND is_numeric($results[0]->post_id))
                        $orderId = $results[0]->post_id;
                    else
                        return false;

                    $order = new WC_Order($orderId);

                    if ($statusPago == 'Cancelled' || $statusPago == 'SuccessReverse') {

                        if ($this->n1co_log_enabled == 'yes') {
                            $this->logger->debug("****** WebHook for n1co Refund:", $this->logger_context);
                            $this->logger->debug(wc_print_r('n1coOrderId ' . $n1coOrderId, true), $this->logger_context);
                            $this->logger->debug(wc_print_r('postId ' . $orderId, true), $this->logger_context);
                            $this->logger->debug(wc_print_r('Description ' . $description, true), $this->logger_context);
                        }

                        $note = '';

                        $note .= ($statusPago == 'Cancelled') ? $description . ' desde portal n1co ' : '';
                        $note .= ($statusPago == 'SuccessReverse') ? $description . ' desde portal n1co ' : '';

                        $order->add_order_note($note, false);
                        update_post_meta($orderId, 'refunded', 'true');
                        $order->update_status('refunded', 'order_note');
                        return true;
                    } else {

                        if ($this->n1co_log_enabled == 'yes') {
                            $this->logger->debug("****** WebHook for n1co:", $this->logger_context);
                            $this->logger->debug(wc_print_r('n1coOrderId ' . $n1coOrderId, true), $this->logger_context);
                            $this->logger->debug(wc_print_r('postId ' . $orderId, true), $this->logger_context);
                            $this->logger->debug(wc_print_r('Description ' . $description, true), $this->logger_context);
                        }

                        $note = '';

                        $note .= $description . ' desde portal n1co ';
                        $order->add_order_note($note, false);
                        return true;
                    }
                }
            } else {

                $validaWebhook = 'NO';

                if ($this->n1co_log_enabled == 'yes') {
                    $this->logger->debug("****** WebHook for n1co Payment:", $this->logger_context);
                    $this->logger->debug(wc_print_r('validaWebhook ' . $validaWebhook, true), $this->logger_context);
                    $this->logger->debug(wc_print_r('$hmacHeader ' . $hmacHeader, true), $this->logger_context);
                    $this->logger->debug(wc_print_r('$base64Hash ' . $base64Hash, true), $this->logger_context);
                    $this->logger->debug(wc_print_r('$base64Hash ' . $base64Hash, true), $this->logger_context);
                    $this->logger->debug(wc_print_r('No se recibio respuesta valida por parte del procesador de pagos n1co', true), $this->logger_context);
                }

                wc_add_notice('No se recibio respuesta por parte del procesador de pagos n1co, intente nuevamente', 'error');
                exit();
            }
        }

        public function admin_options() {
            ?>
            <h2><?php _e('Configuración General', 'woocommerce'); ?></h2>
            <table class="form-table">
                <?php $this->generate_settings_html($this->general_settings); ?>
            </table>
            <h2><?php _e('Live mode', 'woocommerce'); ?></h2>
            <table class="form-table">
                <?php $this->generate_settings_html($this->live_mode); ?>
            </table>
            <h2><?php _e('Sandbox mode', 'woocommerce'); ?></h2>
            <table class="form-table">
                <?php $this->generate_settings_html($this->sandbox_mode); ?>
            </table>
            <?php
        }

    }

}
