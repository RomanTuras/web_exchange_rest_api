<?php
class ModelExtensionModuleWebExchange extends Model {

    public function installImagesTable() {
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "images` (
            `id` INT(11) NOT NULL AUTO_INCREMENT, 
            PRIMARY KEY(`id`), `img_name` TEXT, `hash` TEXT) CHARACTER SET utf8 COLLATE utf8_general_ci");
    }

    public function uninstallImagesTable() {
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "images`");
    }

    public function installPriceTables() {
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "price_types` (
            `id` INT(11) NOT NULL AUTO_INCREMENT, PRIMARY KEY(`id`), `price_id` INT(11), `description` TEXT) 
            CHARACTER SET utf8 COLLATE utf8_general_ci");

        $this->db->query("
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "product_prices` (
            `id` INT(11) NOT NULL AUTO_INCREMENT, PRIMARY KEY(`id`), 
            `product_id` INT(11), `price_id` INT(11), 
            `price_value` DECIMAL(15,4) DEFAULT 0.0000,
            `price_special_value` DECIMAL(15,4) DEFAULT 0.0000,
            `special_date_end` DATE DEFAULT '0000-00-00') CHARACTER SET utf8 COLLATE utf8_general_ci");
    }

    public function uninstallPriceTables() {
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "price_types`");
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "product_prices`");
    }

}