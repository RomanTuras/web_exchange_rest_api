<?php

/**
 * Class ModelApiExchangePrices
 */

class ModelApiExchangePrices extends Model {

    /**
     * Truncate table `price_types`
     */
    function emptyPriceTypeTable(){
        $this->db->query("TRUNCATE TABLE `" . DB_PREFIX . "price_types`");
    }

    /**
     * Truncate table `product_prices`
     */
    function emptyProductPriceTable(){
        $this->db->query("TRUNCATE TABLE `" . DB_PREFIX . "product_prices`");
    }

    /**
     * Adding price type
     * @param $price_id
     * @param $description
     */
    function addPriceType($price_id, $description){
        $this->db->query("INSERT INTO `" . DB_PREFIX . "price_types` (price_id, description) 
        VALUES('$price_id', '$description')");
    }

    /**
     * Adding price type and price value to product
     * @param $product_id
     * @param $price_id
     * @param $price_value
     */
    function addProductPrice($product_id, $price_id, $price_value){
        $this->db->query("INSERT INTO `" . DB_PREFIX . "product_prices` (product_id, price_id, price_value)
        VALUES('$product_id', '$price_id', '$price_value')");
    }


}