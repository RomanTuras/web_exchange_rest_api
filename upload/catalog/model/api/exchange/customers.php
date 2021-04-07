<?php

class ModelApiExchangeCustomers extends Model{

    function getCustomerId($telephone){
        $telephone = '%'.$telephone;
        $result = $this->db->query("SELECT `customer_id` FROM `" . DB_PREFIX . "customer` WHERE `telephone` LIKE '$telephone'");
        if ($result->num_rows > 0) {
            foreach($result->rows as $row) {
                return $row['customer_id'];
            }
        }
        return false;
    }

    function updateCustomer($customer_id, $group_id, $markup){
        $this->db->query("UPDATE `" . DB_PREFIX . "customer` SET `customer_group_id`='$group_id', 
        `fax`='$markup' WHERE `customer_id` = '$customer_id' ");
    }

    function getAllCustomers(){
        return $this->db->query("SELECT * FROM `" . DB_PREFIX . "customer` WHERE 1");
    }

    function updateCustomerTelephone($customer_id, $telephone){
        $this->db->query("UPDATE `" . DB_PREFIX . "customer` SET `telephone`='$telephone' 
        WHERE `customer_id` = '$customer_id' ");
    }
}