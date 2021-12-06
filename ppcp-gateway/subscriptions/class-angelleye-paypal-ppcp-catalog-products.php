<?php

defined('ABSPATH') || exit;

class AngellEYE_PayPal_PPCP_Catalog_Products {

    protected static $_instance = null;

    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct() {
        
    }

    public function create_product() {
        try {
            $param_create_product = array();
            $param_create_product['name'] = '';
            $param_create_product['description'] = '';
            $param_create_product['type'] = '';
            $param_create_product['category'] = '';
            $param_create_product['image_url'] = '';
            $param_create_product['home_url'] = '';
        } catch (Exception $ex) {
            
        }
    }

}
