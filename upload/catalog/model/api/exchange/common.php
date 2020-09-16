<?php

/**
 * Class ModelApiExchangeCommon
 */

class ModelApiExchangeCommon extends Model{

    function rebuildCategories(){
        $listCategory = $this->getCategories(0);
        foreach ($listCategory as $category_id) {
            $this->deleteEmptyCategories($category_id);
        }
        $this->deleteCacheFiles('/home/h63053/data/www/storage_optovik_shop/cache',
            '[^cache.octemplates.category_in_menu*]');
    }

    private function getCategories($parent_id){
        $listCategory = array();
        $result = $this->db->
        query("SELECT `category_id` FROM `" . DB_PREFIX . "category` WHERE `parent_id`='$parent_id'");
        if ($result->num_rows > 0) {
            foreach($result->rows as $row) {
                array_push($listCategory, $row['category_id']);
            }
        }
        return $listCategory;
    }

    private function getNumbersOfProducts($category_id){
        $query = $this->db->query("SELECT COUNT( `product_id` ) AS count FROM `" . DB_PREFIX
            . "product_to_category` WHERE `category_id` ='$category_id'");
        return $query->row['count'];
    }

    private function getParentId($child_id){
        $result = $this->db->
        query("SELECT `parent_id` FROM `" . DB_PREFIX . "category` WHERE `category_id`='$child_id'");
        $parent_id = 0;
        if ($result->num_rows > 0) {
            foreach($result->rows as $row) {
                $parent_id = $row['parent_id'];
            }
        }
        return $parent_id;
    }

    private function hideCategory($category_id){
        $this->db->query("UPDATE `" . DB_PREFIX . "category` SET `status`=0 WHERE `category_id`='$category_id'");
    }

    public function repairCategories($parent_id = 0) {
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

    private function deleteEmptyCategories($category_id){
        $parent_id = 99999;
        $listChildCategories = $this->getCategories($category_id);

        if(count($listChildCategories) == 0){
            $numbersOfProducts = $this->getNumbersOfProducts($category_id);
            if($numbersOfProducts == 0){
                $parent_id = $this->getParentId($category_id);
                $this->hideCategory($category_id);
            }else $parent_id = $this->getParentId($category_id);
        }else {
            foreach ($listChildCategories as $child_id) {
                $this->deleteEmptyCategories($child_id);
            }
            $listChildCategories2 = $this->getCategories($category_id);

            if(count($listChildCategories2) == 0){
                $numbersOfProducts2 = $this->getNumbersOfProducts($category_id);
                if($numbersOfProducts2 == 0){
                    $this->hideCategory($category_id);
                }
            }
        }
        return $parent_id;
    }

    /**
     * Getting language id by code
     * @param $code string
     * @return mixed
     */
    function getLanguageIdByCode($code){
        $result = $this->db->query("SELECT `language_id` FROM `" . DB_PREFIX . "language` WHERE `code` LIKE '$code'");
        $language_id = 0;
        if ($result->num_rows > 0) {
            foreach($result->rows as $row) {
                $language_id = $row['language_id'];
            }
        }
        return $language_id;
    }

    /**
     * Clearing cache, by deleting cache file
     * @param $pathToCache string
     * @param $pattern string (reg expression)
     */
    function deleteCacheFiles($pathToCache, $pattern){
        if ($handle = opendir($pathToCache)) {
            while (false !== ($file = readdir($handle))) {
                if ( preg_match( $pattern, $file ) ) {
                    unlink($pathToCache.'/'.$file);
                }
            }
            closedir($handle);
        }
    }

    /**
     * Adding a seo url
     * @param $data
     */
    function addSeoUrl($data){
        $this->db->query("INSERT INTO `" . DB_PREFIX . "seo_url` (store_id, language_id, query, keyword) 
        VALUES('$data->store_id', '$data->language_id', '$data->query', '$data->keyword')");
    }

    /**
     * Update keyword in seo url by query
     * @param $data
     */
    function updateSeoUrl($data){
        $this->db->query("UPDATE `" . DB_PREFIX . "seo_url` SET `keyword`='$data->keyword' WHERE `query`='$data->query'");
    }

    /**
     * Getting order data
     * @param $order_id
     * @return mixed
     */
    function getOrderData($order_id){
        return $this->db->query("SELECT * FROM `" . DB_PREFIX . "order` WHERE `order_id` = '$order_id'");
    }

    /**
     * Getting order products
     * @param $order_id
     * @return mixed
     */
    function getOrderProducts($order_id){
        return $this->db->query("SELECT * FROM `" . DB_PREFIX . "order_product` WHERE `order_id` = '$order_id'");
    }

    /**
     * Converting cyrillic chars to lat
     * @param $str string
     * @return string
     */
    function cyrToLat($str){
        $cyr = [
            'а', 'б', 'в', 'г', 'д', 'е', 'ё', 'ж', 'з', 'и', 'й', 'к', 'л', 'м', 'н', 'о', 'п',
            'р', 'с', 'т', 'у', 'ф', 'х', 'ц', 'ч', 'ш', 'щ', 'ъ', 'ы', 'ь', 'э', 'ю', 'я',
            'А', 'Б', 'В', 'Г', 'Д', 'Е', 'Ё', 'Ж', 'З', 'И', 'Й', 'К', 'Л', 'М', 'Н', 'О', 'П',
            'Р', 'С', 'Т', 'У', 'Ф', 'Х', 'Ц', 'Ч', 'Ш', 'Щ', 'Ъ', 'Ы', 'Ь', 'Э', 'Ю', 'Я', ' ',
            '.', '(', ')', '!', ';', '"', '+', '/', '*', '?', '@', '&', '>', '<', '#', '$', '%',
            '^', '=', '|', '~', '№', ':', '[', ']'
        ];
        $lat = [
            'a', 'b', 'v', 'g', 'd', 'e', 'io', 'zh', 'z', 'i', 'y', 'k', 'l', 'm', 'n', 'o', 'p',
            'r', 's', 't', 'u', 'f', 'h', 'ts', 'ch', 'sh', 'sht', 'a', 'i', 'y', 'e', 'yu', 'ya',
            'A', 'B', 'V', 'G', 'D', 'E', 'Io', 'Zh', 'Z', 'I', 'Y', 'K', 'L', 'M', 'N', 'O', 'P',
            'R', 'S', 'T', 'U', 'F', 'H', 'Ts', 'Ch', 'Sh', 'Sht', 'A', 'I', 'Y', 'e', 'Yu', 'Ya', '_',
            '_', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '',
            '', '', '', '', '', '', '', ''
        ];
        $str = str_replace($cyr, $lat, $str);
        return strtolower($str);
    }
}
