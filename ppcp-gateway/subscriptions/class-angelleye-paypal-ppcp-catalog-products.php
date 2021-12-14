<?php

defined('ABSPATH') || exit;

class AngellEYE_PayPal_PPCP_Catalog_Products {

    protected static $_instance = null;
    public $is_sandbox;
    public $products_url;
    public $plans;
    public $api_log;
    public $settings;
    public $api_request;
    public $payment;

    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct() {
        try {
            $this->angelleye_ppcp_load_class();
            $this->angelleye_ppcp_add_hooks();
            $this->is_sandbox = 'yes' === $this->settings->get('testmode', 'no');
            if ($this->is_sandbox) {
                $this->products_url = 'https://api-m.sandbox.paypal.com/v1/catalogs/products';
            } else {
                $this->products_url = 'https://api-m.paypal.com/v1/catalogs/products';
            }
        } catch (Exception $ex) {
            
        }
    }

    public function angelleye_ppcp_load_class() {
        try {
            if (!class_exists('WC_Gateway_PPCP_AngellEYE_Settings')) {
                include_once PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-wc-gateway-ppcp-angelleye-settings.php';
            }
            if (!class_exists('AngellEYE_PayPal_PPCP_Request')) {
                include_once PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-angelleye-paypal-ppcp-request.php';
            }
            if (!class_exists('AngellEYE_PayPal_PPCP_Log')) {
                include_once PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-angelleye-paypal-ppcp-log.php';
            }
            if (!class_exists('AngellEYE_PayPal_PPCP_Plans')) {
                include_once ( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/subscriptions/class-angelleye-paypal-ppcp-plans.php');
            }
            $this->plans = AngellEYE_PayPal_PPCP_Plans::instance();
            $this->api_log = AngellEYE_PayPal_PPCP_Log::instance();
            $this->settings = WC_Gateway_PPCP_AngellEYE_Settings::instance();
            $this->api_request = AngellEYE_PayPal_PPCP_Request::instance();
            $this->payment = AngellEYE_PayPal_PPCP_Payment::instance();
        } catch (Exception $ex) {
            $this->api_log->log("The exception was created on line: " . $ex->getLine(), 'error');
            $this->api_log->log($ex->getMessage(), 'error');
        }
    }

    public function angelleye_ppcp_add_hooks() {
        add_action('save_post_product', array($this, 'angelleye_ppcp_sync_paypal_product'), 10, 3);
    }

    public function angelleye_ppcp_sync_paypal_product($post_id, $post, $update) {
        try {
            $product = wc_get_product($post_id);
            if ($product->is_type('subscription')) {
                if ($this->is_product_exist($post_id) === false) {
                    $this->create_product($product);
                }
                if ($this->is_plan_exist() === false) {
                    $this->plans->create_plan();
                } elseif ($update) {
                    $this->plans->update_plan();
                }
            }
        } catch (Exception $ex) {
            
        }
    }

    public function create_product($product) {
        try {
            $product_id = $product->get_id();
            $type = $product->needs_shipping() ? 'PHYSICAL' : 'DIGITAL';
            $param_create_product = array();
            $param_create_product['name'] = $product->get_name();
            $param_create_product['type'] = $type;
            $param_create_product['home_url'] = get_permalink($product_id);
            $args = array(
                'method' => 'POST',
                'headers' => array('Content-Type' => 'application/json', 'Authorization' => '', "prefer" => "return=representation", 'PayPal-Request-Id' => $this->payment->generate_request_id(), 'Paypal-Auth-Assertion' => $this->payment->angelleye_ppcp_paypalauthassertion()),
                'body' => $param_create_product
            );
            $this->api_response = $this->api_request->request($this->products_url, $args, 'create_product');
            if ($this->api_response['id']) {
                update_post_meta($product_id, 'angelleye_ppcp_catalog_product_id', $this->api_response['id']);
            }
        } catch (Exception $ex) {
            
        }
    }

    public function get_product($paypal_product_id) {
        try {
            $args = array(
                'timeout' => 60,
                'redirection' => 5,
                'httpversion' => '1.1',
                'blocking' => true,
                'headers' => array('Content-Type' => 'application/json', 'Authorization' => '', "prefer" => "return=representation", 'PayPal-Request-Id' => $this->payment->generate_request_id(), 'Paypal-Auth-Assertion' => $this->payment->angelleye_ppcp_paypalauthassertion()),
                'cookies' => array()
            );
            $this->api_response = $this->api_request->request($this->products_url . '/' . $paypal_product_id, $args, 'get_product');
            return $this->api_response;
        } catch (Exception $ex) {
            
        }
    }

    public function is_product_exist($post_id) {
        $angelleye_ppcp_catalog_product_id = get_post_meta($post_id, 'angelleye_ppcp_catalog_product_id', true);
        if (!empty($angelleye_ppcp_catalog_product_id)) {
            $response = $this->get_product($angelleye_ppcp_catalog_product_id);
            if (isset($response['id']) && !empty($response['id'])) {
                return true;
            }
        }
        return false;
    }

    public function is_plan_exist() {
        
    }

}
