<?php

/**
 * Class ControllerApiExchangeAddProductPrices
 *
 * Before exchange, table was erasing
 *
 * Params incoming from POST:
 * `username` - API from admin panel
 * `key` - API from admin panel
 *
 * `productPrices` - array of JSON's, which with fields:
 * product_id - Int
 * price_id - Int
 * price_value - Int
 */

class ControllerApiExchangeAddProductPrices extends Controller {

    /**
     * Add types of prices to product
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
                $this->load->model('api/exchange/prices');

                if (isset($this->request->post['productPrices'])) {
                    $productPrices = $_POST['productPrices'];
                    $this->model_api_exchange_prices->emptyProductPriceTable();

                    foreach (json_decode($productPrices) as $item){
                        $this->model_api_exchange_prices->
                        addProductPrice($item->product_id, $item->price_id, $item->price_value);
                    }

                    $json['success'] = sprintf($this->language->get('success'));
                }

                $json['success'] = sprintf($this->language->get('success'));
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

}

