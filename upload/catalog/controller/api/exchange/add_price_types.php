<?php

/**
 * Class ControllerApiExchangeAddPriceTypes
 *
 * Params incoming from POST:
 * `username` - API from admin panel
 * `key` - API from admin panel
 *
 * `priceTypes` - array of JSON's, which with fields:
 * price_id - Int
 * description - String
 */

class ControllerApiExchangeAddPriceTypes extends Controller {

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
                $this->load->model('api/exchange/prices');
                $json['success'] = sprintf($this->language->get('error'));

                if (isset($this->request->post['priceTypes'])) {
                    $priceTypes = $_POST['priceTypes'];

                    foreach (json_decode($priceTypes) as $type){
                        if( $this->model_api_exchange_prices->isPriceTypeExist($type->price_id) ){
                            $this->model_api_exchange_prices->updatePriceType($type->price_id, $type->description);
                        } else {
                            $this->model_api_exchange_prices->addPriceType($type->price_id, $type->description);
                        }
                        //deleting rows from product_prices table, with NOT actual type price
                        if(!$type->actual) {
                            $this->model_api_exchange_prices->deleteRowsByPriceId($type->price_id);
                        }
                    }

                    $json['success'] = sprintf($this->language->get('success'));
                }

            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

}

