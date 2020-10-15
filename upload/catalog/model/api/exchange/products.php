<?php

/**
 * Class ModelApiExchangeProducts
 */

class ModelApiExchangeProducts extends Model {

    /**
     * Getting products with price = 0 and quantity > 0 AND `status` = 0
     * @return mixed
     */
    function getDisabledProducts() {
        return $this->db->query("SELECT * FROM " . DB_PREFIX . "product WHERE `price` = 0 AND `quantity` > 0 AND `status` = 0");
    }

    /**
     * Reset quantity to all products and change status to "Out of stock"
     */
    function hideAllProducts(){
        $this->db->query("UPDATE `" . DB_PREFIX . "product` SET `status` = 0, `quantity` = 0");
    }

    //TODO temporary method for store logs to DB
    function myLog($id, $string){
        $this->db->query("INSERT INTO `" . DB_PREFIX . "location` (address, name) VALUES ('$id', '$string')");
    }

    /**
     * Setting status OFF (0) to all products, where it is price = 0 or quantity of stock < 1
     * Setting status ON (1) to all products, where it is price > 0 and quantity of stock > 0
     */
    function hideZeroProductsBalances(){
        $this->db->query("UPDATE `" . DB_PREFIX . "product` SET `status` = 0 WHERE `price` = 0 OR `quantity` < 1");
        $this->db->query("UPDATE `" . DB_PREFIX . "product` SET `status` = 1 WHERE `price` > 0 AND `quantity` > 0");
    }

    function repairCategories($parent_id = 0) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "category WHERE parent_id = '" . (int)$parent_id . "'");

        foreach ($query->rows as $category) {
            // Delete the path below the current one
            $this->db->query("DELETE FROM `" . DB_PREFIX . "category_path` WHERE category_id = '" . (int)$category['category_id'] . "'");

            // Fix for records with no paths
            $level = 0;

            $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "category_path` WHERE category_id = '" . (int)$parent_id . "' ORDER BY level ASC");

            foreach ($query->rows as $result) {
                $this->db->query("INSERT INTO `" . DB_PREFIX . "category_path` SET category_id = '" . (int)$category['category_id'] . "', `path_id` = '" . (int)$result['path_id'] . "', level = '" . (int)$level . "'");

                $level++;
            }

            $this->db->query("REPLACE INTO `" . DB_PREFIX . "category_path` SET category_id = '" . (int)$category['category_id'] . "', `path_id` = '" . (int)$category['category_id'] . "', level = '" . (int)$level . "'");

            $this->repairCategories($category['category_id']);
        }
    }

    /**
     * Hiding (status = 0) categories, where is all products have status = 0
     * @param $category_id
     * @return int|mixed
     */
    function hideEmptyCategories($category_id){
        $listChildCategories = $this->getCategories($category_id);
        $parent_id = 99999;
        if(empty($listChildCategories)){ //if have not nested categories
            $listProducts = $this->getListProductsByCategory($category_id); //all products in category
            $numbersOfProducts = $this->countActiveProducts($listProducts); //number of status=1 products in category
            $parent_id = $this->getParentId($category_id);
            if($numbersOfProducts == 0){
                $this->hideCategory($category_id); //category was hiding
            }
        }else { //if nested categories is present
            foreach ($listChildCategories as $child_id) {
                $this->hideEmptyCategories($child_id);
            }
            $listChildCategories2 = $this->getCategories($category_id);

            if(empty($listChildCategories2)){
                $listProducts2 = $this->getListProductsByCategory($category_id); //all products in category
                $numbersOfProducts2 = $this->countActiveProducts($listProducts2); //number of status=1 products in category
                if($numbersOfProducts2 == 0){
                    $this->hideCategory($category_id); //category was hiding
                }
            }
        }
        return $parent_id;
    }

    /**
     * Hiding category
     * @param $category_id
     */
    private function hideCategory($category_id){
        $this->db->query("UPDATE `" . DB_PREFIX . "category` SET `status` = 0 WHERE `category_id` = '$category_id'");
     }

    /**
     * Getting parent category by id
     * @param $child_id
     * @return mixed
     */
    private function getParentId($child_id){
        $result = $this->db->query("SELECT `parent_id` FROM `" . DB_PREFIX . "category` WHERE `category_id`='$child_id'");
        $value = 0;
        if ($result->num_rows > 0) {
            foreach($result->rows as $row) {
                $value = $row['parent_id'];
            }
        }
        return $value;
    }

    /**
     * Counting products with status = 1 from list
     * @param $listProducts
     * @return int
     */
    private function countActiveProducts($listProducts){
        $i = 0;
        foreach ($listProducts as $productId){
            $result = $this->db->query("SELECT COUNT( product_id ) AS count FROM `" . DB_PREFIX . "product` WHERE `product_id` = '$productId' AND `status` = 1");
            if ($result->num_rows > 0) {
                foreach($result->rows as $row) {
                    $i += (int)$row['count'];
                }
            }
        }
        return $i;
    }

    /**
     * Counting numbers of product in category
     * @param $category_id
     * @return array
     */
    private function getListProductsByCategory($category_id){
        $listProducts = [];
        $result = $this->db->query("SELECT `product_id` FROM `" . DB_PREFIX . "product_to_category` WHERE `category_id` ='$category_id'");
        if ($result->num_rows > 0) {
            foreach($result->rows as $row) {
                array_push($listProducts, $row['product_id']);
            }
        }
        return $listProducts;
    }

    /**
     * Getting subcategories from parent
     * @param $parent_id
     * @return array
     */
    private function getCategories($parent_id){
        $listCategory = [];
        $result = $this->db->query("SELECT `category_id` FROM `" . DB_PREFIX . "category` WHERE `parent_id`='$parent_id'");
        if ($result->num_rows > 0) {
            foreach($result->rows as $row) {
                array_push($listCategory, $row['category_id']);
            }
        }
        return $listCategory;
    }
//***************************************************************************************************************************************************
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