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
     * Check out, price type is exist / 0909
     * @param $price_id
     * @return bool
     */
    function isPriceTypeExist($price_id){
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "customer_group` WHERE `customer_group_id` = '$price_id'");
        if ($query->num_rows === 0){
            return false;
        } else return true;
    }

    /**
     * Getting the main product price
     * @param $product_id
     * @return int
     */
    function getPrice($product_id) {
        $result = $this->db->query("SELECT `price` FROM `" . DB_PREFIX . "product` WHERE `product_id` = '$product_id'");
        if ($result->num_rows > 0){
            foreach($result->rows as $row) {
                return $row['price'];
            }
        } else return 0;
    }

    /**
     * Adding price type / 0909
     * @param $price_id
     * @param $description
     */
    function addPriceType($price_id, $description){
        $this->db->query("INSERT INTO `" . DB_PREFIX . "customer_group` (customer_group_id, approval, sort_order) 
        VALUES('$price_id', '0', '$price_id')");

        $this->db->query("INSERT INTO `" . DB_PREFIX . "customer_group_description` (customer_group_id, language_id, name, description) 
        VALUES('$price_id', '2', '$description', '$description')");
    }

    /**
     * Updating price type / 0909
     * @param $price_id
     * @param $description
     */
    function updatePriceType($price_id, $description){
        $this->db->query("UPDATE `" . DB_PREFIX . "customer_group_description` SET `description`='$description', `name`='$description' 
        WHERE `customer_group_id` = '$price_id'");
    }

    /**
     * Update main product price
     * @param $product_id
     * @param $value
     */
    function updatePrice($product_id, $value){
        $this->db->query("UPDATE `" . DB_PREFIX . "product` SET `price`='$value' WHERE `product_id` = '$product_id'");
    }

    /**
     * Check out, price type is exist in product / 0909
     * @param $product_id
     * @param $price_id
     * @return bool
     */
    function isPriceTypeExistInProduct($product_id, $price_id){
        $result = $this->db->query("SELECT * FROM `" . DB_PREFIX . "product_discount` WHERE `product_id` = '$product_id' 
        AND `customer_group_id` = '$price_id'");
        if ($result->num_rows > 0){
            foreach($result->rows as $row) {
                return $row;
            }
        } else return false;
    }

    /**
     * Updating price type, price value and specials in product / 0909
     * @param $data
     */
    function updatePriceTypeInProduct($data){
        $this->db->query("UPDATE `" . DB_PREFIX . "product_discount` SET `price`='$data->price_value', `date_start`=NOW() 
        WHERE `product_id` = '$data->product_id' AND `customer_group_id` = '$data->price_id' AND `quantity` = 1 ");
    }

    /**
     * Adding price type, price value and specials to product / 0909
     * @param $data
     */
    function addProductPrice($data){
        $this->db->query("INSERT INTO `" . DB_PREFIX . "product_discount` 
        (product_id, customer_group_id, quantity, priority, price, date_start)
        VALUES('$data->product_id', '$data->price_id', 1, '$data->price_id', '$data->price_value', NOW())");
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

    function updateProductPrice($product_id, $price){
        $this->db->query("UPDATE `" . DB_PREFIX . "product` SET `price`='$price' 
        WHERE `product_id` = '$product_id' ");
    }

    function isSpecialExistInProduct($product_id, $customer_group_id){
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "product_special` WHERE `product_id` = '$product_id' 
        AND `customer_group_id` = '$customer_group_id'");
        if ($query->num_rows === 0){
            return false;
        } else return true;
    }

    function addSpecial($data){
        $this->db->query("INSERT INTO `" . DB_PREFIX . "product_special` 
        (product_id, customer_group_id, price, date_start, date_end)
        VALUES('$data->product_id', '$data->customer_group_id', '$data->price_special_value', 
        NOW(), '$data->special_date_end')");
    }

    /**
     * Deleting rows from table by price_id / 0909
     * @param $price_id
     */
    function deleteRowsByPriceId($price_id){
        $this->db->query("DELETE FROM `" . DB_PREFIX . "product_discount` WHERE `customer_group_id`='$price_id' ");
    }

    function deleteDiscount($data) {
        $this->db->query("DELETE FROM `" . DB_PREFIX . "product_discount` WHERE `product_id`='$data->product_id' AND `customer_group_id`='$data->price_id'");
    }

    /**
     * Deleting special price type
     * @param $data
     */
    function deleteSpecial($data) {
        $this->db->query("DELETE FROM `" . DB_PREFIX . "product_special` WHERE `product_id`='$data->product_id' AND `customer_group_id`='$data->price_id'");
    }

    function updateSpecial($data){
        $this->db->query("UPDATE `" . DB_PREFIX . "product_special` SET `price`='$data->price_special_value', 
        `date_start`= NOW(), `date_end` = '$data->special_date_end' 
        WHERE `product_id` = '$data->product_id' AND `customer_group_id` = '$data->customer_group_id'");
    }

    /**
     * Getting according customers group to price type (Group_id => Type_id)
     * @return string
     */
    function getPriceTypesCustomer(){
        $key = "module_price_types_customer";
        $result = $this->db->query("SELECT * FROM `" . DB_PREFIX . "setting` WHERE `key` = '$key'");
        $value = '';
        if ($result->num_rows > 0) {
            foreach($result->rows as $row) {
                $value = $row['value'];
            }
        }
        return $value;
    }

    /**
     * Getting markup for current customer
     * @param $customer_id
     * @return int
     */
    function getMarkupCustomer($customer_id){
        $result = $this->db->query("SELECT `fax` FROM `" . DB_PREFIX . "customer` WHERE `customer_id` = '$customer_id'");
        $value = 0;
        if ($result->num_rows > 0) {
            foreach($result->rows as $row) {
                $value = $row['fax'];
            }
        }
        return $value;
    }

    /**
     * Getting product prices by type
     * @param $product_id
     * @param $price_id
     * @return bool|void
     */
    function getPriceProductByType($product_id, $price_id){
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "product_prices` WHERE `product_id` = '$product_id' 
        AND `price_id` = '$price_id'");
        if ($query->num_rows === 0){
            return false;
        } else return $query->row;
    }

}