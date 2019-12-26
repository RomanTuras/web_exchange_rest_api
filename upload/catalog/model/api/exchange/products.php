<?php

/**
 * Class ModelApiExchangeProducts
 */

class ModelApiExchangeProducts extends Model {

    /**
     * Reset quantity to all products and change status to "Out of stock"
     */
    function hideAllProducts(){
        $this->db->query("UPDATE `" . DB_PREFIX . "product` SET `status` = 0");
    }

    /**
     * Checking is product already exist
     * @param $product_id
     * @return bool
     */
    function isProductExist($product_id){
        $result = $this->db->query("SELECT * FROM `" . DB_PREFIX . "product` WHERE `product_id` = '$product_id'");
        return $result->num_rows > 0 ? true : false;
    }

    /**
     * Adding product
     * @param $data
     */
    function addProduct($data){
        $this->db->query("INSERT INTO `" . DB_PREFIX . "product` (product_id, model, quantity, stock_status_id, image, price, status) 
        VALUES('$data->product_id', '$data->product_id', '$data->quantity', '$data->stock_status_id', '$data->image', '$data->price', 1)");
    }

    /**
     * Updating product
     * @param $data
     */
    function updateProduct($data){
        $this->db->query("UPDATE `" . DB_PREFIX . "product` 
        SET `quantity`='$data->quantity', `stock_status_id`='$data->stock_status_id', 
         `image`='$data->image', `price`='$data->price', `status`=1 WHERE `product_id`='$data->product_id'");
    }

    /**
     * Updating manufacturer
     * @param $product_id
     * @param $manufacturer_id
     */
    function updateManufacturerProduct($product_id, $manufacturer_id){
        $this->db->query("UPDATE `" . DB_PREFIX . "product` 
        SET `manufacturer_id`='$manufacturer_id' WHERE `product_id`='$product_id'");
    }

    /**
     * Deleting additional images of the product
     * @param $product_id
     */
    function deleteImages($product_id){
        $this->db->query("DELETE FROM `" . DB_PREFIX . "product_image` WHERE `product_id` = '$product_id'");
    }

    /**
     * Adding additional image
     * @param $data
     */
    function addImage($data){
        $this->db->query("INSERT INTO `" . DB_PREFIX . "product_image` (product_id, image, sort_order)
        VALUES('$data->product_id', '$data->image', '$data->sort_order')");
    }

    /**
     * Adding product description
     * @param $data
     */
    function addProductDescription($data){
        $this->db->query("INSERT INTO `" . DB_PREFIX . "product_description` (product_id, language_id, name, description, meta_title) 
        VALUES('$data->product_id', '$data->language_id', '$data->name', '$data->description', '$data->name')");
    }

    /**
     * Updating product description
     * @param $data
     */
    function updateProductDescription($data){
        $this->db->query("UPDATE `" . DB_PREFIX . "product_description` 
        SET `name`='$data->name', `description`='$data->description', 
         `meta_title`='$data->name' WHERE `product_id`='$data->product_id'");
    }

    /**
     * Inserting product to category if not exist
     * @param $product_id
     * @param $category_id
     */
    function insertProductToCategory($product_id, $category_id){
        $this->db->query("INSERT INTO `" . DB_PREFIX . "product_to_category` (`product_id`, `category_id`) 
        SELECT * FROM (SELECT '$product_id', '$category_id') AS tmp WHERE NOT EXISTS (
        SELECT `product_id` FROM `" . DB_PREFIX . "product_to_category` 
        WHERE `product_id` = '$product_id' AND `category_id` = '$category_id') LIMIT 1;");
    }

    /**
     * Inserting product to layout if not exist
     * @param $product_id
     */
    function insertProductToLayout($product_id){
        $this->db->query("INSERT INTO `" . DB_PREFIX . "product_to_layout` (`product_id`) 
        SELECT * FROM (SELECT '$product_id') AS tmp WHERE NOT EXISTS (
        SELECT `product_id` FROM `" . DB_PREFIX . "product_to_layout` 
        WHERE `product_id` = '$product_id') LIMIT 1;");
    }

    /**
     * Inserting product to store if not exist
     * @param $product_id
     */
    function insertProductToStore($product_id){
        $this->db->query("INSERT INTO `" . DB_PREFIX . "product_to_store` (`product_id`) 
        SELECT * FROM (SELECT '$product_id') AS tmp WHERE NOT EXISTS (
        SELECT `product_id` FROM `" . DB_PREFIX . "product_to_store` 
        WHERE `product_id` = '$product_id') LIMIT 1;");
    }

}