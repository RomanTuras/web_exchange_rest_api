<?php

/**
 * Class ModelApiExchangeCommon
 */

class ModelApiExchangeCommon extends Model{
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
