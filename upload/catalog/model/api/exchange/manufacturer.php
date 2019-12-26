<?php

/**
 * Class ModelApiExchangeManufacturer
 */

class ModelApiExchangeManufacturer extends Model {

    /**
     * Getting manufacturer ID by name
     * @param $name
     * @return int - ID, or 0 if manufacturer not found
     */
    function getManufacturerIdByName($name){
        $result = $this->db->query("SELECT * FROM `" . DB_PREFIX . "manufacturer` WHERE `name` = '$name'");
        $id = 0;
        if ($result->num_rows > 0) {
            foreach($result->rows as $row) {
                $id = $row['manufacturer_id'];
            }
        }
        return $id;
    }

    /**
     * Add manufacturer
     * @param $name
     * @return int ID
     */
    function addManufacturer($name){
        $this->db->query("INSERT INTO `" . DB_PREFIX . "manufacturer` (name) VALUES('$name')");
        $manufacturer_id = $this->db->getLastId();
        $this->db->query("INSERT INTO `" . DB_PREFIX . "manufacturer_to_store` (manufacturer_id, store_id) 
        VALUES('$manufacturer_id', 0)");
        return $manufacturer_id;
    }

}
