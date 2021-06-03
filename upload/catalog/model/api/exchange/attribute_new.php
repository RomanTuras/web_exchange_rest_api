<?php

class ModelApiExchangeAttribute extends Model {

    function isAttributeGroupExist($id){
        $result = $this->db->query("SELECT * FROM `" . DB_PREFIX . "attribute_group` 
        WHERE `attribute_group_id` = '$id'");
        if ($result->num_rows > 0) {
            foreach($result->rows as $row) {
                return $row['attribute_group_id'];
            }
        }
        return false;
    }

    function addGroupAttribute($data){
        $index = property_exists($data, 'index_attribute') ? $data->index_attribute : 0;
        $this->db->query("INSERT INTO `" . DB_PREFIX . "attribute_group` (attribute_group_id, sort_order) 
        VALUES('$data->codeGroup', '$index')");

        $result = $this->db->query("SELECT * FROM `" . DB_PREFIX . "category_description` 
        WHERE `category_id` = '$data->codeGroup'");
        $name_group = '';
        if ($result->num_rows > 0) {
            foreach($result->rows as $row) {
                $name_group = $row['name'];
            }
        }

        $this->db->query("INSERT INTO `" . DB_PREFIX . "attribute_group_description` (attribute_group_id, language_id, name) VALUES('$data->codeGroup', '$data->language_id', '$name_group')");
    }

    function isAttributeExist($attribute_id, $group_id){
        $result = $this->db->query("SELECT * FROM `" . DB_PREFIX . "attribute_description` WHERE `attribute_id` = '$attribute_id'");
        if ($result->num_rows > 0) {
            foreach($result->rows as $row) {
                $attribute_id = $row['attribute_id'];
                $result2 = $this->db->query("SELECT * FROM `" . DB_PREFIX . "attribute` 
                WHERE `attribute_group_id` = '$group_id' AND `attribute_id` = '$attribute_id'");
                foreach($result2->rows as $row2) {
                    return $row2['attribute_id'];
                }
            }
        }
        return false;
    }

    function addAttribute($data){
        $index = property_exists($data, 'index_attribute') ? $data->index_attribute : 0;
        $this->db->query("INSERT INTO `" . DB_PREFIX . "attribute` (attribute_id, attribute_group_id, sort_order) VALUES('$data->option_id','$data->codeGroup', '$index')");

        $this->db->query("INSERT INTO `" . DB_PREFIX . "attribute_description` (attribute_id, language_id, name) VALUES('$data->option_id', '$data->language_id', '$data->name')");
    }

    function addAttributeToProduct($attribute_id, $product_id, $text, $language_id){
        $result = $this->db->query("SELECT * FROM `" . DB_PREFIX . "product_attribute` 
        WHERE `product_id` = '$product_id' AND `attribute_id` = '$attribute_id' ");
        if ($result->num_rows == 0) {
            $this->db->query("INSERT INTO `" . DB_PREFIX . "product_attribute` (product_id, attribute_id, language_id, text) 
        VALUES('$product_id', '$attribute_id', '$language_id', '$text')");
        }
    }

    function deleteAttributes($product_id){
        $this->db->query("DELETE FROM `" . DB_PREFIX . "product_attribute` WHERE product_id='$product_id'");
        $this->db->query("DELETE FROM `" . DB_PREFIX . "ocfilter_option_value_to_product` WHERE product_id='$product_id'");
    }
}