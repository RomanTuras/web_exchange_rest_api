<?php

/**
 * Class ControllerApiExchangeAddCategories
 * Params incoming from POST:
 * `username` - API from admin panel
 * `key` - API from admin panel
 * `categories` - array of JSON's, which with fields:
 * category_id - Int
 * parent_id - Int
 * name - String
 * status - Int -- visibility of the category (1 - visible, 0 - not)
 * -----
 * Before exchange all categories will be hide from store
 * Description, image, meta_description, meta_keywords - will not be a overwritten
 */

class ControllerApiExchangeAddCategories extends Controller {

    /**
     * Adding categories to DB
     * Parameters passed through POST
     */
    public function index() {
        $this->load->language('api/exchange/web_exchange');
        $this->load->model('account/api');

        // Delete past coupon in case there is an error
        unset($this->session->data['web_exchange']);

        $json = array();

        // Login with API Key
        if(isset($this->request->post['username'])) {
            $api_info = $this->model_account_api->login($this->request->post['username'], $this->request->post['key']);
        } else {
            $api_info = $this->model_account_api->login('Default', $this->request->post['key']);
        }

        $json['success'] = sprintf($this->language->get('error'));
        if ($api_info) {
            // Check if IP is allowed
            $ip_data = array();
            $results = $this->model_account_api->getApiIps($api_info['api_id']);

            foreach ($results as $result_category) {
                $ip_data[] = trim($result_category['ip']);
            }

            if (!in_array($this->request->server['REMOTE_ADDR'], $ip_data)) {
                $json['error']['ip'] = sprintf($this->language->get('error_ip'), $this->request->server['REMOTE_ADDR']);
            }else{
                $this->load->model('api/exchange/categories');
                $this->load->model('api/exchange/common');

                if (isset($this->request->post['categories'])) {
//                    $this->hideAllCategories();
                    $this->model_api_exchange_categories->clearCategoryPathTable();
                    $categories = $_POST['categories'];

//                    $this->log->write('Categories: '.print_r($categories, true));

                    $language_id = $this->model_api_exchange_common->getLanguageIdByCode('ru-ru');

                    foreach (json_decode($categories) as $category){
                        $name_category = $category->name; //$this->decodeUtf($category->name);
                        $keyword = $this->model_api_exchange_common->cyrToLat($name_category);
                        $query = 'category_id='.$category->category_id;
                        $arr = array(
                            'category_id' => $category->category_id,
                            'parent_id' => $category->parent_id,
                            'name' => $name_category,
                            'language_id' => $language_id,
                            'store_id' => 0,
                            'layout_id' => 0,
                            'keyword' => $keyword,
                            'query' => $query,
                            'status' => $category->status
                        );
                        $data = json_encode($arr);
                        $data = json_decode($data);
                        $result_category = $this->model_api_exchange_categories->getCategoryById($category->category_id);
                        if ($result_category->num_rows > 0) { //if category already exist - try to update it
                            $this->model_api_exchange_categories->updateCategory($data);
                            $this->model_api_exchange_categories->updateCategoryDescription($data);
                            $this->model_api_exchange_common->updateSeoUrl($data);
                        }else{ //category not found - add it
                            $this->model_api_exchange_categories->addCategory($data);
                            $this->model_api_exchange_categories->addCategoryDescription($data);
                            $this->model_api_exchange_categories->addCategoryToLayout($data);
                            $this->model_api_exchange_categories->addCategoryToStore($data);
                            $this->model_api_exchange_common->addSeoUrl($data);
                        }
                        $this->model_api_exchange_categories->addCategoryPath($data);
                    }
                    $json['success'] = sprintf($this->language->get('success'));
                }else $json['success'] = sprintf($this->language->get('error'));

                $this->model_api_exchange_common->deleteCacheFiles('/home/h63053/data/www/storage_optovik_shop/cache',
                    '[^cache.octemplates.category_in_menu*]');
//                $this->model_api_exchange_common->rebuildCategories();
                $this->model_api_exchange_common->repairCategories();
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * Hide all categories (set `status` = 0)
     */
    private function hideAllCategories(){
        $this->model_api_exchange_categories->hideAllCategories();
    }

    function decodeUtf($str){
        $cyr = [
            'é', 'ö', 'ó', 'ê', 'å', 'í', 'ã', 'ø', 'ù', 'ç', 'õ', 'ú', 'ô', 'û', 'â', 'à', 'ï',
            'ð', 'î', 'ë', 'ä', 'æ', 'ý', 'ÿ', '÷', 'ñ', 'ì', 'è', 'ò', 'ü', 'á', 'þ', '¸',
            'É', 'Ö', 'Ó', 'Ê', 'Å', 'Í', 'Ã', 'Ø', 'Ù', 'Ç', 'Õ', 'Ú', 'Ô', 'Û', 'Â', 'À', 'Ï',
            'Ð', 'Î', 'Ë', 'Ä', 'Æ', 'Ý', 'ß', '×', 'Ñ', 'Ì', 'È', 'Ò', 'Ü', 'Á', 'Þ', '¨'
        ];
        $lat = [
            'й', 'ц', 'у', 'к', 'е', 'н', 'г', 'ш', 'щ', 'з', 'х', 'ъ', 'ф', 'ы', 'в', 'а', 'п',
            'р', 'о', 'л', 'д', 'ж', 'э', 'я', 'ч', 'с', 'м', 'и', 'т', 'ь', 'б', 'ю', 'ё',
            'Й', 'Ц', 'У', 'К', 'Е', 'Н', 'Г', 'Ш', 'Щ', 'З', 'Х', 'Ъ', 'Ф', 'Ы', 'В', 'А', 'П',
            'Р', 'О', 'Л', 'Д', 'Ж', 'Э', 'Я', 'Ч', 'С', 'М', 'И', 'Т', 'Ь', 'Б', 'Ю', 'Ё'
        ];

        $str = str_replace($cyr, $lat, $str);
        return $str;
    }

}


