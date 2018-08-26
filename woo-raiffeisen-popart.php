<?php

/**
 * Plugin Name: Woo Raiffeisen PaymentGateway
 * Description: Woocommerce Payment gateway for Raiiffeisen bank, Serbia
 * Version: 1.0
 * Author: Jovan (PopArt Studio)
 */
defined('ABSPATH') or exit;

// Make sure WooCommerce is active

/**
 * Add the gateway to WC Available Gateways
 *
 * @since 1.0.0
 * @param array $gateways all available WC gateways
 * @return array $gateways all WC gateways + offline gateway
 */
function wc_raiffeisen_add_to_gateways($gateways) {
    $gateways[] = 'WC_Gateway_Raiffeisen';
    return $gateways;
}

add_filter('woocommerce_payment_gateways', 'wc_raiffeisen_add_to_gateways');

/**
 * Adds plugin page links
 *
 * @since 1.0.0
 * @param array $links all plugin links
 * @return array $links all plugin links + our custom links (i.e., "Settings")
 */
function wc_raiffeisen_gateway_plugin_links($links) {
    $plugin_links = array(
        '<a href="' . admin_url('admin.php?page=wc-settings&tab=checkout&section=raiffeisen_gateway') . '">' . __('Configure', 'wc-gateway-raiffeisen') . '</a>'
    );
    return array_merge($plugin_links, $links);
}

add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'wc_raiffeisen_gateway_plugin_links');

/**
 * Start session
 */
add_action('init', 'myStartSession', 1);

function myStartSession() {
    if (!session_id()) {
        session_start();
    }
}

/**
 * Raiffeisen Payment Gateway
 * 
 *
 * @extends     WC_Payment_Gateway
 * @version     1.0.0
 * @package     WooCommerce/Classes/Payment
 * @author      Jovan
 */
add_action('plugins_loaded', 'wc_raiffeisen_gateway_init', 11);

function wc_raiffeisen_gateway_init() {

    class WC_Gateway_Raiffeisen extends WC_Payment_Gateway {

        /**
         * Constructor for the gateway.
         */
        public function __construct() {

            $this->id = 'raiffeisen_gateway';
            $this->icon = apply_filters('woocommerce_offline_icon', '');
            $this->has_fields = false;
            $this->method_title = __('Raiffeisen Bank', 'wc-gateway-raiffeisen');
            $this->method_description = __('Allows offline payments. Very handy if you use your cheque gateway for another payment method, and can help with testing. Orders are marked as "on-hold" when received.', 'wc-gateway-offline');

            // Load the settings.
            $this->init_form_fields();
            $this->init_settings();

            // Define user set variables
            $this->title = $this->get_option('title');
            $this->description = $this->get_option('description');
            $this->instructions = $this->get_option('instructions', $this->description);

            // Actions
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
            add_action('woocommerce_thankyou_' . $this->id, array($this, 'thankyou_page'));

            //add_action('check_raiffeisen', array($this, 'check_response'));
            // Customer Emails
            add_action('woocommerce_email_before_order_table', array($this, 'email_instructions'), 10, 3);
        }

        /**
         * Initialize Gateway Settings Form Fields
         */
        public function init_form_fields() {

            $this->form_fields = apply_filters('wc_offline_form_fields', array(
                'enabled' => array(
                    'title' => __('Enable/Disable', 'wc-gateway-raiffeisen'),
                    'type' => 'checkbox',
                    'label' => __('Enable Raiffeisen Bank Payment Gateway', 'wc-gateway-raiffeisen'),
                    'default' => 'yes'
                ),
                'title' => array(
                    'title' => __('Title', 'wc-gateway-raiffeisen'),
                    'type' => 'text',
                    'description' => __('This controls the title for the payment method the customer sees during checkout.', 'wc-gateway-offline'),
                    'default' => __('Raiffeisen Bank Payment', 'wc-gateway-raiffeisen'),
                    'desc_tip' => true,
                ),
                'description' => array(
                    'title' => __('Description', 'wc-gateway-raiffeisen'),
                    'type' => 'textarea',
                    'description' => __('Payment method description that the customer will see on your checkout.', 'wc-gateway-raiffeisen'),
                    'default' => __('Please remit payment to Store Name upon pickup or delivery.', 'wc-gateway-raiffeisen'),
                    'desc_tip' => true,
                ),
                'instructions' => array(
                    'title' => __('Instructions', 'wc-gateway-raiffeisen'),
                    'type' => 'textarea',
                    'description' => __('Instructions that will be added to the thank you page and emails.', 'wc-gateway-offline'),
                    'default' => '',
                    'desc_tip' => true,
                ),
                'terminalid' => array(
                    'title' => __('Terminal ID', 'wc-gateway-raiffeisen'),
                    'type' => 'text',
                    'description' => __('This controls the title for the payment method the customer sees during checkout.', 'wc-gateway-offline'),
                    'default' => __('', 'wc-gateway-raiffeisen'),
                    'desc_tip' => true,
                ),
                'merchantid' => array(
                    'title' => __('Merchant ID', 'wc-gateway-raiffeisen'),
                    'type' => 'text',
                    'description' => __('This controls the title for the payment method the customer sees during checkout.', 'wc-gateway-offline'),
                    'default' => __('', 'wc-gateway-raiffeisen'),
                    'desc_tip' => true,
                ),
                'currency' => array(
                    'title' => __('Currency', 'wc-gateway-raiffeisen'),
                    'type' => 'select',
                    'description' => __('', 'wc-gateway-offline'),
                    'default' => __('', 'wc-gateway-raiffeisen'),
                    'desc_tip' => false,
                    'options' => array('941' => 'Serbian dinar (RSD)', '978' => 'Euro (€)')
                )
            ));
        }

        /**
         * Output for the order received page.
         */
        public function thankyou_page() {
            if ($this->instructions) {
                echo wpautop(wptexturize($this->instructions));
            }
        }

        /**
         * Add content to the WC emails.
         *
         * @access public
         * @param WC_Order $order
         * @param bool $sent_to_admin
         * @param bool $plain_text
         */
        public function email_instructions($order, $sent_to_admin, $plain_text = false) {

            if ($this->instructions && !$sent_to_admin && $this->id === $order->get_payment_method() && $order->has_status('on-hold')) {
                echo wpautop(wptexturize($this->instructions)) . PHP_EOL;
            }
        }

        /**
         * Process the payment and return the result
         *
         * @param int $order_id
         * @return array
         */
        public function process_payment($order_id) {

            global $woocommerce;
            //$order = wc_get_order($order_id);
			$order = new WC_Order($order_id);

            //setcookie('order_id-akumulator-shop', $order_id, 1 * 86400);
            // Mark as on-hold (we're awaiting the payment)
            //$order->update_status('pending', __('Awaiting Raiffeisen payment', 'wc-raiffeisen-payment'));
            $order->update_status('pending', __('Awaiting payment via Raiffeisen', 'wc-raiffeisen-payment'));
            // Reduce stock levels
            //$order->reduce_order_stock();
            // Remove cart
            WC()->cart->empty_cart();
            // Return thankyou redirect
            $_SESSION['return_url'] = $this->get_return_url($order);
            //$_SESSION['total'] = $order->total;	
			$currency = get_woocommerce_currency();
			
			
			
			$_SESSION['currency'] = $currency;
			if($currency == 'RSD') {
				$_SESSION['total'] = $order->get_total();
			} else {
				$_SESSION['total'] = $order->get_total();
				/*$convert_rate = djb_get_currency();				
				$_SESSION['total'] = round($order->get_total() / $convert_rate, 2, PHP_ROUND_HALF_UP);*/
				
			}
            $_SESSION['order_id'] = $order_id;
            return array(
                'result' => 'success',
                //'redirect' => $this->get_return_url($order)
                'redirect' => plugins_url('proccess-payment-form.php', __FILE__)
            );
        }

    }

}