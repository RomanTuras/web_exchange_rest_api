<?php

/**
 * Class ModelApiExchangeOcfilter
 * Please, check out if OCFilter already installed
 */

class ModelApiExchangeOcfilter extends Model {

    /**
     * Check, is option exist by option id
     * @param $option_id
     * @param $keyword
     * @return bool
     */
    function isOptionExist($keyword){
        $result = $this->db->query("SELECT `option_id` FROM `" . DB_PREFIX . "ocfilter_option` 
        WHERE `keyword` LIKE '$keyword'");
        if ($result->num_rows > 0) {
            foreach($result->rows as $row) {
                return $row['option_id'];
            }
        }
        return false;
    }

    function isValueExist($keyword){
        $result = $this->db->query("SELECT `value_id` FROM `" . DB_PREFIX . "ocfilter_option_value` 
        WHERE `keyword` LIKE '$keyword'");
        if ($result->num_rows > 0) {
            foreach($result->rows as $row) {
                return $row['value_id'];
            }
        }
        return false;
    }


    /**
     * Adding option
     * @param $data
     * @return Int option_id
     */
    function addOption($data){
        $this->db->query("INSERT INTO `" . DB_PREFIX . "ocfilter_option` (type, keyword, status) 
        VALUES('$data->type', '$data->keyword', '$data->status')");
        return $this->db->getLastId();
    }

    /**
     * Adding option description
     * @param $data
     */
    function addOptionDescription($data){
        $this->db->query("INSERT INTO `" . DB_PREFIX . "ocfilter_option_description` (option_id, language_id, name) 
        VALUES('$data->option_id', '$data->language_id', '$data->name')");
    }

    /**
     * Adding option to category
     * @param $option_id
     * @param $category_id
     */
    function addOptionToCategory($option_id, $category_id){
        $result = $this->db->query("SELECT * FROM `" . DB_PREFIX . "ocfilter_option_to_category` 
        WHERE `option_id` = '$option_id' AND `category_id` = '$category_id' ");
        if ($result->num_rows == 0) {
                $this->db->query("INSERT INTO `" . DB_PREFIX . "ocfilter_option_to_category` (option_id, category_id) 
            VALUES('$option_id', '$category_id')");
        }

        $result1 = $this->db->query("SELECT * FROM `" . DB_PREFIX . "ocfilter_option_to_store` 
        WHERE `option_id` = '$option_id' ");
        if ($result1->num_rows == 0) {
            $this->db->query("INSERT INTO `" . DB_PREFIX . "ocfilter_option_to_store` (option_id, store_id) 
            VALUES('$option_id', 0)");
        }
    }

    /**
     * Check, is option value exist by option id and keyword
     * @param $option_id
     * @param $keyword
     * @return mysqli_result
     */
    function getOptionValueId($option_id, $keyword){
        return $this->db->query("SELECT * FROM `" . DB_PREFIX . "ocfilter_option_value` 
        WHERE `option_id` = '$option_id' AND `keyword` LIKE '$keyword'");
    }

    /**
     * Adding option value
     * @param $data
     * @return
     */
    function addOptionValue($data){
        $this->db->query("INSERT INTO `" . DB_PREFIX . "ocfilter_option_value` (option_id, keyword, sort_order) 
        VALUES('$data->option_id', '$data->keyword', '$data->sort_order')");
        $value_id = $this->db->getLastId();
        $this->db->query("INSERT INTO `" . DB_PREFIX . "ocfilter_option_value_description` (value_id, option_id, language_id, name) 
        VALUES('$value_id', '$data->option_id', '$data->language_id', '$data->name')");
        return $value_id;
    }

    /**
     * Adding option value to product
     * @param $data
     */
    function addOptionValueToProduct($data){
        $result = $this->db->query("SELECT * FROM `" . DB_PREFIX . "ocfilter_option_value_to_product` 
        WHERE `option_id` = '$data->option_id' AND `value_id` = '$data->value_id' AND `product_id` = '$data->product_id' ");
        if ($result->num_rows == 0) {
            $this->db->query("INSERT INTO `" . DB_PREFIX . "ocfilter_option_value_to_product` (product_id, option_id, value_id) 
        VALUES('$data->product_id', '$data->option_id', '$data->value_id')");
        }
    }

    /**
     * Deleting all option values from product
     * @param $product_id
     */
    function deleteOptionValueFromProduct($product_id){
        $this->db->query("DELETE FROM `" . DB_PREFIX . "ocfilter_option_value_to_product` 
        WHERE `product_id` = '$product_id'");
    }

}