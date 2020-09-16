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
        $this->db->query("INSERT INTO `" . DB_PREFIX . "product` (product_id, model, image, status, length, width, height) 
        VALUES('$data->product_id', '$data->product_id', '$data->image', '$data->status', '$data->length', '$data->width', '$data->height')");
    }

    /**
     * Updating product
     * @param $data
     */
    function updateProduct($data){
        $this->db->query("UPDATE `" . DB_PREFIX . "product` 
        SET `image`='$data->image', `status`='$data->status', `length`='$data->length', `height`='$data->height', `width`='$data->width' WHERE `product_id`='$data->product_id'");
    }

    /**
     * Updating product stock's
     * @param $product_id
     * @param $quantity
     */
    function updateProductStock($product_id, $quantity){
        $status = $quantity > 0 ? 1 : 0;
        $outOfStock = 5;
        $this->db->query("UPDATE `" . DB_PREFIX . "product` 
        SET `quantity`='$quantity', `status`='$status', `stock_status_id` = '$outOfStock' WHERE `product_id`='$product_id'");
    }

    /**
     * Updating product date added (for newest goods)
     * @param $product_id
     */
    function updateProductDateAdded($product_id){
        $this->db->query("UPDATE `" . DB_PREFIX . "product` 
        SET `date_added` = CURRENT_TIMESTAMP WHERE `product_id`='$product_id'");
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
        $this->db->query("INSERT INTO `" . DB_PREFIX . "product_description` (product_id, language_id, name, meta_title) 
        VALUES('$data->product_id', '$data->language_id', '$data->name', '$data->name')");
    }

    /**
     * Updating product description
     * @param $data
     */
    function updateProductDescription($data){
        $this->db->query("UPDATE `" . DB_PREFIX . "product_description` 
        SET `name`='$data->name', `meta_title`='$data->name' WHERE `product_id`='$data->product_id'");
    }

    /**
     * Updating only product description
     * @param $product_id
     * @param $description
     */
    function updateOnlyProductDescription($product_id, $description){
        $this->db->query("UPDATE `" . DB_PREFIX . "product_description` 
        SET `description`='$description' WHERE `product_id`='$product_id'");
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