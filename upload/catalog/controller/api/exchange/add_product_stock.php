<?php

/**
 * Class ControllerApiExchangeAddProductStock
 *
 * Params incoming from POST:
 * `username` - API from admin panel
 * `key` - API from admin panel
 *
 * `productStock` - array of JSON's, which with fields:
 * product_id - Int
 * free_remains - Int
 * products_on_way - Int
 * under_order - Boolean
 */

class ControllerApiExchangeAddProductStock extends Controller {

    /**
     * Add types of prices
     */
    public function index(){
        $this->load->language('api/exchange/web_exchange');
        $this->load->model('account/api');

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

            foreach ($results as $result) {
                $ip_data[] = trim($result['ip']);
            }

            if (!in_array($this->request->server['REMOTE_ADDR'], $ip_data)) {
                $json['error']['ip'] = sprintf($this->language->get('error_ip'), $this->request->server['REMOTE_ADDR']);
            }else{
                $this->load->model('api/exchange/products');
                $json['success'] = sprintf($this->language->get('error'));

                if (isset($this->request->post['productStock'])) {
                    $productStocks = $_POST['productStock'];

                    $this->model_api_exchange_products->hideAllProducts();

                    foreach (json_decode($productStocks) as $type){
                        $totalStock = (int)$type->free_remains + (int)$type->products_on_way;
                        if( $this->model_api_exchange_products->isProductExist($type->product_id) ){
                            $this->model_api_exchange_products->
                            updateProductStock($type->product_id, $totalStock);
                        }
                    }
                    $this->model_api_exchange_products->hideZeroProductsBalances();
                    $this->model_api_exchange_products->hideEmptyCategories(0);
                    $json['success'] = sprintf($this->language->get('success'));
                }
                $this->model_api_exchange_products->repairCategories();
                $this->addExchangeTimestamp();
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * Add exchange timestamp to the log file
     */
    private function addExchangeTimestamp() {
        $path_to_log_images = dirname(__DIR__, 4);
        $log_images = $path_to_log_images.'/admin/controller/extension/module/exchange_timestamp.hlp';
        if(file_exists($log_images))unlink($log_images);
        $d = date('Y-m-d H:i:s', time());
        // $d = date("d-m-Y").', time: '.date("h:i:sa");
        $this->writeFile($log_images, $d);
    }

    /**
     * Written log to file
     * @param $filename String
     * @param $text String
     */
    function writeFile($filename, $text){
        file_put_contents($filename, $text."\r\n", FILE_APPEND);
    }

}