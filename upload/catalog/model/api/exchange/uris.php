<?php

/**
 * Class ModelApiExchangeUris
 */

class ModelApiExchangeUris extends Model {

    /**
     * Getting parent ID by category ID
     * @param int
     * @return int
     */
    function getParentId($category_id){
        $result = $this->db->query("SELECT parent_id FROM `" . DB_PREFIX . "category` WHERE `category_id` = '$category_id' LIMIT 1");
        $parent_id = 0;
        if ($result->num_rows > 0) {
            foreach($result->rows as $row) {
                $parent_id = $row['parent_id'];
            }
        }
        return $parent_id;
    }

    /**
     * Getting all products and it's categories
     * @return mixed
     */
    function getAllProducts(){
        return $this->db->query("SELECT p.product_id, c.category_id FROM `" . DB_PREFIX . "product` p INNER JOIN `" . DB_PREFIX . "product_to_category` c ON p.product_id = c.product_id ;");
    }

    /**
     * Getting SEO alias by ID (product or category)
     * @param int
     * @return string
     */
    function getSeoAlias($id){
        $id = '%'.$id.'%';
        $result = $this->db->query("SELECT keyword FROM `" . DB_PREFIX . "seo_url` WHERE `query` LIKE '$id' ");
        $alias = '';
        if ($result->num_rows > 0) {
            foreach($result->rows as $row) {
                $alias = $row['keyword'];
            }
        }
        return $alias;
    }
    

}