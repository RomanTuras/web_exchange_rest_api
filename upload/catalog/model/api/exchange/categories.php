<?php

/**
 * Class ModelApiExchangeCategories
 */

class ModelApiExchangeCategories extends Model {

    /**
     * Hide all categories
     */
    function hideAllCategories(){
        $this->db->query("UPDATE `" . DB_PREFIX . "category` SET `status` = 0");
    }

    /**
     * Getting category by id
     * @param $category_id
     * @return array
     */
    function getCategoryById($category_id){
        return $this->db->query("SELECT * FROM `" . DB_PREFIX . "category` WHERE `category_id` = '$category_id'");
    }

    /**
     * Getting category description by category id
     * @param $category_id
     * @return array
     */
    function getCategoryDescriptionById($category_id){
        return $this->db->query("SELECT * FROM `" . DB_PREFIX . "category_description` 
        WHERE `category_id` = '$category_id'");
    }

    /**
     * Adding the category
     * @param $data
     */
    function addCategory($data){
        $top = $data->parent_id == 0 ? 1 : 0;
        $this->db->query("INSERT INTO `" . DB_PREFIX . "category` (category_id, parent_id, top, status) 
        VALUES('$data->category_id', '$data->parent_id', '$top', '$data->status')");
    }

    /**
     * Adding the category description
     * @param $data
     */
    function addCategoryDescription($data){
        $this->db->query("INSERT INTO `" . DB_PREFIX . "category_description` 
        (category_id, language_id, name, meta_title) 
        VALUES('$data->category_id', '$data->language_id', '$data->name', '$data->name')");
    }

    /**
     * Updating the category
     * @param $data
     */
    function updateCategory($data){
        $top = $data->parent_id == 0 ? 1 : 0;
        $this->db->query("UPDATE `" . DB_PREFIX . "category` 
        SET `parent_id`='$data->parent_id', `top`='$top', `status`='$data->status' WHERE `category_id`='$data->category_id'");
    }

    /**
     * Updating the category description
     * @param $data
     */
    function updateCategoryDescription($data){
        $this->db->query("UPDATE `" . DB_PREFIX . "category_description` 
        SET `name`='$data->name', `meta_title`='$data->name' WHERE `category_id`='$data->category_id'");
    }

    /**
     * Clearing category path table
     */
    function clearCategoryPathTable(){
        $this->db->query("DELETE FROM `" . DB_PREFIX . "category_path` WHERE 1");
    }

    /**
     * Adding path category
     * @param $data
     */
    function addCategoryPath($data){
        $parent_id = $data->parent_id == 0 ? $data->category_id : $data->parent_id;
        $this->db->query("INSERT INTO `" . DB_PREFIX . "category_path`(`category_id`, `path_id`, `level`) 
        VALUES ('$data->category_id','$parent_id',0)");
    }

    /**
     * Adding the category to layout
     * @param $data
     */
    function addCategoryToLayout($data){
        $this->db->query("INSERT INTO `" . DB_PREFIX . "category_to_layout` (category_id, store_id, layout_id) 
        VALUES('$data->category_id', '$data->store_id', '$data->layout_id')");
    }

    /**
     * Adding the category to store
     * @param $data
     */
    function addCategoryToStore($data){
        $this->db->query("INSERT INTO `" . DB_PREFIX . "category_to_store` (category_id, store_id) 
        VALUES('$data->category_id', '$data->store_id')");
        $this->cache->delete('category');
    }

}